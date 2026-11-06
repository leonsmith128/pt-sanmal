<?php

namespace App\Http\Controllers;

use App\Data\Attendance\AttendanceFilterData;
use App\Enums\DailyAttendanceStatus;
use App\Models\AuditLog;
use App\Models\DailyStatusCorrection;
use App\Models\Employee;
use App\Services\AttendanceReportBuilder;
use App\Services\DailyAttendanceStatusService;
use App\Support\ExportRangeOptions;
use App\Support\ReportFileName;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminDailyAttendanceController extends Controller
{
    public function daily(Request $request, DailyAttendanceStatusService $statusService): View
    {
        $date = $request->query('date', now('Asia/Makassar')->toDateString());
        validator(['date' => $date], ['date' => ['required', 'date_format:Y-m-d']])->validate();

        $rows = Employee::query()
            ->with('user')
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString();

        /** @var Collection<int, Employee> $employeePage */
        $employeePage = $rows->getCollection();
        $statuses = $statusService->forEmployeesDateRange($employeePage, $date, $date, 'Asia/Makassar');

        $rows->setCollection(
            $employeePage->map(fn (Employee $employee) => [
                'employee' => $employee,
                'daily' => $statuses[$employee->id][$date] ?? $statusService->forEmployeeDate($employee, $date),
            ]),
        );

        $selected = Carbon::parse($date, 'Asia/Makassar');
        $exportOptions = ExportRangeOptions::make(
            'admin.daily-attendance.export',
            subject: 'rekap absensi harian seluruh pegawai',
            customFrom: $selected->copy()->startOfMonth()->toDateString(),
            customTo: $selected->copy()->endOfMonth()->toDateString(),
            customLabel: __('Bulan dari tanggal terpilih'),
            customDescription: 'Rekap harian seluruh pegawai untuk '.$selected->translatedFormat('F Y').'.',
        );

        return view('pages::admin.daily-attendance', compact('date', 'rows', 'exportOptions'));
    }

    public function correctDailyStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'attendance_date' => ['required', 'date_format:Y-m-d'],
            'status' => ['required', Rule::enum(DailyAttendanceStatus::class)->only(DailyAttendanceStatus::correctionOptions())],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $status = DailyAttendanceStatus::from($validated['status']);

        DB::transaction(function () use ($request, $validated, $status): void {
            $employee = Employee::query()->findOrFail((int) $validated['employee_id']);
            abort_if($employee->user_id === $request->user()->id, 403, 'Anda tidak dapat mengoreksi status kehadiran milik sendiri.');
            $date = $validated['attendance_date'];

            $correction = DailyStatusCorrection::query()
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', $date)
                ->lockForUpdate()
                ->first();

            $old = $correction ? [
                'status' => $this->enumValue($correction->status),
                'reason' => $correction->reason,
            ] : null;

            if ($correction) {
                $correction->update(['status' => $status, 'reason' => $validated['reason']]);
            } else {
                $correction = DailyStatusCorrection::create([
                    'employee_id' => $employee->id,
                    'attendance_date' => $date,
                    'status' => $status,
                    'reason' => $validated['reason'],
                    'created_by' => $request->user()->id,
                ]);
            }

            AuditLog::record(
                auditable: $correction,
                action: $old ? 'attendance.daily_status_corrected' : 'attendance.daily_status_created',
                oldValues: $old,
                newValues: [
                    'employee_id' => $employee->id,
                    'attendance_date' => $date,
                    'status' => $this->enumValue($correction->status),
                    'reason' => $correction->reason,
                ],
                userId: $request->user()->id,
            );
        });

        return redirect()->route('admin.daily-attendance', ['date' => $validated['attendance_date']])
            ->with('status', 'Koreksi status harian berhasil disimpan dan dicatat dalam audit log.');
    }

    public function exportMonth(Request $request, AttendanceReportBuilder $builder): BinaryFileResponse
    {
        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        if (filled($validated['from'] ?? null)) {
            // Rentang eksplisit (Hari ini, Minggu ini, Bulan ini, atau bulan dari tanggal terpilih).
            $start = Carbon::parse($validated['from'], 'Asia/Makassar')->startOfDay();
            $end = Carbon::parse($validated['to'] ?? $validated['from'], 'Asia/Makassar')->startOfDay();
        } else {
            $month = $validated['month'] ?? now('Asia/Makassar')->format('Y-m');
            $start = Carbon::parse($month.'-01', 'Asia/Makassar')->startOfMonth();
            $end = $start->copy()->endOfMonth();
        }

        // Zona waktu null = sama dengan halaman Rekap Harian (tanggal absensi tersimpan).
        $workbook = $builder->build(Employee::query(), $start, $end, null, new AttendanceFilterData, includeOvertime: false);

        $response = Excel::download($workbook, ReportFileName::admin('rekap-harian', ReportFileName::period($start, $end)));
        $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate');

        return $response;
    }

    private function enumValue(mixed $value): string
    {
        return $value instanceof \BackedEnum ? (string) $value->value : (string) $value;
    }
}
