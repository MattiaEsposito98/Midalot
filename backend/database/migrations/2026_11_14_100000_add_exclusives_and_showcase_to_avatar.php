<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Oggetti esclusivi (in vendita solo quando l'admin li apre, con scadenza)
 * e scelte manuali della vetrina settimanale.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avatar_items', function (Blueprint $table) {
            $table->boolean('is_exclusive')->default(false)->after('shop_min_level');
            $table->boolean('exclusive_open')->default(false)->after('is_exclusive');
            $table->dateTime('exclusive_until')->nullable()->after('exclusive_open');
        });

        Schema::create('avatar_showcase_picks', function (Blueprint $table) {
            $table->id();
            $table->date('week_start');
            $table->foreignId('avatar_item_id')->constrained('avatar_items')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['week_start', 'avatar_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avatar_showcase_picks');

        Schema::table('avatar_items', function (Blueprint $table) {
            $table->dropColumn(['is_exclusive', 'exclusive_open', 'exclusive_until']);
        });
    }
};
