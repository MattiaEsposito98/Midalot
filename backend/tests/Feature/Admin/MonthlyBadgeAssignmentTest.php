<?php

namespace Tests\Feature\Admin;

use App\Models\MonthlyBadge;
use App\Models\MonthlyBadgeRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesQuizData;
use Tests\TestCase;

/**
 * Il premio "Vincitore del mese" viene assegnato solo dal cron (comando
 * app:assign-monthly-badges il 1° del mese); il pannello admin mostra solo
 * l'anteprima e quando scattera' l'assegnazione.
 */
class MonthlyBadgeAssignmentTest extends TestCase
{
    use CreatesQuizData;
    use RefreshDatabase;

    /**
     * Inserimento via query builder: DailyLoginBonus non ha created_at nei
     * fillable (giusto cosi' nel codice reale, e' sempre "ora"), quindi qui
     * serve bypassare Eloquent per simulare punti guadagnati IL MESE SCORSO.
     */
    private function puntiPerUtente(int $userId, int $score, Carbon $quando): void
    {
        DB::table('daily_login_bonuses')->insert([
            'user_id' => $userId,
            'bonus_date' => $quando->toDateString(),
            'score' => $score,
            'created_at' => $quando,
            'updated_at' => $quando,
        ]);
    }

    public function test_lanteprima_mostra_il_vincitore_e_la_data_dellassegnazione_automatica(): void
    {
        $admin = $this->createAdmin();
        $vincitore = $this->createUser(['nickname' => 'vincitore_mese']);

        $mesePassato = now()->subMonthNoOverflow()->startOfMonth()->addDays(3);
        $this->puntiPerUtente($vincitore->id, 5000, $mesePassato);

        $risposta = $this->actingAs($admin)->get(route('admin.period-leaderboard.index'));

        $risposta->assertOk();
        $risposta->assertSee('vincitore_mese');
        $risposta->assertSee('verrà assegnato');
        $risposta->assertDontSee('Assegna il premio di');
        $this->assertSame(0, MonthlyBadge::count(), 'La sola visualizzazione non deve scrivere nulla.');
    }

    public function test_il_comando_crea_il_badge_e_registra_il_mese_come_fatto(): void
    {
        $vincitore = $this->createUser(['nickname' => 'vincitore_cli']);

        $mesePassato = now()->subMonthNoOverflow()->startOfMonth()->addDays(3);
        $this->puntiPerUtente($vincitore->id, 7000, $mesePassato);

        $this->artisan('app:assign-monthly-badges')->assertExitCode(0);

        $badge = MonthlyBadge::where('user_id', $vincitore->id)->first();
        $this->assertNotNull($badge);
        $this->assertSame(7000, $badge->total_score);

        $run = MonthlyBadgeRun::where('month', $mesePassato->format('Y-m'))->first();
        $this->assertNotNull($run);
        $this->assertNull($run->triggered_by);
    }

    public function test_rieseguire_il_comando_non_duplica_il_badge(): void
    {
        $vincitore = $this->createUser(['nickname' => 'vincitore_mese']);

        $mesePassato = now()->subMonthNoOverflow()->startOfMonth()->addDays(3);
        $this->puntiPerUtente($vincitore->id, 5000, $mesePassato);

        $this->artisan('app:assign-monthly-badges')->assertExitCode(0);
        $this->artisan('app:assign-monthly-badges')->assertExitCode(0);

        $this->assertSame(1, MonthlyBadgeRun::count(), 'Non deve crearsi un secondo run per lo stesso mese.');
        $this->assertSame(1, MonthlyBadge::count(), 'Il badge non deve raddoppiare.');
    }

    public function test_un_mese_senza_nessuna_attivita_viene_comunque_segnato_come_fatto(): void
    {
        $this->artisan('app:assign-monthly-badges')->assertExitCode(0);

        $this->assertSame(0, MonthlyBadge::count());
        $this->assertSame(1, MonthlyBadgeRun::count());
    }
}
