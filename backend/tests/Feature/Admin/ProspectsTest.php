<?php

namespace Tests\Feature\Admin;

use App\Models\Prospect;
use App\Models\ProspectSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesQuizData;
use Tests\TestCase;

class ProspectsTest extends TestCase
{
    use CreatesQuizData;
    use RefreshDatabase;

    private function prospect(array $attrs = []): Prospect
    {
        static $n = 0;
        $n++;

        return Prospect::create(array_merge([
            'instagram_handle' => 'negozio' . $n,
            'followers' => 1000,
            'website_status' => 'none',
            'activity' => 'active',
            'priority' => 'B',
            'status' => 'to_contact',
        ], $attrs));
    }

    private function payload(array $extra = []): array
    {
        return array_merge([
            'instagram_handle' => '@Negozio_Nuovo',
            'website_status' => 'none',
            'activity' => 'unverified',
            'priority' => 'B',
            'status' => 'to_contact',
        ], $extra);
    }

    // --- accesso ---------------------------------------------------------

    public function test_le_pagine_sono_solo_per_admin(): void
    {
        $p = $this->prospect();
        $user = $this->createUser();

        $this->get(route('admin.prospects.index'))->assertRedirect();
        $this->actingAs($user)->get(route('admin.prospects.index'))->assertStatus(403);
        $this->actingAs($user)->get(route('admin.prospects.create'))->assertStatus(403);
        $this->actingAs($user)->get(route('admin.prospects.export'))->assertStatus(403);
        $this->actingAs($user)->get(route('admin.prospect-searches.index'))->assertStatus(403);
        $this->actingAs($user)->post(route('admin.prospects.store'), $this->payload())->assertStatus(403);
        $this->actingAs($user)->delete(route('admin.prospects.destroy', $p))->assertStatus(403);

        $admin = $this->createAdmin();
        $this->actingAs($admin)->get(route('admin.prospects.index'))->assertOk()->assertSee('Clienti da contattare');
        $this->actingAs($admin)->get(route('admin.prospects.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.prospects.edit', $p))->assertOk();
        $this->actingAs($admin)->get(route('admin.prospect-searches.index'))->assertOk();
    }

    // --- filtri, paginazione, ordinamento --------------------------------

    public function test_filtri_per_regione_citta_settore_e_stato(): void
    {
        $admin = $this->createAdmin();
        $this->prospect(['instagram_handle' => 'napoli_moda', 'region' => 'Campania', 'city' => 'Napoli', 'sector' => 'Abbigliamento']);
        $this->prospect(['instagram_handle' => 'roma_fiori', 'region' => 'Lazio', 'city' => 'Roma', 'sector' => 'Fiori', 'status' => 'contacted', 'contacted_at' => now()]);

        $this->actingAs($admin)->get(route('admin.prospects.index', ['region' => 'Campania']))
            ->assertSee('napoli_moda')->assertDontSee('roma_fiori');
        $this->actingAs($admin)->get(route('admin.prospects.index', ['city' => 'Roma']))
            ->assertSee('roma_fiori')->assertDontSee('napoli_moda');
        $this->actingAs($admin)->get(route('admin.prospects.index', ['sector' => 'Abbigliamento']))
            ->assertSee('napoli_moda')->assertDontSee('roma_fiori');
        $this->actingAs($admin)->get(route('admin.prospects.index', ['status' => 'contacted']))
            ->assertSee('roma_fiori')->assertDontSee('napoli_moda');
        $this->actingAs($admin)->get(route('admin.prospects.index', ['q' => '@napoli']))
            ->assertSee('napoli_moda')->assertDontSee('roma_fiori');
        $this->actingAs($admin)->get(route('admin.prospects.index', ['contacted' => 'no']))
            ->assertSee('napoli_moda')->assertDontSee('roma_fiori');
    }

    public function test_filtro_follower_minimi_e_massimi(): void
    {
        $admin = $this->createAdmin();
        $this->prospect(['instagram_handle' => 'piccolo', 'followers' => 300]);
        $this->prospect(['instagram_handle' => 'medio', 'followers' => 5000]);
        $this->prospect(['instagram_handle' => 'grande', 'followers' => 90000]);

        $this->actingAs($admin)->get(route('admin.prospects.index', ['min_followers' => 700, 'max_followers' => 40000]))
            ->assertSee('medio')->assertDontSee('piccolo')->assertDontSee('grande');
    }

