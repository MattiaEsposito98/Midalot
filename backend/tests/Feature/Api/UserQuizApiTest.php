<?php

namespace Tests\Feature\Api;

use App\Models\QuizAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesQuizData;
use Tests\TestCase;

class UserQuizApiTest extends TestCase
{
    use CreatesQuizData;
    use RefreshDatabase;

    public function test_user_only_sees_assigned_quizzes(): void
    {
        $user = $this->createUser();
        $assignedQuiz = $this->createAssignedQuiz();
        $unassignedQuiz = $this->createAssignedQuiz(['leaderboard_visible' => false]);

        $user->quizzes()->attach($assignedQuiz);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/my-quizzes');

        $response
            ->assertOk()
            ->assertJsonPath('quizzes.0.id', $assignedQuiz->id);

        $ids = collect($response->json('quizzes'))->pluck('id');
        $this->assertTrue($ids->contains($assignedQuiz->id));
        $this->assertFalse($ids->contains($unassignedQuiz->id));
    }

    public function test_unassigned_quiz_is_forbidden(): void
    {
        $user = $this->createUser();
        $quiz = $this->createAssignedQuiz();

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/quizzes/{$quiz->id}")
            ->assertForbidden();
    }

    public function test_assigned_quiz_can_be_started_and_completed_once(): void
    {
        $user = $this->createUser();
        $quiz = $this->createAssignedQuiz();
        $this->addQuestions($quiz, 1);
        $user->quizzes()->attach($quiz);

        $startResponse = $this
            ->actingAs($user, 'sanctum')
            ->postJson("/api/quiz/{$quiz->id}/start");

        $attemptId = $startResponse
            ->assertCreated()
            ->json('attempt_id');

        $question = $quiz->questions()->with('answers')->first();
        $answer = $question->answers()->where('is_correct', true)->first();

        $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/quiz/answer', [
                'attempt_id' => $attemptId,
                'question_id' => $question->id,
                'answer_id' => $answer->id,
                'time_taken' => 5000,
            ])
            ->assertOk()
            ->assertJsonPath('correct', true);

        $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/quiz/finish', [
                'attempt_id' => $attemptId,
            ])
            ->assertOk();

        $this->assertTrue((bool) QuizAttempt::find($attemptId)->completed);

        $this
            ->actingAs($user, 'sanctum')
            ->postJson("/api/quiz/{$quiz->id}/start")
            ->assertForbidden();
    }

    public function test_wrong_answers_and_timeouts_apply_progressive_penalties(): void
    {
        $user = $this->createUser();
        $quiz = $this->createAssignedQuiz();
        $this->addQuestions($quiz, 3);
        $user->quizzes()->attach($quiz);

        $attemptId = $this
            ->actingAs($user, 'sanctum')
            ->postJson("/api/quiz/{$quiz->id}/start")
            ->assertCreated()
            ->json('attempt_id');

        $questions = $quiz->questions()->with('answers')->orderBy('order')->get();

        $correctScore = $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/quiz/answer', [
                'attempt_id' => $attemptId,
                'question_id' => $questions[0]->id,
                'answer_id' => $questions[0]->answers->firstWhere('is_correct', true)->id,
                'time_taken' => 5000,
            ])
            ->assertOk()
            ->json('score');

        $wrongPenalty = $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/quiz/answer', [
                'attempt_id' => $attemptId,
                'question_id' => $questions[1]->id,
                'answer_id' => $questions[1]->answers->firstWhere('is_correct', false)->id,
                'time_taken' => 5000,
            ])
            ->assertOk()
            ->assertJsonPath('wrong', true)
            ->json('score');

        $scoreAfterWrongAnswer = $correctScore + $wrongPenalty;

        $timeoutPenalty = $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/quiz/answer', [
                'attempt_id' => $attemptId,
                'question_id' => $questions[2]->id,
                'answer_id' => null,
                'time_taken' => 30000,
            ])
            ->assertOk()
            ->assertJsonPath('timeout', true)
            ->json('score');

        $this->assertSame(-(int) round($correctScore * 0.10), $wrongPenalty);
        $this->assertSame(-(int) round($scoreAfterWrongAnswer * 0.05), $timeoutPenalty);
        $this->assertGreaterThan(abs($timeoutPenalty), abs($wrongPenalty));

        $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/quiz/finish', [
                'attempt_id' => $attemptId,
            ])
            ->assertOk()
            ->assertJsonPath('score', $scoreAfterWrongAnswer + $timeoutPenalty);
    }

    public function test_correct_answer_score_preserves_speed_differences_in_hundredths(): void
    {
        $user = $this->createUser();
        $quiz = $this->createAssignedQuiz();
        $this->addQuestions($quiz, 2);
        $quiz->questions()->update(['time_limit_seconds' => 10]);
        $user->quizzes()->attach($quiz);

        $attemptId = $this
            ->actingAs($user, 'sanctum')
            ->postJson("/api/quiz/{$quiz->id}/start")
            ->assertCreated()
            ->json('attempt_id');

        $questions = $quiz->questions()->with('answers')->orderBy('order')->get();

        $fasterScore = $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/quiz/answer', [
                'attempt_id' => $attemptId,
                'question_id' => $questions[0]->id,
                'answer_id' => $questions[0]->answers->firstWhere('is_correct', true)->id,
                'time_taken' => 4200,
            ])
            ->assertOk()
            ->json('score');

        $slowerScore = $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/quiz/answer', [
                'attempt_id' => $attemptId,
                'question_id' => $questions[1]->id,
                'answer_id' => $questions[1]->answers->firstWhere('is_correct', true)->id,
                'time_taken' => 4900,
            ])
            ->assertOk()
            ->json('score');

        $this->assertSame(8740, $fasterScore);
        $this->assertSame(8530, $slowerScore);
        $this->assertGreaterThan($slowerScore, $fasterScore);
    }
}
