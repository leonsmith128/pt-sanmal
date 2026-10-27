<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DailyStatusCorrection;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\DailyAttendanceStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminLeaveRequestController extends Controller
{
    public function index(Request $request): View
    {
        $query = LeaveRequest::query()->with(['employee.user', 'leaveType', 'decidedBy']);

        if ($request->filled('status') && in_array($request->string('status')->toString(), ['pending', 'approved', 'rejected', 'cancelled'], true)) {
            $query->where('status', $request->string('status')->toString());
        }

        $requests = $query->latest()->paginate(20)->withQueryString();

        return view('admin.leave-requests.index', compact('requests'));
    }

    public function show(LeaveRequest $leaveRequest): View
    {
        $leaveRequest->load(['employee.user', 'leaveType', 'decidedBy']);

        return view('admin.leave-requests.show', compact('leaveRequest'));
    }

    public function downloadAttachment(LeaveRequest $leaveRequest): StreamedResponse
    {
        abort_unless($leaveRequest->attachment_path, 404);
        abort_unless(Storage::disk('local')->exists($leaveRequest->attachment_path), 404);

        return Storage::disk('local')->download($leaveRequest->attachment_path);
    }

    public function decide(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'decision_note' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $leaveRequest, $validated): void {
            $locked = LeaveRequest::query()->whereKey($leaveRequest->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                abort(422, 'Permohonan sudah diproses dan tidak dapat diputuskan kembali.');
            }

            $old = ['status' => $locked->status, 'decided_by' => $locked->decided_by, 'decided_at' => $locked->decided_at?->toISOString(), 'decision_note' => $locked->decision_note];

            $locked->update([
                'status' => $validated['decision'],
                'decided_by' => $request->user()->id,
                'decided_at' => now(),
                'decision_note' => $validated['decision_note'] ?? null,
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'leave.'.$validated['decision'],
                'auditable_type' => LeaveRequest::class,
                'auditable_id' => $locked->id,
                'old_values' => $old,
                'new_values' => [
                    'status' => $locked->status,
                    'decided_by' => $locked->decided_by,
                    'decided_at' => $locked->decided_at?->toISOString(),
                    'decision_note' => $locked->decision_note,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);
        });

        return redirect()->route('admin.leave-requests.show', $leaveRequest)
            ->with('status', 'Keputusan permohonan berhasil disimpan.');
    }

    public function daily(Request $request, DailyAttendanceStatusService $statusService): View
    {
        $date = $request->query('date', now()->toDateString());
        validator(['date' => $date], ['date' => ['required', 'date_format:Y-m-d']])->validate();

        $employees = Employee::query()->with('user')->orderBy('id')->get();
        $rows = $employees->map(fn (Employee $employee) => [
            'employee' => $employee,
            'daily' => $statusService->forEmployeeDate($employee, $date),
        ]);

        return view('admin.daily-attendance', compact('date', 'rows'));
    }

    public function correctDailyStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'attendance_date' => ['required', 'date_format:Y-m-d'],
            'status' => ['required', 'in:present,permission,sick,leave,absent'],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $validated): void {
            $employee = Employee::query()->findOrFail($validated['employee_id']);
            $date = $validated['attendance_date'];

            $correction = DailyStatusCorrection::query()
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', $date)
                ->lockForUpdate()
                ->first();

            $old = $correction ? [
                'status' => $correction->status,
                'reason' => $correction->reason,
            ] : null;

            if ($correction) {
                $correction->update([
                    'status' => $validated['status'],
                    'reason' => $validated['reason'],
                ]);
            } else {
                $correction = DailyStatusCorrection::create([
                    'employee_id' => $employee->id,
                    'attendance_date' => $date,
                    'status' => $validated['status'],
                    'reason' => $validated['reason'],
                    'created_by' => $request->user()->id,
                ]);
            }

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => $old ? 'attendance.daily_status_corrected' : 'attendance.daily_status_created',
                'auditable_type' => DailyStatusCorrection::class,
                'auditable_id' => $correction->id,
                'old_values' => $old,
                'new_values' => [
                    'employee_id' => $employee->id,
                    'attendance_date' => $date,
                    'status' => $correction->status,
                    'reason' => $correction->reason,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);
        });

        return redirect()->route('admin.daily-attendance', ['date' => $validated['attendance_date']])
            ->with('status', 'Koreksi status harian berhasil disimpan dan dicatat dalam audit log.');
    }

    public function exportMonth(Request $request, DailyAttendanceStatusService $statusService): StreamedResponse
    {
        $month = $request->query('month', now()->format('Y-m'));
        validator(['month' => $month], ['month' => ['required', 'date_format:Y-m']])->validate();

        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $employees = Employee::query()->with('user')->orderBy('id')->get();

        return response()->streamDownload(function () use ($employees, $start, $end, $statusService): void {
            $out = fopen('php://output', 'w');
            // BOM agar teks UTF-8 terbaca baik di Excel.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['ID Pegawai', 'Nama', 'Tanggal', 'Status', 'Catatan']);

            foreach ($employees as $employee) {
                for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
                    $daily = $statusService->forEmployeeDate($employee, $day);
                    $notes = $daily['reason'] ?? '';
                    fputcsv($out, [
                        $employee->employee_number ?? $employee->id,
                        $employee->user?->name ?? '(tanpa nama)',
                        $day->toDateString(),
                        $daily['label'],
                        $notes,
                    ]);
                }
            }

            fclose($out);
        }, 'rekap-absensi-'.$month.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