    public function test_filtro_promemoria_scaduti_ignora_i_profili_chiusi(): void
    {
        $admin = $this->createAdmin();
        $this->prospect(['instagram_handle' => 'scaduto', 'status' => 'contacted', 'follow_up_at' => today()->subDays(3)]);
        $this->prospect(['instagram_handle' => 'oggi', 'status' => 'contacted', 'follow_up_at' => today()]);
        $this->prospect(['instagram_handle' => 'futuro', 'status' => 'contacted', 'follow_up_at' => today()->addDays(10)]);
        $this->prospect(['instagram_handle' => 'chiuso', 'status' => 'client', 'follow_up_at' => today()->subDays(1)]);
        $this->prospect(['instagram_handle' => 'senzadata']);

        $this->actingAs($admin)->get(route('admin.prospects.index', ['follow_up' => 'due']))
            ->assertSee('scaduto')->assertSee('oggi')
            ->assertDontSee('futuro')->assertDontSee('chiuso')->assertDontSee('senzadata');
        $this->actingAs($admin)->get(route('admin.prospects.index', ['follow_up' => 'upcoming']))
            ->assertSee('futuro')->assertDontSee('scaduto');

        $this->assertSame(2, \App\Http\Controllers\Admin\ProspectController::dueQuery()->count());
    }

    public function test_il_menu_mostra_il_contatore_dei_promemoria_scaduti(): void
    {
        $this->prospect(['status' => 'contacted', 'follow_up_at' => today()->subDay()]);

        $this->actingAs($this->createAdmin())->get(route('admin.prospects.index'))
            ->assertSee('Clienti da contattare')
            ->assertSee('admin-nav-count', false);
    }

    public function test_paginazione_a_50_per_pagina(): void
    {
        $admin = $this->createAdmin();
        for ($i = 1; $i <= 55; $i++) {
            $this->prospect(['instagram_handle' => sprintf('profilo%03d', $i), 'followers' => $i]);
        }

        $response = $this->actingAs($admin)->get(route('admin.prospects.index', ['sort' => 'followers', 'direction' => 'asc']));
        $this->assertCount(50, $response->viewData('prospects'));
        $this->assertSame(55, $response->viewData('prospects')->total());
        $response->assertSee('profilo001')->assertDontSee('profilo055');

        $page2 = $this->actingAs($admin)->get(route('admin.prospects.index', ['sort' => 'followers', 'direction' => 'asc', 'page' => 2]));
        $this->assertCount(5, $page2->viewData('prospects'));
        $response->assertSee('Mostrati 1-50 di 55 profili', false)->assertSee('pagina 1 di 2');

        // Profili per pagina: solo 25/50/100, qualsiasi altro valore torna a 50; la scelta resta nei link.
        $r25 = $this->actingAs($admin)->get(route('admin.prospects.index', ['per_page' => 25]));
        $this->assertCount(25, $r25->viewData('prospects'));
        $this->assertSame(3, $r25->viewData('prospects')->lastPage());
        $r25->assertSee('per_page=25', false);

        $r100 = $this->actingAs($admin)->get(route('admin.prospects.index', ['per_page' => 100]));
        $this->assertCount(55, $r100->viewData('prospects'));
        $r100->assertSee('Mostrati 1-55 di 55 profili', false);

        foreach (['7', '5000', 'abc', '-1'] as $bad) {
            $rBad = $this->actingAs($admin)->get(route('admin.prospects.index', ['per_page' => $bad]));
            $this->assertCount(50, $rBad->viewData('prospects'));
        }
    }

    public function test_ordinamento_solo_su_colonne_in_whitelist(): void
    {
        $admin = $this->createAdmin();
        $this->prospect(['instagram_handle' => 'a_basso', 'followers' => 10]);
        $this->prospect(['instagram_handle' => 'b_alto', 'followers' => 9000]);

        $r = $this->actingAs($admin)->get(route('admin.prospects.index', ['sort' => 'followers', 'direction' => 'desc']));
        $this->assertSame(['b_alto', 'a_basso'], $r->viewData('prospects')->pluck('instagram_handle')->all());
        $this->assertSame('followers', $r->viewData('sort'));

        // Colonna non ammessa (anche un tentativo di injection): si usa l'ordinamento di default.
        $r = $this->actingAs($admin)->get(route('admin.prospects.index', ['sort' => 'id; DROP TABLE prospects', 'direction' => 'sideways']));
        $r->assertOk();
        $this->assertSame('created_at', $r->viewData('sort'));
        $this->assertSame('desc', $r->viewData('direction'));
        $this->assertSame(2, Prospect::count());
    }

