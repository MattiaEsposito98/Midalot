<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->boolean('is_timeout')->default(false)->after('is_correct');
            $table->boolean('is_wrong')->default(false)->after('is_timeout');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->dropColumn(['is_timeout', 'is_wrong']);
        });
    }
};
