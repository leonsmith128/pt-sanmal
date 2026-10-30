<?php

use App\Enums\AttendanceType;
use App\Enums\UserRole;
use App\Enums\WorkType;
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
    $correctedLocal = $originalTime->copy()->subMinutes(15)->setTimezone('Asia/Makassar');

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
