<?php

namespace Tests\Feature\Api;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizParticipant;
use App\Models\User;
use App\Services\MidalarioFinalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Copre il bug trovato l'11/09/2026 simulando una serata Midalario: allo
 * scadere dell'ultima domanda tutti i giocatori chiamano /status insieme, e
 * ogni richiesta esegue il finalizzatore. Prima del fix la prima vinceva e
 * tutte le altre fallivano sull'indice univoco di quiz_answers, restituendo
 * un 500 proprio al momento dei risultati.
 */
class MidalarioFinalizerTest extends TestCase
{
    use RefreshDatabase;

    private function quizConcluso(int $partecipanti = 3, int $domande = 3): Quiz
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $quiz = Quiz::create([
            'title' => 'Midalario di test',
            'type' => 'midalario',
            'created_by' => $admin->id,
            'is_active' => true,
            'leaderboard_visible' => true,
            'midalario_status' => 'running',
            // avviato abbastanza tempo fa da essere gia' scaduto
            'midalario_started_at' => now()->subMinutes(10),
        ]);

        for ($i = 1; $i <= $domande; $i++) {
            $domanda = Question::create([
                'quiz_id' => $quiz->id,
                'question_text' => "Domanda {$i}",
                'time_limit_seconds' => 10,
            ]);

            Answer::create(['question_id' => $domanda->id, 'answer_text' => 'Giusta', 'is_correct' => true]);
            Answer::create(['question_id' => $domanda->id, 'answer_text' => 'Sbagliata', 'is_correct' => false]);
        }

        $questionIds = $quiz->questions()->orderBy('id')->pluck('id')->all();

        for ($i = 1; $i <= $partecipanti; $i++) {
            $utente = User::factory()->create(['is_admin' => false]);
            QuizParticipant::create(['quiz_id' => $quiz->id, 'user_id' => $utente->id]);

            QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'user_id' => $utente->id,
                'started_at' => now()->subMinutes(10),
                'completed' => false,
                'score' => 0,
                'question_order' => $questionIds,
            ]);
        }

        return $quiz;
    }

    public function test_chiude_la_partita_assegnando_i_timeout_mancanti(): void
    {
        $quiz = $this->quizConcluso(partecipanti: 3, domande: 3);

        (new MidalarioFinalizer())->finalizeIfNeeded($quiz);

        $this->assertSame('finished', $quiz->fresh()->midalario_status);

        foreach (QuizAttempt::where('quiz_id', $quiz->id)->get() as $attempt) {
            $this->assertTrue((bool) $attempt->completed);
            $this->assertSame(3, QuizAnswer::where('attempt_id', $attempt->id)->count());
        }
    }

    public function test_finalizzare_piu_volte_non_duplica_e_non_esplode(): void
    {
        $quiz = $this->quizConcluso(partecipanti: 3, domande: 3);

        // Simula piu' richieste /status che arrivano a chiudere la partita.
        // Nessuna deve lanciare eccezioni ne' creare righe doppie.
        foreach (range(1, 5) as $_) {
            (new MidalarioFinalizer())->finalizeIfNeeded($quiz->fresh());
        }

        foreach (QuizAttempt::where('quiz_id', $quiz->id)->get() as $attempt) {
            $righe = QuizAnswer::where('attempt_id', $attempt->id)->get();

            $this->assertSame(3, $righe->count(), 'Nessuna risposta duplicata.');
            $this->assertSame(
                3,
                $righe->pluck('question_id')->unique()->count(),
                'Ogni domanda deve comparire una volta sola.'
            );
        }
    }

    public function test_non_tocca_le_risposte_gia_date_dal_giocatore(): void
    {
        $quiz = $this->quizConcluso(partecipanti: 1, domande: 3);

        $attempt = QuizAttempt::where('quiz_id', $quiz->id)->first();
        $domanda = $quiz->questions()->orderBy('id')->first();
        $giusta = $domanda->answers()->where('is_correct', true)->first();

        QuizAnswer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $domanda->id,
            'answer_id' => $giusta->id,
            'time_taken' => 1200,
            'is_correct' => true,
            'is_timeout' => false,
            'is_wrong' => false,
            'score' => 9000,
        ]);

        (new MidalarioFinalizer())->finalizeIfNeeded($quiz);

        $rimasta = QuizAnswer::where('attempt_id', $attempt->id)
            ->where('question_id', $domanda->id)
            ->first();

        $this->assertTrue((bool) $rimasta->is_correct, 'La risposta corretta non deve diventare un timeout.');
        $this->assertSame(9000, (int) $rimasta->score);
        $this->assertSame(3, QuizAnswer::where('attempt_id', $attempt->id)->count());
    }
}
