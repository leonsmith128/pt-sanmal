<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\PasswordResetRequest;
use App\Models\User;
use App\Notifications\AccountSecurityNotification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class RecoverSuperadmin extends Command
{
    protected $signature = 'sanmal:recover-superadmin';

    protected $description = 'Pulihkan akses satu-satunya akun superadmin melalui terminal.';

    public function handle(): int
    {
        $superadmins = User::query()->where('role', UserRole::Superadmin)->get();

        if ($superadmins->count() !== 1) {
            $this->components->error(
                $superadmins->isEmpty()
                    ? 'Akun superadmin belum tersedia. Gunakan sanmal:bootstrap-superadmin untuk pembuatan pertama.'
                    : 'Ditemukan lebih dari satu superadmin. Pemulihan dihentikan; periksa database secara manual.',
            );

            return self::FAILURE;
        }

        $superadmin = $superadmins->sole();
        $name = trim((string) $this->ask('Nama superadmin', $superadmin->name));
        $email = trim((string) $this->ask('Email baru', $superadmin->email));
        $password = (string) $this->secret('Password baru');
        $passwordConfirmation = (string) $this->secret('Ulangi password baru');

        if ($password !== $passwordConfirmation) {
            $this->components->error('Konfirmasi password tidak cocok.');

            return self::FAILURE;
        }

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($superadmin->id),
            ],
            'password' => ['required', 'string', 'min:8', 'regex:/[A-Za-z]/', 'regex:/[0-9]/'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        if (! $this->confirm('Lanjutkan pemulihan akun superadmin ini?', false)) {
            $this->components->info('Pemulihan dibatalkan. Tidak ada perubahan dilakukan.');

            return self::SUCCESS;
        }

        $oldEmail = $superadmin->email;

        try {
            $superadmin = DB::transaction(function () use ($superadmin, $name, $email, $password, $oldEmail): User {
                $locked = User::query()->lockForUpdate()->findOrFail($superadmin->id);
                abort_unless($locked->role === UserRole::Superadmin, 409, 'Akun target bukan lagi superadmin.');

                $superadminCount = User::query()->where('role', UserRole::Superadmin)->count();
                abort_unless($superadminCount === 1, 409, 'Pemulihan hanya mendukung tepat satu superadmin.');

                $emailChanged = $locked->email !== $email;

                $locked->forceFill([
                    'name' => $name,
                    'email' => $email,
                    'password' => $password,
                    'remember_token' => Str::random(60),
                    ...($emailChanged ? ['email_verified_at' => null] : []),
                ])->save();

                PasswordResetRequest::query()
                    ->where('user_id', $locked->id)
                    ->whereIn('status', ['pending', 'approved'])
                    ->whereNull('used_at')
                    ->update([
                        'status' => 'expired',
                        'token_hash' => null,
                        'expires_at' => null,
                    ]);

                if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
                    DB::table('sessions')->where('user_id', $locked->id)->delete();
                }

                if (Schema::hasTable('personal_access_tokens')) {
                    DB::table('personal_access_tokens')
                        ->where('tokenable_type', $locked->getMorphClass())
                        ->where('tokenable_id', $locked->id)
                        ->delete();
                }

                AuditLog::record($locked, 'superadmin.recovered', [
                    'name' => $superadmin->name,
                    'email' => $oldEmail,
                ], [
                    'name' => $locked->name,
                    'email' => $locked->email,
                    'email_verified' => $locked->email_verified_at !== null,
                    'sessions_revoked' => true,
                ]);

                return $locked;
            });
        } catch (QueryException) {
            $this->components->error('Pemulihan gagal karena email telah digunakan atau terjadi konflik database. Tidak ada superadmin baru yang dibuat.');

            return self::FAILURE;
        }

        if (! $superadmin->hasVerifiedEmail()) {
            try {
                $superadmin->notify(new VerifyEmail);
                $this->components->info('Tautan verifikasi dikirim ke email superadmin.');
            } catch (Throwable $exception) {
                report($exception);
                $this->components->warn('Akun pulih, tetapi email verifikasi gagal dikirim. Kirim ulang setelah konfigurasi email diperiksa.');
            }
        }

        if ($oldEmail !== $superadmin->email) {
            try {
                Notification::route('mail', $oldEmail)->notify(new AccountSecurityNotification(
                    'Akun Superadmin SanMal dipulihkan',
                    'Akses akun Superadmin SanMal baru saja dipulihkan dan alamat email login telah diubah. Jika Anda tidak meminta perubahan ini, segera hubungi administrator sistem.',
                ));
            } catch (Throwable $exception) {
                report($exception);
                $this->components->warn('Notifikasi keamanan ke email lama gagal dikirim.');
            }
        }

        $this->components->info("Akun superadmin {$superadmin->email} berhasil dipulihkan. Seluruh sesi dan token akses yang didukung telah dicabut.");

        return self::SUCCESS;
    }
}
