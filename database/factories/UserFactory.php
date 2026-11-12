<?php

namespace Database\Factories;

use App\Models\StudentAlumniProfile;
use App\Models\User;
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
            'terms_accepted_at' => now(),
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

    public function jobCoordinator(): static
    {
        return $this->state([
            'role' => 'job_coordinator',
            'student_id_number' => null,
            'email' => fake()->unique()->safeEmail(),
        ]);
    }

    /**
     * A student or alumni who has finished Complete Profile, so the
     * EnsureProfileComplete middleware lets them through.
     * Use: User::factory()->withProfile()->create()
     *      User::factory()->alumni()->withProfile()->create()
     */
    public function withProfile(): static
    {
        return $this->afterCreating(function (User $user) {
            $profile = StudentAlumniProfile::factory();

            if ($user->role === 'alumni') {
                $profile = $profile->alumni();
            }

            $profile->for($user)->create();
        });
    }
}