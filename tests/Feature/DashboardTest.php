<?php

use App\Enums\AttendanceType;
use App\Enums\DailyAttendanceStatus;
use App\Enums\OvertimeApprovalStatus;
use App\Enums\OvertimeState;
use App\Enums\UserRole;
use App\Enums\WorkType;
use App\Models\AttendanceRecord;
use App\Models\DailyStatusCorrection;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\LeaveType;
use App\Models\User;
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
        ->assertSee('Belum ada data')
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
