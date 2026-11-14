<?php

namespace Tests\Feature\Api;

use App\Mail\EventRequestMail;
use App\Models\EventRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Le richieste arrivano da due pagine: quiz per eventi e servizi digitali.
 */
class EventRequestKindTest extends TestCase
{
    use RefreshDatabase;

    public function test_senza_kind_e_una_richiesta_di_evento(): void
    {
        Mail::fake();

        $this->postJson('/api/event-requests', [
            'name' => 'Mario Rossi', 'contact' => 'mario@example.test', 'message' => 'Festa di laurea',
            'event_date' => '2026-12-20',
        ])->assertCreated();

        $r = EventRequest::first();
        $this->assertSame('evento', $r->kind);
        $this->assertSame('2026-12-20', $r->event_date->toDateString());
        Mail::assertSent(EventRequestMail::class, fn ($m) => str_contains($m->build()->subject, 'evento'));
    }

    public function test_richiesta_servizi_digitali_senza_data_e_con_oggetto_email_dedicato(): void
    {
        Mail::fake();

        $this->postJson('/api/event-requests', [
            'kind' => 'servizi', 'name' => 'Negozio Bianchi', 'contact' => '3330000000',
            'event_type' => 'E-commerce', 'event_date' => '2026-12-20', 'message' => 'Vorrei vendere online',
        ])->assertCreated()->assertJsonFragment(['message' => 'Richiesta inviata! Ti ricontatteremo il prima possibile per parlare del tuo progetto.']);

        $r = EventRequest::first();
        $this->assertSame('servizi', $r->kind);
        $this->assertSame('E-commerce', $r->event_type);
        $this->assertNull($r->event_date, 'Per i servizi la data non si salva.');
        Mail::assertSent(EventRequestMail::class, fn ($m) => str_contains($m->build()->subject, 'servizi digitali'));
    }

    public function test_kind_non_valido_viene_rifiutato(): void
    {
        $this->postJson('/api/event-requests', [
            'kind' => 'altro', 'name' => 'X', 'contact' => 'x@example.test', 'message' => 'Ciao a tutti',
        ])->assertUnprocessable();
    }

    public function test_ladmin_filtra_per_pagina_di_provenienza(): void
    {
        EventRequest::create(['kind' => 'evento', 'name' => 'Cliente festa', 'contact' => 'a', 'message' => 'compleanno', 'status' => 'new']);
        EventRequest::create(['kind' => 'servizi', 'name' => 'Cliente gestionale', 'contact' => 'b', 'message' => 'gestionale', 'status' => 'new']);

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get(route('admin.event-requests.index', ['kind' => 'servizi']))
            ->assertOk()->assertSee('Cliente gestionale')->assertDontSee('Cliente festa')->assertSee('Servizi digitali');

        $this->actingAs($admin)->get(route('admin.event-requests.index'))
            ->assertOk()->assertSee('Cliente gestionale')->assertSee('Cliente festa');
    }
}
