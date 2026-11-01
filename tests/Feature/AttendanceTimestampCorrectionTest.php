<?php

use App\Enums\AttendanceType;
use App\Enums\UserRole;
use App\Enums\WorkType;
use App\Models\AuditLog;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\User;

it('applies an approved timestamp correction without mutating the original attendance evidence', function () {
    $employeeUser = User::factory()->create();
    $employee = Employee::create(['user_id' => $employeeUser->id]);
    $originalTime = now()->subHours(3)->startOfSecond();

    $record = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => $originalTime,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => $originalTime->copy()->setTimezone('Asia/Makassar')->toDateString(),
    ]);

    $correctedTime = $originalTime->copy()->subMinutes(20);
    $correction = AttendanceCorrection::create([
        'employee_id' => $employee->id,
        'attendance_record_id' => $record->id,
        'requested_type' => AttendanceType::ClockIn->value,
        'requested_recorded_at' => $correctedTime,
        'attendance_date' => $correctedTime->copy()->setTimezone('Asia/Makassar')->toDateString(),
        'reason' => 'Waktu masuk perlu disesuaikan dengan bukti pendukung.',
        'status' => 'pending',
    ]);

    $admin = User::factory()->create();
    $admin->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();

    $response = $this->actingAs($admin)->post(
        route('admin.attendance-corrections.decide', $correction),
        ['decision' => 'approved', 'review_note' => 'Bukti telah diperiksa.'],
    );

    $response->assertRedirect(route('admin.attendance-corrections.show', $correction));

    $freshRecord = $record->fresh();
    expect($freshRecord->recorded_at->format('Y-m-d H:i:s'))->toBe($originalTime->format('Y-m-d H:i:s'))
        ->and($freshRecord->effectiveRecordedAt()->format('Y-m-d H:i:s'))->toBe($correctedTime->format('Y-m-d H:i:s'))
        ->and($correction->fresh()->status)->toBe('approved');
});

it('lets another administrator correct a clock event without changing the original timestamp', function () {
    $employeeUser = User::factory()->create();
    $employee = Employee::create(['user_id' => $employeeUser->id]);
    $originalTime = now()->subHours(3);
    $record = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => $originalTime,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => $originalTime->copy()->setTimezone('Asia/Makassar')->toDateString(),
    ]);
    $admin = User::factory()->create();
    $admin->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();
    $correctedLocal = $originalTime->copy()->subMinutes(15)->setTimezone('Asia/Makassar')->startOfMinute();

    $response = $this->actingAs($admin)->post(route('admin.kehadiran.correct-timestamp', $record), [
        'recorded_at_local' => $correctedLocal->format('Y-m-d\TH:i'),
        'reason' => 'Koreksi berdasarkan bukti foto dan laporan supervisor.',
    ]);

    $response->assertRedirect();
    $freshRecord = $record->fresh();
    expect($freshRecord->recorded_at->format('Y-m-d H:i:s'))->toBe($originalTime->format('Y-m-d H:i:s'))
        ->and($freshRecord->effectiveRecordedAt()->format('Y-m-d H:i:s'))->toBe($correctedLocal->copy()->utc()->format('Y-m-d H:i:s'))
        ->and($freshRecord->approvedCorrection)->not->toBeNull();
});

it('prevents an administrator from correcting their own clock event', function () {
    $user = User::factory()->create();
    $user->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();
    $employee = Employee::create(['user_id' => $user->id]);
    $recordedAt = now()->subHour();
    $record = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => $recordedAt,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => $recordedAt->copy()->setTimezone('Asia/Makassar')->toDateString(),
    ]);

    $response = $this->actingAs($user)->post(route('admin.kehadiran.correct-timestamp', $record), [
        'recorded_at_local' => $record->recordedAtLocal()->format('Y-m-d\TH:i'),
        'reason' => 'Koreksi timestamp milik saya sendiri.',
    ]);

    $response->assertForbidden();
    expect($record->fresh()->approvedCorrection)->toBeNull();
});

