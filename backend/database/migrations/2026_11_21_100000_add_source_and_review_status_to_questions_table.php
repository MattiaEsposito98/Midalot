<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Domande generate con l'AI: `source` dice chi le ha scritte (admin / ai),
     * `review_status` se sono in gioco (approved) o ancora in bozza (pending).
     * Le domande gia' presenti restano "admin" + "approved" grazie ai default.
     */
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->string('source', 20)->default('admin')->after('quiz_id');
            $table->string('review_status', 20)->default('approved')->after('source');
            $table->text('ai_note')->nullable()->after('review_status');
            $table->index('review_status');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex(['review_status']);
            $table->dropColumn(['source', 'review_status', 'ai_note']);
        });
    }
};
