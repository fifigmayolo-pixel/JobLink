<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->string('resume_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Will fail if any rows already have a NULL resume_path.
        Schema::table('job_applications', function (Blueprint $table) {
            $table->string('resume_path')->nullable(false)->change();
        });
    }
};