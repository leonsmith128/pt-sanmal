<?php

use App\Enums\UserRole;
use App\Models\DailyStatusCorrection;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\DailyAttendanceStatusService;

function createUserWithRole(UserRole $role): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => $role, 'is_active' => true])->save();

    return $user;
}

function createEmployeeFor(User $user): Employee
{
    return Employee::create(['user_id' => $user->id]);
}

it('lets an employee submit a leave request for their own profile', function () {
    $user = createUserWithRole(UserRole::Employee);
    createEmployeeFor($user);
    $type = LeaveType::create(['code' => 'izin', 'name' => 'Izin', 'is_active' => true]);

    $response = $this->actingAs($user)->post(route('leave-requests.store'), [
        'leave_type_id' => $type->id,
        'start_date' => '2026-11-02',
        'end_date' => '2026-11-03',
        'reason' => 'Keperluan keluarga.',
    ]);

    $response->assertRedirect(route('leave-requests.index'));
    $this->assertDatabaseHas('leave_requests', [
        'employee_id' => $user->employee->id,
        'leave_type_id' => $type->id,
        'status' => LeaveRequest::STATUS_PENDING,
    ]);
});

it('turns an approved request into a daily status without creating clock events', function () {
    $employeeUser = createUserWithRole(UserRole::Employee);
    $employee = createEmployeeFor($employeeUser);
    $admin = createUserWithRole(UserRole::Admin);
    $type = LeaveType::create(['code' => 'sakit', 'name' => 'Sakit', 'is_active' => true]);

    $leaveRequest = $employee->leaveRequests()->create([
        'leave_type_id' => $type->id,
        'start_date' => '2026-11-04',
        'end_date' => '2026-11-05',
        'reason' => 'Sedang sakit.',
        'status' => LeaveRequest::STATUS_PENDING,
    ]);

    $this->actingAs($admin)->post(route('admin.leave-requests.decide', $leaveRequest), [
        'decision' => 'approved',
        'decision_note' => 'Disetujui.',
    ])->assertRedirect(route('admin.leave-requests.show', $leaveRequest));

    $daily = app(DailyAttendanceStatusService::class)->forEmployeeDate($employee, '2026-11-04');

    expect($daily['status'])->toBe('sick')
        ->and($daily['needs_review'])->toBeFalse()
        ->and($daily['events'])->toBeEmpty();

    $this->assertDatabaseMissing('attendance_records', ['employee_id' => $employee->id]);
    $this->assertDatabaseHas('audit_logs', [
        'action' => 'leave.approved',
        'auditable_type' => LeaveRequest::class,
        'auditable_id' => $leaveRequest->id,
    ]);
});

it('allows an administrator to correct a daily status when no attendance record exists and audits it', function () {
    $employeeUser = createUserWithRole(UserRole::Employee);
    $employee = createEmployeeFor($employeeUser);
    $admin = createUserWithRole(UserRole::Superadmin);

    $this->actingAs($admin)->post(route('admin.daily-attendance.correct'), [
        'employee_id' => $employee->id,
        'attendance_date' => '2026-11-06',
        'status' => 'absent',
        'reason' => 'Admin mengonfirmasi pegawai tidak hadir.',
    ])->assertRedirect(route('admin.daily-attendance', ['date' => '2026-11-06']));

    $daily = app(DailyAttendanceStatusService::class)->forEmployeeDate($employee, '2026-11-06');

    expect($daily['status'])->toBe('absent')
        ->and($daily['needs_review'])->toBeFalse()
        ->and($daily['events'])->toBeEmpty();

    $this->assertDatabaseHas('daily_status_corrections', [
        'employee_id' => $employee->id,
        'attendance_date' => '2026-11-06',
        'status' => 'absent',
        'created_by' => $admin->id,
    ]);
    $this->assertDatabaseHas('audit_logs', [
        'action' => 'attendance.daily_status_created',
        'auditable_type' => DailyStatusCorrection::class,
    ]);
});

it('rejects a second decision for a request that is no longer pending', function () {
    $employeeUser = createUserWithRole(UserRole::Employee);
    $employee = createEmployeeFor($employeeUser);
    $admin = createUserWithRole(UserRole::Admin);
    $type = LeaveType::create(['code' => 'cuti', 'name' => 'Cuti', 'is_active' => true]);
    $leaveRequest = $employee->leaveRequests()->create([
        'leave_type_id' => $type->id,
        'start_date' => '2026-11-07',
        'end_date' => '2026-11-07',
        'reason' => 'Keperluan pribadi.',
        'status' => LeaveRequest::STATUS_APPROVED,
    ]);

    $this->actingAs($admin)->post(route('admin.leave-requests.decide', $leaveRequest), [
        'decision' => 'rejected',
    ])->assertStatus(422);
});

it('flags different approved leave types that overlap on the same day', function () {
    $employeeUser = createUserWithRole(UserRole::Employee);
    $employee = createEmployeeFor($employeeUser);
    $permission = LeaveType::create(['code' => 'izin', 'name' => 'Izin', 'is_active' => true]);
    $sick = LeaveType::create(['code' => 'sakit', 'name' => 'Sakit', 'is_active' => true]);

    foreach ([$permission, $sick] as $type) {
        $employee->leaveRequests()->create([
            'leave_type_id' => $type->id,
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-10',
            'reason' => 'Permohonan yang disetujui.',
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);
    }

    $daily = app(DailyAttendanceStatusService::class)->forEmployeeDate($employee, '2026-11-10');

    expect($daily['status'])->toBe('needs_review')
        ->and($daily['needs_review'])->toBeTrue()
        ->and($daily['reason'])->toContain('jenis status berbeda');
});
