<?php

use App\Enums\AttendanceType;
use App\Enums\DailyAttendanceStatus;
use App\Enums\OvertimeApprovalStatus;
use App\Enums\OvertimeState;
use App\Enums\UserRole;
use App\Enums\WorkType;
use App\Http\Controllers\AdminDashboardController;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\DailyStatusCorrection;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\OvertimeRequest;
use App\Models\User;
use App\Services\DailyAttendanceStatusService;
use App\Services\EmployeeDashboardAggregationService;
use Carbon\Carbon;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk()
        ->assertSee('Selamat datang')
        ->assertDontSee('Dashboard Administrasi')
        ->assertDontSee('Pegawai aktif');
});

test('administrators see the administrative dashboard summary', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Admin])->save();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dashboard Administrasi')
        ->assertSee('Pegawai aktif')
        ->assertSee('Aktivitas absensi terbaru')
        ->assertSee('Permohonan menunggu');
});

test('admin personal dashboard is separate from the admin overview', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Admin])->save();
    Employee::create(['user_id' => $user->id]);

    $this->actingAs($user)->get(route('my-dashboard'))
        ->assertOk()
        ->assertSee('Status absensi hari ini')
        ->assertDontSee('Pegawai aktif');

    $this->get(route('dashboard'))->assertOk()->assertSee('Pegawai aktif');
});

test('employee dashboard does not expose administrative summary when no employee profile is linked', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Profil pegawai belum tersedia')
        ->assertDontSee('Dashboard Administrasi')
        ->assertDontSee('Pegawai aktif')
        ->assertDontSee('Permohonan menunggu');
});

test('dashboard metric cards center labels and values without separators', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Employee])->save();
    Employee::create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Ringkasan bulan ini')
        ->assertSee('grid-cols-2 gap-4 text-center xl:grid-cols-4', false)
        ->assertDontSee('divide-sanmal-border', false)
        ->assertSee('items-center', false)
        ->assertSee('text-center', false)
        ->assertSee('flex flex-col items-center gap-2 text-center', false);
});

test('employee dashboard shows pending leave request breakdown for the authenticated employee and keeps notifications visible on mobile', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Employee])->save();
    $employee = Employee::create(['user_id' => $user->id]);

    $otherUser = User::factory()->create();
    $otherUser->forceFill(['role' => UserRole::Employee])->save();
    $otherEmployee = Employee::create(['user_id' => $otherUser->id]);

    $leaveType = LeaveType::create([
        'code' => 'cuti',
        'name' => 'Cuti Tahunan',
        'is_active' => true,
    ]);
    $sickType = LeaveType::create([
        'code' => 'sakit',
        'name' => 'Sakit',
        'is_active' => true,
    ]);

    foreach ([
        [$employee, $leaveType, 'pending'],
        [$employee, $sickType, 'pending'],
        [$employee, $leaveType, 'approved'],
        [$otherEmployee, $leaveType, 'pending'],
    ] as [$owner, $type, $status]) {
        LeaveRequest::create([
            'employee_id' => $owner->id,
            'leave_type_id' => $type->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'reason' => 'Keperluan pengujian',
            'status' => $status,
        ]);
    }

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Pengajuan Saya')
        ->assertSee('Belum ada aktivitas absensi.')
        ->assertDontSee('Tidak hadir')
        ->assertSee('Cuti Tahunan:')
        ->assertSee('Sakit:')
        ->assertSee('aria-label="Buka notifikasi"', false)
        ->assertSee('class="group relative"', false);
});

