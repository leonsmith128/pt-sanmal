<?php

use App\Actions\ConfirmOvertimeStart;
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
