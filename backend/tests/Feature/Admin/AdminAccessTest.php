<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesQuizData;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use CreatesQuizData;
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_area(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_normal_user_is_forbidden_from_admin_area(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->get('/admin')->assertOk();
    }
}
