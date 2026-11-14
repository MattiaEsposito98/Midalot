<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Le richieste arrivano da due pagine: quiz per eventi (/crea-il-tuo-evento)
        // e servizi digitali (/servizi-digitali). Le vecchie sono tutte di eventi.
        Schema::table('event_requests', function (Blueprint $table) {
            $table->string('kind', 20)->default('evento')->after('user_id');
            $table->index('kind');
        });
    }

    public function down(): void
    {
        Schema::table('event_requests', function (Blueprint $table) {
            $table->dropIndex(['kind']);
            $table->dropColumn('kind');
        });
    }
};
