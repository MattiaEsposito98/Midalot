<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sfida a un amico non registrato: quando il sito gli ha mandato l'invito via
 * email (con WhatsApp o "copia link" non possiamo saperlo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            $table->timestamp('guest_invited_at')->nullable()->after('guest_name');
        });
    }

    public function down(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            $table->dropColumn('guest_invited_at');
        });
    }
};
