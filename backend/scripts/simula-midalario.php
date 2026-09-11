<?php

/**
 * Simulazione locale di una serata Midalario: crea un quiz di prova, iscrive
 * N utenti finti, avvia la partita e li fa giocare tutti insieme via HTTP.
 *
 * Serve a verificare che il flusso regga con piu' persone collegate
 * contemporaneamente. NON tocca mai la produzione: lavora solo sul database
 * locale e su http://localhost:8000.
 *
 *   php scripts/simula-midalario.php prepara   -> crea quiz, utenti, iscrizioni
 *   php scripts/simula-midalario.php avvia     -> chiude iscrizioni e avvia
 *   php scripts/simula-midalario.php gioca     -> tutti giocano in parallelo
 *   php scripts/simula-midalario.php verifica  -> controlla i risultati
 *   php scripts/simula-midalario.php pulisci   -> rimuove i dati di prova
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Models\QuizParticipant;
use App\Models\User;
use App\Services\MidalarioTimeline;
use Illuminate\Support\Facades\DB;

const API = 'http://localhost:8000';
const TITOLO = '[SIMULAZIONE] Midalario di prova';
const NUM_UTENTI = 15;
const NUM_DOMANDE = 7;
const SECONDI_PER_DOMANDA = 10;

if (app()->environment('production')) {
    exit("RIFIUTO: questo script non deve girare in produzione.\n");
}

$comando = $argv[1] ?? 'aiuto';

function quizSimulazione(): ?Quiz
{
    return Quiz::where('type', 'midalario')->where('title', TITOLO)->first();
}

function utentiSimulazione()
{
    return User::where('email', 'like', 'sim-midalario-%@example.test')->orderBy('id')->get();
}

/** Esegue piu' richieste HTTP davvero in parallelo. */
function inParallelo(array $richieste): array
{
    $multi = curl_multi_init();
    $handles = [];

    foreach ($richieste as $chiave => $r) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, API.$r['url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer '.$r['token'],
        ]);

        if (($r['metodo'] ?? 'GET') === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($r['corpo'] ?? []));
        }

        curl_multi_add_handle($multi, $ch);
        $handles[$chiave] = $ch;
    }

    $attive = null;
    do {
        curl_multi_exec($multi, $attive);
        curl_multi_select($multi, 0.1);
    } while ($attive > 0);

    $risposte = [];
    foreach ($handles as $chiave => $ch) {
        $risposte[$chiave] = [
            'http' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
            'corpo' => json_decode(curl_multi_getcontent($ch), true),
        ];
        curl_multi_remove_handle($multi, $ch);
        curl_close($ch);
    }

    curl_multi_close($multi);

    return $risposte;
}

// ---------------------------------------------------------------- prepara
if ($comando === 'prepara') {
    if (quizSimulazione()) {
        exit("Esiste gia' un quiz di simulazione. Lancia prima 'pulisci'.\n");
    }

    $admin = User::where('is_admin', true)->first() ?? User::factory()->create(['is_admin' => true]);

    $quiz = Quiz::create([
        'title' => TITOLO,
        'description' => 'Quiz creato dallo script di simulazione',
        'type' => 'midalario',
        'created_by' => $admin->id,
        'is_active' => true,
        'leaderboard_visible' => true,
        'midalario_status' => 'open',
    ]);

    for ($i = 1; $i <= NUM_DOMANDE; $i++) {
        $domanda = Question::create([
            'quiz_id' => $quiz->id,
            'question_text' => "Domanda di simulazione {$i}",
            'time_limit_seconds' => SECONDI_PER_DOMANDA,
        ]);

        Answer::create(['question_id' => $domanda->id, 'answer_text' => 'Corretta', 'is_correct' => true]);
        foreach (['Sbagliata A', 'Sbagliata B', 'Sbagliata C'] as $testo) {
            Answer::create(['question_id' => $domanda->id, 'answer_text' => $testo, 'is_correct' => false]);
        }
    }

    $token = [];
    for ($i = 1; $i <= NUM_UTENTI; $i++) {
        $utente = User::factory()->create([
            'name' => "Simulazione {$i}",
            'nickname' => "sim_utente_{$i}",
            'email' => "sim-midalario-{$i}@example.test",
            'email_verified_at' => now(),
            'is_admin' => false,
        ]);

        QuizParticipant::firstOrCreate(['quiz_id' => $quiz->id, 'user_id' => $utente->id]);
        $token[$utente->id] = $utente->createToken('simulazione')->plainTextToken;
    }

    file_put_contents(__DIR__.'/.simulazione-token.json', json_encode($token));

    echo "Quiz di simulazione creato (id={$quiz->id})\n";
    echo NUM_UTENTI." utenti iscritti, ".NUM_DOMANDE." domande da ".SECONDI_PER_DOMANDA."s\n";
    echo "Durata partita: ".(NUM_DOMANDE * SECONDI_PER_DOMANDA)."s\n";
    exit;
}

