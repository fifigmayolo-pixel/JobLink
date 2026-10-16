<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('student_alumni_profiles', 'year_graduated')) {
            return;
        }

        // Driver-agnostic (MySQL, PostgreSQL, SQLite): Laravel generates the
        // right ALTER for each, so no raw "ALTER TABLE ... MODIFY" is needed.
        // year_graduated holds academic-year ranges such as "2025-2026".
        Schema::table('student_alumni_profiles', function (Blueprint $table) {
            $table->string('year_graduated', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        // Intentionally a no-op: values like "2025-2026" cannot be cast back
        // to a year/integer column without losing data.
    }
};