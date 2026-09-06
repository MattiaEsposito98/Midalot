<?php

namespace App\Services;

use App\Models\MonthlyBadge;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Aggregazione punti per la classifica premi settimanale/mensile:
 * Quiz One Shot (quiz assegnati) + Minigiochi + bonus giornaliero di login.
 * Il Training NON è incluso (ha una classifica dedicata per categoria/singolo
 * training, isolata da questa). Midalario resta escluso (i suoi quiz hanno
 * `type` diverso da `assigned`). Usata sia dall'API utente sia dalla vista
 * admin, cosi i numeri restano sempre identici tra le due.
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
            ->selectRaw("quiz_attempts.user_id, quiz_attempts.score, quiz_attempts.finished_at, 'quiz' as source");

        $minigiocoAttempts = DB::table('minigioco_attempts')
            ->where('completed', true)
            ->whereNotNull('finished_at')
            ->selectRaw("user_id, score, finished_at, 'minigioco' as source");

        $loginBonuses = DB::table('daily_login_bonuses')
            ->selectRaw("user_id, score, created_at as finished_at, 'login' as source");

        return $quizAttempts->unionAll($minigiocoAttempts)->unionAll($loginBonuses);
    }

    public function finishedDates(): Collection
    {
        return DB::query()
            ->fromSub($this->completedAttemptsUnion(), 'attempts')
            ->pluck('finished_at');
    }

    public function aggregate(Carbon $start, Carbon $end): Collection
    {
        $rows = DB::query()
            ->fromSub($this->completedAttemptsUnion(), 'attempts')
            ->join('users', 'users.id', '=', 'attempts.user_id')
            ->whereBetween('attempts.finished_at', [$start, $end])
            ->groupBy('attempts.user_id', 'users.nickname')
            ->selectRaw("attempts.user_id as user_id, users.nickname as nickname, SUM(attempts.score) as total_score, SUM(CASE WHEN attempts.source != 'login' THEN 1 ELSE 0 END) as quizzes_completed")
            ->orderByDesc('total_score')
            ->limit(50)
            ->get()
            ->values();

        $badgesByUserId = MonthlyBadge::whereIn('user_id', $rows->pluck('user_id'))
            ->orderByDesc('month')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($badges) => $badges->first()->label);

        return $rows->map(function ($row, $index) use ($badgesByUserId) {
            return [
                'position' => $index + 1,
                'user_id' => (int) $row->user_id,
                'nickname' => $row->nickname,
                'total_score' => (int) $row->total_score,
                'quizzes_completed' => (int) $row->quizzes_completed,
                'badge' => $badgesByUserId->get((int) $row->user_id),
            ];
        });
    }
}
