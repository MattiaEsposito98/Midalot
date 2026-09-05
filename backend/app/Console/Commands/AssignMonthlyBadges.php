<?php

namespace App\Console\Commands;

use App\Models\MonthlyBadge;
use App\Services\PeriodLeaderboardService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class AssignMonthlyBadges extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:assign-monthly-badges {--month= : Mese da assegnare, formato YYYY-MM (default: il mese scorso)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assegna il badge "Vincitore del mese" ai primi classificati della classifica premi mensile appena conclusa.';

    public function handle(PeriodLeaderboardService $leaderboard): int
    {
        $month = $this->option('month')
            ? Carbon::createFromFormat('Y-m', $this->option('month'))->startOfMonth()
            : now()->subMonthNoOverflow()->startOfMonth();

        $monthKey = $month->format('Y-m');
        $start = $month->copy()->startOfMonth()->startOfDay();
        $end = $month->copy()->endOfMonth()->endOfDay();

        $results = $leaderboard->aggregate($start, $end);

        if ($results->isEmpty()) {
            $this->info("Nessun partecipante per {$monthKey}, nessun badge assegnato.");

            return self::SUCCESS;
        }

        $topScore = $results->max('total_score');

        if ($topScore <= 0) {
            $this->info("Punteggio massimo pari a 0 per {$monthKey}, nessun badge assegnato.");

            return self::SUCCESS;
        }

        $winners = $results->where('total_score', $topScore);

        $monthLabel = Str::ucfirst($month->locale('it')->translatedFormat('F Y'));

        foreach ($winners as $winner) {
            MonthlyBadge::updateOrCreate(
                ['user_id' => $winner['user_id'], 'month' => $monthKey],
                [
                    'label' => "Vincitore di {$monthLabel}",
                    'total_score' => $winner['total_score'],
                ]
            );

            $this->info("Badge assegnato a {$winner['nickname']} (user_id {$winner['user_id']}) per {$monthKey}.");
        }

        return self::SUCCESS;
    }
}
