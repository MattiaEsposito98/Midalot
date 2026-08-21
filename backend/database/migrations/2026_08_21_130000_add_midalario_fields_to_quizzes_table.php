<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->string('midalario_status')->nullable()->after('training_question_mode');
            $table->timestamp('midalario_started_at')->nullable()->after('midalario_status');
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            $table->dropColumn(['midalario_status', 'midalario_started_at']);
        });
    }
};
