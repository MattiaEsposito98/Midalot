<?php

namespace Tests\Feature\Api;

use App\Models\Challenge;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesQuizData;
use Tests\TestCase;

class ChallengeRematchTest extends TestCase
{
    use CreatesQuizData;
    use RefreshDatabase;

    private Quiz $training;

    protected function setUp(): void
    {
        parent::setUp();

        // 10 domande: basta per estrarne 7 per una sfida.
        $this->training = $this->createTrainingQuiz(10);
    }

    private function completedChallenge(User $challenger, ?User $opponent, array $extra = []): Challenge
    {
        $challenge = Challenge::forceCreate(array_merge([
            'challenger_id' => $challenger->id,
            'opponent_id' => $opponent?->id ?? $challenger->id,
            'mode' => 'async',
            'status' => 'completed',
            'category_ids' => [$this->training->training_category_id],
            'expires_at' => now()->addHours(72),
            'completed_at' => now(),
            'winner_id' => $challenger->id,
        ], $extra));

        $challenge->participants()->create(['user_id' => $challenger->id, 'status' => 'accepted', 'accepted_at' => now()]);
        if ($opponent) {
            $challenge->participants()->create(['user_id' => $opponent->id, 'status' => 'accepted', 'accepted_at' => now()]);
        }

        return $challenge;
    }

    public function test_lo_sfidante_crea_la_rivincita_con_gli_stessi_giocatori_e_argomenti(): void
    {
        $a = $this->createUser();
        $b = $this->createUser();
        $old = $this->completedChallenge($a, $b, ['quiz_ids' => null]);

        $response = $this->actingAs($a, 'sanctum')
            ->postJson("/api/challenges/{$old->id}/rematch")
            ->assertCreated();

        $new = Challenge::findOrFail($response->json('id'));
        $this->assertNotSame($old->id, $new->id);
        $this->assertSame('open', $new->status);
        $this->assertSame('async', $new->mode);
        $this->assertSame($a->id, $new->challenger_id);
        $this->assertSame([$this->training->training_category_id], $new->category_ids);
        $this->assertSame(7, $new->questions()->count());
        $this->assertSame('accepted', $new->participants()->where('user_id', $a->id)->first()->status);
        $this->assertSame('invited', $new->participants()->where('user_id', $b->id)->first()->status);

        // la vecchia sfida non cambia
        $this->assertSame('completed', $old->fresh()->status);
    }

    public function test_anche_lo_sfidato_puo_chiedere_la_rivincita_e_diventa_lo_sfidante(): void
    {
        $a = $this->createUser();
        $b = $this->createUser();
        $old = $this->completedChallenge($a, $b);

        $response = $this->actingAs($b, 'sanctum')
            ->postJson("/api/challenges/{$old->id}/rematch")
            ->assertCreated();

        $new = Challenge::findOrFail($response->json('id'));
        $this->assertSame($b->id, $new->challenger_id);
        $this->assertSame('invited', $new->participants()->where('user_id', $a->id)->first()->status);
    }

    public function test_la_rivincita_mantiene_la_modalita_live(): void
    {
        $a = $this->createUser();
        $b = $this->createUser();
        $old = $this->completedChallenge($a, $b, ['mode' => 'live']);

        $new = Challenge::findOrFail(
            $this->actingAs($a, 'sanctum')->postJson("/api/challenges/{$old->id}/rematch")->assertCreated()->json('id')
        );

        $this->assertSame('live', $new->mode);
        $this->assertNull($new->live_started_at);
    }

    public function test_con_piu_giocatori_invita_tutti_quelli_che_hanno_giocato_e_non_chi_ha_mancato(): void
    {
        $a = $this->createUser();
        $b = $this->createUser();
        $c = $this->createUser();
        $d = $this->createUser();
        $old = $this->completedChallenge($a, $b);
        $old->participants()->create(['user_id' => $c->id, 'status' => 'accepted', 'accepted_at' => now()]);
        $old->participants()->create(['user_id' => $d->id, 'status' => 'missed']);

        $new = Challenge::findOrFail(
            $this->actingAs($a, 'sanctum')->postJson("/api/challenges/{$old->id}/rematch")->assertCreated()->json('id')
        );

        $this->assertEqualsCanonicalizing([$a->id, $b->id, $c->id], $new->participants()->pluck('user_id')->all());
    }

    public function test_non_si_puo_chiedere_la_rivincita_di_una_sfida_aperta(): void
    {
        $a = $this->createUser();
        $b = $this->createUser();
        $open = $this->completedChallenge($a, $b, ['status' => 'open']);

        $this->actingAs($a, 'sanctum')
            ->postJson("/api/challenges/{$open->id}/rematch")
            ->assertStatus(422);

        $this->assertSame(1, Challenge::count());
    }

