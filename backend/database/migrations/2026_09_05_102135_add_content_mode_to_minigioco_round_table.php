<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('minigioco_round', function (Blueprint $table) {
            $table->string('content_mode')->default('testo')->after('shift');
        });
    }

    public function down(): void
    {
        Schema::table('minigioco_round', function (Blueprint $table) {
            $table->dropColumn('content_mode');
        });
    }
};
