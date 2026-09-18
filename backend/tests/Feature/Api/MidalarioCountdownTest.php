<?php

namespace Tests\Feature\Api;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Copre il conto alla rovescia introdotto il 12/09/2026: tra il clic su
 * "Avvia" e l'inizio della prima domanda passano alcuni secondi, che servono
 * al "3, 2, 1" nella pagina dei giocatori.
 */
class MidalarioCountdownTest extends TestCase
{
    use RefreshDatabase;

    private function quiz(array $attributi = []): Quiz
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $quiz = Quiz::create([
            'title' => 'Midalario di test',
            'type' => 'midalario',
            'created_by' => $admin->id,
            'is_active' => true,
            'leaderboard_visible' => true,
            'midalario_status' => 'open',
            ...$attributi,
        ]);

        $domanda = Question::create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Domanda 1',
            'time_limit_seconds' => 10,
        ]);

        Answer::create(['question_id' => $domanda->id, 'answer_text' => 'Giusta', 'is_correct' => true]);
        Answer::create(['question_id' => $domanda->id, 'answer_text' => 'Sbagliata', 'is_correct' => false]);

        return $quiz;
    }

    private function iscrivi(Quiz $quiz, ?User $utente = null): User
    {
        $utente ??= User::factory()->create(['is_admin' => false]);
        QuizParticipant::create(['quiz_id' => $quiz->id, 'user_id' => $utente->id]);

        return $utente;
    }

    public function test_la_sala_dattesa_mostra_lorario_previsto(): void
    {
        // Senza microsecondi: la colonna timestamp non li conserva.
        $orario = now()->addHours(2)->startOfSecond();
        $quiz = $this->quiz(['midalario_scheduled_at' => $orario]);
        $utente = $this->iscrivi($quiz);

        $risposta = $this->actingAs($utente, 'sanctum')
            ->getJson("/api/midalario/quizzes/{$quiz->id}/status")
            ->assertOk();

        $this->assertSame(
            $orario->timestamp,
            strtotime($risposta->json('scheduled_at')),
            'La sala d\'attesa deve ricevere l\'orario previsto per il conto alla rovescia.'
        );
    }

    public function test_durante_il_conto_alla_rovescia_la_domanda_non_viene_rivelata(): void
    {
        $quiz = $this->quiz([
            'midalario_status' => 'running',
            // il via e' stato dato, ma la prima domanda scatta tra 5 secondi
            'midalario_started_at' => now()->addSeconds(5),
        ]);
        $utente = $this->iscrivi($quiz);

        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $utente->id,
            'started_at' => now(),
            'completed' => false,
            'score' => 0,
            'question_order' => $quiz->questions()->pluck('id')->all(),
        ]);

        $risposta = $this->actingAs($utente, 'sanctum')
            ->getJson("/api/midalario/quizzes/{$quiz->id}/status")
            ->assertOk();

        // Il testo della domanda non deve essere leggibile in anticipo dagli
        // strumenti per sviluppatori del browser.
        $risposta->assertJsonMissingPath('question');
        $this->assertGreaterThan(0, $risposta->json('starting_in_ms'));
    }

    public function test_rispondere_durante_il_conto_alla_rovescia_viene_rifiutato(): void
    {
        $quiz = $this->quiz([
            'midalario_status' => 'running',
            'midalario_started_at' => now()->addSeconds(5),
        ]);
        $utente = $this->iscrivi($quiz);

        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $utente->id,
            'started_at' => now(),
            'completed' => false,
            'score' => 0,
            'question_order' => $quiz->questions()->pluck('id')->all(),
        ]);

        $domanda = $quiz->questions()->first();
        $giusta = $domanda->answers()->where('is_correct', true)->first();

        // Senza questo controllo il tempo impiegato risulterebbe zero e la
        // risposta prenderebbe il bonus velocita' massimo.
        $this->actingAs($utente, 'sanctum')
            ->postJson("/api/midalario/quizzes/{$quiz->id}/answer", [
                'question_id' => $domanda->id,
                'answer_id' => $giusta->id,
            ])
            ->assertStatus(422);

        $this->assertSame(0, QuizAnswer::count());
    }

    public function test_a_conto_alla_rovescia_finito_la_domanda_arriva_col_tempo_pieno(): void
    {
        $quiz = $this->quiz([
            'midalario_status' => 'running',
            'midalario_started_at' => now(),
        ]);
        $utente = $this->iscrivi($quiz);

        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $utente->id,
            'started_at' => now(),
            'completed' => false,
            'score' => 0,
            'question_order' => $quiz->questions()->pluck('id')->all(),
        ]);

        $risposta = $this->actingAs($utente, 'sanctum')
            ->getJson("/api/midalario/quizzes/{$quiz->id}/status")
            ->assertOk();

        $this->assertNotNull($risposta->json('question'));
        $this->assertSame(0, $risposta->json('question.index'));

        // La finestra deve durare i 10 secondi pieni della domanda: l'attesa
        // per il "3, 2, 1" non deve rubarle tempo.
        $inizio = strtotime($risposta->json('question.starts_at'));
        $fine = strtotime($risposta->json('question.ends_at'));
        $this->assertSame(10, $fine - $inizio);
    }
}