    public function test_chi_non_partecipa_non_vede_nemmeno_la_sfida(): void
    {
        $a = $this->createUser();
        $b = $this->createUser();
        $old = $this->completedChallenge($a, $b);

        $this->actingAs($this->createUser(), 'sanctum')
            ->postJson("/api/challenges/{$old->id}/rematch")
            ->assertNotFound();
    }

    public function test_una_seconda_rivincita_con_la_stessa_persona_e_bloccata(): void
    {
        $a = $this->createUser();
        $b = $this->createUser();
        $old = $this->completedChallenge($a, $b);

        $this->actingAs($a, 'sanctum')->postJson("/api/challenges/{$old->id}/rematch")->assertCreated();

        // doppio clic o secondo tentativo: c'e' gia una sfida aperta tra i due
        $this->actingAs($a, 'sanctum')
            ->postJson("/api/challenges/{$old->id}/rematch")
            ->assertStatus(422)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'gia una sfida aperta'));

        $this->assertSame(2, Challenge::count());
    }

    public function test_chi_ha_disattivato_le_sfide_non_riceve_la_rivincita(): void
    {
        $a = $this->createUser();
        $b = $this->createUser();
        $old = $this->completedChallenge($a, $b);
        $b->forceFill(['accepts_challenges' => false])->save(); // il campo non e' fillable

        $this->actingAs($a, 'sanctum')
            ->postJson("/api/challenges/{$old->id}/rematch")
            ->assertStatus(422);

        $this->assertSame(1, Challenge::count());
    }

    public function test_senza_abbastanza_domande_la_rivincita_non_parte(): void
    {
        $a = $this->createUser();
        $b = $this->createUser();
        $old = $this->completedChallenge($a, $b);
        $this->training->update(['is_active' => false]); // pool vuoto

        $this->actingAs($a, 'sanctum')
            ->postJson("/api/challenges/{$old->id}/rematch")
            ->assertStatus(422);
    }

    public function test_rivincita_con_amico_non_registrato_crea_un_nuovo_link(): void
    {
        $a = $this->createUser();
        $old = $this->completedChallenge($a, null, ['guest_token' => 'vecchio-token', 'guest_name' => 'Marco']);

        $response = $this->actingAs($a, 'sanctum')
            ->postJson("/api/challenges/{$old->id}/rematch")
            ->assertCreated();

        $new = Challenge::findOrFail($response->json('id'));
        $this->assertNotNull($response->json('guest_url'));
        $this->assertNotSame('vecchio-token', $new->guest_token);
        $this->assertSame('Marco', $new->guest_name);
        $this->assertSame('async', $new->mode);
    }

    public function test_il_dettaglio_dice_se_la_rivincita_e_disponibile(): void
    {
        $a = $this->createUser();
        $b = $this->createUser();
        $done = $this->completedChallenge($a, $b);

        $this->actingAs($a, 'sanctum')->getJson("/api/challenges/{$done->id}")
            ->assertOk()->assertJsonPath('can_rematch', true);

        $c = $this->createUser();
        $open = $this->completedChallenge($a, $c, ['status' => 'open']);
        $this->actingAs($a, 'sanctum')->getJson("/api/challenges/{$open->id}")
            ->assertOk()->assertJsonPath('can_rematch', false);

        // chi ha mancato la sfida non puo chiedere la rivincita
        $done->participants()->where('user_id', $b->id)->update(['status' => 'missed']);
        $this->actingAs($b, 'sanctum')->getJson("/api/challenges/{$done->id}")
            ->assertOk()->assertJsonPath('can_rematch', false);
    }

    public function test_la_creazione_normale_di_una_sfida_funziona_ancora_dopo_il_refactoring(): void
    {
        $a = $this->createUser();
        $b = $this->createUser();

        $response = $this->actingAs($a, 'sanctum')
            ->postJson('/api/challenges', [
                'opponent_ids' => [$b->id],
                'category_ids' => [$this->training->training_category_id],
                'mode' => 'async',
            ])
            ->assertCreated();

        $new = Challenge::findOrFail($response->json('id'));
        $this->assertSame($a->id, $new->challenger_id);
        $this->assertSame(7, $new->questions()->count());

        // le regole di sempre: non si sfida se stessi
        $this->actingAs($a, 'sanctum')
            ->postJson('/api/challenges', ['opponent_ids' => [$a->id], 'mode' => 'async'])
            ->assertStatus(422);
    }
}
