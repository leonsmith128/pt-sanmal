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
        ->toContain('Clock In Efektif')
        ->toContain('Clock Out Efektif')
        ->toContain('2026-11-02 12:00:00');
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

    expect($response->getStatusCode())->toBe(200)
        ->and($csv)->toContain('Pegawai Tanggal Koreksi')
        ->and($csv)->not->toContain('2026-10-30 01:00:00');
});

it('orders daily recap events by their effective timestamps after a correction', function () {
    $employee = dateFilterEmployee('Pegawai Urutan Efektif');
    $clockInAt = now()->subHours(4)->startOfMinute();
    $attendanceDate = $clockInAt->copy()->setTimezone('Asia/Makassar')->toDateString();

    $clockIn = AttendanceRecord::query()->create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => $clockInAt,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => $attendanceDate,
    ]);

    $clockOutOriginalAt = $clockInAt->copy()->subHour();
    $clockOut = AttendanceRecord::query()->create([
        'employee_id' => $employee->id,
        'session_anchor_id' => $clockIn->id,
        'type' => AttendanceType::ClockOut,
        'work_type' => WorkType::Office,
        'recorded_at' => $clockOutOriginalAt,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => $attendanceDate,
    ]);

    $correctedLocal = $clockInAt->copy()->addHour()->setTimezone('Asia/Makassar');
    $admin = dateFilterAdmin();

    $this->actingAs($admin)->post(route('admin.kehadiran.correct-timestamp', $clockOut), [
        'recorded_at_local' => $correctedLocal->format('Y-m-d\\TH:i'),
        'reason' => 'Waktu pulang diperbaiki agar sesuai bukti aktual.',
    ])->assertRedirect();

    $daily = app(DailyAttendanceStatusService::class)->forEmployeeDate($employee, $attendanceDate);

    expect($daily['events']->pluck('type')->map(fn (AttendanceType $type) => $type->value)->all())
        ->toBe([AttendanceType::ClockIn->value, AttendanceType::ClockOut->value]);
});


it('exports one employee row per date including dates without attendance when no date filter is supplied', function () {
    $employee = dateFilterEmployee('Pegawai Ekspor Harian');
    createDateFilterSession($employee, '2026-10-30 01:00:00', '2026-10-30 09:00:00');

    $csv = $this->actingAs($employee->user)
        ->get(route('attendance-history.export'))
        ->streamedContent();

    expect($csv)
        ->toContain('Pegawai Ekspor Harian')
        ->toContain('2026-10-30')
        ->toContain('2026-10-31')
        ->toContain('Belum ada data');
});

it('exports admin daily rows for every employee including dates without attendance', function () {
    $attending = dateFilterEmployee('Pegawai Hadir Ekspor');
    $withoutAttendance = dateFilterEmployee('Pegawai Kosong Ekspor');
    createDateFilterSession($attending, '2026-10-30 01:00:00', '2026-10-30 09:00:00');
    $admin = dateFilterAdmin();

    $response = $this->actingAs($admin)->get(route('admin.kehadiran.export', [
        'from' => '2026-10-30',
        'to' => '2026-10-31',
    ]));
    $csv = $response->streamedContent();

    expect($response->getStatusCode())->toBe(200)
        ->and($csv)->toContain('Pegawai Hadir Ekspor')
        ->and($csv)->toContain('Pegawai Kosong Ekspor')
        ->and($csv)->toContain('2026-10-31')
        ->and($csv)->toContain('Belum ada data');
});
