<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Cornice e titolo scelti nell'avatar (key degli oggetti): si mostrano
            // accanto al ritratto e al nickname in sfide, classifiche e profilo.
            $table->string('avatar_frame', 60)->nullable()->after('avatar_url');
            $table->string('avatar_title', 60)->nullable()->after('avatar_frame');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar_frame', 'avatar_title']);
        });
    }
};
