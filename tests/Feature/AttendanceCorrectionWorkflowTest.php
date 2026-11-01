<?php

use App\Enums\AttendanceType;
use App\Enums\UserRole;
use App\Enums\WorkType;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function correctionEmployeeUser(): array
{
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Employee, 'is_active' => true])->save();

    return [$user, Employee::create(['user_id' => $user->id])];
}

function correctionAdminUser(): User
{
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();

    return $user;
}

function correctionClockIn(Employee $employee): AttendanceRecord
{
    $recordedAt = now()->subHours(3);

    return AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => $recordedAt,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => $recordedAt->copy()->setTimezone('Asia/Makassar')->toDateString(),
    ]);
}

it('lets an employee submit a correction for their own clock-in and audits the submission', function () {
    [$user, $employee] = correctionEmployeeUser();
    $record = correctionClockIn($employee);
    $requestedLocal = $record->recorded_at->copy()->setTimezone('Asia/Makassar')->subMinutes(15);

    $response = $this->actingAs($user)->post(route('attendance-corrections.store'), [
        'requested_type' => 'clock_in',
        'attendance_record_id' => $record->id,
        'requested_recorded_at_local' => $requestedLocal->format('Y-m-d\\TH:i'),
        'reason' => 'Waktu masuk sesuai bukti yang saya lampirkan.',
    ]);

    $response->assertRedirect(route('leave-requests.index'));

    $correction = AttendanceCorrection::query()->firstOrFail();

    expect($correction->employee_id)->toBe($employee->id)
        ->and($correction->attendance_record_id)->toBe($record->id)
        ->and($correction->session_anchor_id)->toBe($record->id)
        ->and($correction->requested_type)->toBe('clock_in')
        ->and($correction->status)->toBe('pending')
        ->and($correction->requested_recorded_at->equalTo($requestedLocal->copy()->utc()))->toBeTrue();

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $user->id,
        'action' => 'attendance_correction.submitted',
        'auditable_type' => AttendanceCorrection::class,
        'auditable_id' => $correction->id,
    ]);
});

it('does not allow an employee to submit a correction for another employee attendance record', function () {
    [$user] = correctionEmployeeUser();
    [, $otherEmployee] = correctionEmployeeUser();
    $record = correctionClockIn($otherEmployee);
    $requestedLocal = $record->recorded_at->copy()->setTimezone('Asia/Makassar')->subMinutes(10);

    $this->actingAs($user)->post(route('attendance-corrections.store'), [
        'requested_type' => 'clock_in',
        'attendance_record_id' => $record->id,
        'requested_recorded_at_local' => $requestedLocal->format('Y-m-d\\TH:i'),
        'reason' => 'Saya ingin mengubah waktu absensi.',
    ])->assertNotFound();

    expect(AttendanceCorrection::query()->count())->toBe(0);
});

it('rejects a second pending correction for the same attendance event', function () {
    [$user, $employee] = correctionEmployeeUser();
    $record = correctionClockIn($employee);
    $requestedLocal = $record->recorded_at->copy()->setTimezone('Asia/Makassar')->subMinutes(10);

    AttendanceCorrection::create([
        'employee_id' => $employee->id,
        'attendance_record_id' => $record->id,
        'session_anchor_id' => $record->id,
        'requested_type' => 'clock_in',
        'requested_recorded_at' => $requestedLocal->copy()->utc(),
        'attendance_date' => $requestedLocal->toDateString(),
        'reason' => 'Permohonan yang sudah menunggu.',
        'status' => 'pending',
    ]);

    $this->actingAs($user)->post(route('attendance-corrections.store'), [
        'requested_type' => 'clock_in',
        'attendance_record_id' => $record->id,
        'requested_recorded_at_local' => $requestedLocal->copy()->subMinutes(5)->format('Y-m-d\\TH:i'),
        'reason' => 'Mengirim permohonan koreksi yang kedua.',
    ])->assertUnprocessable();

    expect(AttendanceCorrection::query()->count())->toBe(1);
});

