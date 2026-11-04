<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceType;
use App\Models\AttendanceRecord;
use App\Models\DailyStatusCorrection;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\DailyAttendanceExportService;
use App\Services\DailyAttendanceStatusService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceHistoryController extends Controller
{
    private const HISTORY_GROUPS_PER_PAGE = 10;

    public function index(Request $request, DailyAttendanceStatusService $statusService): View
    {
        $user = $request->user();
        $employee = $user->employee;

        abort_unless($user->is_active && $employee, 403, 'Akun ini belum terhubung dengan data pegawai aktif.');

        $filters = $this->validatedFilters($request, useCurrentMonthDefault: true);
        $dailyStatuses = $statusService->forEmployeesDateRange(
            collect([$employee]),
            $filters['from'],
            $filters['to'],
        )[$employee->id] ?? [];

        // Date groups are built from the same domain result used by the daily-status
        // service, so session anchors, approved timestamp corrections, and timezone
        // snapshots keep exactly the same attribution rules.
        $dateGroups = collect($dailyStatuses)
            ->filter(fn (array $daily): bool => $daily['events']->isNotEmpty())
            ->map(fn (array $daily, string $date): array => [
                'date' => $date,
                'status' => $daily['status'],
                'label' => $daily['label'],
                'events' => $daily['events'],
                'needs_review' => $daily['needs_review'],
                'reason' => $daily['reason'],
                'correction' => $daily['correction'],
                'leaveRequests' => $daily['leaveRequests'],
            ])
            ->sortBy('date', SORT_STRING, $filters['direction'] === 'desc')
            ->values();

        $groupsPaginator = $this->paginateCollection(
            $dateGroups,
            $request,
            self::HISTORY_GROUPS_PER_PAGE,
            'page',
        );

        // Keep the existing event paginator available during Batch 1 so the
        // current Blade view remains functional until Batch 2 consumes dateGroups.
        $allEvents = collect($dailyStatuses)
            ->flatMap(fn (array $daily) => $daily['events'])
            ->sort(function (AttendanceRecord $left, AttendanceRecord $right) use ($filters): int {
                $timeOrder = $left->effectiveRecordedAt() <=> $right->effectiveRecordedAt();

                if ($timeOrder === 0) {
                    $timeOrder = $left->id <=> $right->id;
                }

                return $filters['direction'] === 'desc' ? -$timeOrder : $timeOrder;
            })
            ->values();

        $legacyRecordsPaginator = $this->paginateCollection($allEvents, $request, 20, 'page');

        $statusCollection = collect($dailyStatuses);
        $summary = [
            // Follow the canonical employee dashboard contract: an explicit
            // administrative correction to Present counts even without events.
            'present_days' => $statusCollection->where('status', 'present')->count(),
            'complete_days' => $statusCollection
                ->filter(fn (array $daily): bool => $daily['status'] === 'present'
                    && $this->hasCompleteClockInAndOut($daily['events']))
                ->count(),
            'needs_review_days' => $statusCollection->where('status', 'needs_review')->count(),
        ];

        return view('pages::employee.attendance-history.index', [
            'records' => $legacyRecordsPaginator,
            'dateGroups' => $groupsPaginator,
            'summary' => $summary,
            'filters' => $filters,
        ]);
    }

    public function export(
        Request $request,
        DailyAttendanceStatusService $statusService,
        DailyAttendanceExportService $exportService,
    ): StreamedResponse {
        $user = $request->user();
        $employee = $user->employee;

        abort_unless($user->is_active && $employee, 403, 'Akun ini belum terhubung dengan data pegawai aktif.');

        // Preserve the export endpoint's existing unfiltered behavior. The page
        // supplies its selected period in the query string when exporting.
        $filters = $this->validatedFilters($request);
        $start = Carbon::parse($filters['from'] !== '' ? $filters['from'] : $this->earliestAvailableDate($employee), 'Asia/Makassar')->startOfDay();
        $end = Carbon::parse($filters['to'] !== '' ? $filters['to'] : Carbon::now('Asia/Makassar')->toDateString(), 'Asia/Makassar')->startOfDay();
        $employees = Employee::query()->whereKey($employee->id);
        $filename = 'rekap-absensi-pegawai-'.$start->format('Ymd').'-'.$end->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($employees, $start, $end, $statusService, $exportService): void {
            $exportService->stream($employees, $start, $end, $statusService);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
        ]);
    }

    private function earliestAvailableDate(Employee $employee): string
    {
        $attendance = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->where(fn (Builder $query) => $query->whereNull('session_anchor_id')->orWhereColumn('session_anchor_id', 'id'))
            ->selectRaw("MIN(COALESCE((SELECT ac.attendance_date FROM attendance_corrections ac WHERE ac.attendance_record_id = attendance_records.id AND ac.status = 'approved' ORDER BY ac.reviewed_at DESC LIMIT 1), attendance_records.attendance_date)) AS earliest_date")
            ->value('earliest_date');

        $leave = LeaveRequest::query()->where('employee_id', $employee->id)->where('status', 'approved')->min('start_date');
        $correction = DailyStatusCorrection::query()->where('employee_id', $employee->id)->min('attendance_date');

        return collect([$attendance, $leave, $correction, $employee->joined_at?->toDateString()])
            ->filter()->sort()->first() ?? Carbon::now('Asia/Makassar')->toDateString();
    }

    private function validatedFilters(Request $request, bool $useCurrentMonthDefault = false): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
        ], [
            'to.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
        ]);

        $from = $validated['from'] ?? '';
        $to = $validated['to'] ?? '';

        if ($useCurrentMonthDefault && $from === '' && $to === '') {
            $today = Carbon::now('Asia/Makassar');
            $from = $today->copy()->startOfMonth()->toDateString();
            $to = $today->toDateString();
        }

        return [
            'from' => $from,
            'to' => $to,
            'direction' => $validated['direction'] ?? 'desc',
        ];
    }

    private function paginateCollection(
        Collection $items,
        Request $request,
        int $perPage,
        string $pageName,
    ): LengthAwarePaginator {
        $page = max(1, (int) $request->query($pageName, 1));

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
                'pageName' => $pageName,
            ],
        );
    }

    private function hasCompleteClockInAndOut(Collection $events): bool
    {
        $clockIns = $events->filter(fn (AttendanceRecord $event): bool => $event->type === AttendanceType::ClockIn);
        $clockOuts = $events->filter(fn (AttendanceRecord $event): bool => $event->type === AttendanceType::ClockOut);

        if ($clockIns->count() !== 1 || $clockOuts->count() !== 1) {
            return false;
        }

        return $clockOuts->first()->effectiveRecordedAt()?->gt($clockIns->first()->effectiveRecordedAt()) ?? false;
    }
}
