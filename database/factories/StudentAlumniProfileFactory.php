<?php

namespace Database\Factories;

use App\Models\StudentAlumniProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentAlumniProfileFactory extends Factory
{
    protected $model = StudentAlumniProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => 'student',
            'full_name' => fake()->name(),
            'date_of_birth' => fake()->dateTimeBetween('-25 years', '-18 years'),
            'age' => fake()->numberBetween(18, 25),
            'gender' => fake()->randomElement(['Male', 'Female']),
            'civil_status' => 'Single',
            'nationality' => 'Filipino',
            'course_program' => 'BS Information Technology',
            'year_level' => '4th Year',
            'section' => 'A',
            'personal_email' => fake()->unique()->safeEmail(),
            'mobile_number' => fake()->numerify('09#########'),
            'house_street' => fake()->streetAddress(),
            'barangay' => fake()->word(),
            'municipality_city' => 'Alaminos City',
            'province' => 'Pangasinan',
            'postal_code' => '2404',
            'profile_completed_at' => now(),
        ];
    }

    public function alumni(): static
    {
        return $this->state(fn () => [
            'status' => 'alumni',
            'year_level' => null,
            'section' => null,
            'year_graduated' => fake()->numberBetween(2018, 2025),
        ]);
    }
}