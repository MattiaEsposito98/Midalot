<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Ritratto dell'avatar (immagine generata dal browser quando l'utente salva):
            // si mostra a fianco del nome in sfide, classifiche e profilo.
            $table->string('avatar_url')->nullable()->after('coins');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar_url');
        });
    }
};
