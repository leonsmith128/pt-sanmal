<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        foreach ([
            ['code' => 'izin', 'name' => 'Izin'],
            ['code' => 'sakit', 'name' => 'Sakit'],
            ['code' => 'cuti', 'name' => 'Cuti'],
        ] as $type) {
            DB::table('leave_types')->updateOrInsert(
                ['code' => $type['code']],
                ['name' => $type['name'], 'is_active' => true, 'updated_at' => $now, 'created_at' => $now],
            );
        }
    }
}
