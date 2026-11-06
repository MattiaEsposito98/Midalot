<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            // Sottocategorie scelte nel dettaglio (es. Disney > Peter Pan). Una
            // categoria scelta "in generale" sta in category_ids e include tutto.
            $table->json('subcategory_ids')->nullable()->after('category_ids');
        });
    }

    public function down(): void
    {
        Schema::table('challenges', function (Blueprint $table) {
            $table->dropColumn('subcategory_ids');
        });
    }
};
