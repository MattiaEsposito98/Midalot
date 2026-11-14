<?php

namespace App\Services;

use App\Models\MonthlyBadge;
use App\Models\MonthlyBadgeRun;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Logica del premio "Vincitore del mese", eseguita dal comando da console
 * schedulato via cron (bootstrap/app.php, il 1° di ogni mese): un solo punto
 * che decide chi vince e un solo registro (`monthly_badge_runs`) che impedisce
 * di assegnare due volte lo stesso mese.
 */
class MonthlyBadgeAssigner
{
    public function __construct(
        private PeriodLeaderboardService $leaderboard,
        private PrizeService $prizes,
    ) {
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
     * Oltre al badge, accredita le monete ai primi della classifica (scala in
     * config/prizes.php, una sola volta per utente e mese grazie a CoinEvent).
     *
     * @return array{topScore: int, winners: array, prizes: array}
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

        $summary['prizes'] = $this->assignCoinPrizes($month, $monthKey, $monthLabel);

        return $summary;
    }

    /**
     * Monete ai primi classificati del mese (pari merito = stessa posizione).
     *
     * @return array<int, array{user_id:int, position:int, coins:int}>
     */
    private function assignCoinPrizes(Carbon $month, string $monthKey, string $monthLabel): array
    {
        $ranked = array_map(
            fn ($row) => ['user_id' => $row['user_id'], 'position' => $row['position']],
            $this->rankedMonth($month)
        );

        return $this->prizes->awardRanked(
            'monthly',
            $monthKey,
            (int) $month->format('Ym'),
            "Classifica di {$monthLabel}",
            $ranked
        );
    }

    /**
     * Anteprima (senza scrivere nulla) dei premi in monete del mese: solo chi
     * ha un premio previsto dalla scala.
     *
     * @return array<int, array{user_id:int, nickname:string, score:int, position:int, coins:int}>
     */
    public function prizePreview(Carbon $month): array
    {
        return array_values(array_filter(
            array_map(fn ($row) => $row + ['coins' => PrizeService::coinsFor('monthly', $row['position'])], $this->rankedMonth($month)),
            fn ($row) => $row['coins'] > 0
        ));
    }

    /**
     * Classifica del mese con posizioni (pari merito = stessa posizione).
     *
     * @return array<int, array{user_id:int, nickname:string, score:int, position:int}>
     */
    private function rankedMonth(Carbon $month): array
    {
        $rows = $this->leaderboard
            ->aggregate($month->copy()->startOfMonth()->startOfDay(), $month->copy()->endOfMonth()->endOfDay())
            ->filter(fn ($row) => $row['total_score'] > 0)
            ->values();

        $ranked = PrizeService::rank(
            $rows->map(fn ($row) => ['user_id' => $row['user_id'], 'key' => [$row['total_score']]])->all()
        );

        return $rows->map(fn ($row, $i) => [
            'user_id' => $row['user_id'],
            'nickname' => $row['nickname'],
            'score' => $row['total_score'],
            'position' => $ranked[$i]['position'],
        ])->all();
    }
}
