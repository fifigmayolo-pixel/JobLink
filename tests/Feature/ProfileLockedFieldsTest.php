<?php

namespace Tests\Feature;

use App\Models\StudentAlumniProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileLockedFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_student_cannot_change_locked_fields_via_update(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $profile = StudentAlumniProfile::factory()->for($user)->create([
            'full_name' => 'Original Name',
            'course_program' => 'BSIT',
        ]);

        $this->actingAs($user)->patch(route('profile.update'), [
            'full_name' => 'Changed Name', // not in ProfileUpdateRequest's rules
            'personal_email' => 'new@example.com',
            'mobile_number' => '09171234567',
            'house_street' => '123 St',
            'barangay' => 'Poblacion',
            'municipality_city' => 'Alaminos City',
            'province' => 'Pangasinan',
            'postal_code' => '2404',
        ]);

        $profile->refresh();

        $this->assertSame('Original Name', $profile->full_name);
        $this->assertSame('new@example.com', $profile->personal_email);
    }

    public function test_coordinator_can_override_a_locked_field_and_it_is_logged(): void
    {
        $coordinator = User::factory()->jobCoordinator()->create();
        $student = User::factory()->create(['role' => 'student']);
        $profile = StudentAlumniProfile::factory()->for($student)->create(['full_name' => 'Typo Namee']);

        $this->actingAs($coordinator)->patch(route('coordinator.records.override', $profile), [
            'field_name' => 'full_name',
            'new_value' => 'Correct Name',
            'reason' => 'Fixed a typo from enrollment records',
        ]);

        $profile->refresh();

        $this->assertSame('Correct Name', $profile->full_name);
        $this->assertDatabaseHas('field_change_logs', [
            'user_id' => $student->id,
            'changed_by' => $coordinator->id,
            'field_name' => 'full_name',
            'old_value' => 'Typo Namee',
            'new_value' => 'Correct Name',
        ]);
    }
}