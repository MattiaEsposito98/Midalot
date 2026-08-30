<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Aggregazione punti per la classifica premi settimanale/mensile:
 * Quiz One Shot (quiz assegnati) + Minigiochi. Il Training NON è incluso
 * (ha una classifica dedicata per categoria/singolo training, isolata da
 * questa). Midalario resta escluso (i suoi quiz hanno `type` diverso da
 * `assigned`). Usata sia dall'API utente sia dalla vista admin, cosi i
 * numeri restano sempre identici tra le due.
 */
class PeriodLeaderboardService
{
    private function completedAttemptsUnion()
    {
        $quizAttempts = DB::table('quiz_attempts')
            ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            ->where('quizzes.type', 'assigned')
            ->where('quiz_attempts.completed', true)
            ->whereNotNull('quiz_attempts.finished_at')
            ->select('quiz_attempts.user_id', 'quiz_attempts.score', 'quiz_attempts.finished_at');

        $minigiocoAttempts = DB::table('minigioco_attempts')
            ->where('completed', true)
            ->whereNotNull('finished_at')
            ->select('user_id', 'score', 'finished_at');

        return $quizAttempts->unionAll($minigiocoAttempts);
    }

    public function finishedDates(): Collection
    {
        return DB::query()
            ->fromSub($this->completedAttemptsUnion(), 'attempts')
            ->pluck('finished_at');
    }

    public function aggregate(Carbon $start, Carbon $end): Collection
    {
        return DB::query()
            ->fromSub($this->completedAttemptsUnion(), 'attempts')
            ->join('users', 'users.id', '=', 'attempts.user_id')
            ->whereBetween('attempts.finished_at', [$start, $end])
            ->groupBy('attempts.user_id', 'users.nickname')
            ->selectRaw('users.nickname as nickname, SUM(attempts.score) as total_score, COUNT(*) as quizzes_completed')
            ->orderByDesc('total_score')
            ->get()
            ->values()
            ->map(function ($row, $index) {
                return [
                    'position' => $index + 1,
                    'nickname' => $row->nickname,
                    'total_score' => (int) $row->total_score,
                    'quizzes_completed' => (int) $row->quizzes_completed,
                ];
            });
    }
}
