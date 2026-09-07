<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Named "job_postings" rather than "jobs" — Laravel's own queue system
     * already owns a "jobs" table (0001_01_01_000002_create_jobs_table.php).
     * Leave that file alone; it is unrelated to job vacancies.
     */
    public function up(): void
    {
        Schema::create('job_postings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->text('qualifications');
            $table->text('requirements');
            $table->string('location');
            $table->date('date_posted');
            $table->date('application_deadline');
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->foreignId('posted_by')->constrained('users');
            $table->foreignId('company_id')->constrained('companies');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
};