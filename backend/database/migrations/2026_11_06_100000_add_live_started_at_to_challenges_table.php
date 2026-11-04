<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            // Istante in cui parte la prima domanda della sfida live (dopo il
            // conto alla rovescia). Null finche' lo sfidato non accetta.
            $table->timestamp('live_started_at')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            $table->dropColumn('live_started_at');
        });
    }
};
