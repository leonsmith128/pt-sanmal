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
    $originalTime = now()->subHours(3);

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
    expect($freshRecord->recorded_at->toISOString())->toBe($originalTime->toISOString())
        ->and($freshRecord->effectiveRecordedAt()->toISOString())->toBe($correctedTime->toISOString())
        ->and($correction->fresh()->status)->toBe('approved');
});
