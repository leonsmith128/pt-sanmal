<?php

namespace App\Actions\Admin\Employees;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeAccountCredential;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class RevealTemporaryPassword
{
    public function handle(Employee $employee): string
    {
        return DB::transaction(function () use ($employee): string {
            $user = User::query()->lockForUpdate()->findOrFail($employee->user_id);
            abort_unless($user->is_active && $user->must_change_password, 404);

            $credential = EmployeeAccountCredential::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            $password = Crypt::decryptString($credential->temporary_password_encrypted);

            AuditLog::record($user, 'account.temporary_password.revealed', null, [
                'revealed_to_user_id' => auth()->id(),
            ]);

            return $password;
        });
    }
}