test('employee dashboard aggregation counts corrected present dates and calculates a closed session duration', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Employee])->save();
    $employee = Employee::create(['user_id' => $user->id]);

    DailyStatusCorrection::create([
        'employee_id' => $employee->id,
        'attendance_date' => '2026-11-02',
        'status' => DailyAttendanceStatus::Present,
        'reason' => 'Koreksi status harian',
        'created_by' => $user->id,
    ]);

    $clockIn = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => Carbon::parse('2026-11-03 00:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-03',
    ]);

    AttendanceRecord::create([
        'employee_id' => $employee->id,
        'session_anchor_id' => $clockIn->id,
        'type' => AttendanceType::ClockOut,
        'work_type' => WorkType::Office,
        'recorded_at' => Carbon::parse('2026-11-03 09:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-03',
    ]);

    $anchor = $clockIn->fresh();
    expect($anchor->session_anchor_id)->toBe($anchor->id)
        ->and($anchor->sessionEvents)->toHaveCount(2);

    $dashboard = app(EmployeeDashboardAggregationService::class)->forEmployee(
        $employee,
        Carbon::parse('2026-11-03 10:00:00', 'UTC'),
    );

    expect($dashboard['monthly']['presentDays'])->toBe(2)
        ->and($dashboard['monthly']['incompleteDays'])->toBe(0)
        ->and($dashboard['monthly']['workMinutes'])->toBe(540)
        ->and($dashboard['monthly']['workIsProvisional'])->toBeFalse()
        ->and($dashboard['todayStatus']['status'])->toBe(DailyAttendanceStatus::Present->value)
        ->and($dashboard['todayIsRunning'])->toBeFalse();
});

test('employee dashboard separates approved overtime from net ordinary work duration', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Employee])->save();
    $employee = Employee::create(['user_id' => $user->id]);

    $clockIn = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => Carbon::parse('2026-11-03 00:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-03',
    ]);

    $overtimeStart = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'session_anchor_id' => $clockIn->id,
        'type' => AttendanceType::OvertimeStart,
        'work_type' => WorkType::Office,
        'recorded_at' => Carbon::parse('2026-11-03 08:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-03',
    ]);

    $clockOut = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'session_anchor_id' => $clockIn->id,
        'type' => AttendanceType::ClockOut,
        'work_type' => WorkType::Office,
        'recorded_at' => Carbon::parse('2026-11-03 10:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-03',
    ]);

    OvertimeRequest::create([
        'employee_id' => $employee->id,
        'session_date' => '2026-11-03',
        'clock_in_record_id' => $clockIn->id,
        'start_record_id' => $overtimeStart->id,
        'clock_out_record_id' => $clockOut->id,
        'scheduled_start_at' => Carbon::parse('2026-11-03 08:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'state' => OvertimeState::Completed,
        'approval_status' => OvertimeApprovalStatus::Approved,
        'finalization_source' => 'actual',
    ]);

    $dashboard = app(EmployeeDashboardAggregationService::class)->forEmployee(
        $employee,
        Carbon::parse('2026-11-03 11:00:00', 'UTC'),
    );

    expect($dashboard['monthly']['workMinutes'])->toBe(480)
        ->and($dashboard['monthly']['overtimeMinutes'])->toBe(120)
        ->and($dashboard['monthly']['workIsProvisional'])->toBeFalse()
        ->and($dashboard['monthly']['overtimeIsProvisional'])->toBeFalse();
});

test('employee dashboard includes an open attendance session as provisional work duration', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Employee])->save();
    $employee = Employee::create(['user_id' => $user->id]);

    AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => Carbon::parse('2026-11-03 00:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-03',
    ]);

    $dashboard = app(EmployeeDashboardAggregationService::class)->forEmployee(
        $employee,
        Carbon::parse('2026-11-03 04:00:00', 'UTC'),
    );

    expect($dashboard['monthly']['incompleteDays'])->toBe(1)
        ->and($dashboard['monthly']['workMinutes'])->toBe(240)
        ->and($dashboard['monthly']['workIsProvisional'])->toBeTrue()
        ->and($dashboard['todayDurationMinutes'])->toBe(240)
        ->and($dashboard['todayIsRunning'])->toBeTrue();
});

