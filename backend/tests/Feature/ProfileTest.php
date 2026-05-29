<?php

namespace Tests\Feature;

use App\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesQuizData;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use CreatesQuizData;
    use RefreshDatabase;

    public function test_admin_profile_page_is_displayed(): void
    {
        $admin = $this->createAdmin();

        $response = $this
            ->actingAs($admin)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_normal_user_cannot_access_backend_profile(): void
    {
        $user = $this->createUser();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertForbidden();
    }

    public function test_api_profile_information_can_be_updated(): void
    {
        $user = $this->createUser();
        $city = City::create([
            'name' => 'Milano',
            'latitude' => 45.464203,
            'longitude' => 9.189982,
        ]);

        $response = $this
            ->actingAs($user, 'sanctum')
            ->putJson('/api/user/profile', [
                'name' => 'Test User',
                'phone' => '+39 333 1234567',
                'birth_date' => '1992-02-02',
                'city_id' => $city->id,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('user.name', 'Test User')
            ->assertJsonPath('user.city.id', $city->id);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Test User',
            'city_id' => $city->id,
        ]);
    }
}