it('corrects a clock-out timestamp without changing its original event time', function () {
    $employeeUser = User::factory()->create();
    $employee = Employee::create(['user_id' => $employeeUser->id]);
    $clockInTime = now()->subHours(4);
    $clockOutTime = now()->subHours(2);
    AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => $clockInTime,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => $clockInTime->copy()->setTimezone('Asia/Makassar')->toDateString(),
    ]);
    $clockOut = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockOut,
        'work_type' => WorkType::Office,
        'recorded_at' => $clockOutTime,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => $clockOutTime->copy()->setTimezone('Asia/Makassar')->toDateString(),
    ]);
    $admin = User::factory()->create();
    $admin->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();
    $correctedLocal = $clockOutTime->copy()->addMinutes(10)->setTimezone('Asia/Makassar')->startOfMinute();

    $response = $this->actingAs($admin)->post(route('admin.kehadiran.correct-timestamp', $clockOut), [
        'recorded_at_local' => $correctedLocal->format('Y-m-d\TH:i'),
        'reason' => 'Waktu pulang dikoreksi sesuai bukti absensi.',
    ]);

    $response->assertRedirect();
    $freshRecord = $clockOut->fresh();
    expect($freshRecord->recorded_at->format('Y-m-d H:i:s'))->toBe($clockOutTime->format('Y-m-d H:i:s'))
        ->and($freshRecord->effectiveRecordedAt()->format('Y-m-d H:i:s'))->toBe($correctedLocal->copy()->utc()->format('Y-m-d H:i:s'));
});

it('creates a new clock-out event linked to the original session when a forgotten clock-out correction is approved', function () {
    $employeeUser = User::factory()->create();
    $employee = Employee::create(['user_id' => $employeeUser->id]);
    $clockInAt = now()->subHours(5);
    $clockIn = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => $clockInAt,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => $clockInAt->copy()->setTimezone('Asia/Makassar')->toDateString(),
    ]);

    $requestedAt = now()->subMinutes(20);
    $correction = AttendanceCorrection::create([
        'employee_id' => $employee->id,
        'attendance_record_id' => null,
        'session_anchor_id' => $clockIn->id,
        'requested_type' => AttendanceType::ClockOut->value,
        'requested_recorded_at' => $requestedAt,
        'attendance_date' => $requestedAt->copy()->setTimezone('Asia/Makassar')->toDateString(),
        'reason' => 'Lupa melakukan Clock Out setelah menyelesaikan pekerjaan.',
        'status' => 'pending',
    ]);

    $admin = User::factory()->create();
    $admin->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();

    $this->actingAs($admin)->post(route('admin.attendance-corrections.decide', $correction), [
        'decision' => 'approved',
        'review_note' => 'Waktu diperiksa dan disetujui.',
    ])->assertRedirect(route('admin.attendance-corrections.show', $correction));

    $generated = AttendanceRecord::query()->where('attendance_correction_id', $correction->id)->firstOrFail();

    expect($generated->type)->toBe(AttendanceType::ClockOut)
        ->and($generated->source)->toBe('correction')
        ->and($generated->session_anchor_id)->toBe($clockIn->id)
        ->and($generated->recorded_at->format('Y-m-d H:i:s'))->toBe($requestedAt->format('Y-m-d H:i:s'))
        ->and($correction->fresh()->status)->toBe('approved');
});

it('rejects an approved clock-out correction that overlaps the next session', function () {
    $employeeUser = User::factory()->create();
    $employee = Employee::create(['user_id' => $employeeUser->id]);
    $firstClockInAt = now()->subDays(4);
    $firstClockIn = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => $firstClockInAt,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => $firstClockInAt->copy()->setTimezone('Asia/Makassar')->toDateString(),
    ]);
    $firstClockOutAt = now()->subDays(2);
    $firstClockOut = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'session_anchor_id' => $firstClockIn->id,
        'type' => AttendanceType::ClockOut,
        'work_type' => WorkType::Office,
        'recorded_at' => $firstClockOutAt,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => $firstClockIn->attendance_date,
    ]);
    $secondClockInAt = now()->subDay();
    AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => $secondClockInAt,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => $secondClockInAt->copy()->setTimezone('Asia/Makassar')->toDateString(),
    ]);

    $requestedAt = now()->subHour();
    $correction = AttendanceCorrection::create([
        'employee_id' => $employee->id,
        'attendance_record_id' => $firstClockOut->id,
        'session_anchor_id' => $firstClockIn->id,
        'requested_type' => AttendanceType::ClockOut->value,
        'requested_recorded_at' => $requestedAt,
        'attendance_date' => $requestedAt->copy()->setTimezone('Asia/Makassar')->toDateString(),
        'reason' => 'Waktu pulang perlu disesuaikan dengan bukti pendukung.',
        'status' => 'pending',
    ]);
    $admin = User::factory()->create();
    $admin->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();

    $this->actingAs($admin)->post(route('admin.attendance-corrections.decide', $correction), [
        'decision' => 'approved',
        'review_note' => 'Diperiksa.',
    ])->assertStatus(422);

    expect($correction->fresh()->status)->toBe('pending')
        ->and($firstClockOut->fresh()->approvedCorrection)->toBeNull();
});

