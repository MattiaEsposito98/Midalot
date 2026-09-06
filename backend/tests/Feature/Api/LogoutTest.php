<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_il_logout_revoca_il_token_usato(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $plainTextToken = $user->createToken('react')->plainTextToken;

        $this->assertSame(1, $user->tokens()->count());

        $this->withHeader('Authorization', "Bearer {$plainTextToken}")
            ->postJson('/api/logout')
            ->assertOk();

        $this->assertSame(0, $user->fresh()->tokens()->count());
    }

    public function test_il_token_non_funziona_piu_dopo_il_logout(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $plainTextToken = $user->createToken('react')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$plainTextToken}")
            ->getJson('/api/user')
            ->assertOk();

        $this->withHeader('Authorization', "Bearer {$plainTextToken}")
            ->postJson('/api/logout')
            ->assertOk();

        // In produzione ogni richiesta risolve la guard da zero; nei test il
        // processo e' lo stesso, quindi l'utente gia' autenticato resterebbe
        // in memoria e maschererebbe l'esito.
        $this->app['auth']->forgetGuards();

        // Il punto dell'intera modifica: prima il token restava valido.
        $this->withHeader('Authorization', "Bearer {$plainTextToken}")
            ->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_il_logout_non_tocca_le_sessioni_degli_altri_dispositivi(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $telefono = $user->createToken('react')->plainTextToken;
        $computer = $user->createToken('react')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$telefono}")
            ->postJson('/api/logout')
            ->assertOk();

        $this->withHeader('Authorization', "Bearer {$computer}")
            ->getJson('/api/user')
            ->assertOk();

        $this->assertSame(1, $user->fresh()->tokens()->count());
    }

    public function test_un_token_scaduto_non_e_piu_valido(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $plainTextToken = $user->createToken('react')->plainTextToken;

        // Il token viene retrodatato oltre la finestra di scadenza configurata.
        $minuti = (int) config('sanctum.expiration');
        $this->assertNotNull($minuti, 'La scadenza dei token deve essere configurata.');

        PersonalAccessToken::query()->update([
            'created_at' => now()->subMinutes($minuti + 60),
        ]);

        $this->withHeader('Authorization', "Bearer {$plainTextToken}")
            ->getJson('/api/user')
            ->assertUnauthorized();
    }
}
