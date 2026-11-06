<?php

namespace App\Actions\Admin\Employees;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeAccountCredential;
use App\Models\User;
use App\Services\EmployeeNumberGenerator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateEmployee
{
    public function __construct(private readonly EmployeeNumberGenerator $employeeNumberGenerator) {}

    /**
     * Create an employee profile and its pending login account atomically.
     *
     * @param  array{name: string, email: string, role: UserRole, position: string, phone?: string|null, joined_at?: string|null}  $data
     */
    public function handle(array $data): Employee
    {
        return DB::transaction(function () use ($data): Employee {
            $temporaryPassword = Str::password(8, symbols: false);
            while (! preg_match('/[A-Za-z]/', $temporaryPassword) || ! preg_match('/[0-9]/', $temporaryPassword)) {
                $temporaryPassword = Str::password(8, symbols: false);
            }

            $user = new User;
            $user->forceFill([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $temporaryPassword,
                'role' => $data['role'],
                'is_active' => true,
                'must_change_password' => true,
                'email_verified_at' => null,
            ]);
            $user->save();

            $employee = Employee::create([
                'user_id' => $user->id,
                'employee_number' => $this->employeeNumberGenerator->next(),
                'phone' => $data['phone'] ?? null,
                'position' => $data['position'],
                'joined_at' => $data['joined_at'] ?? null,
            ]);

            AuditLog::record($user, $data['role'] === UserRole::Admin ? 'admin.created' : 'employee.created', null, [
                'employee_number' => $employee->employee_number,
                'role' => $data['role']->value,
                'position' => $employee->position,
            ]);

            EmployeeAccountCredential::create([
                'user_id' => $user->id,
                'temporary_password_encrypted' => Crypt::encryptString($temporaryPassword),
            ]);

            return $employee->load('user');
        });
    }
}
