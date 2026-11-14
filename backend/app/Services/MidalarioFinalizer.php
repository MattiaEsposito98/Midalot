<?php

namespace App\Services;

use App\Models\MidalarioBadge;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAttempt;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;

class MidalarioFinalizer
{
    private const TIMEOUT_PENALTY_RATE = 0.05;

    // 7000 base + 3000 bonus velocita', come negli altri quiz.
    private const MAX_SCORE_PER_QUESTION = 10000;

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
        $xpService = new XpService();

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

            $xpService->award(
                $attempt->user_id,
                'midalario',
                $quiz->id,
                XpService::fromScore('midalario', (int) $totalScore, $questions->count() * self::MAX_SCORE_PER_QUESTION)
            );
        }

        $quiz->update(['midalario_status' => 'finished']);

        $this->assignWinnerBadges($quiz);

        // Un errore nei premi non deve mai rimettere in discussione la
        // chiusura della partita (XP e badge sono gia' assegnati); l'accredito
        // e' idempotente, quindi si puo' ripetere.
        try {
            $this->assignCoinPrizes($quiz);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Monete ai primi 5 della sessione (scala in config/prizes.php).
     * Classifica come quella mostrata: punteggio piu' alto, a parita' tempo
     * totale minore; solo chi ha lo stesso punteggio E lo stesso tempo
     * condivide la posizione.
     */
    public function assignCoinPrizes(Quiz $quiz): array
    {
        $attempts = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('completed', true)
            ->where('score', '>', 0)
            ->orderByDesc('score')
            ->orderBy('total_time')
            ->get()
            ->map(fn ($a) => ['user_id' => (int) $a->user_id, 'key' => [(int) $a->score, (int) $a->total_time]])
            ->all();

        return app(PrizeService::class)->awardRanked(
            'midalario',
            (string) $quiz->id,
            (int) $quiz->id,
            "Midalario: {$quiz->title}",
            PrizeService::rank($attempts)
        );
    }

    /**
     * Assegna il badge "Vincitore del Midalario" a chi ha chiuso la partita
     * col punteggio piu' alto (tutti i pari-merito, se piu' di uno). Gira una
     * sola volta per quiz grazie al lock in finalizeIfNeeded().
     */
    private function assignWinnerBadges(Quiz $quiz): void
    {
        $topScore = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('completed', true)
            ->max('score');

        if ($topScore === null || $topScore <= 0) {
            return;
        }

        $winners = QuizAttempt::where('quiz_id', $quiz->id)
            ->where('completed', true)
            ->where('score', $topScore)
            ->get();

        foreach ($winners as $winner) {
            MidalarioBadge::updateOrCreate(
                ['user_id' => $winner->user_id, 'quiz_id' => $quiz->id],
                [
                    'label' => "Vincitore del Midalario: {$quiz->title}",
                    'total_score' => $topScore,
                ]
            );
        }
    }
}
