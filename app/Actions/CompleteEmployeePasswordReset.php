<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CompleteEmployeePasswordReset
{
    public function handle(string $token, string $password): void
    {
        DB::transaction(function () use ($token, $password): void {
            $resetRequest = PasswordResetRequest::query()
                ->where('token_hash', hash('sha256', $token))
                ->lockForUpdate()
                ->first();

            abort_unless(
                $resetRequest
                    && $resetRequest->status === 'approved'
                    && $resetRequest->used_at === null
                    && $resetRequest->expires_at?->isFuture(),
                422,
                'Tautan reset tidak valid atau sudah kedaluwarsa.',
            );

            $user = User::query()->lockForUpdate()->findOrFail($resetRequest->user_id);
            abort_unless($user->is_active && ! $user->must_change_password, 403);

            $user->forceFill(['password' => Hash::make($password)])->save();

            $resetRequest->forceFill([
                'status' => 'used',
                'used_at' => now(),
            ])->save();

            AuditLog::record($user, 'password_reset.completed', null, [
                'request_id' => $resetRequest->id,
            ]);
        });
    }
}
