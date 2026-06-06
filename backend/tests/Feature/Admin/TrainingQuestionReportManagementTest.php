<?php

namespace Tests\Feature\Admin;

use App\Models\TrainingQuestionReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesQuizData;
use Tests\TestCase;

class TrainingQuestionReportManagementTest extends TestCase
{
    use CreatesQuizData;
    use RefreshDatabase;

    public function test_admin_sees_open_report_count_and_question_link(): void
    {
        $admin = $this->createAdmin();
        $quiz = $this->createTrainingQuiz();
        $question = $quiz->questions()->firstOrFail();
        $report = $this->createReport($quiz->id, $question->id);

        $this
            ->actingAs($admin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Segnalazioni')
            ->assertSee('Modifica domanda')
            ->assertSee($report->message)
            ->assertSee(route('admin.quizzes.questions.edit', [$quiz, $question]));
    }

    public function test_admin_can_take_report_in_charge_resolve_and_reopen_it(): void
    {
        $admin = $this->createAdmin();
        $quiz = $this->createTrainingQuiz();
        $question = $quiz->questions()->firstOrFail();
        $report = $this->createReport($quiz->id, $question->id);

        $this
            ->actingAs($admin)
            ->patch(route('admin.reports.update', $report), [
                'status' => TrainingQuestionReport::STATUS_IN_PROGRESS,
                'admin_note' => 'Sto verificando la risposta.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('training_question_reports', [
            'id' => $report->id,
            'status' => TrainingQuestionReport::STATUS_IN_PROGRESS,
            'admin_note' => 'Sto verificando la risposta.',
        ]);

        $this
            ->actingAs($admin)
            ->patch(route('admin.reports.update', $report), [
                'status' => TrainingQuestionReport::STATUS_RESOLVED,
            ])
            ->assertRedirect();

        $report->refresh();
        $this->assertSame(TrainingQuestionReport::STATUS_RESOLVED, $report->status);
        $this->assertSame($admin->id, $report->resolved_by);
        $this->assertNotNull($report->resolved_at);

        $this
            ->actingAs($admin)
            ->patch(route('admin.reports.update', $report), [
                'status' => TrainingQuestionReport::STATUS_OPEN,
            ])
            ->assertRedirect();

        $report->refresh();
        $this->assertSame(TrainingQuestionReport::STATUS_OPEN, $report->status);
        $this->assertNull($report->resolved_by);
        $this->assertNull($report->resolved_at);
    }

    public function test_admin_can_delete_spam_report(): void
    {
        $admin = $this->createAdmin();
        $quiz = $this->createTrainingQuiz();
        $question = $quiz->questions()->firstOrFail();
        $report = $this->createReport($quiz->id, $question->id);

        $this
            ->actingAs($admin)
            ->delete(route('admin.reports.destroy', $report))
            ->assertRedirect();

        $this->assertDatabaseMissing('training_question_reports', ['id' => $report->id]);
    }

    private function createReport(int $quizId, int $questionId): TrainingQuestionReport
    {
        return TrainingQuestionReport::create([
            'quiz_id' => $quizId,
            'question_id' => $questionId,
            'status' => TrainingQuestionReport::STATUS_OPEN,
            'reporter_nickname' => 'reporter',
            'reporter_email' => 'reporter@example.com',
            'category_name' => 'Anime',
            'quiz_title' => 'Training Anime',
            'question_text' => 'Domanda da verificare',
            'message' => 'La risposta sembra errata.',
        ]);
    }
}
