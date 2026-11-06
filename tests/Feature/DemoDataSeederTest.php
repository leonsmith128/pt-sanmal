<?php

use App\Enums\AttendanceType;
use App\Enums\UserRole;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\AttendanceDemoSeeder;
use Database\Seeders\DevDataSeeder;
use Database\Seeders\EmployeeDemoSeeder;

it('seeds twenty employee demo accounts and realistic linked attendance events idempotently', function () {
    $this->seed([
        DevDataSeeder::class,
        EmployeeDemoSeeder::class,
        AttendanceDemoSeeder::class,
    ]);

    expect(User::query()->where('role', UserRole::Employee)->count())->toBe(20)
        ->and(Employee::query()->count())->toBe(21); // 20 Employee + 1 Admin profile

    $clockInCount = AttendanceRecord::query()
        ->where('type', AttendanceType::ClockIn)
        ->count();

    $clockOutCount = AttendanceRecord::query()
        ->where('type', AttendanceType::ClockOut)
        ->count();

    $clockOutsWithoutSessionAnchor = AttendanceRecord::query()
        ->where('type', AttendanceType::ClockOut)
        ->whereNull('session_anchor_id')
        ->count();

    expect($clockInCount)->toBeGreaterThan(1000)
        ->and($clockOutCount)->toBeGreaterThan(900)
        ->and($clockInCount)->toBeGreaterThan($clockOutCount)
        ->and($clockOutsWithoutSessionAnchor)->toBe(0);

    $recordCount = AttendanceRecord::query()->count();

    $this->seed([
        DevDataSeeder::class,
        EmployeeDemoSeeder::class,
        AttendanceDemoSeeder::class,
    ]);

    expect(User::query()->where('role', UserRole::Employee)->count())->toBe(20)
        ->and(AttendanceRecord::query()->count())->toBe($recordCount);
});

it('does not create demo employees outside the local environment', function () {
    app()->instance('env', 'production');

    $this->seed(EmployeeDemoSeeder::class);

    expect(User::query()->where('role', UserRole::Employee)->count())->toBe(0);
});
