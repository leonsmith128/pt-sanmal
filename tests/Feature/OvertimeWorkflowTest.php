<?php

use App\Actions\ConfirmOvertimeStart;
use App\Actions\CreateOvertimeRequest;
use App\Actions\RecordAttendance;
use App\Enums\AttendanceType;
use App\Enums\OvertimeApprovalStatus;
use App\Enums\OvertimeState;
use App\Enums\SelfieSource;
use App\Enums\UserRole;
use App\Enums\WorkType;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

function overtimeEmployee(): Employee
{
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Employee, 'is_active' => true])->save();

    return Employee::create(['user_id' => $user->id]);
}

function overtimeClockIn(Employee $employee): AttendanceRecord
{
    $now = now();

    return AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => $now,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => $now->copy()->setTimezone('Asia/Makassar')->toDateString(),
    ]);
}

function overtimeRequest(Employee $employee, AttendanceRecord $clockIn, string $scheduledAt): OvertimeRequest
{
    return OvertimeRequest::create([
        'employee_id' => $employee->id,
        'clock_in_record_id' => $clockIn->id,
        'reason' => 'Penyelesaian pekerjaan kalibrasi.',
        'scheduled_start_at' => $scheduledAt,
        'timezone' => 'Asia/Makassar',
        'state' => OvertimeState::AwaitingPhoto,
        'approval_status' => OvertimeApprovalStatus::Pending,
    ]);
}

it('requires overtime start photo before clock out after the scheduled start', function () {
    Storage::fake('local');
    $employee = overtimeEmployee();
    $clockIn = overtimeClockIn($employee);
    overtimeRequest($employee, $clockIn, now()->subMinute()->toDateTimeString());

    expect(fn () => app(RecordAttendance::class)->handle(
        employee: $employee,
        type: AttendanceType::ClockOut,
        workType: null,
        locationId: null,
        projectId: null,
        latitude: -5.14,
        longitude: 119.41,
        accuracy: 25,
        selfie: UploadedFile::fake()->image('clock-out.jpg'),
        selfieSource: SelfieSource::Camera,
    ))->toThrow(ValidationException::class);
});

it('records overtime photo confirmation without closing the work session', function () {
    Storage::fake('local');
    $employee = overtimeEmployee();
    $clockIn = overtimeClockIn($employee);
    $overtime = overtimeRequest($employee, $clockIn, now()->subMinute());

    $record = app(ConfirmOvertimeStart::class)->handle(
        employee: $employee,
        overtime: $overtime,
        latitude: -5.14,
        longitude: 119.41,
        accuracy: 25,
        addressSnapshot: 'Kassi-Kassi, Makassar',
        selfie: UploadedFile::fake()->image('overtime.jpg'),
    );

    expect($record->type)->toBe(AttendanceType::OvertimeStart)
        ->and($record->address_snapshot)->toBe('Kassi-Kassi, Makassar')
        ->and(RecordAttendance::openSession($employee)?->id)->toBe($clockIn->id)
        ->and($overtime->fresh()->state)->toBe(OvertimeState::InProgress)
        ->and($overtime->fresh()->photo_confirmed_at)->not->toBeNull();
});

it('completes a confirmed overtime session when the employee clocks out', function () {
    Storage::fake('local');
    $employee = overtimeEmployee();
    $clockIn = overtimeClockIn($employee);
    $overtime = overtimeRequest($employee, $clockIn, now()->subMinute());

    app(ConfirmOvertimeStart::class)->handle(
        employee: $employee,
        overtime: $overtime,
        latitude: -5.14,
        longitude: 119.41,
        accuracy: 25,
        addressSnapshot: 'Kassi-Kassi, Makassar',
        selfie: UploadedFile::fake()->image('overtime.jpg'),
    );

    $clockOut = app(RecordAttendance::class)->handle(
        employee: $employee,
        type: AttendanceType::ClockOut,
        workType: null,
        locationId: null,
        projectId: null,
        latitude: -5.14,
        longitude: 119.41,
        accuracy: 25,
        selfie: UploadedFile::fake()->image('clock-out.jpg'),
        selfieSource: SelfieSource::Camera,
    );

    expect($clockOut->type)->toBe(AttendanceType::ClockOut)
        ->and($overtime->fresh()->state)->toBe(OvertimeState::Completed)
        ->and($overtime->fresh()->clock_out_record_id)->toBe($clockOut->id);
});

