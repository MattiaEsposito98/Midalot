<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('audio_source')->default('upload')->after('audio_path');
            $table->unsignedBigInteger('itunes_track_id')->nullable()->after('audio_source');
            $table->string('itunes_track_name')->nullable()->after('itunes_track_id');
            $table->string('itunes_artist_name')->nullable()->after('itunes_track_name');
            $table->string('itunes_preview_url')->nullable()->after('itunes_artist_name');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn([
                'audio_source',
                'itunes_track_id',
                'itunes_track_name',
                'itunes_artist_name',
                'itunes_preview_url',
            ]);
        });
    }
};
