<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contatore ANONIMO delle aperture dei link di campagna (es. midalot.live/ig/gioielleria):
     * una riga per campagna e per giorno con il numero di aperture. Nessun IP, cookie o identificativo.
     */
    public function up(): void
    {
        Schema::create('campaign_visits', function (Blueprint $table) {
            $table->id();
            $table->string('campaign', 60);
            $table->date('day');
            $table->unsignedInteger('hits')->default(0);
            $table->timestamps();

            $table->unique(['campaign', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_visits');
    }
};
