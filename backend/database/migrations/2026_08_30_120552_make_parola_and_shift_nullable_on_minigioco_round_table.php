<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('minigioco_round', function (Blueprint $table) {
            $table->string('parola_originale')->nullable()->change();
            $table->integer('shift')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('minigioco_round', function (Blueprint $table) {
            $table->string('parola_originale')->nullable(false)->change();
            $table->integer('shift')->nullable(false)->change();
        });
    }
};
