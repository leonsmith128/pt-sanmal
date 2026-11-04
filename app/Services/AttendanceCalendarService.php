<?php

namespace App\Services;

use App\Models\Employee;

class AttendanceCalendarService
{
    public function __construct(
        private readonly DailyAttendanceStatusService $dailyAttendanceStatus,
    ) {}

    /**
     * Return the compact status keys used by the employee attendance calendar.
     *
     * Missing records remain "none"; they are never inferred to mean absence.
     *
     * @return array<string, string|null>
     */
    public function indicatorsForRange(Employee $employee, string $startDate, string $endDate): array
    {
        $statuses = $this->dailyAttendanceStatus->forEmployeesDateRange(
            collect([$employee]),
            $startDate,
            $endDate,
        );

        return collect($statuses[$employee->id] ?? [])
            ->mapWithKeys(fn (array $day, string $date) => [
                $date => match ($day['status']) {
                    'present' => 'present',
                    'needs_review' => 'review',
                    'no_record' => 'none',
                    default => null,
                },
            ])
            ->all();
    }
}