    // --- creazione, handle, URL ------------------------------------------

    public function test_handle_normalizzato_e_univoco(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post(route('admin.prospects.store'), $this->payload(['instagram_handle' => 'https://www.instagram.com/Negozio_Nuovo/?hl=it']))
            ->assertRedirect();
        $this->assertSame('negozio_nuovo', Prospect::firstOrFail()->instagram_handle);

        $this->actingAs($admin)->post(route('admin.prospects.store'), $this->payload(['instagram_handle' => '@NEGOZIO_NUOVO']))
            ->assertSessionHasErrors('instagram_handle');
        $this->assertSame(1, Prospect::count());

        $this->actingAs($admin)->post(route('admin.prospects.store'), $this->payload(['instagram_handle' => 'nome con spazi']))
            ->assertSessionHasErrors('instagram_handle');
    }

    public function test_url_non_http_vengono_scartati(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post(route('admin.prospects.store'), $this->payload(['instagram_handle' => 'js', 'website_url' => 'javascript:alert(1)']));
        $this->assertNull(Prospect::where('instagram_handle', 'js')->value('website_url'));

        $this->actingAs($admin)->post(route('admin.prospects.store'), $this->payload(['instagram_handle' => 'ok', 'website_url' => 'www.esempio.it/negozio']));
        $this->assertSame('https://www.esempio.it/negozio', Prospect::where('instagram_handle', 'ok')->value('website_url'));

        $this->assertNull(Prospect::safeUrl('ftp://esempio.it'));
        $this->assertNull(Prospect::safeUrl('data:text/html,x'));
        $this->assertSame('http://esempio.it', Prospect::safeUrl('http://esempio.it'));
    }

    // --- stato e promemoria ----------------------------------------------

    public function test_il_cambio_stato_salva_data_e_autore_una_sola_volta(): void
    {
        $admin = $this->createAdmin();
        $altro = $this->createAdmin(['email' => 'altro@example.com', 'nickname' => 'altroadmin']);
        $p = $this->prospect();

        $this->actingAs($admin)->patch(route('admin.prospects.status', $p), ['status' => 'contacted'])->assertRedirect();
        $p->refresh();
        $this->assertSame('contacted', $p->status);
        $this->assertNotNull($p->contacted_at);
        $this->assertSame($admin->id, $p->contacted_by);
        $firstDate = $p->contacted_at->toDateTimeString();

        $this->travel(2)->days();
        $this->actingAs($altro)->patch(route('admin.prospects.status', $p), ['status' => 'replied'])->assertRedirect();
        $p->refresh();
        $this->assertSame('replied', $p->status);
        $this->assertSame($admin->id, $p->contacted_by);
        $this->assertSame($firstDate, $p->contacted_at->toDateTimeString());
    }

    public function test_tornare_a_da_contattare_non_perde_lo_storico(): void
    {
        $admin = $this->createAdmin();
        $p = $this->prospect();

        $this->actingAs($admin)->patch(route('admin.prospects.status', $p), ['status' => 'contacted']);
        $this->actingAs($admin)->patch(route('admin.prospects.status', $p), ['status' => 'to_contact']);

        $p->refresh();
        $this->assertSame('to_contact', $p->status);
        $this->assertNotNull($p->contacted_at);
    }

    public function test_il_promemoria_si_imposta_dall_elenco_e_si_azzera_per_cliente_e_non_idoneo(): void
    {
        $admin = $this->createAdmin();
        $p = $this->prospect();
        $date = today()->addDays(14)->toDateString();

        $this->actingAs($admin)->patch(route('admin.prospects.status', $p), ['status' => 'contacted', 'follow_up_at' => $date]);
        $this->assertSame($date, $p->fresh()->follow_up_at->toDateString());

        // Campo promemoria svuotato dall'elenco -> promemoria rimosso.
        $this->actingAs($admin)->patch(route('admin.prospects.status', $p), ['status' => 'contacted', 'follow_up_at' => '']);
        $this->assertNull($p->fresh()->follow_up_at);

        foreach (['client', 'not_suitable'] as $closed) {
            $this->actingAs($admin)->patch(route('admin.prospects.status', $p), ['status' => 'contacted', 'follow_up_at' => $date]);
            $this->actingAs($admin)->patch(route('admin.prospects.status', $p), ['status' => $closed, 'follow_up_at' => $date]);
            $this->assertSame($closed, $p->fresh()->status);
            $this->assertNull($p->fresh()->follow_up_at, "promemoria da azzerare per {$closed}");
        }
    }

