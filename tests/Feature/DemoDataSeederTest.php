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

    $clockIns = AttendanceRecord::query()
        ->where('type', AttendanceType::ClockIn)
        ->get();

    $clockOuts = AttendanceRecord::query()
        ->where('type', AttendanceType::ClockOut)
        ->get();

    expect($clockIns->count())->toBeGreaterThan(1000)
        ->and($clockOuts->count())->toBeGreaterThan(900)
        ->and($clockIns->count())->toBeGreaterThan($clockOuts->count())
        ->and($clockOuts->every(fn (AttendanceRecord $record): bool => $record->session_anchor_id !== null))->toBeTrue();

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
