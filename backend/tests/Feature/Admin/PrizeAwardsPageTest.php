<?php

namespace Tests\Feature\Admin;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\MidalarioFinalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Pannello admin "Premi in monete": registro di cosa e' stato assegnato,
 * anteprima del prossimo mese e sessioni Midalario non premiate.
 */
class PrizeAwardsPageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_admin' => true]);
    }

    private function midalario(string $titolo, string $stato, array $punteggi = []): Quiz
    {
        $quiz = Quiz::create([
            'title' => $titolo,
            'type' => 'midalario',
            'created_by' => $this->admin()->id,
            'is_active' => true,
            'leaderboard_visible' => true,
            'midalario_status' => $stato,
            'midalario_started_at' => now()->subMinutes(10),
        ]);

        foreach ($punteggi as $nick => $score) {
            QuizAttempt::create([
                'quiz_id' => $quiz->id,
                'user_id' => User::factory()->create(['nickname' => $nick, 'is_admin' => false])->id,
                'started_at' => now()->subMinutes(10),
                'completed' => true,
                'finished_at' => now()->subMinutes(9),
                'score' => $score,
                'total_time' => 1000,
                'question_order' => [],
            ]);
        }

        return $quiz;
    }

    public function test_un_utente_normale_non_entra(): void
    {
        $utente = User::factory()->create(['is_admin' => false]);

        $this->actingAs($utente)->get(route('admin.prize-awards.index'))->assertForbidden();
    }

    public function test_pagina_vuota_senza_premi(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.prize-awards.index'))
            ->assertOk()
            ->assertSee('Nessun premio assegnato finora.');
    }

    public function test_mostra_i_premi_del_midalario_con_utente_posizione_e_monete(): void
    {
        $quiz = $this->midalario('Midalario Halloween', 'running', ['campione' => 9000, 'secondo' => 5000]);
        (new MidalarioFinalizer())->finalizeIfNeeded($quiz);

        $pagina = $this->actingAs($this->admin())->get(route('admin.prize-awards.index'));

        $pagina->assertOk()
            ->assertSee('Midalario: Midalario Halloween')
            ->assertSee('campione')
            ->assertSee('secondo')
            ->assertSee('500')
            ->assertSee('300')
            ->assertSee('Non ancora visto')
            ->assertDontSee('Midalario conclusi senza premi in monete');
    }

    public function test_filtro_per_tipo(): void
    {
        $quiz = $this->midalario('Midalario Filtro', 'running', ['solo_midalario' => 9000]);
        (new MidalarioFinalizer())->finalizeIfNeeded($quiz);

        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.prize-awards.index', ['kind' => 'monthly']))
            ->assertOk()->assertDontSee('solo_midalario');
        $this->actingAs($admin)->get(route('admin.prize-awards.index', ['kind' => 'midalario']))
            ->assertOk()->assertSee('solo_midalario');
    }

    public function test_segnala_i_midalario_chiusi_senza_premi(): void
    {
        $this->midalario('Midalario vecchio', 'finished', ['vecchio_utente' => 9000]);

        $this->actingAs($this->admin())->get(route('admin.prize-awards.index'))
            ->assertOk()
            ->assertSee('Midalario conclusi senza premi in monete')
            ->assertSee('Midalario vecchio');
    }

    public function test_anteprima_del_mese_e_registro_dopo_lassegnazione(): void
    {
        $utente = User::factory()->create(['nickname' => 're_del_mese', 'is_admin' => false]);
        $quando = now()->subMonthNoOverflow()->startOfMonth()->addDays(3);
        DB::table('daily_login_bonuses')->insert([
            'user_id' => $utente->id, 'bonus_date' => $quando->toDateString(), 'score' => 9000,
            'created_at' => $quando, 'updated_at' => $quando,
        ]);

        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.prize-awards.index'))
            ->assertOk()->assertSee('re_del_mese')->assertSee('1.000')->assertSee('anteprima');

        $this->artisan('app:assign-monthly-badges')->assertExitCode(0);

        $this->actingAs($admin)->get(route('admin.prize-awards.index', ['kind' => 'monthly']))
            ->assertOk()->assertSee('re_del_mese')->assertSee('già stati assegnati')->assertSee('Classifica di');
    }
}
