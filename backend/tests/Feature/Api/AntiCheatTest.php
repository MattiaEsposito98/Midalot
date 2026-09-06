<?php

namespace Tests\Feature\Api;

use App\Models\Minigioco;
use App\Models\MinigiocoAttempt;
use App\Models\MinigiocoRound;
use App\Models\MinigiocoRoundItem;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Services\SaltoTemporaleItemOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesQuizData;
use Tests\TestCase;

/**
 * Copre gli exploit trovati nell'audit di sicurezza del 06/09/2026.
 * Ogni test descrive la richiesta che un giocatore avrebbe potuto inviare
 * per falsare il proprio punteggio o leggere dati non suoi.
 */
class AntiCheatTest extends TestCase
{
    use CreatesQuizData;
    use RefreshDatabase;

    private function startedAttempt(Quiz $quiz, $user, int $secondsAgo = 0): QuizAttempt
    {
        return QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'started_at' => now()->subSeconds($secondsAgo),
            'completed' => false,
            'score' => 0,
        ]);
    }

    public function test_dichiarare_tempo_zero_non_regala_il_bonus_velocita(): void
    {
        $user = $this->createUser();
        $quiz = $this->createAssignedQuiz();
        $this->addQuestions($quiz, 1);

        $question = $quiz->questions()->first();
        $correctAnswer = $question->answers()->where('is_correct', true)->first();

        // Il tentativo e' iniziato 9 secondi fa: e' il tempo che il server ha
        // realmente osservato, qualunque cosa dichiari il client.
        $attempt = $this->startedAttempt($quiz, $user, 9);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/quiz/answer', [
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'answer_id' => $correctAnswer->id,
                'time_taken' => 0,
            ])
            ->assertOk();

        $saved = QuizAnswer::where('attempt_id', $attempt->id)->first();

        $this->assertGreaterThan(
            8000,
            $saved->time_taken,
            'Il tempo salvato deve essere quello osservato dal server, non lo zero dichiarato dal client.'
        );
    }

    public function test_il_tempo_misurato_dal_browser_viene_rispettato(): void
    {
        $user = $this->createUser();
        $quiz = $this->createAssignedQuiz();
        $this->addQuestions($quiz, 1);

        $question = $quiz->questions()->first();
        $correctAnswer = $question->answers()->where('is_correct', true)->first();

        // Il client dichiara piu' di quanto il server abbia osservato: e' il
        // caso normale, perche' il cronometro del browser parte quando la
        // domanda compare a schermo, non alla risposta precedente.
        $attempt = $this->startedAttempt($quiz, $user, 2);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/quiz/answer', [
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'answer_id' => $correctAnswer->id,
                'time_taken' => 5000,
            ])
            ->assertOk();

        // Il giocatore onesto non deve essere penalizzato: il valore misurato
        // dal browser resta esattamente quello salvato.
        $this->assertSame(5000, QuizAnswer::where('attempt_id', $attempt->id)->first()->time_taken);
    }

    public function test_non_si_puo_rispondere_due_volte_alla_stessa_domanda(): void
    {
        $user = $this->createUser();
        $quiz = $this->createAssignedQuiz();
        $this->addQuestions($quiz, 1);

        $question = $quiz->questions()->first();
        $correctAnswer = $question->answers()->where('is_correct', true)->first();
        $attempt = $this->startedAttempt($quiz, $user);

        $payload = [
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'answer_id' => $correctAnswer->id,
            'time_taken' => 1000,
        ];

        $this->actingAs($user, 'sanctum')->postJson('/api/quiz/answer', $payload)->assertOk();
        $this->actingAs($user, 'sanctum')->postJson('/api/quiz/answer', $payload)->assertForbidden();

        $this->assertSame(1, QuizAnswer::where('attempt_id', $attempt->id)->count());
    }

    public function test_il_database_rifiuta_la_risposta_duplicata_anche_aggirando_il_controller(): void
    {
        $user = $this->createUser();
        $quiz = $this->createAssignedQuiz();
        $this->addQuestions($quiz, 1);

        $question = $quiz->questions()->first();
        $attempt = $this->startedAttempt($quiz, $user);

        $row = [
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'answer_id' => $question->answers()->first()->id,
            'time_taken' => 1000,
            'is_correct' => true,
            'is_timeout' => false,
            'is_wrong' => false,
            'score' => 10000,
        ];

        QuizAnswer::create($row);

        // E' l'indice univoco a difendere davvero dalle richieste parallele:
        // il controllo nel controller non e' atomico.
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        QuizAnswer::create($row);
    }

    public function test_un_quiz_midalario_non_e_leggibile_dagli_endpoint_one_shot(): void
    {
        $user = $this->createUser();

        $midalario = Quiz::create([
            'title' => 'Midalario di prova',
            'description' => 'Non deve essere leggibile prima della diretta',
            'type' => 'midalario',
            'created_by' => $this->createAdmin()->id,
            'is_active' => true,
            'leaderboard_visible' => true,
            'restrict_to_specific_users' => false,
        ]);
        $this->addQuestions($midalario, 3);

        // Prima dell'audit questi rispondevano 200, rivelando domande e risposte.
        $this->actingAs($user, 'sanctum')->getJson("/api/quizzes/{$midalario->id}")->assertNotFound();
        $this->actingAs($user, 'sanctum')->getJson("/api/quizzes/{$midalario->id}/review")->assertNotFound();
        $this->actingAs($user, 'sanctum')->getJson("/api/quizzes/{$midalario->id}/leaderboard")->assertNotFound();
        $this->actingAs($user, 'sanctum')->postJson("/api/quiz/{$midalario->id}/start")->assertNotFound();

        $this->assertSame(0, QuizAttempt::where('quiz_id', $midalario->id)->count());
    }

    public function test_un_quiz_di_training_non_e_avviabile_come_one_shot(): void
    {
        $user = $this->createUser();
        $training = $this->createTrainingQuiz(3);

        $this->actingAs($user, 'sanctum')->postJson("/api/quiz/{$training->id}/start")->assertNotFound();
    }

    public function test_salto_temporale_giro_completo_dalle_api(): void
    {
        $user = $this->createUser();

        $minigioco = Minigioco::create([
            'title' => 'Salto Temporale end to end',
            'tipo' => 'salto_temporale',
            'created_by' => $this->createAdmin()->id,
            'is_active' => true,
            'leaderboard_visible' => true,
            'max_score' => 100,
        ]);

        $round = MinigiocoRound::create([
            'minigioco_id' => $minigioco->id,
            'parola_originale' => 'PROVA',
            'shift' => 1,
            'time_limit_seconds' => 20,
        ]);

        foreach (range(1, 4) as $ordine) {
            MinigiocoRoundItem::create([
                'minigioco_round_id' => $round->id,
                'ordine' => $ordine,
                'label' => "Evento {$ordine}",
            ]);
        }

        // Ordine reale del gioco: prima si leggono i round, poi si avvia il
        // tentativo (per questo la permutazione degli id dipende dall'utente
        // e non dal tentativo, che a quel punto non esiste ancora).
        $play = $this->actingAs($user, 'sanctum')
            ->getJson("/api/minigiochi/{$minigioco->id}")
            ->assertOk();

        $attemptId = $this->actingAs($user, 'sanctum')
            ->postJson("/api/minigiochi/{$minigioco->id}/start")
            ->assertCreated()
            ->json('attempt_id');

        $itemsMostrati = collect($play->json('minigioco.rounds.0.items'));

        // Il client deve poter risolvere il round usando solo cio' che riceve:
        // le etichette dicono l ordine, gli id no.
        $rispostaCorretta = $itemsMostrati
            ->sortBy('label')
            ->pluck('id')
            ->all();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/minigiochi/answer', [
                'attempt_id' => $attemptId,
                'round_id' => $round->id,
                'risposta' => $rispostaCorretta,
                'time_taken' => 3000,
            ])
            ->assertOk()
            ->assertJson(['correct' => true]);
    }

    public function test_ordinare_gli_item_per_id_non_risolve_piu_salto_temporale(): void
    {
        $minigioco = Minigioco::create([
            'title' => 'Salto Temporale di prova',
            'tipo' => 'salto_temporale',
            'created_by' => $this->createAdmin()->id,
            'is_active' => true,
            'leaderboard_visible' => true,
        ]);

        $round = MinigiocoRound::create([
            'minigioco_id' => $minigioco->id,
            'parola_originale' => 'PROVA',
            'shift' => 1,
            'time_limit_seconds' => 20,
        ]);

        // Gli item vengono creati in sequenza, quindi id crescente == ordine
        // corretto: era esattamente questo a rendere il gioco indovinabile.
        foreach (range(1, 4) as $ordine) {
            MinigiocoRoundItem::create([
                'minigioco_round_id' => $round->id,
                'ordine' => $ordine,
                'label' => "Evento {$ordine}",
            ]);
        }

        $round->load('items');
        $realOrder = $round->items->pluck('id')->map(fn ($id) => (int) $id)->all();

        $ordinati = $realOrder;
        sort($ordinati);
        $this->assertSame(
            $ordinati,
            $realOrder,
            'Presupposto del test: gli id crescenti coincidono con l ordine corretto.'
        );

        $vintiPerCaso = 0;

        foreach (range(1, 40) as $userId) {
            $map = SaltoTemporaleItemOrder::realToDisplay($round, $userId);
            $mostrati = array_map(fn ($id) => $map[$id], $realOrder);

            $exploit = $mostrati;
            sort($exploit);

            if ($exploit === $mostrati) {
                $vintiPerCaso++;
            }

            // La risposta corretta deve comunque essere sempre riconosciuta.
            $inverso = SaltoTemporaleItemOrder::displayToReal($round, $userId);
            $this->assertSame($realOrder, array_map(fn ($id) => $inverso[$id], $mostrati));
        }

        // Con 4 item la probabilita' di indovinare a caso e' 1/24: l'exploit
        // sistematico non deve piu' esistere, resta solo il caso.
        $this->assertLessThan(
            8,
            $vintiPerCaso,
            'Ordinare per id non deve dare un vantaggio sistematico.'
        );
    }
}
