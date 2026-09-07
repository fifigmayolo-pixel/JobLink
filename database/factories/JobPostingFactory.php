<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\JobPosting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobPostingFactory extends Factory
{
    protected $model = JobPosting::class;

    public function definition(): array
    {
        return [
            'title' => fake()->jobTitle(),
            'description' => fake()->paragraph(),
            'qualifications' => fake()->paragraph(),
            'requirements' => fake()->paragraph(),
            'location' => 'Alaminos City, Pangasinan',
            'date_posted' => now(),
            'application_deadline' => now()->addWeeks(3),
            'status' => 'open',
            'posted_by' => User::factory()->coordinator(),
            'company_id' => Company::factory(),
        ];
    }

    public function closed(): static
    {
        return $this->state(['status' => 'closed']);
    }

    public function expired(): static
    {
        return $this->state(['application_deadline' => now()->subDay()]);
    }
}