<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sfida a un amico non registrato: link con token, partita da ospite salvata
 * a parte e collegata al suo account quando si registra.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            $table->string('guest_token', 40)->nullable()->unique()->after('winner_id');
            $table->string('guest_name', 40)->nullable()->after('guest_token');
        });

        Schema::create('challenge_guest_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_id')->constrained()->cascadeOnDelete();
            $table->string('guest_key', 40)->unique();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->integer('score')->default(0);
            $table->unsignedInteger('correct_answers')->default(0);
            $table->unsignedInteger('total_time')->default(0);
            $table->json('answers')->nullable();
            $table->unsignedBigInteger('claimed_by')->nullable()->index();
            $table->timestamps();

            $table->index('challenge_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenge_guest_attempts');

        Schema::table('challenges', function (Blueprint $table) {
            $table->dropUnique(['guest_token']);
            $table->dropColumn(['guest_token', 'guest_name']);
        });
    }
};
