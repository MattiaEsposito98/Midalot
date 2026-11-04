<?php

namespace App\Services;

use App\Models\Challenge;
use App\Models\Question;
use App\Models\XpEvent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Logica delle sfide 1 contro 1: pesca delle domande, punteggio, chiusura
 * della sfida e assegnazione degli XP.
 */
class ChallengeService
{
    // Stessi valori di Quiz e Training: 7000 base + fino a 3000 di velocita'.
    public const CORRECT_ANSWER_BASE_SCORE = 7000;

    public const MAX_SPEED_BONUS = 3000;

    private const WRONG_ANSWER_PENALTY_RATE = 0.10;

    private const TIMEOUT_PENALTY_RATE = 0.05;

    public const MAX_SCORE_PER_QUESTION = self::CORRECT_ANSWER_BASE_SCORE + self::MAX_SPEED_BONUS;

    /**
     * Domande dei Training attivi (nelle categorie richieste) che hanno una
     * risposta corretta. $categoryIds null = tutte le categorie.
     */
    public function poolQuery(?array $categoryIds)
    {
        return Question::query()
            ->whereHas('answers', fn ($q) => $q->where('is_correct', true))
            ->whereHas('quiz', function ($quiz) use ($categoryIds) {
                $quiz->where('type', 'training')
                    ->where('is_active', true)
                    ->whereHas('trainingCategory', fn ($c) => $c->where('is_active', true));

                if ($categoryIds) {
                    $quiz->whereIn('training_category_id', $categoryIds);
                }
            });
    }

    /**
     * Sceglie le domande della sfida dando priorita' a quelle che nessuno dei
     * due giocatori ha mai incontrato in una sfida, cosi' col tempo escono
     * quasi tutte. Ritorna null se il pool ha meno domande del necessario.
     *
     * @return int[]|null
     */
    public function pickQuestions(?array $categoryIds, int $userA, int $userB): ?array
    {
        $poolIds = $this->poolQuery($categoryIds)->pluck('questions.id')->all();

        if (count($poolIds) < Challenge::QUESTIONS_COUNT) {
            return null;
        }

        $usage = DB::table('challenge_questions as cq')
            ->join('challenges as c', 'c.id', '=', 'cq.challenge_id')
            ->where(function ($q) use ($userA, $userB) {
                $q->whereIn('c.challenger_id', [$userA, $userB])
                    ->orWhereIn('c.opponent_id', [$userA, $userB]);
            })
            ->whereIn('cq.question_id', $poolIds)
            ->groupBy('cq.question_id')
            ->selectRaw('cq.question_id, COUNT(*) as uses')
            ->pluck('uses', 'question_id');

        shuffle($poolIds);

        // sort() di PHP 8 e' stabile: a parita' di utilizzi resta l'ordine casuale.
        usort($poolIds, fn ($a, $b) => ($usage[$a] ?? 0) <=> ($usage[$b] ?? 0));

        $picked = array_slice($poolIds, 0, Challenge::QUESTIONS_COUNT);
        shuffle($picked);

        return $picked;
    }

    /**
     * Porta a "expired" le sfide aperte scadute.
     */
    public function refreshStatus(Challenge $challenge): Challenge
    {
        if ($challenge->status === 'open' && $challenge->expires_at->isPast()) {
            $challenge->update(['status' => 'expired']);
        }

        return $challenge;
    }

