<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Opt-out dalle sfide: chi lo disattiva non compare nella ricerca.
            $table->boolean('accepts_challenges')->default(true);
        });

        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenger_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('opponent_id')->constrained('users')->cascadeOnDelete();
            // async: ognuno gioca quando puo' entro la scadenza. live: riservato
            // alla sfida in diretta (sala d'attesa + timeline del server).
            $table->string('mode', 16)->default('async');
            // open | completed | declined | expired
            $table->string('status', 16)->default('open');
            // null = tutte le categorie Training.
            $table->json('category_ids')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('winner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['opponent_id', 'status']);
            $table->index(['challenger_id', 'status']);
        });

        Schema::create('challenge_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->timestamps();

            $table->unique(['challenge_id', 'question_id']);
            $table->index('question_id');
        });

        Schema::create('challenge_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->integer('score')->default(0);
            $table->integer('total_time')->default(0);
            $table->unsignedTinyInteger('correct_answers')->default(0);
            $table->boolean('completed')->default(false);
            $table->timestamps();

            $table->unique(['challenge_id', 'user_id']);
        });

        Schema::create('challenge_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('answer_id')->nullable()->constrained('answers')->nullOnDelete();
            $table->integer('time_taken');
            $table->boolean('is_correct')->default(false);
            $table->boolean('is_timeout')->default(false);
            $table->boolean('is_wrong')->default(false);
            $table->integer('score')->default(0);
            $table->timestamps();

            $table->unique(['challenge_attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenge_answers');
        Schema::dropIfExists('challenge_attempts');
        Schema::dropIfExists('challenge_questions');
        Schema::dropIfExists('challenges');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('accepts_challenges');
        });
    }
};
