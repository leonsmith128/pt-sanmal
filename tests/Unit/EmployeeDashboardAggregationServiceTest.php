<?php

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
