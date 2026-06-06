<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('quiz_answers')->delete();
        DB::table('quiz_attempts')->delete();
    }

    public function down(): void
    {
        // Deleted legacy quiz results cannot be restored.
    }
};
