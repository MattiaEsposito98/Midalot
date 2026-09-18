<?php

namespace Tests\Feature\Api;

use App\Models\Minigioco;
use App\Models\MinigiocoAttempt;
use App\Models\MinigiocoRound;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesQuizData;
use Tests\TestCase;

/**
 * Nuovo tipo di minigioco "Vero o Falso": l'admin scrive un'affermazione e
 * indica se e' vera o falsa, con una spiegazione opzionale che esce subito
 * dopo la risposta del giocatore (come per Trova l'Intruso).
 */
class VeroFalsoTest extends TestCase
{
    use CreatesQuizData;
    use RefreshDatabase;

    private function minigiocoConDomanda(): array
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $minigioco = Minigioco::create([
            'title' => 'Vero o Falso di test',
            'tipo' => 'vero_falso',
            'max_score' => 10,
            'created_by' => $admin->id,
            'is_active' => true,
            'leaderboard_visible' => true,
        ]);

        $round = MinigiocoRound::create([
            'minigioco_id' => $minigioco->id,
            'time_limit_seconds' => 20,
            'affermazione' => "Roma e' la capitale d'Italia.",
            'risposta_corretta' => true,
            'spiegazione' => "Roma e' la capitale d'Italia dal 1871.",
        ]);

        return [$minigioco, $round];
    }

    public function test_admin_crea_una_domanda_vero_falso(): void
    {
        $admin = $this->createAdmin();
        $minigioco = Minigioco::create([
            'title' => 'Vero o Falso admin',
            'tipo' => 'vero_falso',
            'max_score' => 10,
            'created_by' => $admin->id,
            'is_active' => true,
            'leaderboard_visible' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.minigiochi.rounds.store', $minigioco->id), [
                'affermazione' => 'Il sole gira attorno alla terra.',
                'risposta_corretta' => '0',
                'spiegazione' => 'E\' la terra che gira attorno al sole.',
                'time_limit_seconds' => 15,
            ])
            ->assertRedirect(route('admin.minigiochi.rounds.index', $minigioco->id));

        $round = MinigiocoRound::where('minigioco_id', $minigioco->id)->first();
        $this->assertSame('Il sole gira attorno alla terra.', $round->affermazione);
        $this->assertFalse($round->risposta_corretta);
        $this->assertSame('E\' la terra che gira attorno al sole.', $round->spiegazione);
        $this->assertSame(15, $round->time_limit_seconds);
    }

    public function test_admin_modifica_una_domanda_vero_falso(): void
    {
        $admin = $this->createAdmin();
        [$minigioco, $round] = $this->minigiocoConDomanda();

        $this->actingAs($admin)
            ->put(route('admin.minigiochi.rounds.update', [$minigioco->id, $round->id]), [
                'affermazione' => 'Affermazione aggiornata.',
                'risposta_corretta' => '1',
                'spiegazione' => 'Spiegazione aggiornata.',
                'time_limit_seconds' => 30,
            ])
            ->assertRedirect(route('admin.minigiochi.rounds.index', $minigioco->id));

        $round->refresh();
        $this->assertSame('Affermazione aggiornata.', $round->affermazione);
        $this->assertTrue($round->risposta_corretta);
        $this->assertSame('Spiegazione aggiornata.', $round->spiegazione);
    }

    public function test_la_domanda_in_gioco_non_rivela_la_risposta_ne_la_spiegazione(): void
    {
        [$minigioco] = $this->minigiocoConDomanda();
        $utente = User::factory()->create(['is_admin' => false]);

        $risposta = $this->actingAs($utente, 'sanctum')
            ->getJson("/api/minigiochi/{$minigioco->id}")
            ->assertOk();

        $payload = json_encode($risposta->json());
        $this->assertStringContainsString("Roma e'", $payload);
        $this->assertStringNotContainsString('risposta_corretta', $payload);
        $this->assertStringNotContainsString('dal 1871', $payload);
    }

    public function test_risposta_corretta_restituisce_la_spiegazione(): void
    {
        [$minigioco, $round] = $this->minigiocoConDomanda();
        $utente = User::factory()->create(['is_admin' => false]);

        $attempt = MinigiocoAttempt::create([
            'minigioco_id' => $minigioco->id,
            'user_id' => $utente->id,
            'started_at' => now(),
            'completed' => false,
            'score' => 0,
        ]);

        $this->actingAs($utente, 'sanctum')
            ->postJson('/api/minigiochi/answer', [
                'attempt_id' => $attempt->id,
                'round_id' => $round->id,
                'risposta' => true,
                'time_taken' => 2000,
            ])
            ->assertOk()
            ->assertJson([
                'correct' => true,
                'spiegazione' => "Roma e' la capitale d'Italia dal 1871.",
            ]);
    }

    public function test_risposta_sbagliata_restituisce_comunque_la_spiegazione(): void
    {
        [$minigioco, $round] = $this->minigiocoConDomanda();
        $utente = User::factory()->create(['is_admin' => false]);

        $attempt = MinigiocoAttempt::create([
            'minigioco_id' => $minigioco->id,
            'user_id' => $utente->id,
            'started_at' => now(),
            'completed' => false,
            'score' => 0,
        ]);

        $this->actingAs($utente, 'sanctum')
            ->postJson('/api/minigiochi/answer', [
                'attempt_id' => $attempt->id,
                'round_id' => $round->id,
                'risposta' => false,
                'time_taken' => 2000,
            ])
            ->assertOk()
            ->assertJson([
                'correct' => false,
                'spiegazione' => "Roma e' la capitale d'Italia dal 1871.",
            ]);
    }

    public function test_timeout_restituisce_comunque_la_spiegazione(): void
    {
        [$minigioco, $round] = $this->minigiocoConDomanda();
        $utente = User::factory()->create(['is_admin' => false]);

        $attempt = MinigiocoAttempt::create([
            'minigioco_id' => $minigioco->id,
            'user_id' => $utente->id,
            'started_at' => now(),
            'completed' => false,
            'score' => 0,
        ]);

        $this->actingAs($utente, 'sanctum')
            ->postJson('/api/minigiochi/answer', [
                'attempt_id' => $attempt->id,
                'round_id' => $round->id,
                'risposta' => null,
                'time_taken' => 20000,
            ])
            ->assertOk()
            ->assertJson([
                'timeout' => true,
                'spiegazione' => "Roma e' la capitale d'Italia dal 1871.",
            ]);
    }

    public function test_il_riepilogo_mostra_affermazione_risposta_e_spiegazione(): void
    {
        [$minigioco, $round] = $this->minigiocoConDomanda();
        $utente = User::factory()->create(['is_admin' => false]);

        $attempt = MinigiocoAttempt::create([
            'minigioco_id' => $minigioco->id,
            'user_id' => $utente->id,
            'started_at' => now(),
            'completed' => false,
            'score' => 0,
        ]);

        $this->actingAs($utente, 'sanctum')
            ->postJson('/api/minigiochi/answer', [
                'attempt_id' => $attempt->id,
                'round_id' => $round->id,
                'risposta' => true,
                'time_taken' => 2000,
            ])
            ->assertOk();

        $this->actingAs($utente, 'sanctum')
            ->postJson('/api/minigiochi/finish', ['attempt_id' => $attempt->id])
            ->assertOk();

        $risposta = $this->actingAs($utente, 'sanctum')
            ->getJson("/api/minigiochi/{$minigioco->id}/review")
            ->assertOk();

        $risposta->assertJsonPath('rounds.0.affermazione', "Roma e' la capitale d'Italia.");
        $risposta->assertJsonPath('rounds.0.risposta_corretta', true);
        $risposta->assertJsonPath('rounds.0.risposta_utente', true);
        $risposta->assertJsonPath('rounds.0.spiegazione', "Roma e' la capitale d'Italia dal 1871.");
    }
}
