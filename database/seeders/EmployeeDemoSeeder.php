<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\Title;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EmployeeDemoSeeder extends Seeder
{
    /**
     * Membuat 18 akun Employee tambahan agar total akun Employee demo menjadi 20.
     * Password seluruh akun demo: password (khusus environment local).
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        DB::transaction(function (): void {
            $titles = [];
            foreach ([
                'Teknisi Kalibrasi',
                'Teknisi Lapangan',
                'Staf Administrasi',
                'Supervisor Operasional',
                'Koordinator Proyek',
            ] as $name) {
                $titles[$name] = Title::query()->firstOrCreate(['name' => $name]);
            }

            $people = [
                ['Dimas Pratama', 'pegawai03@sanmal.test', 'EMP-004', 'Teknisi Kalibrasi', '2024-02-12', '081234560003'],
                ['Rizky Saputra', 'pegawai04@sanmal.test', 'EMP-005', 'Teknisi Lapangan', '2024-04-08', '081234560004'],
                ['Fajar Nugraha', 'pegawai05@sanmal.test', 'EMP-006', 'Teknisi Kalibrasi', '2024-07-15', '081234560005'],
                ['Andi Firmansyah', 'pegawai06@sanmal.test', 'EMP-007', 'Teknisi Lapangan', '2024-10-01', '081234560006'],
                ['Budi Santoso', 'pegawai07@sanmal.test', 'EMP-008', 'Staf Administrasi', '2025-01-06', '081234560007'],
                ['Rian Kurniawan', 'pegawai08@sanmal.test', 'EMP-009', 'Teknisi Kalibrasi', '2025-02-17', '081234560008'],
                ['Ilham Maulana', 'pegawai09@sanmal.test', 'EMP-010', 'Teknisi Lapangan', '2025-04-14', '081234560009'],
                ['Agus Setiawan', 'pegawai10@sanmal.test', 'EMP-011', 'Supervisor Operasional', '2025-06-02', '081234560010'],
                ['Wahyu Hidayat', 'pegawai11@sanmal.test', 'EMP-012', 'Teknisi Kalibrasi', '2025-08-11', '081234560011'],
                ['Rizal Ramadhan', 'pegawai12@sanmal.test', 'EMP-013', 'Teknisi Lapangan', '2025-10-20', '081234560012'],
                ['Arif Wibowo', 'pegawai13@sanmal.test', 'EMP-014', 'Teknisi Kalibrasi', '2026-01-05', '081234560013'],
                ['Yoga Prasetyo', 'pegawai14@sanmal.test', 'EMP-015', 'Staf Administrasi', '2026-02-16', '081234560014'],
                ['Hendra Gunawan', 'pegawai15@sanmal.test', 'EMP-016', 'Teknisi Lapangan', '2026-03-09', '081234560015'],
                ['Rangga Permana', 'pegawai16@sanmal.test', 'EMP-017', 'Teknisi Kalibrasi', '2026-04-06', '081234560016'],
                ['Aldi Firmansyah', 'pegawai17@sanmal.test', 'EMP-018', 'Teknisi Lapangan', '2026-06-15', '081234560017'],
                ['Doni Kurnia', 'pegawai18@sanmal.test', 'EMP-019', 'Teknisi Kalibrasi', '2026-07-06', '081234560018'],
                ['Rafi Akbar', 'pegawai19@sanmal.test', 'EMP-020', 'Teknisi Lapangan', '2026-08-03', '081234560019'],
                ['Bagas Maulana', 'pegawai20@sanmal.test', 'EMP-021', 'Koordinator Proyek', '2026-09-14', '081234560020'],
            ];

            foreach ($people as [$name, $email, $number, $title, $joinedAt, $phone]) {
                $user = User::query()->where('email', $email)->first();

                if ($user !== null && $user->role !== UserRole::Employee) {
                    throw new RuntimeException("Email demo {$email} sudah digunakan oleh akun non-Employee.");
                }

                if ($user === null) {
                    $user = User::query()->create([
                        'name' => $name,
                        'email' => $email,
                        'password' => 'password',
                    ]);

                    $user->forceFill([
                        'role' => UserRole::Employee,
                        'is_active' => true,
                        'must_change_password' => false,
                        'email_verified_at' => now(),
                    ])->save();
                }

                $existingProfile = Employee::query()->where('user_id', $user->id)->first();

                if ($existingProfile !== null) {
                    continue;
                }

                $numberOwner = Employee::query()->where('employee_number', $number)->exists();

                if ($numberOwner) {
                    throw new RuntimeException("Nomor pegawai demo {$number} sudah digunakan.");
                }

                Employee::query()->create([
                    'user_id' => $user->id,
                    'title_id' => $titles[$title]->id,
                    'employee_number' => $number,
                    'phone' => $phone,
                    // employment_status sengaja null: nilai bakunya belum ditetapkan.
                    'joined_at' => $joinedAt,
                ]);
            }
        });
    }
}
