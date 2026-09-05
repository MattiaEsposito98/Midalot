<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_subcategories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_category_id')
                ->constrained('training_categories')
                ->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['training_category_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_subcategories');
    }
};
