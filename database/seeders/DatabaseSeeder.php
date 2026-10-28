<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            LeaveTypeSeeder::class,
            SuperadminSeeder::class,
            DevDataSeeder::class, // otomatis dilewati selain di environment local
        ]);
    }
}