    /**
     * Punteggio di una singola risposta (stessa formula di Quiz e Training).
     * Con $answerId null la risposta vale come tempo scaduto.
     *
     * @return array{answer_id: ?int, time_taken: int, is_correct: bool, is_timeout: bool, is_wrong: bool, score: int}
     */
    public function scoreAnswer(Question $question, ?int $answerId, int $clientTimeMs, ?Carbon $lastEventAt, int $currentScore): array
    {
        $maxTimeMs = (int) $question->time_limit_seconds * 1000;
        $timeTaken = AnswerTimer::resolve($clientTimeMs, $lastEventAt, $maxTimeMs);
        $currentScore = max(0, $currentScore);

        if ($answerId === null) {
            return [
                'answer_id' => null,
                'time_taken' => $timeTaken,
                'is_correct' => false,
                'is_timeout' => true,
                'is_wrong' => false,
                'score' => -$this->penalty($currentScore, self::TIMEOUT_PENALTY_RATE),
            ];
        }

        $isCorrect = (bool) $question->answers()->whereKey($answerId)->value('is_correct');

        if ($isCorrect) {
            $speedBonus = (int) round((($maxTimeMs - $timeTaken) / max(1, $maxTimeMs)) * self::MAX_SPEED_BONUS);
            $score = self::CORRECT_ANSWER_BASE_SCORE + $speedBonus;
        } else {
            $score = -$this->penalty($currentScore, self::WRONG_ANSWER_PENALTY_RATE);
        }

        return [
            'answer_id' => $answerId,
            'time_taken' => $timeTaken,
            'is_correct' => $isCorrect,
            'is_timeout' => false,
            'is_wrong' => ! $isCorrect,
            'score' => $score,
        ];
    }

    private function penalty(int $currentScore, float $rate): int
    {
        return min($currentScore, (int) round($currentScore * $rate));
    }

    /**
     * Se entrambi hanno finito: chiude la sfida, decide il vincitore e
     * assegna gli XP. Idempotente (lock sulla riga + controllo dello stato).
     */
    public function completeIfReady(Challenge $challenge): void
    {
        DB::transaction(function () use ($challenge) {
            $locked = Challenge::whereKey($challenge->id)->lockForUpdate()->first();

            if ($locked->status !== 'open') {
                return;
            }

            $attempts = $locked->attempts()->where('completed', true)->get()->keyBy('user_id');

            if ($attempts->count() < 2) {
                return;
            }

            $a = $attempts[$locked->challenger_id];
            $b = $attempts[$locked->opponent_id];

            $winnerId = null;
            if ($a->score !== $b->score) {
                $winnerId = $a->score > $b->score ? $a->user_id : $b->user_id;
            } elseif ($a->total_time !== $b->total_time) {
                $winnerId = $a->total_time < $b->total_time ? $a->user_id : $b->user_id;
            }

            $locked->update([
                'status' => 'completed',
                'completed_at' => now(),
                'winner_id' => $winnerId,
            ]);

            foreach ([$a, $b] as $attempt) {
                $this->awardXp($locked, $attempt->user_id, (int) $attempt->score, $winnerId);
            }
        });

        $challenge->refresh();
    }

    private function awardXp(Challenge $challenge, int $userId, int $score, ?int $winnerId): void
    {
        $dailyCount = XpEvent::where('user_id', $userId)
            ->where('source', 'challenge')
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        if ($dailyCount >= (int) config('xp.challenge_daily_cap')) {
            return;
        }

        $pairToday = Challenge::where('status', 'completed')
            ->where('id', '!=', $challenge->id)
            ->where('completed_at', '>=', now()->startOfDay())
            ->where(function ($q) use ($challenge) {
                $q->where(fn ($x) => $x->where('challenger_id', $challenge->challenger_id)->where('opponent_id', $challenge->opponent_id))
                    ->orWhere(fn ($x) => $x->where('challenger_id', $challenge->opponent_id)->where('opponent_id', $challenge->challenger_id));
            })
            ->count();

        if ($pairToday >= (int) config('xp.challenge_pair_daily_cap')) {
            return;
        }

        $xp = XpService::fromScore('challenge', $score, Challenge::QUESTIONS_COUNT * self::MAX_SCORE_PER_QUESTION);

        if ($winnerId === null) {
            $xp += (int) config('xp.challenge_draw_bonus');
        } elseif ($winnerId === $userId) {
            $xp += (int) config('xp.challenge_win_bonus');
        }

        (new XpService())->award($userId, 'challenge', $challenge->id, $xp);
    }
}
