<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MonthlyBadgeAssigner;
use App\Services\PeriodLeaderboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PeriodLeaderboardController extends Controller
{
    public function __construct(
        private PeriodLeaderboardService $leaderboard,
        private MonthlyBadgeAssigner $badgeAssigner,
    ) {
    }

    public function index(Request $request)
    {
        $tab = $request->query('tab') === 'monthly' ? 'monthly' : 'weekly';

        $weeks = $this->leaderboard->finishedDates()
            ->map(fn ($date) => Carbon::parse($date)->startOfWeek(Carbon::MONDAY)->toDateString())
            ->unique()
            ->sortDesc()
            ->values();

        $months = $this->leaderboard->finishedDates()
            ->map(fn ($date) => Carbon::parse($date)->format('Y-m'))
            ->unique()
            ->sortDesc()
            ->values();

        $currentWeek = Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $currentMonth = Carbon::now()->format('Y-m');

        $weeks = $weeks->push($currentWeek)->unique()->sortDesc()->values();
        $months = $months->push($currentMonth)->unique()->sortDesc()->values();

        $selectedWeek = $request->query('week', $currentWeek);
        $selectedMonth = $request->query('month', $currentMonth);

        if ($tab === 'monthly') {
            $reference = Carbon::createFromFormat('Y-m', $selectedMonth);
            $start = $reference->copy()->startOfMonth()->startOfDay();
            $end = $reference->copy()->endOfMonth()->endOfDay();
        } else {
            $reference = Carbon::parse($selectedWeek);
            $start = $reference->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
            $end = $reference->copy()->startOfWeek(Carbon::MONDAY)->endOfWeek(Carbon::SUNDAY)->endOfDay();
        }

        $results = $this->leaderboard->aggregate($start, $end);

        $badgeTargetMonth = $this->badgeAssigner->targetMonth();
        $badgeRun = $this->badgeAssigner->alreadyRun($badgeTargetMonth);
        $badgePreview = $badgeRun ? null : $this->badgeAssigner->preview($badgeTargetMonth);

        return view('admin.period-leaderboard.index', compact(
            'tab',
            'weeks',
            'months',
            'selectedWeek',
            'selectedMonth',
            'results',
            'start',
            'end',
            'badgeTargetMonth',
            'badgeRun',
            'badgePreview'
        ));
    }

    public function assignMonthlyBadge(Request $request)
    {
        $month = $this->badgeAssigner->targetMonth();

        if ($this->badgeAssigner->alreadyRun($month)) {
            return back()->with('error', 'Il premio di questo mese è già stato assegnato: non è possibile riattivarlo prima del mese prossimo.');
        }

        $summary = $this->badgeAssigner->assign($month, $request->user()->id);
        $monthLabel = Str::ucfirst($month->locale('it')->translatedFormat('F Y'));

        if (empty($summary['winners'])) {
            return back()->with('success', "Nessuna attività registrata per {$monthLabel}: nessun badge assegnato.");
        }

        $nicknames = collect($summary['winners'])->pluck('nickname')->implode(', ');

        return back()->with('success', "Badge \"Vincitore di {$monthLabel}\" assegnato a: {$nicknames}.");
    }
}