test('employee dashboard duration follows approved effective clock timestamps', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Employee])->save();
    $employee = Employee::create(['user_id' => $user->id]);

    $clockIn = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => Carbon::parse('2026-11-03 00:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-03',
    ]);
    $clockOut = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'session_anchor_id' => $clockIn->id,
        'type' => AttendanceType::ClockOut,
        'work_type' => WorkType::Office,
        'recorded_at' => Carbon::parse('2026-11-03 09:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-03',
    ]);

    foreach ([
        [$clockIn, 'clock_in', '2026-11-03 01:00:00'],
        [$clockOut, 'clock_out', '2026-11-03 08:00:00'],
    ] as [$record, $type, $effectiveAt]) {
        AttendanceCorrection::create([
            'employee_id' => $employee->id,
            'attendance_record_id' => $record->id,
            'session_anchor_id' => $clockIn->id,
            'requested_type' => $type,
            'requested_recorded_at' => Carbon::parse($effectiveAt, 'UTC'),
            'attendance_date' => '2026-11-03',
            'reason' => 'Koreksi timestamp disetujui',
            'status' => 'approved',
            'reviewed_by' => $user->id,
            'reviewed_at' => Carbon::parse('2026-11-03 10:00:00', 'UTC'),
        ]);
    }

    $dashboard = app(EmployeeDashboardAggregationService::class)->forEmployee(
        $employee,
        Carbon::parse('2026-11-03 11:00:00', 'UTC'),
    );

    expect($dashboard['monthly']['workMinutes'])->toBe(420)
        ->and($dashboard['todayClockIn']->effectiveRecordedAt()->toDateTimeString())->toBe('2026-11-03 01:00:00')
        ->and($dashboard['todayClockOut']->effectiveRecordedAt()->toDateTimeString())->toBe('2026-11-03 08:00:00');
});

test('employee dashboard attributes a cross-midnight session to its clock-in date', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Employee])->save();
    $employee = Employee::create(['user_id' => $user->id]);

    $clockIn = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => Carbon::parse('2026-11-03 15:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-03',
    ]);

    AttendanceRecord::create([
        'employee_id' => $employee->id,
        'session_anchor_id' => $clockIn->id,
        'type' => AttendanceType::ClockOut,
        'work_type' => WorkType::Office,
        'recorded_at' => Carbon::parse('2026-11-03 18:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-04',
    ]);

    $dashboard = app(EmployeeDashboardAggregationService::class)->forEmployee(
        $employee,
        Carbon::parse('2026-11-04 00:00:00', 'UTC'),
    );

    expect($dashboard['monthly']['presentDays'])->toBe(1)
        ->and($dashboard['monthly']['workMinutes'])->toBe(180)
        ->and($dashboard['todayStatus']['status'])->toBe(DailyAttendanceStatus::NoRecord->value);
});

test('employee dashboard does not display a duration for an attendance day requiring review', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Employee])->save();
    $employee = Employee::create(['user_id' => $user->id]);

    $clockIn = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => Carbon::parse('2026-11-03 00:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-03',
    ]);
    AttendanceRecord::create([
        'employee_id' => $employee->id,
        'session_anchor_id' => $clockIn->id,
        'type' => AttendanceType::ClockOut,
        'work_type' => WorkType::Office,
        'recorded_at' => Carbon::parse('2026-11-03 09:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-03',
    ]);

    $leaveType = LeaveType::create([
        'code' => 'izin',
        'name' => 'Izin',
        'is_active' => true,
    ]);
    LeaveRequest::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => '2026-11-03',
        'end_date' => '2026-11-03',
        'reason' => 'Izin yang bertabrakan dengan bukti absensi',
        'status' => 'approved',
    ]);

    $dashboard = app(EmployeeDashboardAggregationService::class)->forEmployee(
        $employee,
        Carbon::parse('2026-11-03 10:00:00', 'UTC'),
    );

    expect($dashboard['todayStatus']['status'])->toBe(DailyAttendanceStatus::NeedsReview->value)
        ->and($dashboard['todayDurationMinutes'])->toBeNull()
        ->and($dashboard['todayIsRunning'])->toBeFalse()
        ->and($dashboard['monthly']['workMinutes'])->toBe(0);
});

