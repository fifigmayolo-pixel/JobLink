<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            // Set when a student/alumni removes a finished application from
            // their own list. The row itself is never deleted: the company's
            // Applicants page and the Coordinator dashboard counts read it too.
            $table->timestamp('hidden_by_applicant_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropColumn('hidden_by_applicant_at');
        });
    }
};