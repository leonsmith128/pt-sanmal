<?php

use App\Actions\RecordAttendance;
use App\Enums\LeaveRequestStatus;
use App\Enums\UserRole;
use App\Models\DailyStatusCorrection;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\DailyAttendanceStatusService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

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


it('renders responsive admin leave-request cards and status quick filters', function () {
    $admin = createUserWithRole(UserRole::Admin);
    createEmployeeFor($admin);

    $employeeUser = createUserWithRole(UserRole::Employee);
    $employee = createEmployeeFor($employeeUser);
    $type = LeaveType::create(['code' => 'izin-ui', 'name' => 'Izin', 'is_active' => true]);

    $employee->leaveRequests()->create([
        'leave_type_id' => $type->id,
        'start_date' => '2026-11-06',
        'end_date' => '2026-11-07',
        'reason' => 'Keperluan keluarga.',
        'status' => LeaveRequestStatus::Pending,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.leave-requests.index'))
        ->assertOk()
        ->assertSee('Filter cepat status')
        ->assertSee('md:hidden', false)
        ->assertSee('hidden overflow-hidden md:block', false)
        ->assertSee('Buka detail')
        ->assertSee('Menunggu');
});

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
        'attendance_date' => '2026-11-06 00:00:00',
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

it('batches daily attendance status for multiple employees and date range efficiently', function () {
    $emp1 = createEmployeeFor(createUserWithRole(UserRole::Employee));
    $emp2 = createEmployeeFor(createUserWithRole(UserRole::Employee));
    $type = LeaveType::create(['code' => 'cuti', 'name' => 'Cuti', 'is_active' => true]);

    $emp1->leaveRequests()->create([
        'leave_type_id' => $type->id,
        'start_date' => '2026-11-15',
        'end_date' => '2026-11-16',
        'reason' => 'Cuti tahunan.',
        'status' => LeaveRequestStatus::Approved,
    ]);

    $service = app(DailyAttendanceStatusService::class);
    $batch = $service->forEmployeesDateRange(collect([$emp1, $emp2]), '2026-11-15', '2026-11-16');

    expect($batch)->toHaveKeys([$emp1->id, $emp2->id])
        ->and($batch[$emp1->id]['2026-11-15']['status'])->toBe('leave')
        ->and($batch[$emp1->id]['2026-11-16']['status'])->toBe('leave')
        ->and($batch[$emp2->id]['2026-11-15']['status'])->toBe('no_record');
});

it('excludes employees with approved leave requests from notYetPresent on admin kehadiran page', function () {
    $empUser = createUserWithRole(UserRole::Employee);
    $emp = createEmployeeFor($empUser);
    $admin = createUserWithRole(UserRole::Admin);
    $type = LeaveType::create(['code' => 'izin', 'name' => 'Izin', 'is_active' => true]);

    $today = now(RecordAttendance::DEFAULT_TIMEZONE)->toDateString();

    $emp->leaveRequests()->create([
        'leave_type_id' => $type->id,
        'start_date' => $today,
        'end_date' => $today,
        'reason' => 'Izin keperluan resmi.',
        'status' => LeaveRequestStatus::Approved,
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.kehadiran')
        ->assertOk()
        ->assertSet('notYetPresent', fn ($list) => $list->doesntContain('id', $emp->id));
});

it('rejects overlapping leave requests while a previous request is pending or approved', function () {
    $user = createUserWithRole(UserRole::Employee);
    $employee = createEmployeeFor($user);
    $type = LeaveType::create(['code' => 'izin', 'name' => 'Izin', 'is_active' => true]);

    $pending = $employee->leaveRequests()->create([
        'leave_type_id' => $type->id,
        'start_date' => '2026-11-12',
        'end_date' => '2026-11-14',
        'reason' => 'Permohonan pertama yang menunggu.',
        'status' => LeaveRequestStatus::Pending,
    ]);

    $this->actingAs($user)->post(route('leave-requests.store'), [
        'leave_type_id' => $type->id,
        'start_date' => '2026-11-14',
        'end_date' => '2026-11-15',
        'reason' => 'Permohonan yang bertumpang tindih.',
    ])->assertSessionHasErrors('start_date');

    $pending->update(['status' => LeaveRequestStatus::Cancelled]);
    $approved = $employee->leaveRequests()->create([
        'leave_type_id' => $type->id,
        'start_date' => '2026-11-12',
        'end_date' => '2026-11-14',
        'reason' => 'Permohonan yang sudah disetujui.',
        'status' => LeaveRequestStatus::Approved,
    ]);

    $this->actingAs($user)->post(route('leave-requests.store'), [
        'leave_type_id' => $type->id,
        'start_date' => '2026-11-13',
        'end_date' => '2026-11-15',
        'reason' => 'Permohonan setelah persetujuan sebelumnya.',
    ])->assertSessionHasErrors('start_date');

    expect($approved->fresh()->status)->toBe(LeaveRequestStatus::Approved);
});

it('accepts an optional leave attachment up to 1 MB and stores it on the local disk', function () {
    $user = createUserWithRole(UserRole::Employee);
    createEmployeeFor($user);
    $type = LeaveType::create(['code' => 'izin-bukti', 'name' => 'Izin', 'is_active' => true]);

    Storage::fake('local');
    Storage::fake('s3');
    config(['filesystems.default' => 's3']);

    $this->actingAs($user)->post(route('leave-requests.store'), [
        'leave_type_id' => $type->id,
        'start_date' => '2026-11-20',
        'end_date' => '2026-11-20',
        'reason' => 'Keperluan keluarga dengan bukti pendukung.',
        'attachment' => UploadedFile::fake()->create('bukti.pdf', 1024, 'application/pdf'),
    ])->assertRedirect(route('leave-requests.index'));

    $leaveRequest = LeaveRequest::query()->firstOrFail();

    expect($leaveRequest->attachment_path)->not->toBeNull();
    Storage::disk('local')->assertExists($leaveRequest->attachment_path);
    Storage::disk('s3')->assertMissing($leaveRequest->attachment_path);
});

it('rejects leave attachments larger than 1 MB', function () {
    $user = createUserWithRole(UserRole::Employee);
    createEmployeeFor($user);
    $type = LeaveType::create(['code' => 'izin-besar', 'name' => 'Izin', 'is_active' => true]);

    Storage::fake('local');

    $this->actingAs($user)->from(route('leave-requests.create'))->post(route('leave-requests.store'), [
        'leave_type_id' => $type->id,
        'start_date' => '2026-11-21',
        'end_date' => '2026-11-21',
        'reason' => 'Keperluan keluarga dengan bukti pendukung.',
        'attachment' => UploadedFile::fake()->create('bukti.pdf', 1025, 'application/pdf'),
    ])->assertRedirect(route('leave-requests.create'))
        ->assertSessionHasErrors('attachment');

    expect(LeaveRequest::query()->count())->toBe(0);
});
