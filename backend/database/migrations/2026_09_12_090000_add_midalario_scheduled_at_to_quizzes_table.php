<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Orario previsto di inizio, mostrato ai giocatori come conto alla rovescia
 * nella sala d'attesa. NON avvia il quiz da solo: sul server non e'
 * disponibile il cron, quindi il via lo da' sempre l'amministratore.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->timestamp('midalario_scheduled_at')->nullable()->after('midalario_status');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('midalario_scheduled_at');
        });
    }
};
