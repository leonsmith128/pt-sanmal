<?php

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;

function userWithRole(UserRole $role, bool $withEmployee = true): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => $role])->save();

    if ($withEmployee) {
        Employee::query()->create([
            'user_id' => $user->id,
            'joined_at' => now()->toDateString(),
        ]);
    }

    return $user->fresh();
}

test('guests are redirected from attendance pages to login', function () {
    $this->get(route('attendance'))->assertRedirect(route('login'));
    $this->get(route('attendance-history.index'))->assertRedirect(route('login'));
    $this->get(route('admin.kehadiran'))->assertRedirect(route('login'));
});

test('employees can visit the attendance clock page', function () {
    $this->actingAs(userWithRole(UserRole::Employee));

    $this->get(route('attendance'))
        ->assertOk()
        ->assertSee(__('Absensi'))
        ->assertDontSee(__('Kehadiran Pegawai'));
});

test('employees can visit their own attendance history', function () {
    $this->actingAs(userWithRole(UserRole::Employee));

    $this->get(route('attendance-history.index'))
        ->assertOk()
        ->assertSee(__('Riwayat Absensi'))
        ->assertSee(__('Belum ada catatan absensi'));
});

test('employees are forbidden from the admin attendance page', function () {
    $this->actingAs(userWithRole(UserRole::Employee));

    $this->get(route('admin.kehadiran'))->assertForbidden();
});

test('admins can visit the admin attendance page', function () {
    $this->actingAs(userWithRole(UserRole::Admin));

    $this->get(route('admin.kehadiran'))
        ->assertOk()
        ->assertSee(__('Kehadiran Pegawai'));
});
