<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class CreateCoordinator extends Command
{
    protected $signature = 'joblink:create-coordinator {--visible : Show the password while typing (use if hidden input does not work in your terminal)}';

    protected $description = 'Create the base roles and the first Job Coordinator account (password is entered at a prompt)';

    public function handle(): int
    {
        $connection = config('database.default');
        $host = config("database.connections.{$connection}.host");

        $this->warn("Target database: {$connection} @ {$host}");

        if (! $this->confirm('Create the roles and the first Job Coordinator on THIS database?')) {
            $this->info('Cancelled. Nothing was changed.');

            return self::FAILURE;
        }

        $email = $this->ask('Coordinator email');
        $fullName = $this->ask('Coordinator full name');
        if ($this->option('visible')) {
            $this->warn('Visible mode: the password will be shown on screen as you type. Make sure nobody is watching.');
            $password = $this->ask('Password (at least 12 characters)');
            $confirmation = $this->ask('Confirm password');
        } else {
            $password = $this->secret('Password (at least 12 characters)');
            $confirmation = $this->secret('Confirm password');
        }

        $validator = Validator::make(
            [
                'email' => $email,
                'full_name' => $fullName,
                'password' => $password,
                'password_confirmation' => $confirmation,
            ],
            [
                'email' => ['required', 'email', 'unique:users,email'],
                'full_name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string', 'min:12', 'confirmed'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            $this->info('Nothing was created.');

            return self::FAILURE;
        }

        foreach (['job_coordinator', 'company', 'student', 'alumni'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        $user = User::create([
            'role' => 'job_coordinator',
            'email' => $email,
            'full_name' => $fullName,
            'password' => $password,
            'must_change_password' => false,
            'activated_at' => now(),
        ]);

        // The UserObserver normally syncs the Spatie role; this is only a safety net.
        if (! $user->hasRole('job_coordinator')) {
            $user->assignRole('job_coordinator');
        }

        $this->info("Roles are ready and the Job Coordinator account {$email} was created.");

        return self::SUCCESS;
    }
}