it('allows an employee to submit a forgotten clock-out correction for their open session', function () {
    [$user, $employee] = correctionEmployeeUser();
    $clockIn = correctionClockIn($employee);
    $requestedLocal = now('Asia/Makassar')->subMinutes(20);

    $this->actingAs($user)->post(route('attendance-corrections.store'), [
        'requested_type' => 'clock_out',
        'attendance_record_id' => '',
        'session_anchor_id' => $clockIn->id,
        'requested_recorded_at_local' => $requestedLocal->format('Y-m-d\\TH:i'),
        'reason' => 'Saya lupa melakukan Clock Out setelah pekerjaan selesai.',
    ])->assertRedirect(route('leave-requests.index'));

    $correction = AttendanceCorrection::query()->firstOrFail();

    expect($correction->attendance_record_id)->toBeNull()
        ->and($correction->session_anchor_id)->toBe($clockIn->id)
        ->and($correction->requested_type)->toBe('clock_out')
        ->and($correction->status)->toBe('pending');

    $this->assertDatabaseMissing('attendance_records', [
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockOut->value,
    ]);
});

it('stores correction attachments on the private local disk even when another disk is the default', function () {
    [$user, $employee] = correctionEmployeeUser();
    $record = correctionClockIn($employee);
    $requestedLocal = $record->recorded_at->copy()->setTimezone('Asia/Makassar')->subMinutes(10);

    Storage::fake('local');
    Storage::fake('s3');
    config(['filesystems.default' => 's3']);

    $this->actingAs($user)->post(route('attendance-corrections.store'), [
        'requested_type' => 'clock_in',
        'attendance_record_id' => $record->id,
        'requested_recorded_at_local' => $requestedLocal->format('Y-m-d\\TH:i'),
        'reason' => 'Waktu masuk sesuai dokumen pendukung.',
        'attachment' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
    ])->assertRedirect(route('leave-requests.index'));

    $correction = AttendanceCorrection::query()->firstOrFail();

    expect($correction->attachment_path)->not->toBeNull();
    Storage::disk('local')->assertExists($correction->attachment_path);
    Storage::disk('s3')->assertMissing($correction->attachment_path);
});

it('allows an employee to cancel only their own pending correction and records the cancellation', function () {
    [$user, $employee] = correctionEmployeeUser();
    $record = correctionClockIn($employee);
    $requestedAt = $record->recorded_at->copy()->subMinutes(10);
    $correction = AttendanceCorrection::create([
        'employee_id' => $employee->id,
        'attendance_record_id' => $record->id,
        'session_anchor_id' => $record->id,
        'requested_type' => 'clock_in',
        'requested_recorded_at' => $requestedAt,
        'attendance_date' => $requestedAt->copy()->setTimezone('Asia/Makassar')->toDateString(),
        'reason' => 'Waktu masuk perlu disesuaikan.',
        'status' => 'pending',
    ]);

    $this->actingAs($user)->patch(route('attendance-corrections.cancel', $correction))
        ->assertRedirect(route('leave-requests.index'));

    expect($correction->fresh()->status)->toBe('cancelled')
        ->and($record->fresh()->recorded_at->equalTo($record->recorded_at))->toBeTrue();

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $user->id,
        'action' => 'attendance_correction.cancelled',
        'auditable_type' => AttendanceCorrection::class,
        'auditable_id' => $correction->id,
    ]);
});

it('does not allow an employee to cancel another employee correction', function () {
    [$user] = correctionEmployeeUser();
    [, $otherEmployee] = correctionEmployeeUser();
    $record = correctionClockIn($otherEmployee);
    $correction = AttendanceCorrection::create([
        'employee_id' => $otherEmployee->id,
        'attendance_record_id' => $record->id,
        'session_anchor_id' => $record->id,
        'requested_type' => 'clock_in',
        'requested_recorded_at' => $record->recorded_at->copy()->subMinutes(10),
        'attendance_date' => $record->attendance_date,
        'reason' => 'Koreksi milik pegawai lain.',
        'status' => 'pending',
    ]);

    $this->actingAs($user)->patch(route('attendance-corrections.cancel', $correction))
        ->assertNotFound();

    expect($correction->fresh()->status)->toBe('pending');
});

