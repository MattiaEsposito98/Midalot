<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Impostazioni del negozio modificabili dall'admin (sconto e numero di oggetti
 * della vetrina) e promozioni a tempo (es. saldi di Natale).
 * Senza nessuna riga valgono i valori di config/avatar.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avatar_settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('value');
            $table->timestamps();
        });

        Schema::create('avatar_promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);
            $table->unsignedTinyInteger('discount');
            // showcase = solo oggetti in vetrina, all = tutto il negozio
            $table->string('scope', 12)->default('showcase');
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avatar_promotions');
        Schema::dropIfExists('avatar_settings');
    }
};
