<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['job_coordinator', 'company', 'student', 'alumni'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // There's no self-registration for any role, so this is how the very
        // first Job Coordinator gets in to create everyone else's accounts.
        User::firstOrCreate(
            ['email' => 'coordinator@joblink.test'],
            [
                'role' => 'job_coordinator',
                'password' => 'password', // change this immediately after first login
                'must_change_password' => true,
                'activated_at' => now(),
            ]
        );
    }
}