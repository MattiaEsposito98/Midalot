<?php

namespace Tests\Feature\Api;

use App\Mail\TrainingQuestionReportMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\CreatesQuizData;
use Tests\TestCase;

class TrainingReportApiTest extends TestCase
{
    use CreatesQuizData;
    use RefreshDatabase;

    public function test_guest_can_report_a_training_question(): void
    {
        Mail::fake();
        config(['mail.reports_to' => 'reports@midalot.test']);

        $quiz = $this->createTrainingQuiz();
        $question = $quiz->questions()->firstOrFail();

        $this->postJson('/api/training/report-question', [
            'quiz_id' => $quiz->id,
            'question_id' => $question->id,
            'message' => 'La risposta corretta sembra non aggiornata.',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Segnalazione inviata. Grazie per il tuo aiuto!');

        Mail::assertSent(TrainingQuestionReportMail::class, function ($mail) use ($question) {
            return $mail->hasTo('reports@midalot.test')
                && $mail->report['nickname'] === 'Ospite'
                && $mail->report['question_id'] === $question->id;
        });

        $this->assertDatabaseHas('training_question_reports', [
            'quiz_id' => $quiz->id,
            'question_id' => $question->id,
            'status' => 'open',
            'reporter_nickname' => 'Ospite',
            'message' => 'La risposta corretta sembra non aggiornata.',
        ]);
    }

    public function test_authenticated_report_includes_user_nickname(): void
    {
        Mail::fake();

        $user = $this->createUser(['nickname' => 'reporter']);
        $quiz = $this->createTrainingQuiz();
        $question = $quiz->questions()->firstOrFail();

        $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/training/report-question', [
                'quiz_id' => $quiz->id,
                'question_id' => $question->id,
                'message' => 'Il testo della domanda contiene un errore.',
            ])
            ->assertOk();

        Mail::assertSent(TrainingQuestionReportMail::class, function ($mail) use ($user) {
            return $mail->report['nickname'] === $user->nickname
                && $mail->report['user_email'] === $user->email;
        });

        $this->assertDatabaseHas('training_question_reports', [
            'user_id' => $user->id,
            'reporter_nickname' => $user->nickname,
            'reporter_email' => $user->email,
        ]);
    }

    public function test_question_must_belong_to_reported_training(): void
    {
        Mail::fake();

        $quiz = $this->createTrainingQuiz();
        $otherQuiz = $this->createTrainingQuiz(5, [
            'title' => 'Other training',
            'category' => $quiz->trainingCategory,
        ]);
        $otherQuestion = $otherQuiz->questions()->firstOrFail();

        $this->postJson('/api/training/report-question', [
            'quiz_id' => $quiz->id,
            'question_id' => $otherQuestion->id,
            'message' => 'Questa domanda appartiene a un altro training.',
        ])->assertNotFound();

        Mail::assertNothingSent();
    }
}
