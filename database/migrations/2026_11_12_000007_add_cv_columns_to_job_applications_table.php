<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            // nullOnDelete: if the Coordinator removes the profile, the
            // application and its submitted CV snapshot stay.
            $table->foreignId('profile_id')->nullable()->index()
                ->constrained('student_alumni_profiles')->nullOnDelete();
            $table->jsonb('cv_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('profile_id');
            $table->dropColumn('cv_snapshot');
        });
    }
};