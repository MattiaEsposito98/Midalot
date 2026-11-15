<?php

namespace Tests\Feature\Admin;

use App\Models\Answer;
use App\Models\Question;
use App\Services\ChallengeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesQuizData;
use Tests\TestCase;

class AiQuestionDraftsTest extends TestCase
{
    use CreatesQuizData;
    use RefreshDatabase;

    private function addAiDraft($quiz, string $text = 'Domanda AI'): Question
    {
        $question = Question::create([
            'quiz_id' => $quiz->id,
            'source' => Question::SOURCE_AI,
            'review_status' => Question::STATUS_PENDING,
            'ai_note' => 'Controlla la data',
            'question_text' => $text,
            'time_limit_seconds' => 10,
        ]);

        Answer::create(['question_id' => $question->id, 'answer_text' => 'Giusta', 'is_correct' => true]);
        foreach (['A', 'B', 'C'] as $wrong) {
            Answer::create(['question_id' => $question->id, 'answer_text' => $wrong, 'is_correct' => false]);
        }

        return $question;
    }

    public function test_le_domande_esistenti_restano_admin_e_approvate(): void
    {
        $quiz = $this->createTrainingQuiz(3);

        $this->assertSame(3, $quiz->questions()->where('source', 'admin')->count());
        $this->assertSame(3, $quiz->questions()->count());
    }

    public function test_le_bozze_ai_non_entrano_nel_gioco_training(): void
    {
        // 5 approvate + 5 bozze: la sessione da 5 domande usa solo le approvate.
        $quiz = $this->createTrainingQuiz(5);
        $drafts = collect(range(1, 5))->map(fn ($i) => $this->addAiDraft($quiz, "Bozza {$i}"));

        $start = $this->postJson("/api/training/quizzes/{$quiz->id}/guest-start")->assertOk();

        $ids = collect($start->json('quiz.questions'))->pluck('id');
        $this->assertCount(5, $ids);
        $this->assertEmpty($ids->intersect($drafts->pluck('id')));
    }

    public function test_un_training_con_poche_domande_approvate_non_e_giocabile_anche_se_ha_bozze(): void
    {
        $quiz = $this->createTrainingQuiz(3); // servono 5
        foreach (range(1, 5) as $i) {
            $this->addAiDraft($quiz, "Bozza {$i}");
        }

        $this->assertGreaterThanOrEqual(
            400,
            $this->postJson("/api/training/quizzes/{$quiz->id}/guest-start")->status()
        );
    }

    public function test_le_bozze_non_entrano_nel_pool_delle_sfide(): void
    {
        $quiz = $this->createTrainingQuiz(2);
        $draft = $this->addAiDraft($quiz);

        $pool = app(ChallengeService::class)->poolQuery(null)->pluck('questions.id');

        $this->assertFalse($pool->contains($draft->id));
        $this->assertCount(2, $pool);
    }

    public function test_admin_vede_le_bozze_con_badge_e_risposte(): void
    {
        $admin = $this->createAdmin();
        $quiz = $this->createTrainingQuiz(1);
        $this->addAiDraft($quiz, 'Qual e la capitale della Francia?');

        $this->actingAs($admin)
            ->get(route('admin.quizzes.questions.index', $quiz->id))
            ->assertOk()
            ->assertSee('Bozza AI')
            ->assertSee('Qual e la capitale della Francia?')
            ->assertSee('Controlla la data')
            ->assertSee('Attiva tutte');
    }

    public function test_admin_attiva_una_bozza_e_diventa_giocabile(): void
    {
        $admin = $this->createAdmin();
        $quiz = $this->createTrainingQuiz(1);
        $draft = $this->addAiDraft($quiz);

        $this->actingAs($admin)
            ->post(route('admin.quizzes.questions.approve', [$quiz->id, $draft->id]))
            ->assertRedirect();

        $this->assertSame(2, $quiz->questions()->count());
        $this->assertSame('ai', $draft->fresh()->source);
        $this->assertSame('approved', $draft->fresh()->review_status);
    }

