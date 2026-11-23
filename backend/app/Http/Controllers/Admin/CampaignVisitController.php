<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CampaignVisit;
use App\Models\Prospect;

/** Aperture dei link nei messaggi ai clienti (contatore anonimo: solo campagna e giorno). */
class CampaignVisitController extends Controller
{
    private const DAYS = 14;

    public function index()
    {
        $from = now()->subDays(self::DAYS - 1)->toDateString();

        // Totali per campagna (settore): tutto il periodo, ultimi 7 giorni, ultima apertura.
        $weekFrom = now()->subDays(6)->toDateString();
        $perCampaign = CampaignVisit::query()
            ->select('campaign')
            ->selectRaw('SUM(hits) as total')
            ->selectRaw('SUM(CASE WHEN day >= ? THEN hits ELSE 0 END) as week', [$weekFrom])
            ->selectRaw('MAX(day) as last_day')
            ->groupBy('campaign')
            ->orderByDesc('total')
            ->get();

        // Aperture per giorno negli ultimi 14 giorni (anche i giorni a zero).
        $byDay = CampaignVisit::query()
            ->where('day', '>=', $from)
            ->groupBy('day')
            ->selectRaw('day, SUM(hits) as total')
            ->pluck('total', 'day')
            ->mapWithKeys(fn ($total, $day) => [substr((string) $day, 0, 10) => (int) $total]);

        $days = collect(range(self::DAYS - 1, 0))->map(function (int $ago) use ($byDay) {
            $date = now()->subDays($ago);

            return ['date' => $date, 'hits' => $byDay[$date->toDateString()] ?? 0];
        });

        return view('admin.prospects.campaign-visits', [
            'perCampaign' => $perCampaign,
            'days' => $days,
            'grandTotal' => (int) CampaignVisit::sum('hits'),
            'weekTotal' => (int) CampaignVisit::where('day', '>=', $weekFrom)->sum('hits'),
            'contactedTotal' => Prospect::whereNotNull('contacted_at')->count(),
            'daysCount' => self::DAYS,
        ]);
    }
}
