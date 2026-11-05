<?php

namespace App\Actions\Admin\Employees;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeAccountCredential;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RotateTemporaryPassword
{
    public function handle(Employee $employee): void
    {
        DB::transaction(function () use ($employee): void {
            $user = User::query()->lockForUpdate()->findOrFail($employee->user_id);
            abort_unless($user->is_active && $user->must_change_password, 422);

            $credential = EmployeeAccountCredential::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $temporaryPassword = Str::password(24, symbols: false);

            $user->forceFill(['password' => $temporaryPassword])->save();
            $credential->forceFill([
                'temporary_password_encrypted' => Crypt::encryptString($temporaryPassword),
            ])->save();

            $user->accountActivation()
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            AuditLog::record($user, 'account.temporary_password.rotated');
        });
    }
}