$quiz = quizSimulazione();

if (! $quiz) {
    exit("Nessun quiz di simulazione. Lancia prima 'prepara'.\n");
}

// ------------------------------------------------------------------ avvia
if ($comando === 'avvia') {
    $startedAt = now();
    $questionIds = $quiz->questions()->orderBy('id')->pluck('id')->all();

    foreach ($quiz->participants as $partecipante) {
        $mescolate = $questionIds;
        shuffle($mescolate);

        QuizAttempt::firstOrCreate(
            ['quiz_id' => $quiz->id, 'user_id' => $partecipante->user_id],
            ['started_at' => $startedAt, 'completed' => false, 'score' => 0, 'question_order' => $mescolate]
        );
    }

    $quiz->update(['midalario_status' => 'running', 'midalario_started_at' => $startedAt]);

    echo "Partita avviata alle ".$startedAt->toTimeString()."\n";
    echo "Tentativi creati: ".QuizAttempt::where('quiz_id', $quiz->id)->count()."\n";
    exit;
}

// ------------------------------------------------------------------ gioca
if ($comando === 'gioca') {
    $token = json_decode(file_get_contents(__DIR__.'/.simulazione-token.json'), true);
    $durata = NUM_DOMANDE * SECONDI_PER_DOMANDA;
    $fine = microtime(true) + $durata + 5; // +5s per coprire la finalizzazione

    $errori = [];
    $risposteInviate = 0;
    $giro = 0;

    echo "Tutti e ".count($token)." gli utenti giocano insieme per ~{$durata}s...\n";

    while (microtime(true) < $fine) {
        $giro++;

        // 1) tutti chiedono lo stato nello stesso istante
        $richieste = [];
        foreach ($token as $userId => $t) {
            $richieste[$userId] = ['url' => "/api/midalario/quizzes/{$quiz->id}/status", 'token' => $t];
        }
        $stati = inParallelo($richieste);

        // 2) chi ha una domanda attiva e non ha ancora risposto, risponde subito
        $risposte = [];
        foreach ($stati as $userId => $s) {
            if ($s['http'] !== 200) {
                $errori[] = "giro {$giro} utente {$userId}: status HTTP {$s['http']} ".($s['corpo']['message'] ?? '');
                continue;
            }

            $domanda = $s['corpo']['question'] ?? null;
            if (! $domanda || ($s['corpo']['has_answered'] ?? false)) {
                continue;
            }

            // un utente su cinque non risponde: simula chi va in timeout
            if ($userId % 5 === 0) {
                continue;
            }

            $opzioni = $domanda['answers'];
            // meta' rispondono a caso (quindi a volte sbagliano)
            $scelta = $userId % 2 === 0 ? $opzioni[0] : $opzioni[array_rand($opzioni)];

            $risposte[$userId] = [
                'url' => "/api/midalario/quizzes/{$quiz->id}/answer",
                'token' => $token[$userId],
                'metodo' => 'POST',
                'corpo' => ['question_id' => $domanda['id'], 'answer_id' => $scelta['id']],
            ];
        }

        if ($risposte) {
            foreach (inParallelo($risposte) as $userId => $r) {
                if (in_array($r['http'], [200, 403, 422], true)) {
                    if ($r['http'] === 200) {
                        $risposteInviate++;
                    }
                } else {
                    $errori[] = "giro {$giro} utente {$userId}: answer HTTP {$r['http']} ".($r['corpo']['message'] ?? '');
                }
            }
        }

        usleep(700000); // ~0.7s tra un giro e l'altro, come farebbe il frontend
    }

    echo "\nGiri completati: {$giro}\n";
    echo "Risposte accettate: {$risposteInviate}\n";
    echo "Errori HTTP inattesi: ".count($errori)."\n";
    foreach (array_slice($errori, 0, 20) as $e) {
        echo "  ! {$e}\n";
    }
    exit;
}