it('rejects a direct admin clock-out correction that overlaps the next session', function () {
    $employeeUser = User::factory()->create();
    $employee = Employee::create(['user_id' => $employeeUser->id]);
    $firstClockInAt = now()->subDays(4);
    $firstClockIn = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => $firstClockInAt,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => $firstClockInAt->copy()->setTimezone('Asia/Makassar')->toDateString(),
    ]);
    $firstClockOutAt = now()->subDays(2);
    $firstClockOut = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'session_anchor_id' => $firstClockIn->id,
        'type' => AttendanceType::ClockOut,
        'work_type' => WorkType::Office,
        'recorded_at' => $firstClockOutAt,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => $firstClockIn->attendance_date,
    ]);
    $secondClockInAt = now()->subDay();
    AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => $secondClockInAt,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => $secondClockInAt->copy()->setTimezone('Asia/Makassar')->toDateString(),
    ]);
    $admin = User::factory()->create();
    $admin->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();
    $correctedLocal = now()->subHour()->setTimezone('Asia/Makassar')->startOfMinute();

    $this->actingAs($admin)->post(route('admin.kehadiran.correct-timestamp', $firstClockOut), [
        'recorded_at_local' => $correctedLocal->format('Y-m-d\\TH:i'),
        'reason' => 'Koreksi berdasarkan bukti absensi dan laporan supervisor.',
    ])->assertStatus(422);

    expect($firstClockOut->fresh()->approvedCorrection)->toBeNull();
});

it('rejects a direct admin correction when the session already has another clock-out event', function () {
    $employeeUser = User::factory()->create();
    $employee = Employee::create(['user_id' => $employeeUser->id]);
    $clockInAt = now()->subHours(6);
    $clockIn = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => $clockInAt,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => $clockInAt->copy()->setTimezone('Asia/Makassar')->toDateString(),
    ]);
    $firstClockOutAt = now()->subHours(3);
    AttendanceRecord::create([
        'employee_id' => $employee->id,
        'session_anchor_id' => $clockIn->id,
        'type' => AttendanceType::ClockOut,
        'work_type' => WorkType::Office,
        'recorded_at' => $firstClockOutAt,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => $clockIn->attendance_date,
    ]);
    $secondClockOutAt = now()->subHours(2);
    $secondClockOut = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'session_anchor_id' => $clockIn->id,
        'type' => AttendanceType::ClockOut,
        'work_type' => WorkType::Office,
        'recorded_at' => $secondClockOutAt,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => $clockIn->attendance_date,
    ]);
    $admin = User::factory()->create();
    $admin->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();
    $correctedLocal = $secondClockOutAt->copy()->addMinutes(10)->setTimezone('Asia/Makassar')->startOfMinute();

    $this->actingAs($admin)->post(route('admin.kehadiran.correct-timestamp', $secondClockOut), [
        'recorded_at_local' => $correctedLocal->format('Y-m-d\\TH:i'),
        'reason' => 'Koreksi waktu pulang berdasarkan bukti.',
    ])->assertStatus(422);

    expect($secondClockOut->fresh()->approvedCorrection)->toBeNull();
});

it('cancels and audits a pending employee request when an administrator directly corrects the same event', function () {
    $employeeUser = User::factory()->create();
    $employee = Employee::create(['user_id' => $employeeUser->id]);
    $originalTime = now()->subHours(3);
    $record = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => $originalTime,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => $originalTime->copy()->setTimezone('Asia/Makassar')->toDateString(),
    ]);
    $pending = AttendanceCorrection::create([
        'employee_id' => $employee->id,
        'attendance_record_id' => $record->id,
        'session_anchor_id' => $record->id,
        'requested_type' => AttendanceType::ClockIn->value,
        'requested_recorded_at' => $originalTime->copy()->subMinutes(10),
        'attendance_date' => $originalTime->copy()->subMinutes(10)->setTimezone('Asia/Makassar')->toDateString(),
        'reason' => 'Permohonan koreksi waktu masuk dari pegawai.',
        'status' => 'pending',
    ]);
    $admin = User::factory()->create();
    $admin->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();
    $correctedLocal = $originalTime->copy()->subMinutes(20)->setTimezone('Asia/Makassar')->startOfMinute();

    $this->actingAs($admin)->post(route('admin.kehadiran.correct-timestamp', $record), [
        'recorded_at_local' => $correctedLocal->format('Y-m-d\\TH:i'),
        'reason' => 'Koreksi langsung sesuai bukti yang diverifikasi.',
    ])->assertRedirect();

    $freshPending = $pending->fresh();
    expect($freshPending->status)->toBe('cancelled')
        ->and($freshPending->reviewed_by)->toBe($admin->id)
        ->and($freshPending->reviewed_at)->not->toBeNull()
        ->and($freshPending->review_note)->toContain('koreksi langsung');

    expect(AuditLog::query()
        ->where('auditable_type', $pending->getMorphClass())
        ->where('auditable_id', $pending->id)
        ->where('action', 'attendance_correction.cancelled_by_admin_direct_correction')
        ->exists())->toBeTrue();
});

