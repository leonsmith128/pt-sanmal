<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RequestEmployeePasswordReset
{
    public function handle(string $email): void
    {
        $user = User::query()->where('email', $email)->first();

        if (! $user || ! $user->is_active || $user->must_change_password || $user->role !== UserRole::Employee) {
            return;
        }

        DB::transaction(function () use ($user): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            $pending = PasswordResetRequest::query()
                ->where('user_id', $lockedUser->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->exists();

            if ($pending) {
                return;
            }

            $request = PasswordResetRequest::create([
                'user_id' => $lockedUser->id,
                'status' => 'pending',
            ]);

            AuditLog::record($lockedUser, 'password_reset.requested', null, [
                'request_id' => $request->id,
            ]);
        });
    }
}