    public function test_admin_attiva_tutte_le_bozze_di_un_quiz(): void
    {
        $admin = $this->createAdmin();
        $quiz = $this->createTrainingQuiz(1);
        $this->addAiDraft($quiz, 'Uno');
        $this->addAiDraft($quiz, 'Due');
        $altroQuiz = $this->createTrainingQuiz(1, ['title' => 'Altro', 'category' => $quiz->trainingCategory]);
        $altraBozza = $this->addAiDraft($altroQuiz, 'Altro quiz');

        $this->actingAs($admin)
            ->post(route('admin.quizzes.questions.approve-all', $quiz->id))
            ->assertRedirect();

        $this->assertSame(3, $quiz->questions()->count());
        $this->assertSame('pending', Question::withoutGlobalScope('approved')->find($altraBozza->id)->review_status);
        $this->assertSame(1, $altroQuiz->questions()->count());
    }

    public function test_admin_puo_modificare_ed_eliminare_una_bozza_senza_attivarla(): void
    {
        $admin = $this->createAdmin();
        $quiz = $this->createTrainingQuiz(1);
        $draft = $this->addAiDraft($quiz);

        $this->actingAs($admin)
            ->get(route('admin.quizzes.questions.edit', [$quiz->id, $draft->id]))
            ->assertOk();

        $this->actingAs($admin)
            ->put(route('admin.quizzes.questions.update', [$quiz->id, $draft->id]), [
                'question_text' => 'Testo corretto',
                'time_limit_seconds' => 10,
                'answers' => ['Giusta', 'A', 'B', 'C'],
                'correct_answer' => 0,
            ])
            ->assertRedirect();

        $fresh = Question::withoutGlobalScope('approved')->find($draft->id);
        $this->assertSame('Testo corretto', $fresh->question_text);
        $this->assertSame('pending', $fresh->review_status);

        $this->actingAs($admin)
            ->delete(route('admin.quizzes.questions.destroy', [$quiz->id, $draft->id]))
            ->assertRedirect();

        $this->assertNull(Question::withoutGlobalScope('approved')->find($draft->id));
    }

    public function test_il_menu_admin_mostra_il_contatore_delle_domande_da_confermare(): void
    {
        $admin = $this->createAdmin();
        $quiz = $this->createTrainingQuiz(1);
        $this->addAiDraft($quiz, 'Uno');
        $this->addAiDraft($quiz, 'Due');
        $this->addAiDraft($quiz, 'Tre');

        $this->actingAs($admin)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Domande da confermare')
            ->assertSee('<span class="admin-nav-count">3</span>', false);
    }

    public function test_la_pagina_domande_da_confermare_raggruppa_le_bozze_per_quiz(): void
    {
        $admin = $this->createAdmin();
        $quiz = $this->createTrainingQuiz(1, ['title' => 'Quiz Uno']);
        $this->addAiDraft($quiz, 'Bozza del primo');
        $altro = $this->createTrainingQuiz(1, ['title' => 'Quiz Due', 'category' => $quiz->trainingCategory]);
        $this->addAiDraft($altro, 'Bozza del secondo');

        $this->actingAs($admin)
            ->get(route('admin.question-drafts.index'))
            ->assertOk()
            ->assertSee('Quiz Uno')
            ->assertSee('Quiz Due')
            ->assertSee('Bozza del primo')
            ->assertSee('Bozza del secondo')
            ->assertSee('2 bozze');
    }

    public function test_la_pagina_e_vuota_senza_bozze_e_vietata_agli_utenti(): void
    {
        $this->actingAs($this->createAdmin())
            ->get(route('admin.question-drafts.index'))
            ->assertOk()
            ->assertSee('Nessuna domanda da confermare');

        $this->actingAs($this->createUser())
            ->get(route('admin.question-drafts.index'))
            ->assertStatus(403);
    }

    public function test_un_utente_normale_non_puo_attivare_bozze(): void
    {
        $quiz = $this->createTrainingQuiz(1);
        $draft = $this->addAiDraft($quiz);

        $this->actingAs($this->createUser())
            ->post(route('admin.quizzes.questions.approve', [$quiz->id, $draft->id]))
            ->assertStatus(403);

        $this->assertSame('pending', Question::withoutGlobalScope('approved')->find($draft->id)->review_status);
    }
}
