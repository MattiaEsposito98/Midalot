<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'nickname')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('nickname')->unique();
            });

            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->string('nickname')->nullable(false)->change();
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('nickname');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['nickname']);
        });
    }
};
