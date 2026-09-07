<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->company(),
            'company_name' => fake()->company(),
            'industry' => fake()->randomElement(['IT', 'BPO', 'Manufacturing', 'Retail']),
            'contact_person' => fake()->name(),
            'contact_number' => fake()->numerify('09#########'),
            'address' => fake()->address(),
            'created_by' => User::factory()->coordinator(),
        ];
    }
}