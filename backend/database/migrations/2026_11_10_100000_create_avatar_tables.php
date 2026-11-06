<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catalogo degli oggetti dell'avatar. La grafica sta nel frontend (per `key`),
        // qui ci sono le regole: quando si sblocca, quanto costa, se e' attivo.
        Schema::create('avatar_items', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->string('name', 100);
            $table->string('slot', 20);
            $table->string('rarity', 20)->default('comune');
            // Livello a cui si riceve in regalo (null = non si riceve salendo di livello).
            $table->unsignedSmallInteger('unlock_level')->nullable()->index();
            // Prezzo nel negozio in monete (null = non in vendita).
            $table->unsignedInteger('price')->nullable();
            // Livello minimo per poterlo comprare.
            $table->unsignedSmallInteger('shop_min_level')->default(1);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Oggetti posseduti.
        Schema::create('user_avatar_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('avatar_item_id')->constrained()->cascadeOnDelete();
            $table->string('source', 20); // level | shop | admin
            // Quando l'utente ha visto l'avviso di sblocco (null = ancora da mostrare).
            $table->timestamp('seen_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'avatar_item_id']);
        });

        // Aspetto e oggetti indossati.
        Schema::create('user_avatars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('config');
            $table->timestamps();
        });

        // Movimenti di monete: uno solo per (utente, fonte, id fonte), come gli XP.
        Schema::create('coin_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('source', 30);
            $table->unsignedBigInteger('source_id')->default(0);
            $table->integer('amount');
            $table->timestamps();

            $table->unique(['user_id', 'source', 'source_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('coins')->default(0)->after('xp_total');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('coins');
        });

        Schema::dropIfExists('coin_events');
        Schema::dropIfExists('user_avatars');
        Schema::dropIfExists('user_avatar_items');
        Schema::dropIfExists('avatar_items');
    }
};
