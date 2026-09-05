<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->foreignId('training_subcategory_id')
                ->nullable()
                ->after('training_category_id')
                ->constrained('training_subcategories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropForeign(['training_subcategory_id']);
            $table->dropColumn('training_subcategory_id');
        });
    }
};
