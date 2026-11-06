<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            LeaveTypeSeeder::class,
            DevDataSeeder::class, // otomatis dilewati selain di environment local
            EmployeeDemoSeeder::class,
            AttendanceDemoSeeder::class,
        ]);
    }
}
