<?php

use App\Enums\AttendanceType;
use App\Enums\OvertimeState;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\OvertimeRequest;
use App\Services\DailyAttendanceStatusService;
use App\Services\EmployeeDashboardAggregationService;
use Carbon\Carbon;

it('subtracts overlapping approved overtime windows only once', function () {
    $service = new EmployeeDashboardAggregationService(new DailyAttendanceStatusService);
    $method = new ReflectionMethod($service, 'mergedOverlapSeconds');

    $seconds = $method->invoke(
        $service,
        Carbon::parse('2026-11-03 08:00:00', 'UTC'),
        Carbon::parse('2026-11-03 17:00:00', 'UTC'),
        collect([
            [
                'start' => Carbon::parse('2026-11-03 12:00:00', 'UTC'),
                'end' => Carbon::parse('2026-11-03 14:00:00', 'UTC'),
                'provisional' => false,
            ],
            [
                'start' => Carbon::parse('2026-11-03 13:00:00', 'UTC'),
                'end' => Carbon::parse('2026-11-03 15:00:00', 'UTC'),
                'provisional' => false,
            ],
        ]),
    );

    expect($seconds)->toBe(10_800);
});

it('clips overtime subtraction to the attendance session and merges adjacent windows', function () {
    $service = new EmployeeDashboardAggregationService(new DailyAttendanceStatusService);
    $method = new ReflectionMethod($service, 'mergedOverlapSeconds');

    $seconds = $method->invoke(
        $service,
        Carbon::parse('2026-11-03 09:00:00', 'UTC'),
        Carbon::parse('2026-11-03 17:00:00', 'UTC'),
        collect([
            [
                'start' => Carbon::parse('2026-11-03 08:00:00', 'UTC'),
                'end' => Carbon::parse('2026-11-03 12:00:00', 'UTC'),
                'provisional' => false,
            ],
            [
                'start' => Carbon::parse('2026-11-03 12:00:00', 'UTC'),
                'end' => Carbon::parse('2026-11-03 13:00:00', 'UTC'),
                'provisional' => false,
            ],
            [
                'start' => Carbon::parse('2026-11-03 18:00:00', 'UTC'),
                'end' => Carbon::parse('2026-11-03 19:00:00', 'UTC'),
                'provisional' => false,
            ],
        ]),
    );

    expect($seconds)->toBe(14_400);
});

function dashboardOvertimeEvent(string $time, int $id): AttendanceRecord
{
    $record = new AttendanceRecord;
    $record->forceFill([
        'id' => $id,
        'recorded_at' => Carbon::parse($time, 'UTC'),
    ]);
    $record->setRelation('approvedCorrection', null);

    return $record;
}

function dashboardOvertimeInterval(EmployeeDashboardAggregationService $service, OvertimeRequest $request, Carbon $now): ?array
{
    $method = new ReflectionMethod($service, 'overtimeInterval');

    return $method->invoke($service, $request, $now);
}

it('uses effective event timestamps for actual overtime finalization after approved corrections', function () {
    $service = new EmployeeDashboardAggregationService(new DailyAttendanceStatusService);
    $clockIn = dashboardOvertimeEvent('2026-11-03 08:00:00', 41);
    $start = dashboardOvertimeEvent('2026-11-03 17:00:00', 42);
    $end = dashboardOvertimeEvent('2026-11-03 19:00:00', 43);

    $start->setRelation('approvedCorrection', new AttendanceCorrection([
        'requested_recorded_at' => Carbon::parse('2026-11-03 17:30:00', 'UTC'),
    ]));
    $end->setRelation('approvedCorrection', new AttendanceCorrection([
        'requested_recorded_at' => Carbon::parse('2026-11-03 20:00:00', 'UTC'),
    ]));

    $request = new OvertimeRequest([
        'state' => OvertimeState::Completed,
        'finalization_source' => 'actual',
    ]);
    $request->setRelation('clockInRecord', $clockIn);
    $request->setRelation('startRecord', $start);
    $request->setRelation('clockOutRecord', $end);

    $interval = dashboardOvertimeInterval($service, $request, Carbon::parse('2026-11-03 21:00:00', 'UTC'));

    expect($interval['start']->toDateTimeString())->toBe('2026-11-03 17:30:00')
        ->and($interval['end']->toDateTimeString())->toBe('2026-11-03 20:00:00')
        ->and($interval['provisional'])->toBeFalse()
        ->and($interval['sessionAnchorId'])->toBe(41);
});

it('keeps scheduled and manual overtime finalization timestamps authoritative', function (string $source) {
    $service = new EmployeeDashboardAggregationService(new DailyAttendanceStatusService);
    $request = new OvertimeRequest([
        'state' => OvertimeState::Completed,
        'finalization_source' => $source,
        'final_start_at' => Carbon::parse('2026-11-03 18:00:00', 'UTC'),
        'final_end_at' => Carbon::parse('2026-11-03 20:00:00', 'UTC'),
    ]);
    $request->setRelation('clockInRecord', dashboardOvertimeEvent('2026-11-03 08:00:00', 51));
    $request->setRelation('startRecord', dashboardOvertimeEvent('2026-11-03 18:30:00', 52));
    $request->setRelation('clockOutRecord', dashboardOvertimeEvent('2026-11-03 20:30:00', 53));

    $interval = dashboardOvertimeInterval($service, $request, Carbon::parse('2026-11-03 21:00:00', 'UTC'));

    expect($interval['start']->toDateTimeString())->toBe('2026-11-03 18:00:00')
        ->and($interval['end']->toDateTimeString())->toBe('2026-11-03 20:00:00')
        ->and($interval['provisional'])->toBeFalse();
})->with(['scheduled', 'manual']);

