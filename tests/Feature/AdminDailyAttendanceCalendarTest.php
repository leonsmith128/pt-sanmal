<?php

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\User;

it('renders the admin daily recap with the selected date in the calendar filter', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();

    $employeeUser = User::factory()->create(['name' => 'Pegawai Kalender']);
    Employee::query()->create(['user_id' => $employeeUser->id]);

    $this->actingAs($admin)
        ->get(route('admin.daily-attendance', ['date' => '2026-11-03']))
        ->assertOk()
        ->assertSee('admin-calendar-select', false)
        ->assertSee('name="date"', false)
        ->assertSee('type="date"', false)
        ->assertSee('2026-11-03')
        ->assertSee('Unduh CSV');
});

it('rejects an invalid date on the admin daily recap route', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();

    $this->actingAs($admin)
        ->get(route('admin.daily-attendance', ['date' => 'not-a-date']))
        ->assertSessionHasErrors('date');
});
