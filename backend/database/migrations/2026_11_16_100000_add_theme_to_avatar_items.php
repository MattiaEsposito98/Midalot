<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Oggetti a tema (config/avatar.php -> themes): ogni oggetto puo' far parte
 * di un tema; chi li possiede tutti riceve il titolo del set completo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avatar_items', function (Blueprint $table) {
            $table->string('theme', 40)->nullable()->after('slot')->index();
        });
    }

    public function down(): void
    {
        Schema::table('avatar_items', function (Blueprint $table) {
            $table->dropColumn('theme');
        });
    }
};
