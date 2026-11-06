<?php

namespace App\Actions\Admin\Employees;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\PasswordResetRequest;
use Illuminate\Support\Facades\DB;

class UpdateEmployee
{
    /**
     * @param  array{name: string, email: string, position: string, phone?: string|null, joined_at?: string|null, is_active: bool}  $data
     */
    public function handle(Employee $employee, array $data): Employee
    {
        return DB::transaction(function () use ($employee, $data): Employee {
            $user = $employee->user;
            $oldValues = [
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'employee_number' => $employee->employee_number,
                'position' => $employee->position,
            ];

            $userAttributes = [
                'name' => $data['name'],
                'email' => $data['email'],
                'is_active' => $data['is_active'],
            ];

            $emailChanged = $user->email !== $data['email'];

            if ($emailChanged) {
                $userAttributes['email_verified_at'] = null;
            }

            $user->forceFill($userAttributes)->save();

            if (! $data['is_active'] || $emailChanged) {
                $user->accountActivation()
                    ->whereNull('consumed_at')
                    ->update(['consumed_at' => now()]);

                PasswordResetRequest::query()
                    ->where('user_id', $user->id)
                    ->whereIn('status', ['pending', 'approved'])
                    ->whereNull('used_at')
                    ->update([
                        'status' => 'expired',
                        'token_hash' => null,
                        'expires_at' => null,
                    ]);
            }

            $employee->update([
                'phone' => $data['phone'] ?? null,
                'position' => $data['position'],
                'joined_at' => $data['joined_at'] ?? null,
            ]);

            AuditLog::record($user, $user->role === UserRole::Admin ? 'admin.updated' : 'employee.updated', $oldValues, [
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'employee_number' => $employee->employee_number,
                'position' => $employee->position,
            ]);

            return $employee->load('user');
        });
    }
}
