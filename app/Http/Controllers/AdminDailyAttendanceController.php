<?php

namespace App\\Http\\Controllers;

use App\\Enums\\DailyAttendanceStatus;
use App\\Models\\AuditLog;
use App\\Models\\DailyStatusCorrection;
use App\\Models\\Employee;
use App\\Services\\DailyAttendanceStatusService;
use Illuminate\\Http\\RedirectResponse;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Carbon;
use Illuminate\\Support\\Collection;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Validation\\Rule;
use Illuminate\\View\\View;
use Symfony\\Component\\HttpFoundation\\StreamedResponse;

class AdminDailyAttendanceController extends Controller
{
    public function daily(Request $request, DailyAttendanceStatusService $statusService): View
    {
        $date = $request->query('date', now()->toDateString());
        validator(['date' => $date], ['date' => ['required', 'date_format:Y-m-d']])->validate();

        $rows = Employee::query()
            ->with('user')
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString();

        /** @var Collection<int, Employee> $employeePage */
        $employeePage = $rows->getCollection();
        $statuses = $statusService->forEmployeesDateRange($employeePage, $date, $date);

        $rows->setCollection(
            $employeePage->map(fn (Employee $employee) => [
                'employee' => $employee,
                'daily' => $statuses[$employee->id][$date] ?? $statusService->forEmployeeDate($employee, $date),
            ]),
        );

        return view('pages::admin.daily-attendance', compact('date', 'rows'));
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

    public function exportMonth(Request $request, DailyAttendanceStatusService $statusService): StreamedResponse
    {
        $month = $request->query('month', now()->format('Y-m'));
        validator(['month' => $month], ['month' => ['required', 'date_format:Y-m']])->validate();

        $start = Carbon::parse($month.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return response()->streamDownload(function () use ($start, $end, $statusService): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['ID Pegawai', 'Nama', 'Tanggal', 'Status', 'Catatan']);

            Employee::query()->with('user')->chunkById(100, function ($employees) use ($statusService, $start, $end, $out): void {
                /** @var Collection<int, Employee> $employees */
                $statuses = $statusService->forEmployeesDateRange($employees, $start, $end);

                foreach ($employees as $employee) {
                    for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
                        $dayStr = $day->toDateString();
                        $daily = $statuses[$employee->id][$dayStr] ?? [
                            'label' => 'Belum ada data',
                            'reason' => '',
                        ];

                        fputcsv($out, array_map($this->spreadsheetSafe(...), [
                            $employee->employee_number ?? $employee->id,
                            $employee->user->name,
                            $dayStr,
                            $daily['label'],
                            $daily['reason'],
                        ]));
                    }
                }
            });

            fclose($out);
        }, 'rekap-absensi-'.$month.'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
        ]);
    }

    private function spreadsheetSafe(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return preg_match('/^[\\s\\x00-\\x1F]*[=+\\-@]/u', $value) === 1
            ? "'".$value
            : $value;
    }

    private function enumValue(mixed $value): string
    {
        return $value instanceof \\BackedEnum ? (string) $value->value : (string) $value;
    }
}
