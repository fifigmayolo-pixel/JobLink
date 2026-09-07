<?php

namespace Database\Factories;

use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobApplicationFactory extends Factory
{
    protected $model = JobApplication::class;

    public function definition(): array
    {
        return [
            'job_posting_id' => JobPosting::factory(),
            'applicant_id' => User::factory(),
            'resume_path' => 'resumes/fake-resume.pdf',
            'date_applied' => now(),
            'status' => 'pending',
        ];
    }
}