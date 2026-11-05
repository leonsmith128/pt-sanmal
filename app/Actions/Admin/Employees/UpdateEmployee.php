<?php

namespace App\Actions\Admin\Employees;

use App\Models\Employee;
use App\Models\PasswordResetRequest;
use Illuminate\Support\Facades\DB;

class UpdateEmployee
{
    /**
     * @param  array{name: string, email: string, employee_number?: string|null, phone?: string|null, title_id?: int|null, joined_at?: string|null, is_active: bool}  $data
     */
    public function handle(Employee $employee, array $data): Employee
    {
        return DB::transaction(function () use ($employee, $data): Employee {
            $user = $employee->user;

            $userAttributes = [
                'name' => $data['name'],
                'email' => $data['email'],
                'is_active' => $data['is_active'],
            ];

            if ($user->email !== $data['email']) {
                $userAttributes['email_verified_at'] = null;
            }

            $user->forceFill($userAttributes)->save();

            if (! $data['is_active']) {
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
                'employee_number' => $data['employee_number'] ?? null,
                'phone' => $data['phone'] ?? null,
                'title_id' => $data['title_id'] ?? null,
                'joined_at' => $data['joined_at'] ?? null,
            ]);

            return $employee->load(['user', 'title']);
        });
    }
}
