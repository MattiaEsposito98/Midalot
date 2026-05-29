<?php

namespace Tests\Feature\Auth;

use App\Mail\VerifyEmailMail;
use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_backend_registration_redirects_to_react(): void
    {
        $response = $this->get('/register');

        $response->assertRedirect(rtrim(config('app.frontend_url'), '/') . '/register');
    }

    public function test_api_user_can_register_but_does_not_receive_token_before_email_verification(): void
    {
        Mail::fake();

        $city = City::create([
            'name' => 'Roma',
            'latitude' => 41.902782,
            'longitude' => 12.496366,
        ]);

        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'nickname' => 'test.user',
            'email' => 'test@example.com',
            'phone' => '+39 333 1234567',
            'password' => 'password',
            'password_confirmation' => 'password',
            'birth_date' => '1990-01-01',
            'city_id' => $city->id,
        ]);

        $response
            ->assertCreated()
            ->assertJsonMissing(['token']);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'nickname' => 'test.user',
        ]);

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertSame(0, $user->tokens()->count());
        Mail::assertSent(VerifyEmailMail::class);
    }
}
