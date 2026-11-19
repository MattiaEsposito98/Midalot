<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Clienti da contattare": attivita' trovate su Instagram a cui proporre i servizi digitali.
     * Solo dati pubblici di attivita'. `prospect_searches` sono le operazioni di ricerca
     * (regione + settore + data) e servono anche a sapere cosa e' gia' stato fatto.
     */
    public function up(): void
    {
        Schema::create('prospect_searches', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('region', 40)->nullable()->index();
            $table->string('city', 80)->nullable();
            $table->string('sector', 60)->index();
            $table->string('status', 20)->default('planned')->index(); // planned | in_progress | done
            $table->date('searched_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('prospects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prospect_search_id')->nullable()->constrained('prospect_searches')->nullOnDelete();
            $table->string('instagram_handle', 60)->unique();
            $table->string('name', 150)->nullable();
            $table->unsignedInteger('followers')->nullable()->index();
            $table->string('region', 40)->nullable()->index();
            $table->string('city', 80)->nullable()->index();
            $table->string('sector', 60)->nullable()->index();
            $table->text('bio')->nullable();
            $table->string('website_url', 255)->nullable();
            $table->string('email', 190)->nullable();
            $table->string('phone', 40)->nullable();
            $table->boolean('has_physical_store')->nullable();
            $table->boolean('orders_in_dm')->nullable();
            $table->string('website_status', 20)->default('unknown'); // none | link_only | outdated | yes | unknown
            $table->string('activity', 20)->default('unverified');   // active | inactive | unverified
            $table->text('signals')->nullable();
            $table->string('proposal', 20)->nullable();              // vetrina | ecommerce | prenotazioni | gestionale
            $table->string('priority', 1)->default('B')->index();    // A | B | C
            $table->string('status', 20)->default('to_contact')->index();
            $table->timestamp('contacted_at')->nullable()->index();
            $table->foreignId('contacted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('follow_up_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['region', 'city']);
            $table->index(['status', 'follow_up_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospects');
        Schema::dropIfExists('prospect_searches');
    }
};
