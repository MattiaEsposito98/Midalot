<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('minigioco_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('minigiochi', function (Blueprint $table) {
            $table->foreignId('minigioco_category_id')->nullable()
                ->after('tipo')
                ->constrained('minigioco_categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('minigiochi', function (Blueprint $table) {
            $table->dropConstrainedForeignId('minigioco_category_id');
        });

        Schema::dropIfExists('minigioco_categories');
    }
};
