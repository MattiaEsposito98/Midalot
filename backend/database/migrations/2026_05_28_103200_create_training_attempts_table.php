<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->foreignId('training_category_id')->constrained('training_categories')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->json('question_ids');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->integer('score')->default(0);
            $table->integer('total_time')->nullable();
            $table->integer('correct_answers')->default(0);
            $table->integer('total_questions')->default(0);
            $table->boolean('completed')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'training_category_id', 'completed']);
            $table->index(['training_category_id', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_attempts');
    }
};
