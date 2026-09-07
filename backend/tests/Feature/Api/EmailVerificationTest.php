<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Copre la richiesta del 07/09/2026: cliccare un link di verifica scaduto
 * mostrava il 403 "Invalid signature" generico di Laravel, invece di una
 * pagina che spiega la scadenza (60 minuti) con un bottone per farsene
 * inviare uno nuovo. Cliccare un link gia' usato deve invece dire
 * semplicemente "account gia' verificato", non "scaduto".
 */
class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function unverifiedUser(): User
    {
        return User::factory()->create(['email_verified_at' => null]);
    }

    private function signedVerifyUrl(User $user, int $minutes = 60): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes($minutes),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );
    }

    public function test_un_link_valido_verifica_lemail_e_va_al_login(): void
    {
        $user = $this->unverifiedUser();
        $url = $this->signedVerifyUrl($user);

        $this->get($url)->assertRedirect(config('app.frontend_url').'/login?verified=1');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_un_link_scaduto_reindirizza_alla_pagina_di_scadenza(): void
    {
        $user = $this->unverifiedUser();
        $url = $this->signedVerifyUrl($user, 60);

        // Si simula il passare di piu' tempo di quello concesso alla firma.
        $this->travel(61)->minutes();

        $this->get($url)->assertRedirect(
            config('app.frontend_url')."/verifica-email?stato=scaduto&id={$user->id}"
        );

        // Non deve essere verificato: il link scaduto non deve avere effetto.
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_un_link_gia_usato_dice_semplicemente_gia_verificato(): void
    {
        $user = $this->unverifiedUser();
        $url = $this->signedVerifyUrl($user);

        // Prima volta: verifica normalmente.
        $this->get($url)->assertRedirect(config('app.frontend_url').'/login?verified=1');

        // Si simula il tempo passato oltre la scadenza della firma originale:
        // a un utente gia' verificato non deve interessare se il link e'
        // anche scaduto, il messaggio giusto e' "gia' verificato".
        $this->travel(61)->minutes();

        $this->get($url)->assertRedirect(
            config('app.frontend_url').'/verifica-email?stato=gia-verificato'
        );
    }

    public function test_un_hash_manomesso_resta_un_link_non_valido(): void
    {
        $user = $this->unverifiedUser();
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('email-diverso@example.com')]
        );

        $this->get($url)->assertForbidden();
    }

    public function test_rinvio_link_manda_una_nuova_email_se_non_verificato(): void
    {
        Mail::fake();

        $user = $this->unverifiedUser();

        $this->postJson('/api/email/verification-notification/resend', ['id' => $user->id])
            ->assertOk();

        Mail::assertSent(\App\Mail\VerifyEmailMail::class);
    }

    public function test_rinvio_link_non_manda_nulla_se_gia_verificato(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email_verified_at' => now()]);

        // Risposta identica in ogni caso: non deve rivelare lo stato dell'account.
        $this->postJson('/api/email/verification-notification/resend', ['id' => $user->id])
            ->assertOk();

        Mail::assertNothingSent();
    }

    public function test_rinvio_link_non_rivela_se_lid_esiste(): void
    {
        Mail::fake();

        $risposta = $this->postJson('/api/email/verification-notification/resend', ['id' => 999999])
            ->assertOk();

        $this->assertStringContainsString('Se l\'account esiste', $risposta->json('message'));
        Mail::assertNothingSent();
    }
}
