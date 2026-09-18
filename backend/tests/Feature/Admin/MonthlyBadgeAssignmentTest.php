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
 * Il cron per il premio "Vincitore del mese" non e' attivabile su questo
 * hosting (vedi notes.md), quindi l'admin puo' farlo scattare a mano dal
 * pannello - ma una sola volta per il mese di competenza, per evitare doppie
 * assegnazioni o confusione su quale mese si sta assegnando.
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

    public function test_lanteprima_mostra_il_vincitore_del_mese_scorso_senza_assegnare_nulla(): void
    {
        $admin = $this->createAdmin();
        $vincitore = $this->createUser(['nickname' => 'vincitore_mese']);

        $mesePassato = now()->subMonthNoOverflow()->startOfMonth()->addDays(3);
        $this->puntiPerUtente($vincitore->id, 5000, $mesePassato);

        $risposta = $this->actingAs($admin)->get(route('admin.period-leaderboard.index'));

        $risposta->assertOk();
        $risposta->assertSee('vincitore_mese');
        $risposta->assertSee('Assegna il premio di');
        $this->assertSame(0, MonthlyBadge::count(), 'La sola visualizzazione non deve scrivere nulla.');
    }

    public function test_lassegnazione_crea_il_badge_e_registra_il_mese_come_fatto(): void
    {
        $admin = $this->createAdmin();
        $vincitore = $this->createUser(['nickname' => 'vincitore_mese']);

        $mesePassato = now()->subMonthNoOverflow()->startOfMonth()->addDays(3);
        $this->puntiPerUtente($vincitore->id, 5000, $mesePassato);

        $risposta = $this->actingAs($admin)
            ->post(route('admin.period-leaderboard.assign-monthly-badge'));

        $risposta->assertRedirect();
        $risposta->assertSessionHas('success');

        $badge = MonthlyBadge::where('user_id', $vincitore->id)->first();
        $this->assertNotNull($badge);
        $this->assertSame(5000, $badge->total_score);

        $run = MonthlyBadgeRun::where('month', $mesePassato->format('Y-m'))->first();
        $this->assertNotNull($run);
        $this->assertSame($admin->id, $run->triggered_by);
    }

    public function test_non_si_puo_assegnare_due_volte_lo_stesso_mese(): void
    {
        $admin = $this->createAdmin();
        $vincitore = $this->createUser(['nickname' => 'vincitore_mese']);

        $mesePassato = now()->subMonthNoOverflow()->startOfMonth()->addDays(3);
        $this->puntiPerUtente($vincitore->id, 5000, $mesePassato);

        $this->actingAs($admin)->post(route('admin.period-leaderboard.assign-monthly-badge'));

        // Secondo tentativo, anche da un admin diverso: deve essere bloccato.
        $altroAdmin = $this->createAdmin();
        $secondoTentativo = $this->actingAs($altroAdmin)
            ->post(route('admin.period-leaderboard.assign-monthly-badge'));

        $secondoTentativo->assertSessionHas('error');
        $this->assertSame(1, MonthlyBadgeRun::count(), 'Non deve crearsi un secondo run per lo stesso mese.');
        $this->assertSame(1, MonthlyBadge::count(), 'Il badge non deve raddoppiare.');
    }

    public function test_un_mese_senza_nessuna_attivita_viene_comunque_segnato_come_fatto(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post(route('admin.period-leaderboard.assign-monthly-badge'));

        $this->assertSame(0, MonthlyBadge::count());
        $this->assertSame(1, MonthlyBadgeRun::count(), 'Anche un mese vuoto va segnato, altrimenti il bottone resta cliccabile a vuoto.');
    }

    public function test_il_comando_da_console_usa_la_stessa_logica_condivisa(): void
    {
        $vincitore = $this->createUser(['nickname' => 'vincitore_cli']);

        $mesePassato = now()->subMonthNoOverflow()->startOfMonth()->addDays(3);
        $this->puntiPerUtente($vincitore->id, 7000, $mesePassato);

        $this->artisan('app:assign-monthly-badges')->assertExitCode(0);

        $badge = MonthlyBadge::where('user_id', $vincitore->id)->first();
        $this->assertNotNull($badge);
        $this->assertSame(7000, $badge->total_score);
        $this->assertSame(1, MonthlyBadgeRun::count());
    }
}
