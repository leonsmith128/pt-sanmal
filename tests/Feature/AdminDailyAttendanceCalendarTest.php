<?php

use App\\Enums\\UserRole;
use App\\Models\\Employee;
use App\\Models\\User;

it('renders the admin daily recap with the selected date in the calendar filter', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();

    $employeeUser = User::factory()->create(['name' => 'Pegawai Kalender']);
    Employee::query()->create(['user_id' => $employeeUser->id]);

    $this->actingAs($admin)
        ->get(route('admin.daily-attendance', ['date' => '2026-11-03']))
        ->assertOk()
        ->assertSee('admin-daily-attendance-calendar', false)
        ->assertSee('Pilih tanggal rekap')
        ->assertSee('selected: "2026-11-03"', false)
        ->assertSee('month: "2026-11-01"', false)
        ->assertDontSee('type="date"', false)
        ->assertSee('Unduh CSV');
});

it('rejects an invalid date on the admin daily recap route', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();

    $this->actingAs($admin)
        ->get(route('admin.daily-attendance', ['date' => 'not-a-date']))
        ->assertUnprocessable();
});
