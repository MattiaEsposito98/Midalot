<?php

namespace App\Services;

use App\Models\MonthlyBadge;
use App\Models\MonthlyBadgeRun;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Logica del premio "Vincitore del mese", condivisa dal comando da console
 * (per quando/se il cron sul server verrà attivato) e dal bottone manuale nel
 * pannello admin (finché il cron non c'è): un solo punto che decide chi vince
 * e un solo registro (`monthly_badge_runs`) che impedisce di assegnare due
 * volte lo stesso mese, a prescindere da chi/come lo scatena.
 */
class MonthlyBadgeAssigner
{
    public function __construct(private PeriodLeaderboardService $leaderboard)
    {
    }

    /**
     * Il mese di calendario che verrebbe assegnato AVENDO PREMUTO/ESEGUITO
     * ORA: sempre il mese scorso rispetto ad oggi.
     */
    public function targetMonth(): Carbon
    {
        return now()->subMonthNoOverflow()->startOfMonth();
    }

    public function alreadyRun(Carbon $month): ?MonthlyBadgeRun
    {
        return MonthlyBadgeRun::where('month', $month->format('Y-m'))->first();
    }

    /**
     * Calcola chi vincerebbe per quel mese SENZA scrivere nulla: usata per
     * l'anteprima mostrata prima di confermare.
     *
     * @return array{topScore: int, winners: array}
     */
    public function preview(Carbon $month): array
    {
        $start = $month->copy()->startOfMonth()->startOfDay();
        $end = $month->copy()->endOfMonth()->endOfDay();

        $results = $this->leaderboard->aggregate($start, $end);

        if ($results->isEmpty()) {
            return ['topScore' => 0, 'winners' => []];
        }

        $topScore = (int) $results->max('total_score');

        if ($topScore <= 0) {
            return ['topScore' => 0, 'winners' => []];
        }

        return [
            'topScore' => $topScore,
            'winners' => $results->where('total_score', $topScore)->values()->all(),
        ];
    }

    /**
     * Assegna davvero i badge per quel mese e registra l'esecuzione (anche se
     * non c'era nessun vincitore), cosi' quel mese non potra' piu' essere
     * riassegnato. Non controlla da solo se e' gia' stato fatto: il chiamante
     * decide (il pannello admin blocca prima di arrivare qui, il comando
     * da console accetta `--month` per forzare un mese specifico).
     *
     * @return array{topScore: int, winners: array}
     */
    public function assign(Carbon $month, ?int $triggeredBy = null): array
    {
        $summary = $this->preview($month);
        $monthKey = $month->format('Y-m');
        $monthLabel = Str::ucfirst($month->locale('it')->translatedFormat('F Y'));

        foreach ($summary['winners'] as $winner) {
            MonthlyBadge::updateOrCreate(
                ['user_id' => $winner['user_id'], 'month' => $monthKey],
                [
                    'label' => "Vincitore di {$monthLabel}",
                    'total_score' => $winner['total_score'],
                ]
            );
        }

        MonthlyBadgeRun::updateOrCreate(
            ['month' => $monthKey],
            ['triggered_by' => $triggeredBy, 'top_score' => $summary['topScore']]
        );

        return $summary;
    }
}
