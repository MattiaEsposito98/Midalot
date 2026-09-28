<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            // Orario per cui e' partito il promemoria: se l'admin sposta il Midalario, il promemoria riparte per il nuovo orario.
            $table->dateTime('midalario_reminder_sent_for')->nullable()->after('midalario_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('midalario_reminder_sent_for');
        });
    }
};
