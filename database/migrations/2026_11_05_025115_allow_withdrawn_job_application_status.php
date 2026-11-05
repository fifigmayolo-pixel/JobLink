<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // On PostgreSQL (Supabase) an enum column is a varchar with a CHECK
        // constraint, and changing the column type does not remove it. Drop it
        // first, otherwise 'withdrawn' would be rejected.
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
DO $$
DECLARE r record;
BEGIN
    FOR r IN
        SELECT conname FROM pg_constraint
        WHERE conrelid = 'job_applications'::regclass
          AND contype = 'c'
          AND pg_get_constraintdef(oid) ILIKE '%status%'
    LOOP
        EXECUTE format('ALTER TABLE job_applications DROP CONSTRAINT %I', r.conname);
    END LOOP;
END $$;
SQL);
        }

        // A plain string, so a new status never needs another migration.
        Schema::table('job_applications', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->change();
        });
    }

    public function down(): void
    {
        // Intentionally left as a string column: restoring the enum would fail
        // if any 'withdrawn' rows exist.
    }
};