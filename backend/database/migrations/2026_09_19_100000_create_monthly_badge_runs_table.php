<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Traccia ogni assegnazione (manuale da pannello admin, o da cron/CLI
     * quando ci sarà) del premio "Vincitore del mese", un mese alla volta:
     * impedisce che lo stesso mese venga assegnato due volte, indipendentemente
     * dal fatto che quel mese abbia prodotto un vincitore o sia stato "vuoto".
     */
    public function up(): void
    {
        Schema::create('monthly_badge_runs', function (Blueprint $table) {
            $table->id();
            $table->string('month', 7)->unique(); // formato YYYY-MM
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('top_score')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_badge_runs');
    }
};
