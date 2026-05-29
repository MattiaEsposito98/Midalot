<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\CreatesQuizData;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use CreatesQuizData;
    use RefreshDatabase;

    public function test_api_password_can_be_updated_and_existing_tokens_are_revoked(): void
    {
        $user = $this->createUser();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/change-password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response->assertOk();

        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_api_current_password_must_be_correct(): void
    {
        $user = $this->createUser();

        $response = $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/change-password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response->assertUnprocessable();
    }
}
