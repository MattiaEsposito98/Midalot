<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->string('type')->default('assigned')->after('description');
            $table->foreignId('training_category_id')
                ->nullable()
                ->after('type')
                ->constrained('training_categories')
                ->nullOnDelete();
            $table->string('training_question_mode', 10)->nullable()->after('training_category_id');

            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropIndex(['type', 'is_active']);
            $table->dropForeign(['training_category_id']);
            $table->dropColumn(['type', 'training_category_id', 'training_question_mode']);
        });
    }
};
