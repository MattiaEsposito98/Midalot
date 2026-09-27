<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('minigiochi', function (Blueprint $table) {
            $table->dateTime('disponibile_fino_a')->nullable()->after('is_active');
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->dateTime('disponibile_fino_a')->nullable()->after('is_active');
        });

        // I contenuti esistenti chiudono con settembre: dal 1/10 restano solo quelli nuovi a scadenza settimanale.
        DB::table('minigiochi')->update(['disponibile_fino_a' => '2026-09-30 23:59:00']);
        DB::table('quizzes')->where('type', 'assigned')->update(['disponibile_fino_a' => '2026-09-30 23:59:00']);
    }

    public function down(): void
    {
        Schema::table('minigiochi', function (Blueprint $table) {
            $table->dropColumn('disponibile_fino_a');
        });

        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn('disponibile_fino_a');
        });
    }
};
