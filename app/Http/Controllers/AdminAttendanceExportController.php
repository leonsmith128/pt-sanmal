<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceType;
use App\Data\Attendance\AttendanceFilterData;
use App\Enums\WorkType;
use App\Models\AttendanceRecord;
use App\Models\DailyStatusCorrection;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\DailyAttendanceExportService;
use App\Services\DailyAttendanceStatusService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminAttendanceExportController extends Controller
{
    public function export(
        Request $request,
        DailyAttendanceStatusService $statusService,
        DailyAttendanceExportService $exportService,
    ): StreamedResponse {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'attendance_type' => ['nullable', Rule::enum(AttendanceType::class)],
            'work_type' => ['nullable', Rule::enum(WorkType::class)],
        ]);

        $today = Carbon::now('Asia/Makassar')->startOfDay();
        $filterData = AttendanceFilterData::fromArray($filters);

        if ($filterData->from === null && $filterData->to === null) {
            $filterData = $filterData->withDateRange($this->earliestAvailableDate(), $today->toDateString());
        }

        $from = $filterData->from ?? $today->toDateString();
        $to = $filterData->to ?? $from;
        $start = Carbon::parse($from, 'Asia/Makassar')->startOfDay();
        $end = Carbon::parse($to, 'Asia/Makassar')->startOfDay();
        $employees = Employee::query();

        $filename = 'rekap-absensi-admin-'.$start->format('Ymd').'-'.$end->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($employees, $start, $end, $statusService, $exportService, $filterData): void {
            $exportService->stream($employees, $start, $end, $statusService, $filterData);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
        ]);
    }

    private function earliestAvailableDate(): string
    {
        $earliestAttendanceAt = AttendanceRecord::query()
            ->where(fn (Builder $query) => $query->whereNull('session_anchor_id')->orWhereColumn('session_anchor_id', 'id'))
            ->selectRaw("MIN(COALESCE((SELECT ac.requested_recorded_at FROM attendance_corrections ac WHERE ac.attendance_record_id = attendance_records.id AND ac.status = 'approved' ORDER BY ac.reviewed_at DESC LIMIT 1), attendance_records.recorded_at)) AS earliest_at")
            ->value('earliest_at');

        $attendance = $earliestAttendanceAt
            ? Carbon::parse($earliestAttendanceAt, 'UTC')->setTimezone('Asia/Makassar')->toDateString()
            : null;

        $leave = LeaveRequest::query()->where('status', 'approved')->min('start_date');
        $correction = DailyStatusCorrection::query()->min('attendance_date');
        $joined = Employee::query()->min('joined_at');

        return collect([$attendance, $leave, $correction, $joined])->filter()->sort()->first()
            ?? Carbon::now('Asia/Makassar')->toDateString();
    }
}