test('employee dashboard flags finalized overtime that extends beyond its attendance session', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Employee])->save();
    $employee = Employee::create(['user_id' => $user->id]);

    $clockIn = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => Carbon::parse('2026-11-03 00:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-03',
    ]);
    $overtimeStart = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'session_anchor_id' => $clockIn->id,
        'type' => AttendanceType::OvertimeStart,
        'work_type' => WorkType::Office,
        'recorded_at' => Carbon::parse('2026-11-03 08:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-03',
    ]);
    $clockOut = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'session_anchor_id' => $clockIn->id,
        'type' => AttendanceType::ClockOut,
        'work_type' => WorkType::Office,
        'recorded_at' => Carbon::parse('2026-11-03 09:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-03',
    ]);

    OvertimeRequest::create([
        'employee_id' => $employee->id,
        'session_date' => '2026-11-03',
        'clock_in_record_id' => $clockIn->id,
        'start_record_id' => $overtimeStart->id,
        'clock_out_record_id' => $clockOut->id,
        'scheduled_start_at' => Carbon::parse('2026-11-03 08:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'state' => OvertimeState::Completed,
        'approval_status' => OvertimeApprovalStatus::Approved,
        'finalization_source' => 'manual',
        'final_start_at' => Carbon::parse('2026-11-03 08:00:00', 'UTC'),
        'final_end_at' => Carbon::parse('2026-11-03 10:00:00', 'UTC'),
    ]);

    $dashboard = app(EmployeeDashboardAggregationService::class)->forEmployee(
        $employee,
        Carbon::parse('2026-11-03 11:00:00', 'UTC'),
    );

    expect($dashboard['monthly']['workMinutes'])->toBe(480)
        ->and($dashboard['monthly']['overtimeMinutes'])->toBe(120)
        ->and($dashboard['monthly']['overtimeNeedsReview'])->toBeTrue();
});

test('employee dashboard excludes a session with duplicate clock-out events from duration', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Employee])->save();
    $employee = Employee::create(['user_id' => $user->id]);

    $clockIn = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => Carbon::parse('2026-11-03 00:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-03',
    ]);

    foreach (['08:00:00', '09:00:00'] as $time) {
        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'session_anchor_id' => $clockIn->id,
            'type' => AttendanceType::ClockOut,
            'work_type' => WorkType::Office,
            'recorded_at' => Carbon::parse('2026-11-03 '.$time, 'UTC'),
            'timezone' => 'Asia/Makassar',
            'attendance_date' => '2026-11-03',
        ]);
    }

    $dashboard = app(EmployeeDashboardAggregationService::class)->forEmployee(
        $employee,
        Carbon::parse('2026-11-03 10:00:00', 'UTC'),
    );

    expect($dashboard['todayStatus']['status'])->toBe(DailyAttendanceStatus::NeedsReview->value)
        ->and($dashboard['todayDurationMinutes'])->toBeNull()
        ->and($dashboard['monthly']['workMinutes'])->toBe(0);
});

test('employee dashboard attributes a cross-month session to its clock-in month in the attendance timezone', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Employee])->save();
    $employee = Employee::create(['user_id' => $user->id]);

    $clockIn = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => Carbon::parse('2026-10-31 15:30:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-10-31',
    ]);

    AttendanceRecord::create([
        'employee_id' => $employee->id,
        'session_anchor_id' => $clockIn->id,
        'type' => AttendanceType::ClockOut,
        'work_type' => WorkType::Office,
        'recorded_at' => Carbon::parse('2026-10-31 17:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-01',
    ]);

    $dashboard = app(EmployeeDashboardAggregationService::class)->forEmployee(
        $employee,
        Carbon::parse('2026-10-31 16:30:00', 'UTC'),
    );

    expect($dashboard['today'])->toContain('01 November 2026')
        ->and($dashboard['monthly']['presentDays'])->toBe(0)
        ->and($dashboard['monthly']['workMinutes'])->toBe(0)
        ->and($dashboard['todayStatus']['status'])->toBe(DailyAttendanceStatus::NoRecord->value);
});

