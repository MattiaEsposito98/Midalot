<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('xp_total')->default(0);
        });

        Schema::create('xp_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source', 32);
            $table->unsignedBigInteger('source_id');
            $table->unsignedInteger('xp');
            $table->timestamps();

            // Un solo accredito per partita/giorno: rende award() idempotente.
            $table->unique(['user_id', 'source', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xp_events');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('xp_total');
        });
    }
};
