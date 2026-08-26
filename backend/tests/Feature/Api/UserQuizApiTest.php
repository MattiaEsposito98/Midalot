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

    public function test_user_sees_open_quizzes_and_only_assigned_restricted_quizzes(): void
    {
        $user = $this->createUser();
        $openQuiz = $this->createAssignedQuiz();
        $assignedRestrictedQuiz = $this->createAssignedQuiz(['restrict_to_specific_users' => true]);
        $unassignedRestrictedQuiz = $this->createAssignedQuiz(['restrict_to_specific_users' => true]);

        $user->quizzes()->attach($assignedRestrictedQuiz);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->getJson('/api/my-quizzes');

        $response->assertOk();

        $ids = collect($response->json('quizzes'))->pluck('id');
        $this->assertTrue($ids->contains($openQuiz->id));
        $this->assertTrue($ids->contains($assignedRestrictedQuiz->id));
        $this->assertFalse($ids->contains($unassignedRestrictedQuiz->id));
    }

    public function test_open_quiz_is_accessible_without_assignment(): void
    {
        $user = $this->createUser();
        $quiz = $this->createAssignedQuiz();

        $this
            ->actingAs($user, 'sanctum')
            ->getJson("/api/quizzes/{$quiz->id}")
            ->assertOk();
    }

    public function test_unassigned_restricted_quiz_is_forbidden(): void
    {
        $user = $this->createUser();
        $quiz = $this->createAssignedQuiz(['restrict_to_specific_users' => true]);

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

        $questions = $quiz->questions()->with('answers')->orderBy('id')->get();

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

        $questions = $quiz->questions()->with('answers')->orderBy('id')->get();

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