test('pending overtime moves from ordinary work to overtime when approved', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Employee])->save();
    $employee = Employee::create(['user_id' => $user->id]);

    $clockIn = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => Carbon::parse('2026-11-03 00:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-03',
    ]);
    $overtimeStart = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'session_anchor_id' => $clockIn->id,
        'type' => AttendanceType::OvertimeStart,
        'work_type' => WorkType::Office,
        'recorded_at' => Carbon::parse('2026-11-03 08:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-03',
    ]);
    $clockOut = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'session_anchor_id' => $clockIn->id,
        'type' => AttendanceType::ClockOut,
        'work_type' => WorkType::Office,
        'recorded_at' => Carbon::parse('2026-11-03 10:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-03',
    ]);

    $request = OvertimeRequest::create([
        'employee_id' => $employee->id,
        'session_date' => '2026-11-03',
        'clock_in_record_id' => $clockIn->id,
        'start_record_id' => $overtimeStart->id,
        'clock_out_record_id' => $clockOut->id,
        'scheduled_start_at' => Carbon::parse('2026-11-03 08:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'state' => OvertimeState::Completed,
        'approval_status' => OvertimeApprovalStatus::Pending,
        'finalization_source' => 'actual',
    ]);

    $service = app(EmployeeDashboardAggregationService::class);
    $beforeApproval = $service->forEmployee($employee, Carbon::parse('2026-11-03 11:00:00', 'UTC'));

    $request->update(['approval_status' => OvertimeApprovalStatus::Approved]);
    $afterApproval = $service->forEmployee($employee, Carbon::parse('2026-11-03 11:00:00', 'UTC'));

    expect($beforeApproval['monthly']['workMinutes'])->toBe(600)
        ->and($beforeApproval['monthly']['overtimeMinutes'])->toBe(0)
        ->and($afterApproval['monthly']['workMinutes'])->toBe(480)
        ->and($afterApproval['monthly']['overtimeMinutes'])->toBe(120);
});

test('employee monthly attendance classification matches the admin daily summary for the same employee', function () {
    $this->travelTo(Carbon::parse('2026-11-03 11:00:00', 'UTC'));

    $employeeUser = User::factory()->create();
    $employeeUser->forceFill(['role' => UserRole::Employee])->save();
    $employee = Employee::create(['user_id' => $employeeUser->id]);

    $clockIn = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => Carbon::parse('2026-11-03 00:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-03',
    ]);
    AttendanceRecord::create([
        'employee_id' => $employee->id,
        'session_anchor_id' => $clockIn->id,
        'type' => AttendanceType::ClockOut,
        'work_type' => WorkType::Office,
        'recorded_at' => Carbon::parse('2026-11-03 08:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-03',
    ]);

    $employeeDashboard = app(EmployeeDashboardAggregationService::class)->forEmployee(
        $employee,
        Carbon::parse('2026-11-03 11:00:00', 'UTC'),
    );

    $admin = User::factory()->create();
    $admin->forceFill(['role' => UserRole::Admin])->save();
    $this->actingAs($admin);

    $adminView = app(AdminDashboardController::class)->__invoke(
        app(DailyAttendanceStatusService::class),
        app(EmployeeDashboardAggregationService::class),
    );

    expect($employeeDashboard['monthly']['presentDays'])->toBe(1)
        ->and($adminView->getData()['presentToday'])->toBe(1);
});

