<?php

namespace Database\Seeders;

use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Project;
use App\Models\ProjectAssignment;
use App\Models\Title;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DevDataSeeder extends Seeder
{
    /**
     * Data uji untuk development. Hanya jalan di environment "local".
     * Semua akun uji memakai password: password
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        DB::transaction(function () {
            $titles = [];
            foreach (['Manajer', 'Staf Administrasi', 'Teknisi Kalibrasi'] as $name) {
                $titles[$name] = Title::firstOrCreate(['name' => $name]);
            }

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
                'type' => 'site',
                'address' => 'Alamat lokasi proyek contoh',
                'latitude' => -5.1400000,
                'longitude' => 119.4100000,
                'timezone' => 'Asia/Makassar',
                'is_active' => true,
            ]);

            $project = Project::firstOrCreate(['code' => 'PRJ-001'], [
                'name' => 'Proyek Kalibrasi Contoh',
                'location_id' => $site->id,
                'status' => ProjectStatus::Active->value,
                'start_date' => now()->toDateString(),
            ]);

            $people = [
                ['Admin Uji', 'admin@sanmal.test', UserRole::Admin, 'Staf Administrasi', 'EMP-001'],
                ['Pegawai Satu', 'pegawai1@sanmal.test', UserRole::Employee, 'Teknisi Kalibrasi', 'EMP-002'],
                ['Pegawai Dua', 'pegawai2@sanmal.test', UserRole::Employee, 'Teknisi Kalibrasi', 'EMP-003'],
            ];

            $employees = [];
            foreach ($people as [$name, $email, $role, $title, $number]) {
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

                $employees[$email] = Employee::firstOrCreate(['user_id' => $user->id], [
                    'title_id' => $titles[$title]->id,
                    'employee_number' => $number,
                    'joined_at' => now()->toDateString(),
                ]);
            }

            // Hanya Pegawai Satu yang ditugaskan, supaya pembatasan proyek bisa diuji.
            ProjectAssignment::firstOrCreate(
                ['project_id' => $project->id, 'employee_id' => $employees['pegawai1@sanmal.test']->id],
                ['starts_on' => now()->toDateString()],
            );
        });
    }
}
