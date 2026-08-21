<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\QuizAttempt;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PeriodLeaderboardController extends Controller
{
    public function weekly(Request $request)
    {
        $reference = $request->query('week')
            ? Carbon::parse($request->query('week'))
            : Carbon::now();

        $start = $reference->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $end = $reference->copy()->startOfWeek(Carbon::MONDAY)->endOfWeek(Carbon::SUNDAY)->endOfDay();

        return response()->json([
            'period' => 'weekly',
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'results' => $this->aggregate($start, $end),
        ]);
    }

    public function monthly(Request $request)
    {
        $reference = $request->query('month')
            ? Carbon::createFromFormat('Y-m', $request->query('month'))
            : Carbon::now();

        $start = $reference->copy()->startOfMonth()->startOfDay();
        $end = $reference->copy()->endOfMonth()->endOfDay();

        return response()->json([
            'period' => 'monthly',
            'month' => $start->format('Y-m'),
            'results' => $this->aggregate($start, $end),
        ]);
    }

    public function availableWeeks()
    {
        $weeks = $this->finishedDates()
            ->map(fn ($date) => Carbon::parse($date)->startOfWeek(Carbon::MONDAY)->toDateString())
            ->unique()
            ->sortDesc()
            ->values();

        return response()->json(['weeks' => $weeks]);
    }

    public function availableMonths()
    {
        $months = $this->finishedDates()
            ->map(fn ($date) => Carbon::parse($date)->format('Y-m'))
            ->unique()
            ->sortDesc()
            ->values();

        return response()->json(['months' => $months]);
    }

    private function finishedDates()
    {
        return QuizAttempt::query()
            ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            ->where('quizzes.type', 'assigned')
            ->where('quiz_attempts.completed', true)
            ->whereNotNull('quiz_attempts.finished_at')
            ->pluck('quiz_attempts.finished_at');
    }

    private function aggregate(Carbon $start, Carbon $end)
    {
        return QuizAttempt::query()
            ->join('quizzes', 'quizzes.id', '=', 'quiz_attempts.quiz_id')
            ->join('users', 'users.id', '=', 'quiz_attempts.user_id')
            ->where('quizzes.type', 'assigned')
            ->where('quiz_attempts.completed', true)
            ->whereBetween('quiz_attempts.finished_at', [$start, $end])
            ->groupBy('quiz_attempts.user_id', 'users.nickname')
            ->selectRaw('users.nickname as nickname, SUM(quiz_attempts.score) as total_score, COUNT(*) as quizzes_completed')
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
