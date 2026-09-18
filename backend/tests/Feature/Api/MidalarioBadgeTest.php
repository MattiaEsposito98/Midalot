<?php

namespace Tests\Feature\Api;

use App\Models\MidalarioBadge;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\MidalarioFinalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Il badge "Vincitore del Midalario" viene assegnato automaticamente da
 * MidalarioFinalizer quando la partita si chiude, senza nessuna azione admin.
 */
class MidalarioBadgeTest extends TestCase
{
    use RefreshDatabase;

    private function quizConMezzo(array $punteggi): Quiz
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $quiz = Quiz::create([
            'title' => 'Midalario di prova',
            'type' => 'midalario',
            'created_by' => $admin->id,
            'is_active' => true,
            'leaderboard_visible' => true,
            'midalario_status' => 'running',
            'midalario_started_at' => now()->subMinutes(10),
        ]);

        foreach ($punteggi as $score) {
            $utente = User::factory()->create(['is_admin' => false]);

            QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'user_id' => $utente->id,
                'started_at' => now()->subMinutes(10),
                'completed' => true,
                'finished_at' => now()->subMinutes(9),
                'score' => $score,
                'question_order' => [],
            ]);
        }

        return $quiz;
    }

    public function test_il_punteggio_piu_alto_riceve_il_badge(): void
    {
        $quiz = $this->quizConMezzo([5000, 9000, 3000]);
        $vincitore = QuizAttempt::where('quiz_id', $quiz->id)->where('score', 9000)->first()->user;

        (new MidalarioFinalizer())->finalizeIfNeeded($quiz);

        $this->assertSame(1, MidalarioBadge::where('quiz_id', $quiz->id)->count());

        $badge = MidalarioBadge::where('quiz_id', $quiz->id)->first();
        $this->assertSame($vincitore->id, $badge->user_id);
        $this->assertSame(9000, $badge->total_score);
        $this->assertSame("Vincitore del Midalario: {$quiz->title}", $badge->label);
    }

    public function test_i_pari_merito_ricevono_tutti_il_badge(): void
    {
        $quiz = $this->quizConMezzo([9000, 9000, 4000]);

        (new MidalarioFinalizer())->finalizeIfNeeded($quiz);

        $this->assertSame(2, MidalarioBadge::where('quiz_id', $quiz->id)->count());
    }

    public function test_punteggio_massimo_a_zero_non_assegna_nessun_badge(): void
    {
        $quiz = $this->quizConMezzo([0, 0, 0]);

        (new MidalarioFinalizer())->finalizeIfNeeded($quiz);

        $this->assertSame(0, MidalarioBadge::where('quiz_id', $quiz->id)->count());
    }

    public function test_finalizzare_piu_volte_non_duplica_il_badge(): void
    {
        $quiz = $this->quizConMezzo([9000, 4000]);

        foreach (range(1, 3) as $_) {
            (new MidalarioFinalizer())->finalizeIfNeeded($quiz->fresh());
        }

        $this->assertSame(1, MidalarioBadge::where('quiz_id', $quiz->id)->count());
    }
}
