<?php

use App\\Enums\\UserRole;
use App\\Models\\Employee;
use App\\Models\\User;

function dashboardUserWithRole(UserRole $role, bool $withEmployee = false): User
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

test('guests are redirected from the admin dashboard to login', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

test('employees cannot access the admin dashboard', function () {
    $this->actingAs(dashboardUserWithRole(UserRole::Employee, true));

    $this->get(route('admin.dashboard'))->assertForbidden();
});

test('admins can open the admin dashboard', function () {
    $this->actingAs(dashboardUserWithRole(UserRole::Admin));

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Dashboard Admin')
        ->assertSee('Aktivitas absensi hari ini')
        ->assertSee('Diperbarui otomatis setiap 20 detik');
});

test('superadmins can open the admin dashboard', function () {
    $this->actingAs(dashboardUserWithRole(UserRole::Superadmin));

    $this->get(route('admin.dashboard'))->assertOk();
});
