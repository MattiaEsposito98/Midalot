<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesQuizData;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use CreatesQuizData;
    use RefreshDatabase;

    public function test_admin_confirm_password_screen_can_be_rendered(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get('/confirm-password');

        $response->assertStatus(200);
    }

    public function test_normal_user_cannot_render_backend_confirm_password_screen(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->get('/confirm-password');

        $response->assertForbidden();
    }

    public function test_admin_password_can_be_confirmed(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post('/confirm-password', [
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }
}
