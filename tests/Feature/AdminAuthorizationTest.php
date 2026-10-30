<?php

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;

function adminAccessUser(UserRole $role): User
{
    $user = User::factory()->create();
    $user->forceFill([
        'role' => $role,
        'is_active' => true,
    ])->save();

    return $user;
}

it('denies employees access to administrative detail and decision endpoints', function () {
    $employee = adminAccessUser(UserRole::Employee);
    $targetUser = User::factory()->create();
    $targetEmployee = Employee::query()->create(['user_id' => $targetUser->id]);

    $this->actingAs($employee)
        ->get(route('admin.employees.show', $targetEmployee))
        ->assertForbidden();

    $this->actingAs($employee)
        ->post(route('admin.daily-attendance.correct'), [
            'employee_id' => $targetEmployee->id,
            'attendance_date' => '2026-11-02',
            'status' => 'absent',
            'reason' => 'Koreksi untuk pengujian permission.',
        ])
        ->assertForbidden();

    $this->actingAs($employee)
        ->get(route('admin.leave-requests.index'))
        ->assertForbidden();

    $this->actingAs($employee)
        ->get(route('admin.overtime-requests.index'))
        ->assertForbidden();
});

it('allows both administrator roles to access administrative employee pages', function () {
    foreach ([UserRole::Admin, UserRole::Superadmin] as $role) {
        $user = adminAccessUser($role);

        $this->actingAs($user)
            ->get(route('admin.employees.index'))
            ->assertOk();
    }
});
