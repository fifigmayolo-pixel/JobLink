<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_spatie_role_is_synced_when_a_user_is_created(): void
    {
        $coordinator = User::factory()->jobCoordinator()->create();

        $this->assertTrue($coordinator->hasRole('job_coordinator'));
    }

    public function test_spatie_role_updates_when_the_role_column_changes(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $this->assertTrue($user->hasRole('student'));

        $user->update(['role' => 'alumni']);

        $this->assertTrue($user->fresh()->hasRole('alumni'));
        $this->assertFalse($user->fresh()->hasRole('student'));
    }
}