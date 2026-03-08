<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_answers', function (Blueprint $table) {

            $table->id();

            $table->foreignId('attempt_id')
                ->constrained('quiz_attempts')
                ->cascadeOnDelete();

            $table->foreignId('question_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('answer_id')
                ->constrained()
                ->cascadeOnDelete();

            // tempo impiegato per rispondere
            $table->integer('time_taken');

            // risposta corretta o sbagliata
            $table->boolean('is_correct');

            // punteggio della domanda
            $table->integer('score')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_answers');
    }
};
