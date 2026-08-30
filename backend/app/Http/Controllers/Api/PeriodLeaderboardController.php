<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PeriodLeaderboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PeriodLeaderboardController extends Controller
{
    public function __construct(private PeriodLeaderboardService $leaderboard)
    {
    }

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
            'results' => $this->leaderboard->aggregate($start, $end),
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
            'results' => $this->leaderboard->aggregate($start, $end),
        ]);
    }

    public function availableWeeks()
    {
        $weeks = $this->leaderboard->finishedDates()
            ->map(fn ($date) => Carbon::parse($date)->startOfWeek(Carbon::MONDAY)->toDateString())
            ->unique()
            ->sortDesc()
            ->values();

        return response()->json(['weeks' => $weeks]);
    }

    public function availableMonths()
    {
        $months = $this->leaderboard->finishedDates()
            ->map(fn ($date) => Carbon::parse($date)->format('Y-m'))
            ->unique()
            ->sortDesc()
            ->values();

        return response()->json(['months' => $months]);
    }
}