    public function test_stato_non_valido_rifiutato(): void
    {
        $p = $this->prospect();

        $this->actingAs($this->createAdmin())->patch(route('admin.prospects.status', $p), ['status' => 'inventato'])
            ->assertSessionHasErrors('status');
        $this->assertSame('to_contact', $p->fresh()->status);
    }

    public function test_modifica_ed_eliminazione(): void
    {
        $admin = $this->createAdmin();
        $p = $this->prospect(['instagram_handle' => 'da_modificare']);

        $this->actingAs($admin)->put(route('admin.prospects.update', $p), $this->payload([
            'instagram_handle' => 'da_modificare', 'name' => 'Boutique Rosa', 'followers' => '2500', 'status' => 'interested',
            'has_physical_store' => '1', 'orders_in_dm' => '',
        ]))->assertRedirect();

        $p->refresh();
        $this->assertSame('Boutique Rosa', $p->name);
        $this->assertSame(2500, $p->followers);
        $this->assertTrue($p->has_physical_store);
        $this->assertNull($p->orders_in_dm);
        $this->assertNotNull($p->contacted_at);

        $this->actingAs($admin)->delete(route('admin.prospects.destroy', $p))->assertRedirect(route('admin.prospects.index'));
        $this->assertSame(0, Prospect::count());
    }

    // --- aggiornamenti senza ricaricare la pagina --------------------------

    public function test_elenco_ajax_restituisce_solo_le_parti_che_cambiano(): void
    {
        $admin = $this->createAdmin();
        $this->prospect(['instagram_handle' => 'napoli_moda', 'region' => 'Campania']);
        $this->prospect(['instagram_handle' => 'roma_fiori', 'region' => 'Lazio']);

        $response = $this->actingAs($admin)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('admin.prospects.index', ['region' => 'Campania']));

