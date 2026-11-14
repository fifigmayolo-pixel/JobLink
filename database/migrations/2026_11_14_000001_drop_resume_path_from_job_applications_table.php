<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The resume upload feature is gone, and applications now carry a CV snapshot
 * (job_applications.cv_snapshot) instead of a file path.
 *
 * RUN THIS LAST. Code from before the profile-based CV still writes resume_path,
 * so do not run this against a database that the old version is still using.
 * Deploy the new version first, then migrate.
 *
 * Old PDFs in storage are not touched by this migration. See the command
 * `php artisan joblink:purge-legacy-resumes`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('job_applications', 'resume_path')) {
            Schema::table('job_applications', function (Blueprint $table) {
                $table->dropColumn('resume_path');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('job_applications', 'resume_path')) {
            Schema::table('job_applications', function (Blueprint $table) {
                $table->string('resume_path')->nullable();
            });
        }
    }
};
