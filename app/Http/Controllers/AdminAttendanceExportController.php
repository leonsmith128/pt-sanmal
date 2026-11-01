<?php

namespace App\\Http\\Controllers;

use App\\Enums\\AttendanceType;
use App\\Enums\\WorkType;
use App\\Models\\AttendanceRecord;
use App\\Models\\DailyStatusCorrection;
use App\\Models\\Employee;
use App\\Models\\LeaveRequest;
use App\\Services\\DailyAttendanceExportService;
use App\\Services\\DailyAttendanceStatusService;
use Illuminate\\Database\\Eloquent\\Builder;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Carbon;
use Illuminate\\Support\\Collection;
use Illuminate\\Validation\\Rule;
use Symfony\\Component\\HttpFoundation\\StreamedResponse;

class AdminAttendanceExportController extends Controller
{
    public function export(
        Request $request,
        DailyAttendanceStatusService $statusService,
        DailyAttendanceExportService $exportService,
    ): StreamedResponse {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'attendance_type' => ['nullable', Rule::enum(AttendanceType::class)],
            'work_type' => ['nullable', Rule::enum(WorkType::class)],
        ]);

        $today = Carbon::now('Asia/Makassar')->startOfDay();
        $from = $filters['from'] ?? $this->earliestAvailableDate();
        $to = $filters['to'] ?? $today->toDateString();
        $start = Carbon::parse($from, 'Asia/Makassar')->startOfDay();
        $end = Carbon::parse($to, 'Asia/Makassar')->startOfDay();

        $employees = Employee::query()
            ->when($filters['employee_id'] ?? null, fn (Builder $query, $id) => $query->whereKey($id))
            ->when(trim($filters['search'] ?? '') !== '', function (Builder $query) use ($filters): void {
                $term = trim($filters['search']);
                $query->where(function (Builder $nested) use ($term): void {
                    $nested->whereHas('user', fn (Builder $user) => $user->where('name', 'like', '%'.$term.'%'))
                        ->orWhere('employee_number', 'like', '%'.$term.'%')
                        ->orWhereHas('attendanceRecords', fn (Builder $records) => $records->where('notes', 'like', '%'.$term.'%'));
                });
            });

        $filename = 'rekap-absensi-admin-'.$start->format('Ymd').'-'.$end->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($employees, $start, $end, $filters, $statusService, $exportService): void {
            $exportService->stream($employees, $start, $end, $statusService, $filters);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
        ]);
    }

    private function earliestAvailableDate(): string
    {
        $attendance = AttendanceRecord::query()
            ->where(fn (Builder $query) => $query->whereNull('session_anchor_id')->orWhereColumn('session_anchor_id', 'id'))
            ->selectRaw("MIN(COALESCE((SELECT ac.attendance_date FROM attendance_corrections ac WHERE ac.attendance_record_id = attendance_records.id AND ac.status = 'approved' ORDER BY ac.reviewed_at DESC LIMIT 1), attendance_records.attendance_date)) AS earliest_date")
            ->value('earliest_date');

        $leave = LeaveRequest::query()->where('status', 'approved')->min('start_date');
        $correction = DailyStatusCorrection::query()->min('attendance_date');

        return collect([$attendance, $leave, $correction])->filter()->sort()->first()
            ?? Carbon::now('Asia/Makassar')->toDateString();
    }
}
