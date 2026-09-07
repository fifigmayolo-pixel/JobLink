<?php

namespace Tests\Feature;

use App\Jobs\ConvertStudentsToAlumni;
use App\Models\StudentAlumniProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BulkUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_coordinator_can_queue_a_bulk_conversion(): void
    {
        Queue::fake();

        $coordinator = User::factory()->jobCoordinator()->create();
        $student = User::factory()->create(['role' => 'student']);
        $profile = StudentAlumniProfile::factory()->for($student)->create(['status' => 'student']);

        $this->actingAs($coordinator)->post(route('coordinator.bulk-update.store'), [
            'profile_ids' => [$profile->id],
            'year_graduated' => 2026,
        ]);

        Queue::assertPushed(ConvertStudentsToAlumni::class, function ($job) use ($profile) {
            return $job->profileIds === [$profile->id] && $job->yearGraduated === 2026;
        });
    }

    public function test_the_conversion_job_updates_status_and_clears_student_only_fields(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $profile = StudentAlumniProfile::factory()->for($student)->create([
            'status' => 'student',
            'year_level' => '4th Year',
            'section' => 'A',
        ]);

        (new ConvertStudentsToAlumni([$profile->id], 2026))->handle();

        $profile->refresh();

        $this->assertSame('alumni', $profile->status);
        $this->assertSame(2026, $profile->year_graduated);
        $this->assertNull($profile->year_level);
        $this->assertNull($profile->section);
    }
}