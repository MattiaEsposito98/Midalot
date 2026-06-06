<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('training_answers')->delete();
        DB::table('training_attempts')->delete();
    }

    public function down(): void
    {
        // Deleted legacy training results cannot be restored.
    }
};
