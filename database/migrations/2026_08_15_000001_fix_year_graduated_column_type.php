<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Raw ALTER rather than Schema::table()->change(), which needs
        // doctrine/dbal — this works regardless of whether that's installed.
        DB::statement('ALTER TABLE student_alumni_profiles MODIFY year_graduated VARCHAR(20) NULL');
    }

    public function down(): void
    {
        // No safe automatic reversal — going back to a numeric type would
        // truncate any "2025-2026"-style values already saved by then.
    }
};