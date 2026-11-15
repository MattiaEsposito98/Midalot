<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Da dove arriva chi si iscrive (es. instagram / bio / reel-disney), per capire quali
     * canali portano utenti. Solo un'etichetta di marketing, nessun dato personale.
     * Gli utenti gia' presenti restano NULL (= diretto / non tracciato).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('signup_source', 40)->nullable();
            $table->string('signup_medium', 40)->nullable();
            $table->string('signup_campaign', 40)->nullable();
            $table->index('signup_source');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['signup_source']);
            $table->dropColumn(['signup_source', 'signup_medium', 'signup_campaign']);
        });
    }
};
