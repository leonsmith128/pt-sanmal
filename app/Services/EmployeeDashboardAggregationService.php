<?php

namespace App\Services;

use App\Enums\AttendanceType;
use App\Enums\DailyAttendanceStatus;
use App\Enums\OvertimeApprovalStatus;
use App\Enums\OvertimeState;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class EmployeeDashboardAggregationService
{
    public function __construct(
        private readonly DailyAttendanceStatusService $dailyStatusService,
    ) {}

    /**
     * Build dashboard data for exactly one employee. The caller must resolve the
     * employee through the authenticated user; this service never accepts an ID.
     */
    public function forEmployee(Employee $employee, ?Carbon $now = null): array
    {
        // Keep the dashboard's display date in the employee's latest session timezone,
        // but evaluate each historical/current session against its own Clock In timezone.
        $now = ($now ?? now())->copy();
        $timezone = $this->attendanceTimezone($employee);
        $displayNow = $now->copy()->setTimezone($timezone);
        $today = $displayNow->toDateString();

        $sessionTimezones = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereNotNull('timezone')
            ->distinct()
            ->pluck('timezone')
            ->push($timezone)
            ->filter()
            ->unique()
            ->values();

        $periods = $sessionTimezones->mapWithKeys(function (string $sessionTimezone) use ($now): array {
            $sessionNow = $now->copy()->setTimezone($sessionTimezone);

            return [$sessionTimezone => [
                'start' => $sessionNow->copy()->startOfMonth()->toDateString(),
                'end' => $sessionNow->toDateString(),
            ]];
        });
        $monthStart = $periods->min('start');
        $periodEnd = $periods->max('end');

        $dailyRange = $this->dailyStatusService->forEmployeesDateRange(
            collect([$employee]),
            $monthStart,
            $periodEnd,
        )[$employee->id] ?? [];

        // A date's events are retained only when their Clock In anchor belongs
        // to the current month in that session's own timezone.
        $daily = collect($dailyRange)->map(function (array $day) use ($now, $timezone): array {
            $events = $day['events']
                ->filter(fn (AttendanceRecord $event) => $this->isInCurrentMonthForSession($event, $now, $timezone))
                ->values();

            return $this->dailyStatusService->resolveStatus($events, $day['leaveRequests'], $day['correction']);
        })->all();

        $statusCounts = collect($daily)->countBy(
            fn (array $day) => $day['status'],
        );

        $anchors = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->where('type', AttendanceType::ClockIn)
            ->whereColumn('session_anchor_id', 'id')
            ->where(function ($query) use ($monthStart, $periodEnd): void {
                $query->whereDate('attendance_date', '>=', $monthStart)
                    ->whereDate('attendance_date', '<=', $periodEnd)
                    ->orWhereHas('approvedCorrection', fn ($correction) => $correction
                        ->whereDate('attendance_date', '>=', $monthStart)
                        ->whereDate('attendance_date', '<=', $periodEnd));
            })
            ->with([
                'approvedCorrection',
                'sessionEvents.approvedCorrection',
                'sessionEvents.sessionAnchor.approvedCorrection',
            ])
            ->get()
            ->filter(fn (AttendanceRecord $anchor) => $this->isInCurrentMonthForSession($anchor, $now, $timezone))
            ->values();

        $approvedOvertime = OvertimeRequest::query()
            ->where('employee_id', $employee->id)
            ->where('approval_status', OvertimeApprovalStatus::Approved)
            ->whereDate('session_date', '>=', $monthStart)
            ->whereDate('session_date', '<=', $periodEnd)
            ->with([
                'startRecord.approvedCorrection',
                'clockOutRecord.approvedCorrection',
                'clockInRecord.approvedCorrection',
            ])
            ->get()
            ->filter(fn (OvertimeRequest $request) => $request->clockInRecord
                && $this->isInCurrentMonthForSession($request->clockInRecord, $now, $timezone));

        $overtimeIntervals = $approvedOvertime
            ->map(fn (OvertimeRequest $request) => $this->overtimeInterval($request, $now))
            ->filter()
            ->values();

        $workMinutes = 0;
        $runningWork = false;
        $overtimeNeedsReview = false;

        foreach ($anchors as $anchor) {
            $date = $anchor->effectiveAttendanceDate();
            $dayStatus = $daily[$date]['status'] ?? DailyAttendanceStatus::NoRecord->value;

            if ($dayStatus === DailyAttendanceStatus::NeedsReview->value) {
                continue;
            }

            $events = $anchor->sessionEvents
                ->prepend($anchor)
                ->unique('id')
                ->filter(fn (AttendanceRecord $event) => (int) $event->employee_id === (int) $anchor->employee_id && in_array($event->type, [
                    AttendanceType::ClockIn,
                    AttendanceType::OvertimeStart,
                    AttendanceType::ClockOut,
                ], true))
                ->values();

            $clockIns = $events->where('type', AttendanceType::ClockIn);
            $clockOuts = $events->where('type', AttendanceType::ClockOut);

            if ($clockIns->count() !== 1 || $clockOuts->count() > 1) {
                continue;
            }

            $start = $anchor->effectiveRecordedAt();
            $end = $clockOuts->first()?->effectiveRecordedAt() ?? $now;

            if (! $start || ! $end || $end->lte($start)) {
                continue;
            }

            if ($clockOuts->isEmpty()) {
                $runningWork = true;
            }

            $duration = max(0, $start->diffInSeconds($end));
            $sessionOvertime = $overtimeIntervals
                ->filter(fn (array $interval) => $interval['sessionAnchorId'] === $anchor->id)
                ->values();
            $overtimeNeedsReview = $overtimeNeedsReview || $sessionOvertime->contains(
                fn (array $interval) => $interval['start']->lt($start) || $interval['end']->gt($end),
            );
            $subtraction = $this->mergedOverlapSeconds($start, $end, $sessionOvertime);
            $workMinutes += (int) floor(max(0, $duration - $subtraction) / 60);
        }

        $overtimeMinutes = 0;
        $runningOvertime = false;

        foreach ($overtimeIntervals as $interval) {
            $seconds = max(0, $interval['start']->diffInSeconds($interval['end']));
            $overtimeMinutes += (int) floor($seconds / 60);
            $runningOvertime = $runningOvertime || $interval['provisional'];
        }

        $pendingCorrections = AttendanceCorrection::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->count();

        $pendingOvertime = OvertimeRequest::query()
            ->where('employee_id', $employee->id)
            ->where('approval_status', OvertimeApprovalStatus::Pending)
            ->count();

        $pendingLeave = LeaveRequest::query()
            ->with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->get();

        $recentAttendance = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->with(['approvedCorrection'])
            ->latest('recorded_at')
            ->limit(30)
            ->get()
            ->sortByDesc(fn (AttendanceRecord $record) => $record->effectiveRecordedAt())
            ->take(5)
            ->values();

        $todayStatus = $daily[$today] ?? $this->dailyStatusService->forEmployeeDate($employee, $today);

        $todayEvents = $todayStatus['events'];
        $todayClockIn = $todayEvents->first(fn (AttendanceRecord $record) => $record->type === AttendanceType::ClockIn);
        $todayClockOut = $todayEvents->first(fn (AttendanceRecord $record) => $record->type === AttendanceType::ClockOut);
        $todayOpen = $todayClockIn && ! $todayClockOut && $todayStatus['status'] === DailyAttendanceStatus::Incomplete->value;
        $todayEnd = $todayClockOut?->effectiveRecordedAt() ?? ($todayOpen ? $now : null);
        $todayDuration = $todayStatus['status'] !== DailyAttendanceStatus::NeedsReview->value
            && $todayClockIn
            && $todayEnd
            && $todayEnd->gt($todayClockIn->effectiveRecordedAt())
                ? (int) floor($todayClockIn->effectiveRecordedAt()->diffInSeconds($todayEnd) / 60)
                : null;

        return [
            'timezone' => $timezone,
            'today' => $displayNow->translatedFormat('l, d F Y'),
            'todayStatus' => $todayStatus,
            'todayClockIn' => $todayClockIn,
            'todayClockOut' => $todayClockOut,
            'todayDurationMinutes' => $todayDuration,
            'todayIsRunning' => (bool) $todayOpen,
            'monthly' => [
                'presentDays' => $statusCounts->get(DailyAttendanceStatus::Present->value, 0),
                'incompleteDays' => $statusCounts->get(DailyAttendanceStatus::Incomplete->value, 0),
                'workMinutes' => $workMinutes,
                'workIsProvisional' => $runningWork,
                'overtimeMinutes' => $overtimeMinutes,
                'overtimeIsProvisional' => $runningOvertime,
                'overtimeNeedsReview' => $overtimeNeedsReview,
            ],
            'pending' => [
                'total' => $pendingCorrections + $pendingOvertime + $pendingLeave->count(),
                'corrections' => $pendingCorrections,
                'overtime' => $pendingOvertime,
                'leave' => $pendingLeave->count(),
                'leaveBreakdown' => $pendingLeave
                    ->groupBy(fn (LeaveRequest $request) => $request->leaveType?->name ?? 'Lainnya')
                    ->map->count(),
            ],
            'recentAttendance' => $recentAttendance,
        ];
    }

    /**
     * Determine whether an event/session belongs to the current calendar month
     * in the timezone established by its Clock In anchor.
     */
    private function isInCurrentMonthForSession(
        AttendanceRecord $record,
        Carbon $now,
        string $fallbackTimezone,
    ): bool {
        $anchor = $record->type === AttendanceType::ClockIn
            ? $record
            : $record->sessionAnchor;
        $session = $anchor ?? $record;
        $sessionTimezone = $session->timezone ?: $fallbackTimezone;
        $sessionNow = $now->copy()->setTimezone($sessionTimezone);
        $date = $session->effectiveAttendanceDate();

        return $date >= $sessionNow->copy()->startOfMonth()->toDateString()
            && $date <= $sessionNow->toDateString();
    }

    private function attendanceTimezone(Employee $employee): string
    {
        $timezone = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->latest('recorded_at')
            ->value('timezone');

        return $timezone ?: config('app.timezone', 'UTC');
    }

    /**
     * @return array{start:Carbon,end:Carbon,provisional:bool,sessionAnchorId:int}|null
     */
    private function overtimeInterval(OvertimeRequest $request, Carbon $now): ?array
    {
        if ($request->state === OvertimeState::Cancelled || ! $request->startRecord || ! $request->clockInRecord) {
            return null;
        }

        $source = $request->finalization_source;
        $provisional = false;

        if (in_array($source, ['scheduled', 'manual'], true)) {
            $start = $request->final_start_at;
            $end = $request->final_end_at;
        } else {
            $start = $request->startRecord?->effectiveRecordedAt();

            if ($source === 'actual') {
                $end = $request->clockOutRecord?->effectiveRecordedAt();
            } else {
                $end = $request->clockOutRecord?->effectiveRecordedAt() ?? $now;
                $provisional = $request->clockOutRecord === null;
            }
        }

        if (! $start || ! $end || $end->lte($start)) {
            return null;
        }

        return [
            'start' => $start,
            'end' => $end,
            'provisional' => $provisional,
            'sessionAnchorId' => $request->clockInRecord->id,
        ];
    }

    /**
     * Merge overlap windows before subtraction so overlapping overtime cannot
     * subtract the same ordinary-work seconds more than once.
     *
     * @param  Collection<int, array{start:CarbonInterface,end:CarbonInterface,provisional:bool,sessionAnchorId:int}>  $intervals
     */
    private function mergedOverlapSeconds(CarbonInterface $sessionStart, CarbonInterface $sessionEnd, Collection $intervals): int
    {
        $windows = $intervals
            ->map(function (array $interval) use ($sessionStart, $sessionEnd): ?array {
                $start = $interval['start']->greaterThan($sessionStart) ? $interval['start'] : $sessionStart;
                $end = $interval['end']->lessThan($sessionEnd) ? $interval['end'] : $sessionEnd;

                return $end->gt($start) ? ['start' => $start, 'end' => $end] : null;
            })
            ->filter()
            ->sortBy(fn (array $window) => $window['start']->getTimestamp())
            ->values();

        $total = 0;
        $currentStart = null;
        $currentEnd = null;

        foreach ($windows as $window) {
            if ($currentStart === null) {
                $currentStart = $window['start'];
                $currentEnd = $window['end'];

                continue;
            }

            if ($window['start']->lte($currentEnd)) {
                if ($window['end']->gt($currentEnd)) {
                    $currentEnd = $window['end'];
                }

                continue;
            }

            $total += $currentStart->diffInSeconds($currentEnd);
            $currentStart = $window['start'];
            $currentEnd = $window['end'];
        }

        if ($currentStart !== null) {
            $total += $currentStart->diffInSeconds($currentEnd);
        }

        return (int) $total;
    }
}
