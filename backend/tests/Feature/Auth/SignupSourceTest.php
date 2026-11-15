<?php

namespace Tests\Feature\Auth;

use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Support\CreatesQuizData;
use Tests\TestCase;

class SignupSourceTest extends TestCase
{
    use CreatesQuizData;
    use RefreshDatabase;

    private int $counter = 0;

    private function register(array $extra = []): User
    {
        Mail::fake();
        $this->counter++;
        $city = City::first() ?? City::create(['name' => 'Roma', 'latitude' => 41.9, 'longitude' => 12.4]);

        $this->postJson('/api/register', array_merge([
            'name' => 'Test User',
            'nickname' => "utente.prova{$this->counter}",
            'email' => "prova{$this->counter}@example.com",
            'password' => 'password',
            'password_confirmation' => 'password',
            'birth_date' => '1990-01-01',
            'city_id' => $city->id,
            'privacy_accepted' => true,
            'rules_accepted' => true,
        ], $extra))->assertCreated();

        return User::where('email', "prova{$this->counter}@example.com")->firstOrFail();
    }

    public function test_salva_l_origine_dai_parametri_utm(): void
    {
        $user = $this->register(['acq_source' => 'instagram', 'acq_medium' => 'bio', 'acq_campaign' => 'reel-disney']);

        $this->assertSame('instagram', $user->signup_source);
        $this->assertSame('bio', $user->signup_medium);
        $this->assertSame('reel-disney', $user->signup_campaign);
    }

    public function test_senza_origine_resta_non_tracciato(): void
    {
        $user = $this->register();

        $this->assertNull($user->signup_source);
        $this->assertNull($user->signup_medium);
        $this->assertNull($user->signup_campaign);
    }

    public function test_valori_strani_vengono_ripuliti_e_non_bloccano_la_registrazione(): void
    {
        $user = $this->register([
            'acq_source' => '  Instagram <script>alert(1)</script> ',
            'acq_medium' => str_repeat('a', 200),
            'acq_campaign' => ['non', 'una', 'stringa'],
        ]);

        $this->assertSame('instagramscriptalert1script', $user->signup_source);
        $this->assertSame(40, strlen($user->signup_medium));
        $this->assertNull($user->signup_campaign);
    }

    public function test_solo_simboli_diventano_nessuna_origine(): void
    {
        $user = $this->register(['acq_source' => '!!! ???']);

        $this->assertNull($user->signup_source);
    }

    public function test_un_invito_amico_vale_piu_del_parametro_del_link(): void
    {
        $user = $this->register(['ref' => 'CODICE-A-CASO', 'acq_source' => 'instagram']);

        $this->assertSame('invito_amico', $user->signup_source);
        $this->assertSame('link', $user->signup_medium);
    }

    public function test_una_sfida_a_un_amico_vale_piu_di_tutto(): void
    {
        $user = $this->register(['guest_token' => 'token-finto', 'guest_key' => 'chiave-finta', 'ref' => 'X', 'acq_source' => 'instagram']);

        $this->assertSame('sfida_amico', $user->signup_source);
        $this->assertSame('ospite', $user->signup_medium);
    }

    public function test_la_dashboard_admin_mostra_le_iscrizioni_per_origine(): void
    {
        $this->register(['acq_source' => 'instagram', 'acq_medium' => 'bio']);
        $this->register(['acq_source' => 'instagram', 'acq_medium' => 'bio']);
        $this->register(['acq_source' => 'tiktok', 'acq_medium' => 'social']);
        $this->register();

        $response = $this->actingAs($this->createAdmin())
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Iscrizioni per origine')
            ->assertSee('instagram')
            ->assertSee('tiktok')
            ->assertSee('Diretto / non tracciato');

        $rows = $response->viewData('signupSources');
        $this->assertSame(2, (int) $rows->firstWhere('signup_source', 'instagram')->total);
        $this->assertSame(2, (int) $rows->firstWhere('signup_source', 'instagram')->recent);
        $this->assertSame(1, (int) $rows->firstWhere('signup_source', null)->total);
    }
}
