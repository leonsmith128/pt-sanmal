<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\DailyStatusCorrection;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\DailyAttendanceExportService;
use App\Services\DailyAttendanceStatusService;
use App\Support\AttendanceDateFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $employee = $user->employee;

        abort_unless($user->is_active && $employee, 403, 'Akun ini belum terhubung dengan data pegawai aktif.');

        $filters = $this->validatedFilters($request);
        $records = $this->attendanceQuery($employee, $filters)
            ->orderByRaw("COALESCE((SELECT ac.requested_recorded_at FROM attendance_corrections ac WHERE ac.attendance_record_id = attendance_records.id AND ac.status = 'approved' ORDER BY ac.reviewed_at DESC LIMIT 1), attendance_records.recorded_at) ".$filters['direction'])
            ->orderBy('id', $filters['direction'])
            ->paginate(20)
            ->withQueryString();

        return view('pages::employee.attendance-history.index', [
            'records' => $records,
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

    private function attendanceQuery(Employee $employee, array $filters): Builder
    {
        $query = $employee->attendanceRecords()
            ->getQuery()
            ->with(['location', 'project', 'approvedCorrection', 'sessionAnchor.approvedCorrection']);

        AttendanceDateFilter::apply(
            $query,
            $filters['from'] !== '' ? $filters['from'] : null,
            $filters['to'] !== '' ? $filters['to'] : null,
        );

        return $query;
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

    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
        ], [
            'to.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
        ]);

        return [
            'from' => $validated['from'] ?? '',
            'to' => $validated['to'] ?? '',
            'direction' => $validated['direction'] ?? 'desc',
        ];
    }
}
