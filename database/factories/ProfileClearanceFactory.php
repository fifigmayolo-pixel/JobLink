<?php

namespace Database\Factories;

use App\Models\ProfileClearance;
use App\Models\StudentAlumniProfile;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProfileClearance>
 */
class ProfileClearanceFactory extends Factory
{
    protected $model = ProfileClearance::class;

    /**
     * A valid clearance. The path points nowhere unless a test puts a file there,
     * which is enough for the apply check (it only reads the row).
     */
    public function definition(): array
    {
        return [
            'profile_id' => StudentAlumniProfile::factory(),
            'type' => 'nbi',
            'file_path' => 'clearances/test/'.Str::lower((string) Str::ulid()).'.pdf',
            'size_bytes' => 120000,
            'valid_until' => now()->addMonths(10)->toDateString(),
        ];
    }

    public function type(string $type): static
    {
        return $this->state(['type' => $type]);
    }

    public function expired(): static
    {
        return $this->state(['valid_until' => now()->subDays(3)->toDateString()]);
    }

    public function validUntil(CarbonInterface|string $date): static
    {
        return $this->state([
            'valid_until' => $date instanceof CarbonInterface ? $date->toDateString() : $date,
        ]);
    }
}
