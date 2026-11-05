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

    $csv = xlsxContents($this->actingAs($employee->user)
        ->get(route('attendance-history.export', ['from' => '2026-10-30', 'to' => '2026-10-30'])))['text'];

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

    $csv = xlsxContents($response)['text'];

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

it('exports the employee history over the full range but skips dates without attendance, like the page', function () {
    $employee = dateFilterEmployee('Pegawai Ekspor Harian');
    createDateFilterSession($employee, '2026-10-30 01:00:00', '2026-10-30 09:00:00');

    $csv = xlsxContents($this->actingAs($employee->user)
        ->get(route('attendance-history.export')))['text'];

    expect($csv)
        ->toContain('Pegawai Ekspor Harian')
        ->toContain('2026-10-30')
        ->not->toContain('2026-10-31')
        ->not->toContain('Belum ada data');
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
    $csv = xlsxContents($response)['text'];

    expect($response->getStatusCode())->toBe(200)
        ->and($csv)->toContain('Pegawai Hadir Ekspor')
        ->and($csv)->toContain('Pegawai Kosong Ekspor')
        ->and($csv)->toContain('2026-10-31')
        ->and($csv)->toContain('Belum ada data');
});

it('includes forgotten clock-out correction status and note in the daily export', function () {
    $employee = dateFilterEmployee('Pegawai Koreksi Lupa Pulang');
    $clockIn = AttendanceRecord::query()->create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => '2026-10-30 01:00:00',
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-10-30',
    ]);

    AttendanceCorrection::query()->create([
        'employee_id' => $employee->id,
        'attendance_record_id' => null,
        'session_anchor_id' => $clockIn->id,
        'requested_type' => AttendanceType::ClockOut->value,
        'requested_recorded_at' => '2026-10-30 09:00:00',
        'attendance_date' => '2026-10-30',
        'reason' => 'Lupa melakukan Clock Out karena pekerjaan lapangan.',
        'status' => 'pending',
    ]);

    $csv = xlsxContents($this->actingAs($employee->user)
        ->get(route('attendance-history.export', ['from' => '2026-10-30', 'to' => '2026-10-30'])))['text'];

    expect($csv)
        ->toContain('Menunggu')
        ->toContain('Lupa melakukan Clock Out karena pekerjaan lapangan.');
});

it('groups onsite sessions by the WITA company reporting date without changing the employee local date', function () {
    $employee = dateFilterEmployee('Pegawai Batas WITA');

    $clockIn = AttendanceRecord::query()->create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Onsite,
        'recorded_at' => '2026-11-01 16:30:00',
        'timezone' => 'Asia/Jakarta',
        'timezone_source' => 'gps_resolved',
        'location_status' => 'captured',
        'attendance_date' => '2026-11-01',
    ]);

    AttendanceRecord::query()->create([
        'employee_id' => $employee->id,
        'session_anchor_id' => $clockIn->id,
        'type' => AttendanceType::ClockOut,
        'work_type' => WorkType::Onsite,
        'recorded_at' => '2026-11-01 17:30:00',
        'timezone' => 'Asia/Jakarta',
        'timezone_source' => 'session_anchor',
        'location_status' => 'captured',
        'attendance_date' => '2026-11-01',
    ]);

    $service = app(DailyAttendanceStatusService::class);
    $employeeLocal = $service->forEmployeeDate($employee, '2026-11-01');
    $companyCalendar = $service->forEmployeesDateRange(
        collect([$employee]),
        '2026-11-02',
        '2026-11-02',
        'Asia/Makassar',
    );

    Livewire::actingAs(dateFilterAdmin())
        ->test('pages::admin.kehadiran')
        ->set('dateFrom', '2026-11-02')
        ->set('dateTo', '2026-11-02')
        ->assertSee('data-record-count="2"', false)
        ->set('dateFrom', '2026-11-01')
        ->set('dateTo', '2026-11-01')
        ->assertSee('data-record-count="0"', false);

    $adminCsv = xlsxContents($this->actingAs(dateFilterAdmin())->get(route('admin.kehadiran.export', [
        'from' => '2026-11-02',
        'to' => '2026-11-02',
    ])))['text'];

    expect($adminCsv)
        ->toContain('2026-11-02')
        ->toContain('Pegawai Batas WITA');

    expect($employeeLocal['events'])->toHaveCount(2)
        ->and($companyCalendar[$employee->id]['2026-11-02']['events'])->toHaveCount(2)
        ->and($companyCalendar[$employee->id]['2026-11-02']['status'])->toBe('present')
        ->and($companyCalendar[$employee->id]['2026-11-01']['events'] ?? collect())->toBeEmpty();
});

it('uses the approved effective timestamp when assigning a session to the WITA report date', function () {
    $employee = dateFilterEmployee('Pegawai Koreksi WITA');
    $clockIn = AttendanceRecord::query()->create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Onsite,
        'recorded_at' => '2026-11-01 15:30:00',
        'timezone' => 'Asia/Jakarta',
        'attendance_date' => '2026-11-01',
    ]);

    AttendanceRecord::query()->create([
        'employee_id' => $employee->id,
        'session_anchor_id' => $clockIn->id,
        'type' => AttendanceType::ClockOut,
        'work_type' => WorkType::Onsite,
        'recorded_at' => '2026-11-01 17:00:00',
        'timezone' => 'Asia/Jakarta',
        'attendance_date' => '2026-11-01',
    ]);

    AttendanceCorrection::query()->create([
        'employee_id' => $employee->id,
        'attendance_record_id' => $clockIn->id,
        'session_anchor_id' => $clockIn->id,
        'requested_type' => AttendanceType::ClockIn->value,
        'requested_recorded_at' => '2026-11-01 16:30:00',
        'attendance_date' => '2026-11-01',
        'reason' => 'Timestamp disesuaikan dengan bukti.',
        'status' => 'approved',
        'reviewed_by' => dateFilterAdmin()->id,
        'reviewed_at' => now(),
    ]);

    $service = app(DailyAttendanceStatusService::class);
    $companyCalendar = $service->forEmployeesDateRange(
        collect([$employee]),
        '2026-11-02',
        '2026-11-02',
        'Asia/Makassar',
    );

    expect($companyCalendar[$employee->id]['2026-11-02']['events'])->toHaveCount(2)
        ->and($companyCalendar[$employee->id]['2026-11-02']['status'])->toBe('present');
});
