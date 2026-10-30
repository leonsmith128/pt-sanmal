<?php

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;
use Livewire\Livewire;

function attendancePageAdmin(): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();

    return $user;
}

function attendancePageEmployee(string $name): Employee
{
    $user = User::factory()->create(['name' => $name]);

    return Employee::query()->create(['user_id' => $user->id]);
}

it('shows employees without attendance only for a single-day unfiltered view', function () {
    $admin = attendancePageAdmin();
    attendancePageEmployee('Pegawai Alpha');
    attendancePageEmployee('Pegawai Beta');

    Livewire::actingAs($admin)
        ->test('pages::admin.kehadiran')
        ->set('dateFrom', '2026-11-02')
        ->set('dateTo', '2026-11-02')
        ->assertSee('Belum ada absensi')
        ->assertSee('Pegawai Alpha')
        ->assertSee('Pegawai Beta')
        ->set('dateTo', '2026-11-03')
        ->assertDontSee('Belum ada absensi');
});

it('hides the not-yet-present list when an employee filter is selected', function () {
    $admin = attendancePageAdmin();
    $employee = attendancePageEmployee('Pegawai Terpilih');
    attendancePageEmployee('Pegawai Lain');

    Livewire::actingAs($admin)
        ->test('pages::admin.kehadiran')
        ->set('dateFrom', '2026-11-02')
        ->set('dateTo', '2026-11-02')
        ->set('employeeId', (string) $employee->id)
        ->assertDontSee('Belum ada absensi');
});

it('rejects non-manager access during Livewire requests', function () {
    $employee = User::factory()->create();
    $employee->forceFill(['role' => UserRole::Employee, 'is_active' => true])->save();

    Livewire::actingAs($employee)
        ->test('pages::admin.kehadiran')
        ->assertForbidden();
});
