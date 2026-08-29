<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('minigioco_round', function (Blueprint $table) {
            $table->id();

            $table->foreignId('minigioco_id')
                ->constrained('minigiochi')
                ->cascadeOnDelete();

            $table->string('parola_originale');

            // Shift orizzontale sulla riga della tastiera: positivo = destra, negativo = sinistra
            $table->integer('shift');

            $table->integer('time_limit_seconds')->default(20);

            $table->timestamps();

            $table->index('minigioco_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('minigioco_round');
    }
};
