<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesQuizData;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use CreatesQuizData;
    use RefreshDatabase;

    public function test_admin_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
    }

    public function test_admin_can_authenticate_using_the_backend_login_screen(): void
    {
        $admin = $this->createAdmin();

        $response = $this->post('/admin/login', [
            'login' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.index', absolute: false));
    }

    public function test_normal_user_is_blocked_from_backend_login(): void
    {
        $user = $this->createUser();

        $response = $this->from('/admin/login')->post('/admin/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors('login');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $admin = $this->createAdmin();

        $this->post('/admin/login', [
            'login' => $admin->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->post('/admin/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
