<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_participants', function (Blueprint $table) {
            // IP al momento dell'iscrizione: permette di risalire con certezza
            // all'attivita' di un utente nei log del server in caso di dispute
            // ("mi hanno buttato fuori dalla sala" ecc.), senza dover indovinare
            // l'IP incrociando gli orari a mano.
            $table->string('ip_address', 45)->nullable()->after('user_id');

            // Valorizzato al primo poll riuscito su /status con joined=true:
            // prova che il dispositivo dell'utente ha davvero caricato la sala
            // d'attesa, distinto dalla semplice iscrizione (join).
            $table->timestamp('room_entered_at')->nullable()->after('ip_address');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_participants', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'room_entered_at']);
        });
    }
};
