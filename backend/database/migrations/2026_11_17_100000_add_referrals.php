<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Invita un amico: codice personale, chi ha invitato chi e registro degli
 * inviti via email (solo l'hash dell'indirizzo, per limiti e doppioni).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'referral_code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('referral_code', 16)->nullable()->unique()->after('coins');
                // Indice semplice, senza vincolo: aggiungere una foreign key ricostruisce la
                // tabella users, che ha vecchie date "0000-00-00" rifiutate da MySQL.
                $table->unsignedBigInteger('referred_by')->nullable()->after('referral_code')->index();
            });
        }

        Schema::create('invite_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('email_hash', 64);
            $table->string('kind', 20)->default('friend');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['email_hash', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invite_emails');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['referred_by']);
            $table->dropUnique(['referral_code']);
            $table->dropColumn(['referral_code', 'referred_by']);
        });
    }
};
