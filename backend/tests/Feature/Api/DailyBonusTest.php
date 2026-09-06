<?php

namespace Tests\Feature\Api;

use App\Models\DailyLoginBonus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Copre il bug segnalato il 06/09/2026: il bonus giornaliero veniva
 * assegnato solo dentro AuthController::login(), ma il token resta valido
 * per giorni (fino a 30). Un utente che riapre il sito con una sessione
 * gia' attiva non richiama mai /api/login, quindi non riceveva piu' il
 * bonus dal secondo giorno in poi nonostante visitasse il sito.
 */
class DailyBonusTest extends TestCase
{
    use RefreshDatabase;

    public function test_aprire_lapp_con_un_token_gia_valido_assegna_il_bonus(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $token = $user->createToken('react')->plainTextToken;

        // Nessun /api/login qui: e' esattamente il caso di chi torna sul
        // sito il giorno dopo con la sessione ancora attiva.
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/daily-bonus')
            ->assertOk()
            ->assertJson(['granted' => true]);

        $this->assertSame(
            1,
            DailyLoginBonus::where('user_id', $user->id)
                ->whereDate('bonus_date', now()->toDateString())
                ->count()
        );
    }

    public function test_il_bonus_non_si_prende_due_volte_nello_stesso_giorno(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $token = $user->createToken('react')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/daily-bonus')
            ->assertJson(['granted' => true]);

        // Aprendo di nuovo l'app piu' volte nello stesso giorno (piu' tab,
        // refresh, un'altra pagina) il bonus non deve raddoppiare.
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/daily-bonus')
            ->assertJson(['granted' => false]);

        $this->assertSame(1, DailyLoginBonus::where('user_id', $user->id)->count());
    }

    public function test_il_bonus_torna_disponibile_il_giorno_dopo(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        DailyLoginBonus::insertOrIgnore([
            'user_id' => $user->id,
            'bonus_date' => now()->subDay()->toDateString(),
            'score' => 1000,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $token = $user->createToken('react')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/daily-bonus')
            ->assertJson(['granted' => true]);

        $this->assertSame(2, DailyLoginBonus::where('user_id', $user->id)->count());
    }
}
