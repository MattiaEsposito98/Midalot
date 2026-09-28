<?php

namespace Tests\Feature\Api;

use App\Mail\MidalarioReminderMail;
use App\Models\Quiz;
use App\Models\QuizParticipant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\Support\CreatesQuizData;
use Tests\TestCase;

class MidalarioReminderTest extends TestCase
{
    use CreatesQuizData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Carbon::setTestNow('2026-10-04 20:15');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function createMidalario(string $scheduledAt, string $status = 'open', int $iscritti = 2): Quiz
    {
        $quiz = Quiz::create([
            'title' => 'Midalario di prova',
            'type' => 'midalario',
            'created_by' => $this->createAdmin()->id,
            'is_active' => true,
            'leaderboard_visible' => true,
            'midalario_status' => $status,
            'midalario_scheduled_at' => $scheduledAt,
        ]);

        for ($i = 0; $i < $iscritti; $i++) {
            QuizParticipant::create(['quiz_id' => $quiz->id, 'user_id' => $this->createUser()->id]);
        }

        return $quiz;
    }

    public function test_invia_il_promemoria_a_tutti_gli_iscritti_entro_unora_dallinizio(): void
    {
        $quiz = $this->createMidalario('2026-10-04 21:00');

        $this->artisan('app:send-midalario-reminders')->assertExitCode(0);

        Mail::assertSent(MidalarioReminderMail::class, 2);
        Mail::assertSent(MidalarioReminderMail::class, fn ($mail) => $mail->roomUrl === rtrim(config('app.frontend_url'), '/')."/midalario/{$quiz->id}");
        $this->assertTrue($quiz->fresh()->midalario_reminder_sent_for->eq($quiz->midalario_scheduled_at));
    }

    public function test_non_invia_due_volte_lo_stesso_promemoria(): void
    {
        $this->createMidalario('2026-10-04 21:00');

        $this->artisan('app:send-midalario-reminders');
        $this->artisan('app:send-midalario-reminders');

        Mail::assertSent(MidalarioReminderMail::class, 2);
    }

    public function test_se_lorario_cambia_il_promemoria_riparte_per_il_nuovo_orario(): void
    {
        $quiz = $this->createMidalario('2026-10-04 21:00', iscritti: 1);
        $this->artisan('app:send-midalario-reminders');

        $quiz->update(['midalario_scheduled_at' => '2026-10-04 23:00']);

        Carbon::setTestNow('2026-10-04 21:30');
        $this->artisan('app:send-midalario-reminders');
        Mail::assertSent(MidalarioReminderMail::class, 1);

        Carbon::setTestNow('2026-10-04 22:05');
        $this->artisan('app:send-midalario-reminders');
        Mail::assertSent(MidalarioReminderMail::class, 2);
    }

    public function test_non_invia_per_midalario_troppo_lontani_passati_o_gia_partiti(): void
    {
        $this->createMidalario('2026-10-04 22:00');
        $this->createMidalario('2026-10-04 21:00', 'running');
        $this->createMidalario('2026-10-04 21:00', 'finished');
        $this->createMidalario('2026-10-04 20:00');

        $disattivato = $this->createMidalario('2026-10-04 21:00');
        $disattivato->update(['is_active' => false]);

        $this->artisan('app:send-midalario-reminders');

        Mail::assertNothingSent();
    }

    public function test_invia_anche_a_iscrizioni_chiuse_se_il_quiz_non_e_ancora_partito(): void
    {
        $this->createMidalario('2026-10-04 20:30', 'closed', 3);

        $this->artisan('app:send-midalario-reminders');

        Mail::assertSent(MidalarioReminderMail::class, 3);
    }

    public function test_lemail_contiene_orario_link_e_istruzioni(): void
    {
        $quiz = $this->createMidalario('2026-10-04 21:00', iscritti: 1);
        $user = $quiz->participants()->first()->user;

        $html = (new MidalarioReminderMail($quiz, $user))->render();

        $this->assertStringContainsString('Domenica 4 ottobre alle 21:00', $html);
        $this->assertStringContainsString("/midalario/{$quiz->id}", $html);
        $this->assertStringContainsString($user->nickname, $html);
        $this->assertStringContainsString('in diretta', $html);
    }
}