it('uses now for running overtime intervals and excludes cancelled or invalid intervals', function () {
    $service = new EmployeeDashboardAggregationService(new DailyAttendanceStatusService);
    $now = Carbon::parse('2026-11-03 20:00:00', 'UTC');

    $running = new OvertimeRequest(['state' => OvertimeState::InProgress]);
    $running->setRelation('clockInRecord', dashboardOvertimeEvent('2026-11-03 08:00:00', 61));
    $running->setRelation('startRecord', dashboardOvertimeEvent('2026-11-03 18:00:00', 62));
    $running->setRelation('clockOutRecord', null);

    $cancelled = new OvertimeRequest(['state' => OvertimeState::Cancelled]);
    $cancelled->setRelation('clockInRecord', dashboardOvertimeEvent('2026-11-03 08:00:00', 71));
    $cancelled->setRelation('startRecord', dashboardOvertimeEvent('2026-11-03 18:00:00', 72));

    $invalid = new OvertimeRequest(['state' => OvertimeState::Completed, 'finalization_source' => 'manual']);
    $invalid->setRelation('clockInRecord', dashboardOvertimeEvent('2026-11-03 08:00:00', 81));
    $invalid->setRelation('startRecord', dashboardOvertimeEvent('2026-11-03 18:00:00', 82));
    $invalid->setRelation('clockOutRecord', dashboardOvertimeEvent('2026-11-03 19:00:00', 83));
    $invalid->forceFill([
        'final_start_at' => Carbon::parse('2026-11-03 20:00:00', 'UTC'),
        'final_end_at' => Carbon::parse('2026-11-03 19:00:00', 'UTC'),
    ]);

    $runningInterval = dashboardOvertimeInterval($service, $running, $now);

    expect($runningInterval['start']->toDateTimeString())->toBe('2026-11-03 18:00:00')
        ->and($runningInterval['end']->toDateTimeString())->toBe('2026-11-03 20:00:00')
        ->and($runningInterval['provisional'])->toBeTrue()
        ->and(dashboardOvertimeInterval($service, $cancelled, $now))->toBeNull()
        ->and(dashboardOvertimeInterval($service, $invalid, $now))->toBeNull();
});

it('evaluates the current month independently in each Clock In session timezone', function () {
    $service = new EmployeeDashboardAggregationService(new DailyAttendanceStatusService);
    $method = new ReflectionMethod($service, 'isInCurrentMonthForSession');
    $now = Carbon::parse('2026-11-01 00:30:00', 'UTC');

    $jakartaClockIn = new AttendanceRecord;
    $jakartaClockIn->forceFill([
        'type' => AttendanceType::ClockIn,
        'timezone' => 'Asia/Jakarta',
        'attendance_date' => '2026-10-31',
    ]);

    $makassarClockIn = new AttendanceRecord;
    $makassarClockIn->forceFill([
        'type' => AttendanceType::ClockIn,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-01',
    ]);

    expect($method->invoke($service, $jakartaClockIn, $now, 'Asia/Makassar'))->toBeTrue()
        ->and($method->invoke($service, $makassarClockIn, $now, 'Asia/Makassar'))->toBeTrue();
});

it('does not reinterpret a session month using the employee latest timezone', function () {
    $service = new EmployeeDashboardAggregationService(new DailyAttendanceStatusService);
    $method = new ReflectionMethod($service, 'isInCurrentMonthForSession');
    $now = Carbon::parse('2026-11-01 00:30:00', 'UTC');

    $previousMonthInMakassar = new AttendanceRecord;
    $previousMonthInMakassar->forceFill([
        'type' => AttendanceType::ClockIn,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-10-31',
    ]);

    $futureLocalDateInJakarta = new AttendanceRecord;
    $futureLocalDateInJakarta->forceFill([
        'type' => AttendanceType::ClockIn,
        'timezone' => 'Asia/Jakarta',
        'attendance_date' => '2026-11-01',
    ]);

    expect($method->invoke($service, $previousMonthInMakassar, $now, 'Asia/Makassar'))->toBeFalse()
        ->and($method->invoke($service, $futureLocalDateInJakarta, $now, 'Asia/Makassar'))->toBeFalse();
});

it('uses the Clock In anchor timezone and date for a later session event', function () {
    $service = new EmployeeDashboardAggregationService(new DailyAttendanceStatusService);
    $method = new ReflectionMethod($service, 'isInCurrentMonthForSession');
    $now = Carbon::parse('2026-11-01 00:30:00', 'UTC');

    $clockIn = new AttendanceRecord;
    $clockIn->forceFill([
        'id' => 101,
        'type' => AttendanceType::ClockIn,
        'timezone' => 'Asia/Jakarta',
        'attendance_date' => '2026-10-31',
    ]);

    $clockOut = new AttendanceRecord;
    $clockOut->forceFill([
        'type' => AttendanceType::ClockOut,
        'timezone' => 'Asia/Makassar',
        'attendance_date' => '2026-11-01',
    ]);
    $clockOut->setRelation('sessionAnchor', $clockIn);

    expect($method->invoke($service, $clockOut, $now, 'Asia/Makassar'))->toBeTrue();
});
