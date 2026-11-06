<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\PasswordResetRequest;
use App\Models\User;
use App\Notifications\AccountSecurityNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class CompleteEmployeePasswordReset
{
    public function handle(string $token, string $password): void
    {
        $user = DB::transaction(function () use ($token, $password): User {
            $tokenHash = hash('sha256', $token);
            $snapshot = PasswordResetRequest::query()->where('token_hash', $tokenHash)->first();
            abort_unless($snapshot, 422, 'Tautan reset tidak valid atau sudah kedaluwarsa.');

            $user = User::query()->lockForUpdate()->findOrFail($snapshot->user_id);
            $resetRequest = PasswordResetRequest::query()->whereKey($snapshot->id)->lockForUpdate()->first();

            abort_unless(
                $resetRequest
                    && $resetRequest->status === 'approved'
                    && $resetRequest->used_at === null
                    && $resetRequest->expires_at?->isFuture(),
                422,
                'Tautan reset tidak valid atau sudah kedaluwarsa.',
            );
            abort_unless(
                $user->is_active
                    && ! $user->must_change_password
                    && in_array($user->role, [UserRole::Employee, UserRole::Admin], true),
                403,
            );

            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            PasswordResetRequest::query()
                ->where('user_id', $user->id)
                ->where('id', '!=', $resetRequest->id)
                ->whereIn('status', ['pending', 'approved'])
                ->whereNull('used_at')
                ->update([
                    'status' => 'expired',
                    'token_hash' => null,
                    'expires_at' => null,
                ]);

            $resetRequest->forceFill(['status' => 'used', 'used_at' => now()])->save();

            AuditLog::record($user, 'password_reset.completed', null, ['request_id' => $resetRequest->id]);

            return $user;
        });

        try {
            $user->notify(new AccountSecurityNotification(
                'Password akun SanMal diubah melalui reset',
                'Password akun SanMal Anda baru saja diubah melalui proses reset yang disetujui administrator. Seluruh sesi login sebelumnya telah dicabut. Jika Anda tidak melakukan permintaan ini, segera hubungi administrator.',
            ));
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('Password reset completion security notification could not be sent.', [
                'user_id' => $user->id,
                'exception' => $exception::class,
            ]);
        }
    }
}
