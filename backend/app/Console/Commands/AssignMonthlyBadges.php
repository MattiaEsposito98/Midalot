<?php

namespace App\Console\Commands;

use App\Services\MonthlyBadgeAssigner;
use Carbon\Carbon;
use Illuminate\Console\Command;

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
    protected $description = 'Assegna il badge "Vincitore del mese" al primo classificato della classifica premi mensile appena conclusa e le monete ai primi 5 (config/prizes.php).';

    public function handle(MonthlyBadgeAssigner $assigner): int
    {
        $month = $this->option('month')
            ? Carbon::createFromFormat('Y-m', $this->option('month'))->startOfMonth()
            : $assigner->targetMonth();

        $monthKey = $month->format('Y-m');

        $summary = $assigner->assign($month);

        foreach ($summary['prizes'] as $prize) {
            $this->info("Premio di {$prize['coins']} monete a user_id {$prize['user_id']} ({$prize['position']}° posto) per {$monthKey}.");
        }

        if (empty($summary['winners'])) {
            $this->info("Nessun vincitore per {$monthKey} (nessuna attivita' o punteggio a 0), nessun badge assegnato.");

            return self::SUCCESS;
        }

        foreach ($summary['winners'] as $winner) {
            $this->info("Badge assegnato a {$winner['nickname']} (user_id {$winner['user_id']}) per {$monthKey}.");
        }

        return self::SUCCESS;
    }
}