it('cancels a forgotten clock-out request when an administrator directly corrects a clock-out in that session', function () {
    $employeeUser = User::factory()->create();
    $employee = Employee::create(['user_id' => $employeeUser->id]);
    $clockInAt = now()->subHours(5);
    $clockIn = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => $clockInAt,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => $clockInAt->copy()->setTimezone('Asia/Makassar')->toDateString(),
    ]);
    $clockOutAt = now()->subHours(2);
    $clockOut = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'session_anchor_id' => $clockIn->id,
        'type' => AttendanceType::ClockOut,
        'work_type' => WorkType::Office,
        'recorded_at' => $clockOutAt,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => $clockIn->attendance_date,
    ]);
    $pending = AttendanceCorrection::create([
        'employee_id' => $employee->id,
        'attendance_record_id' => null,
        'session_anchor_id' => $clockIn->id,
        'requested_type' => AttendanceType::ClockOut->value,
        'requested_recorded_at' => $clockOutAt->copy()->subMinutes(10),
        'attendance_date' => $clockOutAt->copy()->subMinutes(10)->setTimezone('Asia/Makassar')->toDateString(),
        'reason' => 'Permohonan lupa Clock Out.',
        'status' => 'pending',
    ]);
    $admin = User::factory()->create();
    $admin->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();
    $correctedLocal = $clockOutAt->copy()->addMinutes(10)->setTimezone('Asia/Makassar')->startOfMinute();

    $this->actingAs($admin)->post(route('admin.kehadiran.correct-timestamp', $clockOut), [
        'recorded_at_local' => $correctedLocal->format('Y-m-d\\TH:i'),
        'reason' => 'Waktu pulang dikoreksi langsung oleh administrator.',
    ])->assertRedirect();

    expect($pending->fresh()->status)->toBe('cancelled')
        ->and($pending->fresh()->reviewed_by)->toBe($admin->id);
});

it('rejects a direct clock-in correction that overlaps the previous session', function () {
    $employeeUser = User::factory()->create();
    $employee = Employee::create(['user_id' => $employeeUser->id]);
    $previousClockInAt = now()->subDays(5);
    $previousClockIn = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => $previousClockInAt,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => $previousClockInAt->copy()->setTimezone('Asia/Makassar')->toDateString(),
    ]);
    $previousClockOutAt = now()->subDays(2);
    AttendanceRecord::create([
        'employee_id' => $employee->id,
        'session_anchor_id' => $previousClockIn->id,
        'type' => AttendanceType::ClockOut,
        'work_type' => WorkType::Office,
        'recorded_at' => $previousClockOutAt,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => $previousClockIn->attendance_date,
    ]);
    $targetClockInAt = now()->subDay();
    $targetClockIn = AttendanceRecord::create([
        'employee_id' => $employee->id,
        'type' => AttendanceType::ClockIn,
        'work_type' => WorkType::Office,
        'recorded_at' => $targetClockInAt,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => $targetClockInAt->copy()->setTimezone('Asia/Makassar')->toDateString(),
    ]);
    $admin = User::factory()->create();
    $admin->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();
    $correctedLocal = now()->subDays(3)->setTimezone('Asia/Makassar')->startOfMinute();

    $this->actingAs($admin)->post(route('admin.kehadiran.correct-timestamp', $targetClockIn), [
        'recorded_at_local' => $correctedLocal->format('Y-m-d\\TH:i'),
        'reason' => 'Koreksi waktu masuk berdasarkan bukti yang diverifikasi.',
    ])->assertStatus(422);

    expect($targetClockIn->fresh()->approvedCorrection)->toBeNull();
});
