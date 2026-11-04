<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenge_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // invited | accepted | declined | missed
            $table->string('status', 16)->default('invited');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->unique(['challenge_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });

        // Le sfide 1 contro 1 gia' esistenti diventano sfide con due partecipanti.
        $now = now();

        DB::table('challenges')->orderBy('id')->each(function ($challenge) use ($now) {
            $opponentStarted = DB::table('challenge_attempts')
                ->where('challenge_id', $challenge->id)
                ->where('user_id', $challenge->opponent_id)
                ->exists();

            DB::table('challenge_participants')->insert([
                [
                    'challenge_id' => $challenge->id,
                    'user_id' => $challenge->challenger_id,
                    'status' => 'accepted',
                    'accepted_at' => $challenge->created_at,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'challenge_id' => $challenge->id,
                    'user_id' => $challenge->opponent_id,
                    'status' => $challenge->status === 'declined'
                        ? 'declined'
                        : ($opponentStarted ? 'accepted' : 'invited'),
                    'accepted_at' => $opponentStarted ? $challenge->created_at : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenge_participants');
    }
};
