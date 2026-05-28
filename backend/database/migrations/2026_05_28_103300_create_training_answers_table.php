<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_attempt_id')->constrained('training_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->foreignId('answer_id')->nullable()->constrained('answers')->nullOnDelete();
            $table->integer('time_taken');
            $table->boolean('is_correct')->default(false);
            $table->boolean('is_timeout')->default(false);
            $table->boolean('is_wrong')->default(false);
            $table->integer('score')->default(0);
            $table->timestamps();

            $table->unique(['training_attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_answers');
    }
};
