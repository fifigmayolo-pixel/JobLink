<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Roles and the very first Job Coordinator. Safe everywhere.
        $this->call(RolesAndPermissionsSeeder::class);

        // Demo accounts and sample jobs. Never part of a normal production seed.
        // To load them on purpose (for example for the defense), run:
        //   php artisan db:seed --class=DemoDataSeeder --force
        if (! app()->environment('production')) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
