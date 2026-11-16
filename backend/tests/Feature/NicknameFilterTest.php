<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\User;
use App\Support\NicknameFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\CreatesQuizData;
use Tests\TestCase;

class NicknameFilterTest extends TestCase
{
    use CreatesQuizData;
    use RefreshDatabase;

    public function test_blocca_parolacce_anche_camuffate(): void
    {
        $blocked = [
            'st_oca_zzo', 'st0c4zz0', 'stocazzzo', 'STOCAZZO', 'cazzi.miei', 'merda99', 'vaffanculo',
            'figlio_di_puttana', 'z0cc0la', 'minchia.rossi', 'coglione', 'f.i.g.a', 'mario_pene',
            'sborra', 'hitler88', 'fuck_you', 'sesso', 'nigger', 'scunthorpe.cunt',
        ];

        foreach ($blocked as $nickname) {
            $this->assertFalse(NicknameFilter::isAllowed($nickname), "Doveva essere bloccato: {$nickname}");
        }
    }

    public function test_blocca_le_bestemmie_in_ogni_ordine(): void
    {
        foreach (['porcodio', 'porco.dio', 'p0rcod1o', 'porcoddio', 'diocane', 'dio_cane', 'porcamadonna', 'madonna_troia', 'cristo.porco'] as $nickname) {
            $this->assertFalse(NicknameFilter::isAllowed($nickname), "Doveva essere bloccato: {$nickname}");
        }
    }

    public function test_blocca_chi_si_spaccia_per_lo_staff(): void
    {
        foreach (['admin', 'admin_mario', 'moderatore', 'staff.midalot', 'midalot_ufficiale'] as $nickname) {
            $this->assertFalse(NicknameFilter::isAllowed($nickname), "Doveva essere bloccato: {$nickname}");
        }
    }

    public function test_non_blocca_nomi_innocenti_che_contengono_pezzi_di_parole(): void
    {
        $allowed = [
            'penelope', 'figaro', 'nazionale', 'badminton', 'roberto_65', 'ilaria.rossi', 'cassandra',
            'scunthorpe', 'nigeria', 'diogene', 'madonnina', 'cristoforo', 'sbornia', 'sesto', 'dicembre',
            'lalla83', 'serena.venanzio', 'tizy71', 'debss', 'cecilia', 'pomodoro', 'massimo', 'mario.rossi',
        ];

        foreach ($allowed as $nickname) {
            $this->assertTrue(NicknameFilter::isAllowed($nickname), "Non doveva essere bloccato: {$nickname} (".NicknameFilter::reason($nickname).')');
        }
    }

    public function test_la_registrazione_rifiuta_un_nickname_vietato(): void
    {
        Mail::fake();
        $city = City::create(['name' => 'Roma', 'latitude' => 41.9, 'longitude' => 12.4]);

        $this->postJson('/api/register', [
            'name' => 'Prova Prova',
            'nickname' => 'st_oca_zzo',
            'email' => 'prova@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'birth_date' => '1990-01-01',
            'city_id' => $city->id,
            'privacy_accepted' => true,
            'rules_accepted' => true,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nickname'])
            ->assertJsonPath('errors.nickname.0', 'Questo nickname non è consentito: scegline un altro.');

        $this->assertDatabaseMissing('users', ['email' => 'prova@example.com']);
    }

    public function test_il_nome_di_un_amico_invitato_non_puo_essere_offensivo(): void
    {
        $this->createTrainingQuiz(10);

        $this->actingAs($this->createUser(), 'sanctum')
            ->postJson('/api/challenges', ['guest' => true, 'guest_name' => 'porco dio'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['guest_name']);
    }

    public function test_l_admin_vede_l_avviso_e_la_proposta_ricavata_dall_email(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser(['nickname' => 'st_oca_zzo', 'email' => 'Mario.Rossi+quiz@example.com']);

        $this->actingAs($admin)
            ->get(route('admin.users.show', $user))
            ->assertOk()
            ->assertSee('Nickname non consentito')
            ->assertSee('mario.rossi.quiz');
    }

    public function test_la_proposta_evita_i_nickname_gia_presi(): void
    {
        $this->createUser(['nickname' => 'mario.rossi']);
        $user = $this->createUser(['nickname' => 'st_oca_zzo', 'email' => 'mario.rossi@example.com']);

        $this->assertSame('mario.rossi2', \App\Http\Controllers\Admin\UserController::suggestNickname($user));
    }

    public function test_l_admin_cambia_il_nickname(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser(['nickname' => 'st_oca_zzo']);

        $this->actingAs($admin)
            ->patch(route('admin.users.nickname.update', $user), ['nickname' => ' Mario.Rossi '])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('mario.rossi', $user->fresh()->nickname);
    }

    public function test_l_admin_non_puo_mettere_un_nickname_vietato_o_gia_preso(): void
    {
        $admin = $this->createAdmin();
        $this->createUser(['nickname' => 'occupato']);
        $user = $this->createUser(['nickname' => 'st_oca_zzo']);

        $this->actingAs($admin)
            ->patch(route('admin.users.nickname.update', $user), ['nickname' => 'diocane'])
            ->assertSessionHasErrors('nickname');

        $this->actingAs($admin)
            ->patch(route('admin.users.nickname.update', $user), ['nickname' => 'occupato'])
            ->assertSessionHasErrors('nickname');

        $this->actingAs($admin)
            ->patch(route('admin.users.nickname.update', $user), ['nickname' => 'con spazi'])
            ->assertSessionHasErrors('nickname');

        $this->assertSame('st_oca_zzo', $user->fresh()->nickname);
    }

    public function test_un_utente_normale_non_puo_cambiare_nickname_agli_altri(): void
    {
        $user = $this->createUser(['nickname' => 'st_oca_zzo']);

        $this->actingAs($this->createUser())
            ->patch(route('admin.users.nickname.update', $user), ['nickname' => 'mario.rossi'])
            ->assertStatus(403);

        $this->assertSame('st_oca_zzo', $user->fresh()->nickname);
    }
}
