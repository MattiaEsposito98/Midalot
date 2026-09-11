<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;

class MidalarioFinalizer
{
    private const TIMEOUT_PENALTY_RATE = 0.05;

    public function finalizeIfNeeded(Quiz $quiz): void
    {
        if ($quiz->midalario_status !== 'running') {
            return;
        }

        $timeline = new MidalarioTimeline($quiz);

        if (! $timeline->isFinished()) {
            return;
        }

        // Allo scadere dell'ultima domanda TUTTI i giocatori stanno chiamando
        // /status nello stesso momento, e ognuna di quelle richieste arriva
        // qui. Senza lock ciascuna prova a inserire le righe di timeout: la
        // prima vince e le altre esplodono sull'indice univoco di
        // quiz_answers, restituendo un 500 proprio quando l'utente aspetta il
        // risultato. Chiude la partita una richiesta sola, le altre escono
        // subito e vedranno lo stato aggiornato al giro successivo.
        $lock = Cache::lock("midalario:finalize:{$quiz->id}", 30);

        if (! $lock->get()) {
            return;
        }

        try {
            $this->finalize($quiz, $timeline);
        } finally {
            $lock->release();
        }
    }

    private function finalize(Quiz $quiz, MidalarioTimeline $timeline): void
    {
        $questions = $timeline->questions();

        $attempts = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('completed', false)
            ->get();

        foreach ($attempts as $attempt) {
            foreach ($questions as $question) {
                $exists = QuizAnswer::where('attempt_id', $attempt->id)
                    ->where('question_id', $question->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $currentScore = max(0, (int) QuizAnswer::where('attempt_id', $attempt->id)->sum('score'));

                // Rete di sicurezza se il lock non fosse disponibile: una riga
                // gia' presente (risposta dell'utente arrivata un istante fa,
                // o altra finalizzazione in corso) non deve far fallire tutto.
                try {
                    QuizAnswer::create([
                        'attempt_id' => $attempt->id,
                        'question_id' => $question->id,
                        'answer_id' => null,
                        'time_taken' => (int) $question->time_limit_seconds * 1000,
                        'is_correct' => false,
                        'is_timeout' => true,
                        'is_wrong' => false,
                        'score' => -min($currentScore, (int) round($currentScore * self::TIMEOUT_PENALTY_RATE)),
                    ]);
                } catch (UniqueConstraintViolationException) {
                    continue;
                }
            }

            $totalScore = QuizAnswer::where('attempt_id', $attempt->id)->sum('score');
            $totalTime = QuizAnswer::where('attempt_id', $attempt->id)->sum('time_taken');

            $attempt->update([
                'score' => $totalScore,
                'total_time' => $totalTime,
                'completed' => true,
                'finished_at' => now(),
            ]);
        }

        $quiz->update(['midalario_status' => 'finished']);
    }
}
