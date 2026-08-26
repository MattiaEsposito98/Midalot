<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            // L'indice composito (quiz_id, order) è l'unico che supporta
            // la foreign key su quiz_id: prima di rimuoverlo serve un
            // indice dedicato su quiz_id, altrimenti MySQL rifiuta il drop.
            $table->index('quiz_id');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['quiz_id', 'order']);
            $table->dropColumn('order');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->integer('order')->default(1)->after('question_text');
            $table->index(['quiz_id', 'order']);
            $table->dropIndex(['quiz_id']);
        });
    }
};
