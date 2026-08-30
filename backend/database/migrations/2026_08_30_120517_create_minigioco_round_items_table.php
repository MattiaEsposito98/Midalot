<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('minigioco_round_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('minigioco_round_id')
                ->constrained('minigioco_round')
                ->cascadeOnDelete();

            // Salto Temporale: rango cronologico corretto (1-4, l'admin inserisce gli
            // elementi già in ordine corretto). Trova l'Intruso: solo ordine di visualizzazione.
            $table->unsignedTinyInteger('ordine');

            $table->string('label')->nullable();
            $table->string('image_path')->nullable();

            // Usato solo da Trova l'Intruso: true su esattamente 1 dei 4 item del round.
            $table->boolean('is_intruso')->default(false);

            $table->timestamps();

            $table->index('minigioco_round_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('minigioco_round_items');
    }
};
