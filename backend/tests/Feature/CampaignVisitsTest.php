<?php

namespace Tests\Feature;

use App\Models\CampaignVisit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\Support\CreatesQuizData;
use Tests\TestCase;

/** Contatore anonimo delle aperture dei link di campagna (midalot.live/ig/...). */
class CampaignVisitsTest extends TestCase
{
    use CreatesQuizData;
    use RefreshDatabase;

    public function test_ogni_apertura_si_somma_per_campagna_e_giorno(): void
    {
        $this->postJson('/api/campaign-visit', ['campaign' => 'clienti_gioielleria'])->assertNoContent();
        $this->postJson('/api/campaign-visit', ['campaign' => 'clienti_gioielleria'])->assertNoContent();
        $this->postJson('/api/campaign-visit', ['campaign' => 'clienti'])->assertNoContent();

        $this->assertSame(2, (int) CampaignVisit::where('campaign', 'clienti_gioielleria')->value('hits'));
        $this->assertSame(1, (int) CampaignVisit::where('campaign', 'clienti')->value('hits'));
        $this->assertSame(2, CampaignVisit::count()); // una riga per campagna e giorno
    }

    public function test_etichette_non_valide_sono_ignorate_senza_errori(): void
    {
        foreach (['', 'altro', 'clienti_<script>', 'clienti_' . str_repeat('a', 60), 'CLIENTI', 'marketing_x'] as $bad) {
            $this->postJson('/api/campaign-visit', ['campaign' => $bad])->assertNoContent();
        }
        $this->postJson('/api/campaign-visit', [])->assertNoContent();

        $this->assertSame(0, CampaignVisit::count());
    }

    public function test_non_si_salva_nulla_che_identifichi_chi_apre_il_link(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9', 'HTTP_USER_AGENT' => 'Mozilla/5.0 prova'])
            ->postJson('/api/campaign-visit', ['campaign' => 'clienti_abbigliamento'])->assertNoContent();

        // La tabella ha solo campagna, giorno e numero di aperture.
        $this->assertEqualsCanonicalizing(
            ['id', 'campaign', 'day', 'hits', 'created_at', 'updated_at'],
            Schema::getColumnListing('campaign_visits')
        );
        $this->assertStringNotContainsString('203.0.113.9', json_encode(CampaignVisit::all()->toArray()));
        $this->assertStringNotContainsString('Mozilla', json_encode(CampaignVisit::all()->toArray()));
    }

    public function test_le_aperture_del_team_non_si_contano(): void
    {
        config(['campaigns.excluded_nicknames' => ['maes98', 'tony21']]);

        $admin = $this->createAdmin();
        $team = $this->createUser(['nickname' => 'Tony21']); // maiuscole ininfluenti
        $altro = $this->createUser(['nickname' => 'cliente_vero']);

        Sanctum::actingAs($admin);
        $this->postJson('/api/campaign-visit', ['campaign' => 'clienti_gioielleria'], ['Authorization' => 'Bearer x'])->assertNoContent();
        Sanctum::actingAs($team);
        $this->postJson('/api/campaign-visit', ['campaign' => 'clienti_gioielleria'], ['Authorization' => 'Bearer x'])->assertNoContent();
        $this->assertSame(0, CampaignVisit::count());

        Sanctum::actingAs($altro);
        $this->postJson('/api/campaign-visit', ['campaign' => 'clienti_gioielleria'], ['Authorization' => 'Bearer x'])->assertNoContent();
        $this->assertSame(1, (int) CampaignVisit::value('hits'));
    }

    public function test_la_pagina_admin_e_solo_per_admin_e_mostra_i_conteggi(): void
    {
        CampaignVisit::create(['campaign' => 'clienti_gioielleria', 'day' => now()->toDateString(), 'hits' => 7]);
        CampaignVisit::create(['campaign' => 'clienti_abbigliamento', 'day' => now()->subDays(3)->toDateString(), 'hits' => 2]);
        CampaignVisit::create(['campaign' => 'clienti_vecchia', 'day' => now()->subDays(30)->toDateString(), 'hits' => 5]);

        $this->get(route('admin.campaign-visits.index'))->assertRedirect();
        $this->actingAs($this->createUser())->get(route('admin.campaign-visits.index'))->assertStatus(403);

        $this->actingAs($this->createAdmin())->get(route('admin.campaign-visits.index'))
            ->assertOk()
            ->assertSee('clienti_gioielleria')
            ->assertSee('clienti_abbigliamento')
            ->assertSee('clienti_vecchia')   // nel totale per campagna anche se piu' vecchia di 14 giorni
            ->assertSee('anonimo');
    }
}
