<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->decimal('audio_start_seconds', 6, 2)->nullable()->after('itunes_preview_url');
            $table->decimal('audio_end_seconds', 6, 2)->nullable()->after('audio_start_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['audio_start_seconds', 'audio_end_seconds']);
        });
    }
};
