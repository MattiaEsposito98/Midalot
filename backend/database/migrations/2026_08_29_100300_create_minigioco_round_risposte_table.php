<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('minigioco_round_risposte', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attempt_id')
                ->constrained('minigioco_attempts')
                ->cascadeOnDelete();

            $table->foreignId('round_id')
                ->constrained('minigioco_round')
                ->cascadeOnDelete();

            $table->string('risposta_utente')->nullable();
            $table->unsignedInteger('tentativi_falliti')->default(0);

            $table->integer('time_taken')->default(0);

            $table->boolean('is_correct')->default(false);
            $table->boolean('is_timeout')->default(false);

            $table->integer('score')->default(0);

            $table->timestamps();

            $table->unique(['attempt_id', 'round_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('minigioco_round_risposte');
    }
};
