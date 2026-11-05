<?php

namespace App\Actions\Admin\Employees;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\EmployeeAccountCredential;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateEmployee
{
    /**
     * Create an employee profile and its pending login account atomically.
     *
     * @param  array{name: string, email: string, employee_number?: string|null, phone?: string|null, title_id?: int|null, joined_at?: string|null}  $data
     */
    public function handle(array $data): Employee
    {
        return DB::transaction(function () use ($data): Employee {
            $temporaryPassword = Str::password(24);

            $user = new User;
            $user->forceFill([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $temporaryPassword,
                'role' => UserRole::Employee,
                'is_active' => true,
                'must_change_password' => true,
                'email_verified_at' => null,
            ]);
            $user->save();

            $employee = Employee::create([
                'user_id' => $user->id,
                'employee_number' => $data['employee_number'] ?? null,
                'phone' => $data['phone'] ?? null,
                'title_id' => $data['title_id'] ?? null,
                'joined_at' => $data['joined_at'] ?? null,
            ]);

            EmployeeAccountCredential::create([
                'user_id' => $user->id,
                'temporary_password_encrypted' => Crypt::encryptString($temporaryPassword),
            ]);

            return $employee->load(['user', 'title']);
        });
    }
}
