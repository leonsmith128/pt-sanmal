<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class SuperadminSeeder extends Seeder
{
    /**
     * Membuat akun superadmin pertama.
     * Kredensial dibaca dari .env supaya tidak ikut ter-commit ke GitHub:
     *   SUPERADMIN_NAME, SUPERADMIN_EMAIL, SUPERADMIN_PASSWORD
     */
    public function run(): void
    {
        $name = env('SUPERADMIN_NAME');
        $email = env('SUPERADMIN_EMAIL');
        $password = env('SUPERADMIN_PASSWORD');

        if (! $name || ! $email || ! $password) {
            throw new RuntimeException(
                'Isi SUPERADMIN_NAME, SUPERADMIN_EMAIL, dan SUPERADMIN_PASSWORD di .env sebelum menjalankan seeder.'
            );
        }

        $user = User::firstOrNew(['email' => $email]);

        // Hanya dibuat jika belum ada, jadi menjalankan ulang seeder tidak mereset password.
        if (! $user->exists) {
            // forceFill karena role dkk. sengaja tidak masuk $fillable.
            $user->forceFill([
                'name' => $name,
                'password' => $password, // di-hash otomatis oleh cast 'hashed'
                'role' => UserRole::Superadmin,
                'is_active' => true,
                'must_change_password' => false,
                'email_verified_at' => now(),
            ])->save();
        }

        // Semua akun, termasuk superadmin, wajib punya profil pegawai untuk absensi.
        // firstOrCreate: aman dijalankan ulang, tidak membuat profil ganda.
        Employee::firstOrCreate(['user_id' => $user->id], ['joined_at' => now()->toDateString()]);
    }
}
