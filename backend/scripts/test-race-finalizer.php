<?php

/**
 * Verifica cosa succede quando piu' richieste chiudono la partita nello stesso
 * istante. E' lo scenario reale: allo scadere dell'ultima domanda tutti i
 * giocatori stanno interrogando /status, che chiama MidalarioFinalizer.
 *
 * Il server di sviluppo PHP e' a thread singolo e maschera il problema, quindi
 * qui si lanciano processi separati davvero paralleli.
 *
 *   php scripts/test-race-finalizer.php preparaScenario
 *   php scripts/test-race-finalizer.php finalizza   <- lanciato in parallelo
 *   php scripts/test-race-finalizer.php esito
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use App\Services\MidalarioFinalizer;

if (app()->environment('production')) {
    exit("RIFIUTO: non deve girare in produzione.\n");
}

const TITOLO = '[SIMULAZIONE] Midalario di prova';

$quiz = Quiz::where('type', 'midalario')->where('title', TITOLO)->first();

if (! $quiz) {
    exit("Nessun quiz di simulazione: lancia prima simula-midalario.php prepara/avvia.\n");
}

$comando = $argv[1] ?? '';

// Riporta il quiz in uno stato "appena finito" con tentativi ancora aperti,
// cosi' il finalizzatore ha molte righe da inserire e la corsa e' evidente.
if ($comando === 'preparaScenario') {
    $attemptIds = QuizAttempt::where('quiz_id', $quiz->id)->pluck('id');
    QuizAnswer::whereIn('attempt_id', $attemptIds)->delete();

    QuizAttempt::where('quiz_id', $quiz->id)->update([
        'completed' => false,
        'score' => 0,
        'total_time' => 0,
        'finished_at' => null,
    ]);

    // avviato abbastanza tempo fa da essere gia' scaduto
    $quiz->update([
        'midalario_status' => 'running',
        'midalario_started_at' => now()->subMinutes(10),
    ]);

    echo "Scenario pronto: ".$attemptIds->count()." tentativi aperti, 0 risposte, partita gia' scaduta.\n";
    exit;
}

if ($comando === 'finalizza') {
    $etichetta = $argv[2] ?? '?';

    try {
        (new MidalarioFinalizer())->finalizeIfNeeded($quiz);
        echo "[{$etichetta}] OK\n";
    } catch (\Throwable $e) {
        echo "[{$etichetta}] ECCEZIONE: ".get_class($e).' -> '.substr($e->getMessage(), 0, 160)."\n";
    }
    exit;
}

if ($comando === 'esito') {
    $quiz->refresh();
    echo "stato quiz: {$quiz->midalario_status}\n\n";

    $problemi = [];
    $domande = $quiz->questions()->count();

    foreach (QuizAttempt::where('quiz_id', $quiz->id)->with('user')->get() as $a) {
        $righe = QuizAnswer::where('attempt_id', $a->id)->get();
        $duplicati = $righe->groupBy('question_id')->filter(fn ($g) => $g->count() > 1);

        if ($righe->count() !== $domande) {
            $problemi[] = "{$a->user->nickname}: {$righe->count()} risposte invece di {$domande}";
        }
        if ($duplicati->isNotEmpty()) {
            $problemi[] = "{$a->user->nickname}: DUPLICATI su ".$duplicati->count()." domande";
        }
        if (! $a->completed) {
            $problemi[] = "{$a->user->nickname}: NON completato";
        }
    }

    if ($problemi) {
        echo count($problemi)." PROBLEMI:\n";
        foreach (array_slice($problemi, 0, 20) as $p) {
            echo "  ! {$p}\n";
        }
    } else {
        echo "Tutti i tentativi chiusi correttamente, senza duplicati.\n";
    }
    exit;
}

echo "Comandi: preparaScenario | finalizza <etichetta> | esito\n";
