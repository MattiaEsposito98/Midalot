<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Premi in monete per posizione (classifica mensile, Midalario) con
        // l'avviso da mostrare all'utente. Unico per (utente, tipo, riferimento).
        Schema::create('prize_awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20); // monthly | midalario
            $table->string('ref', 40); // YYYY-MM oppure id del quiz
            $table->string('label', 150);
            $table->unsignedTinyInteger('position');
            $table->unsignedInteger('coins');
            // Quando l'utente ha visto l'avviso (null = ancora da mostrare).
            $table->timestamp('seen_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'kind', 'ref']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prize_awards');
    }
};
