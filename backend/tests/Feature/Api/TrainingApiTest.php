<?php

namespace Tests\Feature\Api;

use App\Models\TrainingAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesQuizData;
use Tests\TestCase;

class TrainingApiTest extends TestCase
{
    use CreatesQuizData;
    use RefreshDatabase;

    public function test_public_training_categories_only_include_playable_active_quizzes(): void
    {
        $this->createTrainingQuiz(5);

        $response = $this->getJson('/api/training/categories');

        $response
            ->assertOk()
            ->assertJsonPath('categories.0.slug', 'anime')
            ->assertJsonPath('categories.0.quizzes_count', 1);
    }

    public function test_guest_can_complete_training_without_persisting_attempt(): void
    {
        $quiz = $this->createTrainingQuiz(5);

        $start = $this->postJson("/api/training/quizzes/{$quiz->id}/guest-start")
            ->assertOk();

        $sessionToken = $start->json('session_token');
        $question = $quiz->questions()->with('answers')->first();
        $answer = $question->answers()->where('is_correct', true)->first();

        $this->postJson('/api/training/guest-answer', [
            'session_token' => $sessionToken,
            'question_id' => $question->id,
            'answer_id' => $answer->id,
            'time_taken' => 5000,
        ])->assertOk();

        $this->postJson('/api/training/guest-finish', [
            'session_token' => $sessionToken,
        ])
            ->assertOk()
            ->assertJsonPath('saved', false);

        $this->assertDatabaseCount('training_attempts', 0);
    }

    public function test_authenticated_user_training_attempt_is_saved(): void
    {
        $user = $this->createUser();
        $quiz = $this->createTrainingQuiz(5);

        $start = $this
            ->actingAs($user, 'sanctum')
            ->postJson("/api/training/quizzes/{$quiz->id}/start")
            ->assertCreated();

        $attemptId = $start->json('attempt_id');
        $question = $quiz->questions()->with('answers')->first();
        $answer = $question->answers()->where('is_correct', true)->first();

        $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/training/answer', [
                'attempt_id' => $attemptId,
                'question_id' => $question->id,
                'answer_id' => $answer->id,
                'time_taken' => 5000,
            ])
            ->assertOk();

        $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/training/finish', [
                'attempt_id' => $attemptId,
            ])
            ->assertOk()
            ->assertJsonPath('saved', true);

        $attempt = TrainingAttempt::findOrFail($attemptId);
        $this->assertTrue($attempt->completed);
        $this->assertSame($user->id, $attempt->user_id);
    }

    public function test_training_with_too_few_questions_is_not_playable(): void
    {
        $quiz = $this->createTrainingQuiz(4);

        $this->postJson("/api/training/quizzes/{$quiz->id}/guest-start")
            ->assertNotFound();
    }

    public function test_training_correct_answer_score_preserves_speed_differences_in_hundredths(): void
    {
        $quiz = $this->createTrainingQuiz(5);
        $quiz->questions()->update(['time_limit_seconds' => 10]);

        $start = $this->postJson("/api/training/quizzes/{$quiz->id}/guest-start")
            ->assertOk();

        $questions = collect($start->json('quiz.questions'));
        $firstQuestion = $quiz->questions()->with('answers')->findOrFail($questions[0]['id']);
        $secondQuestion = $quiz->questions()->with('answers')->findOrFail($questions[1]['id']);

        $fasterScore = $this->postJson('/api/training/guest-answer', [
            'session_token' => $start->json('session_token'),
            'question_id' => $firstQuestion->id,
            'answer_id' => $firstQuestion->answers->firstWhere('is_correct', true)->id,
            'time_taken' => 4200,
        ])
            ->assertOk()
            ->json('score');

        $slowerScore = $this->postJson('/api/training/guest-answer', [
            'session_token' => $start->json('session_token'),
            'question_id' => $secondQuestion->id,
            'answer_id' => $secondQuestion->answers->firstWhere('is_correct', true)->id,
            'time_taken' => 4900,
        ])
            ->assertOk()
            ->json('score');

        $this->assertSame(8740, $fasterScore);
        $this->assertSame(8530, $slowerScore);
        $this->assertGreaterThan($slowerScore, $fasterScore);
    }

    public function test_training_wrong_answers_and_timeouts_apply_progressive_penalties(): void
    {
        $user = $this->createUser();
        $quiz = $this->createTrainingQuiz(5);

        $start = $this
            ->actingAs($user, 'sanctum')
            ->postJson("/api/training/quizzes/{$quiz->id}/start")
            ->assertCreated();

        $questions = collect($start->json('quiz.questions'));
        $firstQuestion = $quiz->questions()->with('answers')->findOrFail($questions[0]['id']);
        $secondQuestion = $quiz->questions()->with('answers')->findOrFail($questions[1]['id']);
        $thirdQuestion = $quiz->questions()->with('answers')->findOrFail($questions[2]['id']);

        $correctScore = $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/training/answer', [
                'attempt_id' => $start->json('attempt_id'),
                'question_id' => $firstQuestion->id,
                'answer_id' => $firstQuestion->answers->firstWhere('is_correct', true)->id,
                'time_taken' => 5000,
            ])
            ->assertOk()
            ->json('score');

        $wrongPenalty = $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/training/answer', [
                'attempt_id' => $start->json('attempt_id'),
                'question_id' => $secondQuestion->id,
                'answer_id' => $secondQuestion->answers->firstWhere('is_correct', false)->id,
                'time_taken' => 5000,
            ])
            ->assertOk()
            ->assertJsonPath('wrong', true)
            ->assertJsonPath('correct_answer_id', $secondQuestion->answers->firstWhere('is_correct', true)->id)
            ->json('score');

        $scoreAfterWrongAnswer = $correctScore + $wrongPenalty;

        $timeoutPenalty = $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/training/answer', [
                'attempt_id' => $start->json('attempt_id'),
                'question_id' => $thirdQuestion->id,
                'answer_id' => null,
                'time_taken' => 30000,
            ])
            ->assertOk()
            ->assertJsonPath('timeout', true)
            ->assertJsonPath('correct_answer_id', $thirdQuestion->answers->firstWhere('is_correct', true)->id)
            ->json('score');

        $this->assertSame(-(int) round($correctScore * 0.10), $wrongPenalty);
        $this->assertSame(-(int) round($scoreAfterWrongAnswer * 0.05), $timeoutPenalty);
        $this->assertGreaterThan(abs($timeoutPenalty), abs($wrongPenalty));
    }
}
