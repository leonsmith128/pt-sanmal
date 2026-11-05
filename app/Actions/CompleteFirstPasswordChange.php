<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\EmployeeAccountActivation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CompleteFirstPasswordChange
{
    public function handle(User $user, string $password): void
    {
        DB::transaction(function () use ($user, $password): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            abort_unless($lockedUser->is_active && $lockedUser->must_change_password, 403);

            $lockedUser->forceFill([
                'password' => Hash::make($password),
                'must_change_password' => false,
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
