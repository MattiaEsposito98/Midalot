<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('minigioco_round', function (Blueprint $table) {
            $table->text('affermazione')->nullable()->after('intruso_spiegazione');
            $table->boolean('risposta_corretta')->nullable()->after('affermazione');
            $table->text('spiegazione')->nullable()->after('risposta_corretta');
        });
    }

    public function down(): void
    {
        Schema::table('minigioco_round', function (Blueprint $table) {
            $table->dropColumn(['affermazione', 'risposta_corretta', 'spiegazione']);
        });
    }
};
