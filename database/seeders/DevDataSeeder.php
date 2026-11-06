<?php

namespace Database\Seeders;

use App\Enums\AdminPosition;
use App\Enums\EmployeePosition;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Enums\UserRole;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DevDataSeeder extends Seeder
{
    /**
     * Data uji untuk development. Hanya jalan di environment local/testing.
     * Semua akun uji memakai password: password
     */
    public function run(): void
    {
        if (! app()->environment('local') && ! app()->environment('testing')) {
            return;
        }

        DB::transaction(function () {
            $client = Client::firstOrCreate(['code' => 'CL-DEV'], [
                'name' => 'Client Contoh Development',
                'address' => 'Alamat client contoh',
            ]);

            // Koordinat di bawah hanya placeholder (pusat Kota Makassar). Ganti dengan koordinat asli nanti.
            Location::firstOrCreate(['name' => 'Kantor PT SanMal Calibration'], [
                'type' => 'office',
                'address' => 'Alamat kantor contoh',
                'latitude' => -5.1477000,
                'longitude' => 119.4327000,
                'timezone' => 'Asia/Makassar',
                'is_active' => true,
            ]);

            $site = Location::firstOrCreate(['name' => 'Lokasi Proyek Contoh'], [
                'client_id' => $client->id,
                'type' => 'site',
                'address' => 'Alamat lokasi proyek contoh',
                'latitude' => -5.1400000,
                'longitude' => 119.4100000,
                'timezone' => 'Asia/Makassar',
                'is_active' => true,
            ]);

            $project = Project::firstOrCreate(['code' => 'PRJ-001'], [
                'client_id' => $client->id,
                'name' => 'Proyek Kalibrasi Contoh',
                'project_type' => ProjectType::Service,
                'location_id' => $site->id,
                'status' => ProjectStatus::Active->value,
                'start_date' => '2026-05-01',
            ]);

            if ($project->start_date === null || $project->start_date->toDateString() > '2026-05-01') {
                $project->forceFill(['start_date' => '2026-05-01'])->save();
            }

            $people = [
                ['Admin Uji', 'admin@sanmal.test', UserRole::Admin, AdminPosition::StaffAdministration->value, 'EMP-001'],
                ['Pegawai Satu', 'pegawai1@sanmal.test', UserRole::Employee, EmployeePosition::Technician->value, 'EMP-002'],
                ['Pegawai Dua', 'pegawai2@sanmal.test', UserRole::Employee, EmployeePosition::Technician->value, 'EMP-003'],
            ];

            foreach ($people as [$name, $email, $role, $position, $number]) {
                $user = User::firstOrNew(['email' => $email]);

                if (! $user->exists) {
                    $user->forceFill([
                        'name' => $name,
                        'password' => 'password',
                        'role' => $role,
                        'is_active' => true,
                        'must_change_password' => false,
                        'email_verified_at' => now(),
                    ])->save();
                }

                Employee::firstOrCreate(['user_id' => $user->id], [
                    'position' => $position,
                    'employee_number' => $number,
                    'joined_at' => now()->toDateString(),
                ]);
            }

        });
    }
}
