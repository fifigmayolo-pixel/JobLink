<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'role' => 'student',
            'student_id_number' => fake()->unique()->numerify('##-####-##'),
            'password' => 'password', // 'hashed' cast on User handles hashing
            'must_change_password' => false,
            'activated_at' => now(),
        ];
    }

    public function alumni(): static
    {
        return $this->state(['role' => 'alumni']);
    }

    public function company(): static
    {
        return $this->state([
            'role' => 'company',
            'student_id_number' => null,
            'email' => fake()->unique()->safeEmail(),
        ]);
    }

    public function peso(): static
    {
        return $this->state([
            'role' => 'peso',
            'student_id_number' => null,
            'email' => fake()->unique()->safeEmail(),
        ]);
    }

    public function jobCoordinator(): static
    {
        return $this->state([
            'role' => 'job_coordinator',
            'student_id_number' => null,
            'email' => fake()->unique()->safeEmail(),
        ]);
    }
}