test('employee dashboard aggregates multiple approved overtime intervals within one session', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Employee])->save();
    $employee = Employee::create(['user_id' => $user->id]);

    $clockIn = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => Carbon::parse('2026-11-03 08:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-03',
    ]);

    foreach ([
        ['14:00:00', '15:00:00'],
        ['16:00:00', '17:00:00'],
    ] as [$startTime, $endTime]) {
        $overtimeStart = AttendanceRecord::create([
            'employee_id' => $employee->id,
            'session_anchor_id' => $clockIn->id,
            'type' => AttendanceType::OvertimeStart,
            'work_type' => WorkType::Office,
            'recorded_at' => Carbon::parse('2026-11-03 '.$startTime, 'UTC'),
            'timezone' => 'Asia/Makassar',
            'attendance_date' => '2026-11-03',
        ]);

        OvertimeRequest::create([
            'employee_id' => $employee->id,
            'session_date' => '2026-11-03',
            'clock_in_record_id' => $clockIn->id,
            'start_record_id' => $overtimeStart->id,
            'scheduled_start_at' => Carbon::parse('2026-11-03 '.$startTime, 'UTC'),
            'timezone' => 'Asia/Makassar',
            'state' => OvertimeState::Completed,
            'approval_status' => OvertimeApprovalStatus::Approved,
            'finalization_source' => 'manual',
            'final_start_at' => Carbon::parse('2026-11-03 '.$startTime, 'UTC'),
            'final_end_at' => Carbon::parse('2026-11-03 '.$endTime, 'UTC'),
        ]);
    }

    AttendanceRecord::create([
        'employee_id' => $employee->id,
        'session_anchor_id' => $clockIn->id,
        'type' => AttendanceType::ClockOut,
        'work_type' => WorkType::Office,
        'recorded_at' => Carbon::parse('2026-11-03 18:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-03',
    ]);

    $dashboard = app(EmployeeDashboardAggregationService::class)->forEmployee(
        $employee,
        Carbon::parse('2026-11-03 19:00:00', 'UTC'),
    );

    expect($dashboard['monthly']['presentDays'])->toBe(1)
        ->and($dashboard['monthly']['workMinutes'])->toBe(480)
        ->and($dashboard['monthly']['overtimeMinutes'])->toBe(120);
});

test('employee dashboard marks ambiguous unanchored session events for review', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Employee])->save();
    $employee = Employee::create(['user_id' => $user->id]);

    foreach (['08:00:00', '08:15:00'] as $time) {
        AttendanceRecord::create([
            'employee_id' => $employee->id,
            'type' => AttendanceType::ClockIn,
            'work_type' => WorkType::Office,
            'recorded_at' => Carbon::parse('2026-11-03 '.$time, 'UTC'),
            'timezone' => 'Asia/Makassar',
            'attendance_date' => '2026-11-03',
        ]);
    }

    $unanchoredClockOut = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockOut,
        'work_type' => WorkType::Office,
        'recorded_at' => Carbon::parse('2026-11-03 10:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-03',
    ]);

    expect($unanchoredClockOut->session_anchor_id)->toBeNull();

    $dashboard = app(EmployeeDashboardAggregationService::class)->forEmployee(
        $employee,
        Carbon::parse('2026-11-03 11:00:00', 'UTC'),
    );

    expect($dashboard['todayStatus']['status'])->toBe(DailyAttendanceStatus::NeedsReview->value)
        ->and($dashboard['todayDurationMinutes'])->toBeNull()
        ->and($dashboard['monthly']['workMinutes'])->toBe(0);
});

test('employee dashboard excludes a session with a non-positive effective time interval', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Employee])->save();
    $employee = Employee::create(['user_id' => $user->id]);

    $clockIn = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => Carbon::parse('2026-11-03 09:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-03',
    ]);

    AttendanceRecord::create([
        'employee_id' => $employee->id,
        'session_anchor_id' => $clockIn->id,
        'type' => AttendanceType::ClockOut,
        'work_type' => WorkType::Office,
        'recorded_at' => Carbon::parse('2026-11-03 08:00:00', 'UTC'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-03',
    ]);

    $dashboard = app(EmployeeDashboardAggregationService::class)->forEmployee(
        $employee,
        Carbon::parse('2026-11-03 10:00:00', 'UTC'),
    );

    expect($dashboard['todayStatus']['status'])->toBe(DailyAttendanceStatus::NeedsReview->value)
        ->and($dashboard['todayDurationMinutes'])->toBeNull()
        ->and($dashboard['monthly']['workMinutes'])->toBe(0);
});

test('personal admin dashboard is unavailable without a linked employee profile', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Admin])->save();

    $this->actingAs($user)->get(route('my-dashboard'))->assertNotFound();
});

test('employee accounts cannot access the personal admin dashboard route', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Employee])->save();
    Employee::create(['user_id' => $user->id]);

    $this->actingAs($user)->get(route('my-dashboard'))->assertNotFound();
});
