<?php

namespace Tests\Feature\Api;

use App\Models\Minigioco;
use App\Models\MinigiocoAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\CreatesQuizData;
use Tests\TestCase;

class DisponibilitaTest extends TestCase
{
    use CreatesQuizData;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createMinigioco(?string $disponibileFinoA): Minigioco
    {
        return Minigioco::create([
            'title' => 'Minigioco settimanale',
            'tipo' => 'vero_falso',
            'created_by' => $this->createAdmin()->id,
            'is_active' => true,
            'leaderboard_visible' => true,
            'disponibile_fino_a' => $disponibileFinoA,
        ]);
    }

    public function test_un_minigioco_scaduto_resta_in_lista_come_terminato_ma_non_si_puo_avviare(): void
    {
        Carbon::setTestNow('2026-10-05 10:00');
        $minigioco = $this->createMinigioco('2026-10-04 23:59');
        $utente = $this->createUser();

        $lista = $this->actingAs($utente, 'sanctum')->getJson('/api/my-minigiochi');

        $lista->assertOk();
        $lista->assertJsonPath('minigiochi.0.status', 'expired');
        $lista->assertJsonPath('minigiochi.0.archived', false);

        $this->actingAs($utente, 'sanctum')
            ->postJson("/api/minigiochi/{$minigioco->id}/start")
            ->assertForbidden();

        $this->assertSame(0, MinigiocoAttempt::count());
    }

    public function test_prima_della_scadenza_il_minigioco_si_puo_avviare(): void
    {
        Carbon::setTestNow('2026-10-04 23:58');
        $minigioco = $this->createMinigioco('2026-10-04 23:59');

        $this->actingAs($this->createUser(), 'sanctum')
            ->postJson("/api/minigiochi/{$minigioco->id}/start")
            ->assertCreated();
    }

    public function test_chi_lo_ha_completato_lo_vede_completato_anche_dopo_la_scadenza(): void
    {
        Carbon::setTestNow('2026-10-05 10:00');
        $minigioco = $this->createMinigioco('2026-10-04 23:59');
        $utente = $this->createUser();

        MinigiocoAttempt::create([
            'minigioco_id' => $minigioco->id,
            'user_id' => $utente->id,
            'started_at' => '2026-10-03 18:00',
            'finished_at' => '2026-10-03 18:05',
            'completed' => true,
            'score' => 4200,
        ]);

        $this->actingAs($utente, 'sanctum')
            ->getJson('/api/my-minigiochi')
            ->assertJsonPath('minigiochi.0.status', 'completed')
            ->assertJsonPath('minigiochi.0.score', 4200);
    }

    public function test_dal_mese_successivo_il_contenuto_scaduto_viene_archiviato(): void
    {
        $this->createMinigioco('2026-09-30 23:59');
        $utente = $this->createUser();

        Carbon::setTestNow('2026-09-30 23:00');
        $this->actingAs($utente, 'sanctum')
            ->getJson('/api/my-minigiochi')
            ->assertJsonPath('minigiochi.0.archived', false);

        Carbon::setTestNow('2026-10-01 00:05');
        $this->actingAs($utente, 'sanctum')
            ->getJson('/api/my-minigiochi')
            ->assertJsonPath('minigiochi.0.archived', true);
    }

    public function test_un_quiz_one_shot_scaduto_non_si_puo_avviare_ma_resta_attivo_per_lo_storico(): void
    {
        Carbon::setTestNow('2026-10-05 10:00');
        $quiz = $this->createAssignedQuiz();
        $quiz->update(['disponibile_fino_a' => '2026-10-04 23:59']);
        $utente = $this->createUser();

        $this->actingAs($utente, 'sanctum')
            ->getJson('/api/my-quizzes')
            ->assertJsonPath('quizzes.0.status', 'expired')
            ->assertJsonPath('quizzes.0.is_active', true);

        $this->actingAs($utente, 'sanctum')
            ->postJson("/api/quiz/{$quiz->id}/start")
            ->assertForbidden();
    }

    public function test_senza_scadenza_il_contenuto_non_scade_mai(): void
    {
        Carbon::setTestNow('2030-01-01 12:00');
        $minigioco = $this->createMinigioco(null);

        $this->assertTrue($minigioco->isPlayable());
        $this->assertFalse($minigioco->isArchived());
    }
}
