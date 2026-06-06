<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesQuizData;
use Tests\TestCase;

class QuizUserManagementTest extends TestCase
{
    use CreatesQuizData;
    use RefreshDatabase;

    public function test_admin_can_open_user_assignment_page_with_show_all_option(): void
    {
        $admin = $this->createAdmin();
        $quiz = $this->createAssignedQuiz(['created_by' => $admin->id]);

        $this
            ->actingAs($admin)
            ->get(route('admin.quizzes.users', $quiz))
            ->assertOk()
            ->assertSee('Mostra tutti gli utenti');
    }

    public function test_show_all_returns_available_users_and_excludes_attached_users(): void
    {
        $admin = $this->createAdmin();
        $quiz = $this->createAssignedQuiz(['created_by' => $admin->id]);
        $availableUser = $this->createUser(['nickname' => 'Disponibile']);
        $attachedUser = $this->createUser(['nickname' => 'Associato']);
        $quiz->users()->attach($attachedUser);

        $response = $this
            ->actingAs($admin)
            ->getJson(route('admin.quizzes.users.search', [
                'quiz' => $quiz,
                'all' => 1,
            ]))
            ->assertOk();

        $ids = collect($response->json())->pluck('id');

        $this->assertTrue($ids->contains($availableUser->id));
        $this->assertFalse($ids->contains($attachedUser->id));
        $this->assertFalse($ids->contains($admin->id));
    }

    public function test_user_search_still_filters_by_nickname_or_email(): void
    {
        $admin = $this->createAdmin();
        $quiz = $this->createAssignedQuiz(['created_by' => $admin->id]);
        $matchedUser = $this->createUser(['nickname' => 'MarioQuiz']);
        $this->createUser(['nickname' => 'UtenteDiverso']);

        $response = $this
            ->actingAs($admin)
            ->getJson(route('admin.quizzes.users.search', [
                'quiz' => $quiz,
                'q' => 'Mario',
            ]))
            ->assertOk();

        $ids = collect($response->json())->pluck('id');

        $this->assertTrue($ids->contains($matchedUser->id));
        $this->assertCount(1, $ids);
    }
}
