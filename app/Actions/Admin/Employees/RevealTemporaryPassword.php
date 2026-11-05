<?php

namespace App\Actions\Admin\Employees;

use App\Models\AuditLog;
use App\Models\Employee;
use Illuminate\Support\Facades\Crypt;

class RevealTemporaryPassword
{
    public function handle(Employee $employee): string
    {
        abort_unless($employee->user->is_active, 404);

        $credential = $employee->user->accountCredential;

        abort_unless($credential, 404);

        AuditLog::record($employee->user, 'account.temporary_password.revealed', null, [
            'revealed_to_user_id' => auth()->id(),
        ]);

        return Crypt::decryptString($credential->temporary_password_encrypted);
    }
}
