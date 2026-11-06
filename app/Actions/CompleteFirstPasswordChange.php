<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\EmployeeAccountActivation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CompleteFirstPasswordChange
{
    public function handle(User $user, string $password): void
    {
        DB::transaction(function () use ($user, $password): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            abort_unless($lockedUser->is_active && $lockedUser->must_change_password, 403);

            if (Hash::check($password, $lockedUser->password)) {
                throw ValidationException::withMessages([
                    'password' => 'Password baru harus berbeda dari password sementara.',
                ]);
            }

            $lockedUser->forceFill([
                'password' => Hash::make($password),
                'must_change_password' => false,
                'remember_token' => Str::random(60),
            ])->save();

            $lockedUser->accountCredential()->delete();
            EmployeeAccountActivation::query()
                ->where('user_id', $lockedUser->id)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            AuditLog::record($lockedUser, 'account.initial_password.changed');
        });
    }
}
