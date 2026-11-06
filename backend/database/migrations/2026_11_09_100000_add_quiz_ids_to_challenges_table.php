<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            // Training scelti uno per uno (es. Disney > Peter Pan, Geografia > Capitali d'Europa).
            $table->json('quiz_ids')->nullable()->after('subcategory_ids');
        });
    }

    public function down(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            $table->dropColumn('quiz_ids');
        });
    }
};
