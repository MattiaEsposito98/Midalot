<?php

namespace Tests\Support;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\TrainingCategory;
use App\Models\User;

trait CreatesQuizData
{
    protected function createAdmin(array $attributes = []): User
    {
        return User::factory()->create([
            'is_admin' => true,
            ...$attributes,
        ]);
    }

    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create([
            'is_admin' => false,
            ...$attributes,
        ]);
    }

    protected function createAssignedQuiz(array $attributes = []): Quiz
    {
        $adminId = $attributes['created_by'] ?? $this->createAdmin()->id;

        return Quiz::create([
            'title' => 'Assigned Quiz',
            'description' => 'Assigned quiz description',
            'type' => 'assigned',
            'created_by' => $adminId,
            'is_active' => $attributes['is_active'] ?? true,
            'leaderboard_visible' => $attributes['leaderboard_visible'] ?? true,
            'restrict_to_specific_users' => $attributes['restrict_to_specific_users'] ?? false,
        ]);
    }

    protected function createTrainingQuiz(int $questionsCount = 5, array $attributes = []): Quiz
    {
        $category = $attributes['category'] ?? TrainingCategory::create([
            'name' => 'Anime',
            'slug' => 'anime',
            'description' => 'Training anime',
            'is_active' => true,
        ]);

        $quiz = Quiz::create([
            'title' => $attributes['title'] ?? 'Training Anime',
            'description' => $attributes['description'] ?? 'Training quiz description',
            'type' => 'training',
            'training_category_id' => $category->id,
            'training_question_mode' => $attributes['training_question_mode'] ?? '5',
            'created_by' => $attributes['created_by'] ?? $this->createAdmin()->id,
            'is_active' => $attributes['is_active'] ?? true,
            'leaderboard_visible' => true,
        ]);

        $this->addQuestions($quiz, $questionsCount);

        return $quiz->fresh();
    }

    protected function addQuestions(Quiz $quiz, int $count = 1): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $question = Question::create([
                'quiz_id' => $quiz->id,
                'question_text' => "Question {$i}",
                'time_limit_seconds' => 30,
            ]);

            Answer::create([
                'question_id' => $question->id,
                'answer_text' => 'Correct',
                'is_correct' => true,
            ]);

            foreach (['Wrong 1', 'Wrong 2', 'Wrong 3'] as $answerText) {
                Answer::create([
                    'question_id' => $question->id,
                    'answer_text' => $answerText,
                    'is_correct' => false,
                ]);
            }
        }
    }
}