it('blocks clock out before a future scheduled overtime until the request is cancelled', function () {
    Storage::fake('local');
    $employee = overtimeEmployee();
    $clockIn = overtimeClockIn($employee);
    $overtime = overtimeRequest($employee, $clockIn, now()->addHour()->toDateTimeString());
    $overtime->update(['state' => OvertimeState::Scheduled]);

    expect(fn () => app(RecordAttendance::class)->handle(
        employee: $employee,
        type: AttendanceType::ClockOut,
        workType: null,
        locationId: null,
        projectId: null,
        latitude: -5.14,
        longitude: 119.41,
        accuracy: 25,
        selfie: UploadedFile::fake()->image('clock-out.jpg'),
        selfieSource: SelfieSource::Camera,
    ))->toThrow(ValidationException::class);

    expect($overtime->fresh()->state)->toBe(OvertimeState::Scheduled)
        ->and($overtime->fresh()->cancelled_at)->toBeNull();
});

it('allows cancellation after the scheduled start while actual start is not confirmed', function () {
    $employee = overtimeEmployee();
    $clockIn = overtimeClockIn($employee);
    $overtime = overtimeRequest($employee, $clockIn, now()->subMinute()->toDateTimeString());
    $overtime->update(['state' => OvertimeState::AwaitingPhoto]);

    $response = $this->actingAs($employee->user)->patch(route('overtime-requests.cancel', $overtime));

    $response->assertRedirect(route('overtime-requests.index'));
    expect($overtime->fresh()->state)->toBe(OvertimeState::Cancelled)
        ->and($overtime->fresh()->cancelled_at)->not->toBeNull();
});

it('keeps a future-date overtime schedule detached from the current work session', function () {
    $employee = overtimeEmployee();
    overtimeClockIn($employee);
    $start = now('Asia/Makassar')->addDays(2)->setTime(17, 0);
    $overtime = app(CreateOvertimeRequest::class)->handle(
        employee: $employee,
        reason: null,
        scheduledStartLocal: $start->format('Y-m-d\\TH:i'),
    );

    expect($overtime->clock_in_record_id)->toBeNull()
        ->and($overtime->reason)->toBeNull()
        ->and($overtime->scheduled_start_at->toISOString())->toBe($start->copy()->utc()->toISOString())
        ->and($overtime->scheduled_end_at)->toBeNull();
});

it('anchors a clock-out after midnight to the original clock-in attendance date', function () {
    Storage::fake('local');
    $employee = overtimeEmployee();
    $clockIn = overtimeClockIn($employee);
    $sessionDate = now('Asia/Makassar')->subDay()->toDateString();
    $clockIn->update([
        'recorded_at' => now()->subHours(6),
        'attendance_date' => $sessionDate,
    ]);

    $clockOut = app(RecordAttendance::class)->handle(
        employee: $employee,
        type: AttendanceType::ClockOut,
        workType: null,
        locationId: null,
        projectId: null,
        latitude: -5.14,
        longitude: 119.41,
        accuracy: 25,
        selfie: UploadedFile::fake()->image('clock-out-midnight.jpg'),
        selfieSource: SelfieSource::Camera,
    );

    expect($clockOut->attendance_date->toDateString())->toBe($sessionDate);
});

it('groups legacy cross-midnight clock-out into the clock-in day in daily status', function () {
    $employee = overtimeEmployee();
    $start = now('Asia/Makassar')->subDay()->setTime(20, 0);
    $end = $start->copy()->addHours(8);

    AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => $start->copy()->utc(),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => $start->toDateString(),
    ]);
    AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockOut,
        'work_type' => WorkType::Office,
        'recorded_at' => $end->copy()->utc(),
        'timezone' => 'Asia/Makassar',
        'attendance_date' => $end->toDateString(),
    ]);

    $result = app(DailyAttendanceStatusService::class)->forEmployeeDate($employee, $start->toDateString());

    expect($result['status'])->toBe('present')
        ->and($result['events'])->toHaveCount(2);
});

it('allows an overtime request without a reason through the employee form', function () {
    $employee = overtimeEmployee();
    $scheduledStart = now('Asia/Makassar')->addDay()->setTime(17, 0);

    $response = $this->actingAs($employee->user)->post(route('overtime-requests.store'), [
        'scheduled_start_at' => $scheduledStart->format('Y-m-d\\TH:i'),
    ]);

    $response->assertRedirect(route('overtime-requests.index'));
    expect(OvertimeRequest::query()->where('employee_id', $employee->id)->first()?->reason)->toBeNull();
});

it('prevents an administrator from approving their own overtime request', function () {
    $employee = overtimeEmployee();
    $employee->user->forceFill(['role' => UserRole::Admin])->save();
    $clockIn = overtimeClockIn($employee);
    $overtime = overtimeRequest($employee, $clockIn, now()->addHour()->toDateTimeString());

    $response = $this->actingAs($employee->user)->post(route('admin.overtime-requests.decide', $overtime), [
        'decision' => 'approved',
    ]);

    $response->assertForbidden();
    expect($overtime->fresh()->approval_status)->toBe(OvertimeApprovalStatus::Pending);
});
