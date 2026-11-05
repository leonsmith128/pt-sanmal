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
        ->post(route('admin.employees.temporary-password', $targetEmployee))
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

it('lets only superadmin open the employee account creation form', function () {
    $this->actingAs(adminAccessUser(UserRole::Superadmin))
        ->get(route('admin.employees.create'))
        ->assertOk();

    $this->actingAs(adminAccessUser(UserRole::Admin))
        ->get(route('admin.employees.create'))
        ->assertForbidden();

    $this->actingAs(adminAccessUser(UserRole::Employee))
        ->get(route('admin.employees.create'))
        ->assertForbidden();
});

it('rejects direct employee account creation requests from admin and employee roles', function () {
    $payload = [
        'name' => 'Pegawai Ditolak',
        'email' => 'pegawai.ditolak@example.com',
        'employee_number' => 'EMP-DENIED',
        'phone' => '081234567890',
        'title_id' => null,
        'joined_at' => '2026-11-05',
    ];

    foreach ([UserRole::Admin, UserRole::Employee] as $role) {
        $this->actingAs(adminAccessUser($role))
            ->post(route('admin.employees.store'), $payload)
            ->assertForbidden();
    }

    expect(User::query()->where('email', 'pegawai.ditolak@example.com')->exists())->toBeFalse()
        ->and(Employee::query()->where('employee_number', 'EMP-DENIED')->exists())->toBeFalse();
});

it('keeps existing employee management available to admin and superadmin but not employees', function () {
    $targetUser = User::factory()->create();
    $targetEmployee = Employee::query()->create(['user_id' => $targetUser->id]);

    foreach ([UserRole::Admin, UserRole::Superadmin] as $role) {
        $this->actingAs(adminAccessUser($role))
            ->get(route('admin.employees.edit', $targetEmployee))
            ->assertOk();
    }

    $this->actingAs(adminAccessUser(UserRole::Employee))
        ->get(route('admin.employees.index'))
        ->assertForbidden();

    $this->actingAs(adminAccessUser(UserRole::Employee))
        ->get(route('admin.employees.edit', $targetEmployee))
        ->assertForbidden();
});