// --------------------------------------------------------------- verifica
if ($comando === 'verifica') {
    $quiz->refresh();
    $timeline = new MidalarioTimeline($quiz);
    $domande = $timeline->questions();

    echo "stato quiz: {$quiz->midalario_status}\n";
    echo "partecipanti: ".$quiz->participants()->count()."\n\n";

    $problemi = [];
    $attempts = QuizAttempt::where('quiz_id', $quiz->id)->with('user')->get();

    echo "--- tentativi ---\n";
    foreach ($attempts as $a) {
        $risposte = QuizAnswer::where('attempt_id', $a->id)->get();
        $perDomanda = $risposte->groupBy('question_id');
        $duplicati = $perDomanda->filter(fn ($g) => $g->count() > 1);

        $somma = (int) $risposte->sum('score');
        $tempo = (int) $risposte->sum('time_taken');

        printf(
            "%-16s completato:%-3s risposte:%-2d punti:%-7d (somma righe:%-7d) tempo:%d\n",
            $a->user->nickname,
            $a->completed ? 'si' : 'NO',
            $risposte->count(),
            (int) $a->score,
            $somma,
            (int) $a->total_time
        );

        if ($risposte->count() !== $domande->count()) {
            $problemi[] = "{$a->user->nickname}: ha {$risposte->count()} risposte invece di {$domande->count()}";
        }
        if ($duplicati->isNotEmpty()) {
            $problemi[] = "{$a->user->nickname}: risposte DUPLICATE su ".$duplicati->count()." domande";
        }
        if (! $a->completed) {
            $problemi[] = "{$a->user->nickname}: tentativo non completato";
        }
        if ((int) $a->score !== $somma) {
            $problemi[] = "{$a->user->nickname}: punteggio salvato {$a->score} != somma righe {$somma}";
        }
        if ((int) $a->total_time !== $tempo) {
            $problemi[] = "{$a->user->nickname}: tempo salvato {$a->total_time} != somma righe {$tempo}";
        }
    }

    // ogni utente deve aver visto tutte le domande, nessuna ripetuta
    echo "\n--- ordine domande per utente (deve essere mescolato ma completo) ---\n";
    foreach ($attempts->take(3) as $a) {
        $ordine = $a->question_order ?? [];
        $unici = count(array_unique($ordine));
        echo "{$a->user->nickname}: ".implode(',', $ordine)." (unici: {$unici}/".count($ordine).")\n";
        if ($unici !== $domande->count()) {
            $problemi[] = "{$a->user->nickname}: ordine domande incompleto o con ripetizioni";
        }
    }

    echo "\n--- classifica ---\n";
    $classifica = $attempts->sortByDesc('score')->values();
    foreach ($classifica->take(5) as $i => $a) {
        $corrette = QuizAnswer::where('attempt_id', $a->id)->where('is_correct', true)->count();
        echo "#".($i + 1)." {$a->user->nickname}: {$a->score} punti, {$corrette} corrette\n";
    }

    echo "\n=== ESITO ===\n";
    if ($problemi) {
        echo count($problemi)." PROBLEMI TROVATI:\n";
        foreach ($problemi as $p) {
            echo "  ! {$p}\n";
        }
    } else {
        echo "Nessun problema: tutti i tentativi completi, coerenti e senza duplicati.\n";
    }
    exit;
}

// ---------------------------------------------------------------- pulisci
if ($comando === 'pulisci') {
    $utenti = utentiSimulazione();

    DB::transaction(function () use ($quiz, $utenti) {
        $attemptIds = QuizAttempt::where('quiz_id', $quiz->id)->pluck('id');
        QuizAnswer::whereIn('attempt_id', $attemptIds)->delete();
        QuizAttempt::where('quiz_id', $quiz->id)->delete();
        QuizParticipant::where('quiz_id', $quiz->id)->delete();

        foreach ($quiz->questions as $d) {
            Answer::where('question_id', $d->id)->delete();
        }
        Question::where('quiz_id', $quiz->id)->delete();
        $quiz->delete();

        foreach ($utenti as $u) {
            $u->tokens()->delete();
            $u->delete();
        }
    });

    @unlink(__DIR__.'/.simulazione-token.json');

    echo "Dati di simulazione rimossi ({$utenti->count()} utenti, 1 quiz).\n";
    exit;
}

echo "Comandi: prepara | avvia | gioca | verifica | pulisci\n";