it('does not allow an employee to cancel a correction that has already been processed', function () {
    [$user, $employee] = correctionEmployeeUser();
    $record = correctionClockIn($employee);
    $correction = AttendanceCorrection::create([
        'employee_id' => $employee->id,
        'attendance_record_id' => $record->id,
        'session_anchor_id' => $record->id,
        'requested_type' => 'clock_in',
        'requested_recorded_at' => $record->recorded_at->copy()->subMinutes(10),
        'attendance_date' => $record->attendance_date,
        'reason' => 'Koreksi sudah diproses.',
        'status' => 'approved',
        'reviewed_by' => correctionAdminUser()->id,
        'reviewed_at' => now(),
    ]);

    $this->actingAs($user)->patch(route('attendance-corrections.cancel', $correction))
        ->assertUnprocessable();

    expect($correction->fresh()->status)->toBe('approved');
});

it('allows a new correction request after the previous pending request was cancelled', function () {
    [$user, $employee] = correctionEmployeeUser();
    $record = correctionClockIn($employee);
    $cancelledAt = $record->recorded_at->copy()->subMinutes(20);

    AttendanceCorrection::create([
        'employee_id' => $employee->id,
        'attendance_record_id' => $record->id,
        'session_anchor_id' => $record->id,
        'requested_type' => 'clock_in',
        'requested_recorded_at' => $cancelledAt,
        'attendance_date' => $cancelledAt->copy()->setTimezone('Asia/Makassar')->toDateString(),
        'reason' => 'Permohonan sebelumnya dibatalkan.',
        'status' => 'cancelled',
    ]);

    $newRequestedLocal = $record->recorded_at->copy()->setTimezone('Asia/Makassar')->subMinutes(10);

    $this->actingAs($user)->post(route('attendance-corrections.store'), [
        'requested_type' => 'clock_in',
        'attendance_record_id' => $record->id,
        'requested_recorded_at_local' => $newRequestedLocal->format('Y-m-d\\TH:i'),
        'reason' => 'Saya mengirim ulang koreksi dengan waktu yang benar.',
    ])->assertRedirect(route('leave-requests.index'));

    expect(AttendanceCorrection::query()->count())->toBe(2)
        ->and(AttendanceCorrection::query()->latest('id')->first()->status)->toBe('pending');
});

it('does not allow an administrator to approve a correction after the employee cancelled it', function () {
    [$user, $employee] = correctionEmployeeUser();
    $record = correctionClockIn($employee);
    $requestedAt = $record->recorded_at->copy()->subMinutes(10);
    $correction = AttendanceCorrection::create([
        'employee_id' => $employee->id,
        'attendance_record_id' => $record->id,
        'session_anchor_id' => $record->id,
        'requested_type' => 'clock_in',
        'requested_recorded_at' => $requestedAt,
        'attendance_date' => $requestedAt->copy()->setTimezone('Asia/Makassar')->toDateString(),
        'reason' => 'Permohonan yang dibatalkan pegawai.',
        'status' => 'cancelled',
    ]);

    $admin = correctionAdminUser();

    $this->actingAs($admin)->post(route('admin.attendance-corrections.decide', $correction), [
        'decision' => 'approved',
        'review_note' => 'Tidak boleh diproses setelah pembatalan.',
    ])->assertUnprocessable();

    expect($correction->fresh()->status)->toBe('cancelled')
        ->and($record->fresh()->approvedCorrection)->toBeNull();
});

it('shows cancelled corrections with a localized status in the administrator queue', function () {
    [, $employee] = correctionEmployeeUser();
    $record = correctionClockIn($employee);
    $requestedAt = $record->recorded_at->copy()->subMinutes(10);

    AttendanceCorrection::create([
        'employee_id' => $employee->id,
        'attendance_record_id' => $record->id,
        'session_anchor_id' => $record->id,
        'requested_type' => 'clock_in',
        'requested_recorded_at' => $requestedAt,
        'attendance_date' => $requestedAt->copy()->setTimezone('Asia/Makassar')->toDateString(),
        'reason' => 'Permohonan telah dibatalkan.',
        'status' => 'cancelled',
    ]);

    $this->actingAs(correctionAdminUser())
        ->get(route('admin.attendance-corrections.index', ['status' => 'cancelled']))
        ->assertOk()
        ->assertSee('Dibatalkan')
        ->assertDontSee('Cancelled');
});
