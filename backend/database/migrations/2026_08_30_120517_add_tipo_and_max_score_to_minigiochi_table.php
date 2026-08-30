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
            $table->string('tipo')->default('tastiera_rotta')->after('description');
            $table->unsignedInteger('max_score')->default(30)->after('tipo');
        });

        // Righe esistenti (es. "Tastiera Rotta" già in produzione) restano invariate:
        // tipo=tastiera_rotta e max_score=30 sono già i default della colonna.
        DB::table('minigiochi')->whereNull('tipo')->update(['tipo' => 'tastiera_rotta']);
    }

    public function down(): void
    {
        Schema::table('minigiochi', function (Blueprint $table) {
            $table->dropColumn(['tipo', 'max_score']);
        });
    }
};
