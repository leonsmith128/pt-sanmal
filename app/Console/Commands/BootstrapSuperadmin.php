<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BootstrapSuperadmin extends Command
{
    protected $signature = 'sanmal:bootstrap-superadmin';

    protected $description = 'Buat akun superadmin pertama dari kredensial konfigurasi lingkungan.';

    public function handle(): int
    {
        $validator = Validator::make(config('sanmal.superadmin', []), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'regex:/[A-Za-z]/', 'regex:/[0-9]/'],
        ]);

        if ($validator->fails()) {
            $this->components->error('Konfigurasi SUPERADMIN_NAME, SUPERADMIN_EMAIL, dan SUPERADMIN_PASSWORD wajib valid. Password minimal 8 karakter serta memuat huruf dan angka.');

            return self::FAILURE;
        }

        $credentials = $validator->validated();

        if (User::query()->where('role', UserRole::Superadmin)->exists()) {
            $this->components->error('Akun superadmin sudah tersedia. Command ini hanya dapat membuat akun pertama dan tidak mengubah akun yang ada.');

            return self::FAILURE;
        }

        if (User::query()->where('email', $credentials['email'])->exists()) {
            $this->components->error('Email konfigurasi sudah digunakan oleh akun lain.');

            return self::FAILURE;
        }

        try {
            $user = DB::transaction(function () use ($credentials): User {
                $user = new User;
                $user->forceFill([
                    'name' => $credentials['name'],
                    'email' => $credentials['email'],
                    'password' => $credentials['password'],
                    'role' => UserRole::Superadmin,
                    'is_active' => true,
                    'must_change_password' => false,
                    'email_verified_at' => null,
                ]);
                $user->save();

                AuditLog::record($user, 'superadmin.bootstrapped', null, [
                    'role' => UserRole::Superadmin->value,
                    'is_active' => true,
                    'email_verified' => false,
                ]);

                return $user;
            });
        } catch (QueryException) {
            $this->components->error('Pembuatan superadmin gagal karena email telah digunakan atau superadmin dibuat secara bersamaan. Periksa database sebelum mencoba kembali.');

            return self::FAILURE;
        }

        $this->components->info("Akun superadmin pertama berhasil dibuat untuk {$user->email}. Email belum terverifikasi; verifikasi dapat diselesaikan dari menu Keamanan Akun.");

        return self::SUCCESS;
    }
}
