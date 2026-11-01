<?php

use App\Enums\AttendanceType;
use App\Enums\UserRole;
use App\Enums\WorkType;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\User;
use App\Services\DailyAttendanceStatusService;
use Livewire\Livewire;

function dateFilterEmployee(string $name): Employee
{
    $user = User::factory()->create(['name' => $name]);

    return Employee::query()->create(['user_id' => $user->id]);
}

function dateFilterAdmin(): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();

    return $user;
}

function createDateFilterSession(Employee $employee, string $clockInAt, string $clockOutAt): array
{
    $clockIn = AttendanceRecord::query()->create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => $clockInAt,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => substr($clockInAt, 0, 10),
    ]);

    $clockOut = AttendanceRecord::query()->create([
        'employee_id' => $employee->id,
        'session_anchor_id' => $clockIn->id,
        'type' => AttendanceType::ClockOut,
        'work_type' => WorkType::Office,
        'recorded_at' => $clockOutAt,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => substr($clockOutAt, 0, 10),
    ]);

    return [$clockIn, $clockOut];
}

it('filters sessions longer than 24 hours by their clock-in anchor date', function () {
    $employee = dateFilterEmployee('Pegawai Sesi Panjang');
    [$clockIn, $clockOut] = createDateFilterSession(
        $employee,
        '2026-10-30 01:00:00',
        '2026-11-02 04:00:00',
    );
    $admin = dateFilterAdmin();

    Livewire::actingAs($admin)
        ->test('pages::admin.kehadiran')
        ->set('dateFrom', '2026-10-30')
        ->set('dateTo', '2026-10-30')
        ->assertSee('data-record-count="2"', false)
        ->set('dateFrom', '2026-11-02')
        ->set('dateTo', '2026-11-02')
        ->assertSee('data-record-count="0"', false);

    $service = app(DailyAttendanceStatusService::class);
    expect($service->forEmployeeDate($employee, '2026-10-30')['events'])->toHaveCount(2)
        ->and($service->forEmployeeDate($employee, '2026-11-02')['events'])->toBeEmpty();

    $csv = $this->actingAs($employee->user)
        ->get(route('attendance-history.export', ['from' => '2026-10-30', 'to' => '2026-10-30']))
        ->streamedContent();

    expect($csv)
        ->toContain('Clock In')
        ->toContain('Clock Out')
        ->toContain('2026-11-02 11:00:00');
});

it('uses the latest approved clock-in date correction as the session filter date', function () {
    $employee = dateFilterEmployee('Pegawai Tanggal Koreksi');
    [$clockIn, $clockOut] = createDateFilterSession(
        $employee,
        '2026-10-30 01:00:00',
        '2026-11-03 04:00:00',
    );

    AttendanceCorrection::query()->create([
        'employee_id' => $employee->id,
        'attendance_record_id' => $clockIn->id,
        'session_anchor_id' => $clockIn->id,
        'requested_type' => AttendanceType::ClockIn->value,
        'requested_recorded_at' => '2026-11-03 03:00:00',
        'attendance_date' => '2026-11-03',
        'reason' => 'Tanggal masuk dikoreksi sesuai bukti pendukung.',
        'status' => 'approved',
        'reviewed_by' => dateFilterAdmin()->id,
        'reviewed_at' => now(),
    ]);

    $admin = dateFilterAdmin();

    Livewire::actingAs($admin)
        ->test('pages::admin.kehadiran')
        ->set('dateFrom', '2026-11-03')
        ->set('dateTo', '2026-11-03')
        ->assertSee('data-record-count="2"', false)
        ->set('dateFrom', '2026-10-30')
        ->set('dateTo', '2026-10-30')
        ->assertSee('data-record-count="0"', false);

    $service = app(DailyAttendanceStatusService::class);
    expect($service->forEmployeeDate($employee, '2026-11-03')['events'])->toHaveCount(2)
        ->and($service->forEmployeeDate($employee, '2026-10-30')['events'])->toBeEmpty();

    $response = $this->actingAs($admin)->get(route('admin.kehadiran.export', [
        'from' => '2026-11-03',
        'to' => '2026-11-03',
    ]));

    $csv = $response->streamedContent();

    expect($response->status())->toBe(200)
        ->and($csv)->toContain('Pegawai Tanggal Koreksi')
        ->and($csv)->not->toContain('2026-10-30 01:00:00');
});