        $response->assertOk()
            ->assertSee('ajax-payload', false)
            ->assertSee('data-has-filters="1"', false)
            ->assertSee('data-total="1"', false)
            ->assertSee('napoli_moda')
            ->assertDontSee('roma_fiori')
            ->assertDontSee('<html', false);
    }

    public function test_stato_e_promemoria_si_salvano_in_json_e_l_eliminazione_pure(): void
    {
        $admin = $this->createAdmin();
        $p = $this->prospect(['instagram_handle' => 'da_salvare']);

        $this->actingAs($admin)
            ->patchJson(route('admin.prospects.status', $p), ['status' => 'contacted', 'follow_up_at' => now()->addDays(14)->toDateString()])
            ->assertOk()
            ->assertJsonStructure(['message']);

        $p->refresh();
        $this->assertSame('contacted', $p->status);
        $this->assertNotNull($p->follow_up_at);
        $this->assertNotNull($p->contacted_at);

        $this->actingAs($admin)
            ->patchJson(route('admin.prospects.status', $p), ['status' => 'non_esiste'])
            ->assertStatus(422);

        $this->actingAs($admin)->deleteJson(route('admin.prospects.destroy', $p))->assertOk()->assertJsonStructure(['message']);
        $this->assertSame(0, Prospect::count());
    }

    // --- export ----------------------------------------------------------

    public function test_export_excel_rispetta_i_filtri_e_dimensiona_le_colonne(): void
    {
        $admin = $this->createAdmin();
        $this->prospect(['instagram_handle' => 'in_campania', 'name' => 'Nome molto molto lungo per la colonna', 'region' => 'Campania']);
        $this->prospect(['instagram_handle' => 'in_lazio', 'name' => 'Altro', 'region' => 'Lazio']);

        $response = $this->actingAs($admin)->get(route('admin.prospects.export', ['region' => 'Campania']));
        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml.sheet', $response->headers->get('Content-Type'));

        $file = tempnam(sys_get_temp_dir(), 'x');
        file_put_contents($file, $response->streamedContent());
        $zip = new \ZipArchive();
        $this->assertTrue($zip->open($file) === true);
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        @unlink($file);

        $this->assertStringContainsString('@in_campania', $sheet);
        $this->assertStringNotContainsString('in_lazio', $sheet);
        $this->assertMatchesRegularExpression('/<col min="3" max="3" width="3[0-9]" customWidth="1"\/>/', $sheet);
    }

    // --- ricerche --------------------------------------------------------

    public function test_ricerche_nome_di_default_stato_e_copertura(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post(route('admin.prospect-searches.store'), [
            'region' => 'Campania', 'city' => 'Napoli', 'sector' => 'Abbigliamento', 'status' => 'planned', 'searched_at' => '2026-11-19',
        ])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.prospect-searches.store'), [
            'region' => 'Campania', 'sector' => 'Fiori', 'status' => 'in_progress', 'name' => 'Giro fiorai',
        ]);

        $search = ProspectSearch::where('sector', 'Abbigliamento')->firstOrFail();
        $this->assertSame('Campania · Napoli · Abbigliamento · 19/11/2026', $search->name);

        $this->prospect(['prospect_search_id' => $search->id, 'contacted_at' => now(), 'status' => 'contacted']);
        $this->prospect(['prospect_search_id' => $search->id]);

        // Segnare "fatta" imposta la data se mancava.
        $fiorai = ProspectSearch::where('name', 'Giro fiorai')->firstOrFail();
        $this->actingAs($admin)->patch(route('admin.prospect-searches.update', $fiorai), ['status' => 'done']);
        $this->assertSame('done', $fiorai->fresh()->status);
        $this->assertNotNull($fiorai->fresh()->searched_at);

        $r = $this->actingAs($admin)->get(route('admin.prospect-searches.index'));
        $r->assertOk()->assertSee('Giro fiorai');

        $row = $r->viewData('coverage')->firstWhere('region', 'Campania');
        $this->assertSame(2, (int) $row->searches);
        $this->assertSame(1, (int) $row->done_searches);
        $this->assertSame(2, (int) $row->prospects);
        $this->assertSame(1, (int) $row->contacted);

        // In corso e da fare vengono prima delle fatte.
        $this->assertSame('planned', $r->viewData('searches')->first()->status);
        $this->assertSame(2, $r->viewData('searches')->firstWhere('id', $search->id)->prospects_count);

        $this->actingAs($admin)->get(route('admin.prospect-searches.index', ['status' => 'done']))
            ->assertSee('Giro fiorai')->assertDontSee('Abbigliamento · 19/11/2026');
    }

    public function test_eliminando_una_ricerca_i_profili_restano(): void
    {
        $admin = $this->createAdmin();
        $search = ProspectSearch::create(['name' => 'Da togliere', 'sector' => 'Bar']);
        $p = $this->prospect(['prospect_search_id' => $search->id]);

        $this->actingAs($admin)->delete(route('admin.prospect-searches.destroy', $search))->assertRedirect();

        $this->assertNull(ProspectSearch::find($search->id));
        $this->assertNull($p->fresh()->prospect_search_id);
    }

    // --- messaggio personalizzato ------------------------------------------

    public function test_il_messaggio_usa_i_dati_veri_del_profilo(): void
    {
        $p = $this->prospect([
            'instagram_handle' => 'moda_dna', 'name' => 'DNA Moda (donna, Napoli)', 'city' => 'Napoli',
            'has_physical_store' => true, 'orders_in_dm' => true, 'website_status' => 'none',
        ]);

        $msg = $p->outreach_message;
        $this->assertStringContainsString('DNA Moda', $msg);
        $this->assertStringNotContainsString('(donna', $msg);          // niente testo tra parentesi nel saluto
        $this->assertStringContainsString('negozio a Napoli', $msg);
        $this->assertStringContainsString('gli ordini li gestisci direttamente in messaggio', $msg);
        $this->assertStringContainsString('Midalot', $msg);
        $this->assertStringContainsString('midalot.live/servizi-digitali', $msg); // link alla pagina dei servizi
        $this->assertStringContainsString('siti web', $msg);
        $this->assertStringNotContainsString('marketing', mb_strtolower($msg)); // solo siti, nessun marketing
        $this->assertStringNotContainsString('gestionali', $msg);
        $this->assertStringContainsString('non ti scrivo più', $msg);   // via d'uscita sempre presente
        $this->assertStringNotContainsString("e'", $msg);               // accenti veri, non apostrofi

        $this->assertSame('https://www.instagram.com/m/moda_dna', $p->instagram_chat_url);
        $this->assertSame('https://ig.me/m/moda_dna', $p->instagram_chat_url_mobile); // da telefono apre la chat nell'app

        // Link con parametri per Analytics: sorgente instagram / mezzo dm, settore in utm_campaign, nessun profilo nell'indirizzo.
        $this->assertStringContainsString('https://midalot.live/servizi-digitali?utm_source=instagram&utm_medium=dm&utm_campaign=clienti', $msg);
        $this->assertStringNotContainsString('moda_dna', explode('Qui vedi cosa facciamo: ', $msg)[1] ?? '');
        $conSettore = $this->prospect(['instagram_handle' => 'gioie_rossi', 'sector' => 'Estetica e benessere']);
        $this->assertStringContainsString('utm_campaign=clienti_estetica_e_benessere', $conSettore->outreach_message);
    }

    public function test_il_messaggio_cambia_con_la_situazione_del_sito_e_non_inventa_nulla(): void
    {
        $conSito = $this->prospect(['website_status' => 'yes', 'has_physical_store' => null, 'city' => 'Napoli']);
        $this->assertStringContainsString('hai già un sito', $conSito->outreach_message);
        $this->assertStringNotContainsString('negozio a', $conSito->outreach_message); // negozio non verificato

        $senzaNome = $this->prospect(['instagram_handle' => 'solo_handle', 'name' => null, 'website_status' => 'none', 'proposal' => 'prenotazioni']);
        $this->assertStringContainsString('solo_handle', $senzaNome->outreach_message);
        $this->assertStringContainsString('prenotazioni online', $senzaNome->outreach_message);

        $datato = $this->prospect(['website_status' => 'outdated']);
        $this->assertStringContainsString('potrebbe essere rinnovato', $datato->outreach_message);

        $rotto = $this->prospect(['website_status' => 'broken']);
        $this->assertStringContainsString('non si apre correttamente', $rotto->outreach_message);
        $this->assertStringNotContainsString('hai già un sito', $rotto->outreach_message);
        $this->assertSame('Sito non funziona', Prospect::WEBSITE_STATUSES['broken']);
    }

    public function test_le_aperture_variano_tra_profili_ma_sono_stabili(): void
    {
        $openings = [];
        foreach (range(1, 30) as $i) {
            $p = $this->prospect(['instagram_handle' => "profilo_{$i}", 'name' => 'Negozio']);
            $first = explode("\n\n", $p->outreach_message)[0];
            $this->assertSame($first, explode("\n\n", $p->outreach_message)[0]); // stabile
            $openings[$first] = true;
        }

        $this->assertGreaterThan(1, count($openings));
    }

    public function test_pulsanti_copia_e_apri_in_elenco_e_nel_form(): void
    {
        $admin = $this->createAdmin();
        $aperto = $this->prospect(['instagram_handle' => 'da_scrivere']);
        $cliente = $this->prospect(['instagram_handle' => 'gia_cliente', 'status' => 'client']);

        $html = $this->actingAs($admin)->get(route('admin.prospects.index'))->getContent();
        $this->assertStringContainsString('https://www.instagram.com/m/da_scrivere', $html);
        $this->assertStringNotContainsString('https://www.instagram.com/m/gia_cliente', $html); // chiusi: nessun pulsante

        $this->actingAs($admin)->get(route('admin.prospects.edit', $aperto))
            ->assertOk()->assertSee('Messaggio per questo profilo')->assertSee('outreach-message', false);
        $this->actingAs($admin)->get(route('admin.prospects.create'))
            ->assertOk()->assertDontSee('outreach-message', false);
    }

    // --- comando di importazione -----------------------------------------

    private function writeJson(array $data): string
    {
        $path = tempnam(sys_get_temp_dir(), 'prs');
        file_put_contents($path, json_encode($data));

        return $path;
    }

    private function importData(array $prospects): array
    {
        return [
            'search' => ['region' => 'Campania', 'city' => 'Napoli', 'sector' => 'Abbigliamento', 'searched_at' => '2026-11-19'],
            'prospects' => $prospects,
        ];
    }

    public function test_import_dry_run_non_scrive_nulla(): void
    {
        $file = $this->writeJson($this->importData([['handle' => '@Moda_Napoli', 'followers' => 1200]]));

        $this->artisan('app:import-prospects', ['file' => $file, '--dry-run' => true])
            ->expectsOutputToContain('DRY-RUN')
            ->assertSuccessful();

        $this->assertSame(0, Prospect::count());
        $this->assertSame(0, ProspectSearch::count());
    }

    public function test_import_crea_ricerca_e_profili_collegati(): void
    {
        $file = $this->writeJson($this->importData([
            ['handle' => '@Moda_Napoli', 'name' => 'Moda Napoli', 'followers' => 1200, 'website_url' => 'linktr.ee/moda', 'website_status' => 'link_only',
                'email' => 'info@moda.example', 'has_physical_store' => true, 'priority' => 'A', 'proposal' => 'vetrina'],
            ['handle' => 'https://www.instagram.com/scarpe.sud/', 'followers' => 900, 'sector' => 'Calzature'],
        ]));

        $this->artisan('app:import-prospects', ['file' => $file])->assertSuccessful();

        $search = ProspectSearch::firstOrFail();
        $this->assertSame('Campania · Napoli · Abbigliamento · 19/11/2026', $search->name);
        $this->assertSame('in_progress', $search->status);
        $this->assertSame(2, Prospect::where('prospect_search_id', $search->id)->count());

        $moda = Prospect::where('instagram_handle', 'moda_napoli')->firstOrFail();
        $this->assertSame('https://linktr.ee/moda', $moda->website_url);
        $this->assertSame('link_only', $moda->website_status);
        $this->assertSame('A', $moda->priority);
        $this->assertSame('Campania', $moda->region);
        $this->assertSame('Napoli', $moda->city);
        $this->assertSame('Abbigliamento', $moda->sector);
        $this->assertTrue($moda->has_physical_store);
        $this->assertSame('to_contact', $moda->status);

        $scarpe = Prospect::where('instagram_handle', 'scarpe.sud')->firstOrFail();
        $this->assertSame('Calzature', $scarpe->sector);
        $this->assertSame('unverified', $scarpe->activity);
    }

    public function test_import_scarta_doppioni_nel_file_e_in_elenco_e_riusa_la_ricerca(): void
    {
        $this->prospect(['instagram_handle' => 'gia_presente', 'followers' => 5555]);
        $file = $this->writeJson($this->importData([
            ['handle' => 'gia_presente', 'followers' => 1],
            ['handle' => 'nuovo_uno'],
            ['handle' => '@Nuovo_Uno'],
        ]));

        $this->artisan('app:import-prospects', ['file' => $file])
            ->expectsOutputToContain('scartati come doppioni: 2')
            ->assertSuccessful();

        $this->assertSame(2, Prospect::count());
        $this->assertSame(5555, Prospect::where('instagram_handle', 'gia_presente')->value('followers'));

        // Secondo giro con lo stesso file: nessun nuovo profilo, ricerca riusata (non duplicata).
        $this->artisan('app:import-prospects', ['file' => $file])->assertSuccessful();
        $this->assertSame(2, Prospect::count());
        $this->assertSame(1, ProspectSearch::count());
    }

    public function test_import_con_errori_non_scrive_nulla(): void
    {
        $file = $this->writeJson($this->importData([
            ['handle' => 'valido'],
            ['handle' => 'url_cattivo', 'website_url' => 'javascript:alert(1)'],
            ['handle' => 'email_cattiva', 'email' => 'non-una-email'],
            ['handle' => 'stato_strano', 'website_status' => 'boh'],
            ['handle' => 'spazi non validi'],
            ['name' => 'senza handle'],
        ]));

        $this->artisan('app:import-prospects', ['file' => $file])
            ->expectsOutputToContain('nessuna scrittura')
            ->assertFailed();

        $this->assertSame(0, Prospect::count());
        $this->assertSame(0, ProspectSearch::count());
    }

    public function test_import_file_mancante_o_json_non_valido(): void
    {
        $this->artisan('app:import-prospects', ['file' => '/non/esiste.json'])->assertFailed();

        $path = tempnam(sys_get_temp_dir(), 'prs');
        file_put_contents($path, '{non json');
        $this->artisan('app:import-prospects', ['file' => $path])->assertFailed();
    }
}
