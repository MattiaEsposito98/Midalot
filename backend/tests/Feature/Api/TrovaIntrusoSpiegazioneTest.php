<?php

namespace Tests\Feature\Api;

use App\Models\Minigioco;
use App\Models\MinigiocoAttempt;
use App\Models\MinigiocoRound;
use App\Models\MinigiocoRoundItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La spiegazione dell'intruso deve uscire subito dopo la risposta (giusta,
 * sbagliata o timeout), non solo nel riepilogo finale - ma non deve essere
 * leggibile prima, tramite il payload della domanda in gioco.
 */
class TrovaIntrusoSpiegazioneTest extends TestCase
{
    use RefreshDatabase;

    private function minigiocoConSpiegazione(): array
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $minigioco = Minigioco::create([
            'title' => 'Trova intruso di test',
            'tipo' => 'trova_intruso',
            'max_score' => 10,
            'created_by' => $admin->id,
            'is_active' => true,
            'leaderboard_visible' => true,
        ]);

        $round = MinigiocoRound::create([
            'minigioco_id' => $minigioco->id,
            'time_limit_seconds' => 20,
            'content_mode' => 'testo',
            'intruso_spiegazione' => 'La sedia non è un frutto come gli altri tre.',
        ]);

        $items = [];
        foreach (['Mela', 'Pera', 'Sedia', 'Banana'] as $index => $label) {
            $items[] = MinigiocoRoundItem::create([
                'minigioco_round_id' => $round->id,
                'ordine' => $index + 1,
                'label' => $label,
                'is_intruso' => $label === 'Sedia',
            ]);
        }

        return [$minigioco, $round, $items];
    }

    public function test_la_domanda_in_gioco_non_rivela_la_spiegazione(): void
    {
        [$minigioco] = $this->minigiocoConSpiegazione();
        $utente = User::factory()->create(['is_admin' => false]);

        $risposta = $this->actingAs($utente, 'sanctum')
            ->getJson("/api/minigiochi/{$minigioco->id}")
            ->assertOk();

        $payload = json_encode($risposta->json());
        $this->assertStringNotContainsString('sedia non', strtolower($payload));
        $this->assertStringNotContainsString('spiegazione', strtolower($payload));
    }

    public function test_risposta_corretta_restituisce_la_spiegazione(): void
    {
        [$minigioco, $round, $items] = $this->minigiocoConSpiegazione();
        $utente = User::factory()->create(['is_admin' => false]);
        $intruso = collect($items)->firstWhere('is_intruso', true);

        $attempt = MinigiocoAttempt::create([
            'minigioco_id' => $minigioco->id,
            'user_id' => $utente->id,
            'started_at' => now(),
            'completed' => false,
            'score' => 0,
        ]);

        $risposta = $this->actingAs($utente, 'sanctum')
            ->postJson('/api/minigiochi/answer', [
                'attempt_id' => $attempt->id,
                'round_id' => $round->id,
                'risposta' => $intruso->id,
                'time_taken' => 2000,
            ])
            ->assertOk();

        $risposta->assertJson([
            'correct' => true,
            'intruso_spiegazione' => 'La sedia non è un frutto come gli altri tre.',
        ]);
    }

    public function test_risposta_sbagliata_restituisce_comunque_la_spiegazione(): void
    {
        [$minigioco, $round, $items] = $this->minigiocoConSpiegazione();
        $utente = User::factory()->create(['is_admin' => false]);
        $sbagliata = collect($items)->firstWhere('is_intruso', false);

        $attempt = MinigiocoAttempt::create([
            'minigioco_id' => $minigioco->id,
            'user_id' => $utente->id,
            'started_at' => now(),
            'completed' => false,
            'score' => 0,
        ]);

        $risposta = $this->actingAs($utente, 'sanctum')
            ->postJson('/api/minigiochi/answer', [
                'attempt_id' => $attempt->id,
                'round_id' => $round->id,
                'risposta' => $sbagliata->id,
                'time_taken' => 2000,
            ])
            ->assertOk();

        $risposta->assertJson([
            'correct' => false,
            'intruso_spiegazione' => 'La sedia non è un frutto come gli altri tre.',
        ]);
    }

    public function test_timeout_restituisce_comunque_la_spiegazione(): void
    {
        [$minigioco, $round] = $this->minigiocoConSpiegazione();
        $utente = User::factory()->create(['is_admin' => false]);

        $attempt = MinigiocoAttempt::create([
            'minigioco_id' => $minigioco->id,
            'user_id' => $utente->id,
            'started_at' => now(),
            'completed' => false,
            'score' => 0,
        ]);

        $risposta = $this->actingAs($utente, 'sanctum')
            ->postJson('/api/minigiochi/answer', [
                'attempt_id' => $attempt->id,
                'round_id' => $round->id,
                'risposta' => null,
                'time_taken' => 20000,
            ])
            ->assertOk();

        $risposta->assertJson([
            'timeout' => true,
            'intruso_spiegazione' => 'La sedia non è un frutto come gli altri tre.',
        ]);
    }
}
