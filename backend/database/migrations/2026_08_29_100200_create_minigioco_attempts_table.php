<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('minigioco_attempts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('minigioco_id')
                ->constrained('minigiochi')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->integer('score')->default(0);
            $table->unsignedInteger('total_time')->default(0);

            $table->boolean('completed')->default(false);

            $table->timestamps();

            $table->unique(['minigioco_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('minigioco_attempts');
    }
};
