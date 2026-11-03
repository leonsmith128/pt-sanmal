<?php

use App\Enums\AttendanceType;
use App\Enums\DailyAttendanceStatus;
use App\Enums\UserRole;
use App\Enums\WorkType;
use App\Models\AttendanceRecord;
use App\Models\DailyStatusCorrection;
use App\Models\Employee;
use App\Models\LeaveRequest;
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
        ->assertSee('Cuti Tahunan: 1')
        ->assertSee('Sakit: 1')
        ->assertSee('aria-label="Buka notifikasi"', false)
        ->assertDontSee('hidden lg:block');
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
        'recorded_at' => Carbon::parse('2026-11-03 08:00:00', 'Asia/Makassar'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-03',
    ]);

    AttendanceRecord::create([
        'employee_id' => $employee->id,
        'session_anchor_id' => $clockIn->id,
        'type' => AttendanceType::ClockOut,
        'work_type' => WorkType::Office,
        'recorded_at' => Carbon::parse('2026-11-03 17:00:00', 'Asia/Makassar'),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-03',
    ]);

    $dashboard = app(EmployeeDashboardAggregationService::class)->forEmployee(
        $employee,
        Carbon::parse('2026-11-03 18:00:00', 'Asia/Makassar'),
    );

    expect($dashboard['monthly']['presentDays'])->toBe(2)
        ->and($dashboard['monthly']['incompleteDays'])->toBe(0)
        ->and($dashboard['monthly']['workMinutes'])->toBe(540)
        ->and($dashboard['monthly']['workIsProvisional'])->toBeFalse()
        ->and($dashboard['todayStatus']['status'])->toBe(DailyAttendanceStatus::Present->value)
        ->and($dashboard['todayIsRunning'])->toBeFalse();
});
