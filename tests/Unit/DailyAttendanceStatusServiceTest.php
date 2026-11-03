<?php

use App\Enums\AttendanceType;
use App\Enums\DailyAttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\DailyStatusCorrection;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Services\DailyAttendanceStatusService;

function dailyStatusEvent(AttendanceType $type): AttendanceRecord
{
    return new AttendanceRecord(['type' => $type]);
}

function dailyStatusLeave(string $code): LeaveRequest
{
    $leave = new LeaveRequest;
    $leave->setRelation('leaveType', new LeaveType(['code' => $code, 'name' => $code]));

    return $leave;
}

it('gives an explicit administrative correction precedence over events and leave requests', function () {
    $correction = new DailyStatusCorrection(['status' => DailyAttendanceStatus::Absent, 'reason' => 'Koreksi admin']);

    $result = (new DailyAttendanceStatusService)->resolveStatus(
        collect([dailyStatusEvent(AttendanceType::ClockIn)]),
        collect([dailyStatusLeave('sakit')]),
        $correction,
    );

    expect($result['status'])->toBe(DailyAttendanceStatus::Absent->value)
        ->and($result['needs_review'])->toBeFalse()
        ->and($result['reason'])->toBe('Koreksi admin');
});

it('marks different approved leave categories on the same day for review', function () {
    $result = (new DailyAttendanceStatusService)->resolveStatus(
        collect(),
        collect([dailyStatusLeave('sakit'), dailyStatusLeave('cuti')]),
        null,
    );

    expect($result['status'])->toBe(DailyAttendanceStatus::NeedsReview->value)
        ->and($result['needs_review'])->toBeTrue();
});

it('marks approved leave combined with an attendance event for review', function () {
    $result = (new DailyAttendanceStatusService)->resolveStatus(
        collect([dailyStatusEvent(AttendanceType::ClockIn)]),
        collect([dailyStatusLeave('izin')]),
        null,
    );

    expect($result['status'])->toBe(DailyAttendanceStatus::NeedsReview->value)
        ->and($result['needs_review'])->toBeTrue();
});

it('maps approved leave categories to daily statuses', function (string $code, DailyAttendanceStatus $expected) {
    $result = (new DailyAttendanceStatusService)->resolveStatus(
        collect(),
        collect([dailyStatusLeave($code)]),
        null,
    );

    expect($result['status'])->toBe($expected->value);
})->with([
    'sick leave' => ['sakit', DailyAttendanceStatus::Sick],
    'annual leave' => ['cuti', DailyAttendanceStatus::Leave],
    'permission' => ['izin', DailyAttendanceStatus::Permission],
]);

it('marks a day present only when both clock-in and clock-out events exist', function () {
    $service = new DailyAttendanceStatusService;

    $complete = $service->resolveStatus(
        collect([
            dailyStatusEvent(AttendanceType::ClockIn),
            dailyStatusEvent(AttendanceType::ClockOut),
        ]),
        collect(),
        null,
    );
    $incomplete = $service->resolveStatus(
        collect([dailyStatusEvent(AttendanceType::ClockIn)]),
        collect(),
        null,
    );

    expect($complete['status'])->toBe(DailyAttendanceStatus::Present->value)
        ->and($incomplete['status'])->toBe(DailyAttendanceStatus::Incomplete->value);
});

it('keeps a day without events or leave as no record rather than absent', function () {
    $result = (new DailyAttendanceStatusService)->resolveStatus(collect(), collect(), null);

    expect($result['status'])->toBe(DailyAttendanceStatus::NoRecord->value)
        ->and($result['status'])->not->toBe(DailyAttendanceStatus::Absent->value);
});

it('marks duplicate overtime start events on a session for review', function () {
    $result = (new DailyAttendanceStatusService)->resolveStatus(
        collect([
            dailyStatusEvent(AttendanceType::ClockIn),
            dailyStatusEvent(AttendanceType::OvertimeStart),
            dailyStatusEvent(AttendanceType::OvertimeStart),
            dailyStatusEvent(AttendanceType::ClockOut),
        ]),
        collect(),
        null,
    );

    expect($result['status'])->toBe(DailyAttendanceStatus::NeedsReview->value)
        ->and($result['needs_review'])->toBeTrue()
        ->and($result['reason'])->toContain('duplikat');
});


function dailyStatusTimedEvent(AttendanceType $type, string $time): AttendanceRecord
{
    $event = new AttendanceRecord(['type' => $type]);
    $event->forceFill(['recorded_at' => Carbon::parse($time)]);
    $event->setRelation('approvedCorrection', null);

    return $event;
}

it('marks a closed session with clock out at or before clock in for review', function () {
    $result = (new DailyAttendanceStatusService)->resolveStatus(
        collect([
            dailyStatusTimedEvent(AttendanceType::ClockIn, '2026-11-03 09:00:00'),
            dailyStatusTimedEvent(AttendanceType::ClockOut, '2026-11-03 08:00:00'),
        ]),
        collect(),
        null,
    );

    expect($result['status'])->toBe(DailyAttendanceStatus::NeedsReview->value)
        ->and($result['needs_review'])->toBeTrue()
        ->and($result['reason'])->toContain('tidak lebih akhir');
});
