<?php

namespace App\Http\Controllers;

use App\Enums\AttendanceType;
use App\Enums\WorkType;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminAttendanceExportController extends Controller
{
    public function export(Request $request): StreamedResponse
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'search' => ['nullable', 'string', 'max:255'],
            'attendance_type' => ['nullable', Rule::enum(AttendanceType::class)],
            'work_type' => ['nullable', Rule::enum(WorkType::class)],
        ]);

        $records = AttendanceRecord::query()
            ->with(['employee.user', 'location', 'project', 'approvedCorrection'])
            ->when(($filters['from'] ?? null) || ($filters['to'] ?? null), function ($query) use ($filters): void {
                $from = $filters['from'] ?? null;
                $to = $filters['to'] ?? null;
                $previousDayBoundary = match (\\Illuminate\\Support\\Facades\\DB::connection()->getDriverName()) {
                    'sqlite' => "datetime(attendance_records.recorded_at, '-24 hours')",
                    default => 'DATE_SUB(attendance_records.recorded_at, INTERVAL 24 HOUR)',
                };
                $effectiveDate = "COALESCE((SELECT ac.attendance_date FROM attendance_corrections ac WHERE ac.attendance_record_id = attendance_records.id AND ac.status = 'approved' ORDER BY ac.reviewed_at DESC LIMIT 1), attendance_records.attendance_date)";
                $sessionDate = "(SELECT COALESCE((SELECT ac2.attendance_date FROM attendance_corrections ac2 WHERE ac2.attendance_record_id = ci.id AND ac2.status = 'approved' ORDER BY ac2.reviewed_at DESC LIMIT 1), ci.attendance_date) FROM attendance_records ci WHERE ci.employee_id = attendance_records.employee_id AND ci.type = 'clock_in' AND ci.recorded_at <= attendance_records.recorded_at AND ci.recorded_at >= {$previousDayBoundary} ORDER BY ci.recorded_at DESC LIMIT 1)";

                $query->where(function ($nested) use ($from, $to, $effectiveDate, $sessionDate): void {
                    foreach ([$effectiveDate, $sessionDate] as $dateExpression) {
                        $nested->orWhere(function ($range) use ($from, $to, $dateExpression): void {
                            if ($from) {
                                $range->whereRaw($dateExpression.' >= ?', [$from]);
                            }

                            if ($to) {
                                $range->whereRaw($dateExpression.' <= ?', [$to]);
                            }
                        });
                    }
                });
            })
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
            ->when($filters['attendance_type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['work_type'] ?? null, fn ($query, $type) => $query->where('work_type', $type))
            ->when(trim($filters['search'] ?? '') !== '', function ($query) use ($filters): void {
                $term = trim($filters['search']);
                $query->where(function ($nested) use ($term): void {
                    $nested->whereHas('employee.user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$term.'%'))
                        ->orWhereHas('employee', fn ($employeeQuery) => $employeeQuery->where('employee_number', 'like', '%'.$term.'%'))
                        ->orWhere('notes', 'like', '%'.$term.'%');
                });
            })
            ->orderByRaw("COALESCE((SELECT ac.requested_recorded_at FROM attendance_corrections ac WHERE ac.attendance_record_id = attendance_records.id AND ac.status = 'approved' ORDER BY ac.reviewed_at DESC LIMIT 1), attendance_records.recorded_at) DESC")
            ->orderByDesc('id');

        $filename = 'aktivitas-absensi-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($records): void {
            $stream = fopen('php://output', 'w');
            if ($stream === false) {
                return;
            }

            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Waktu', 'Nomor Pegawai', 'Nama', 'Jenis Absensi', 'Jenis Kerja', 'Lokasi / Proyek', 'Latitude', 'Longitude', 'Catatan']);

            $records->chunk(500, function ($chunk) use ($stream): void {
                foreach ($chunk as $record) {
                    fputcsv($stream, array_map($this->spreadsheetSafe(...), [
                        $record->effectiveRecordedAtLocal()->format('Y-m-d H:i:s'),
                        $record->employee->employee_number,
                        $record->employee->user->name,
                        $record->type->label(),
                        $record->work_type->label(),
                        $record->project?->name ?? $record->location?->name ?? '',
                        $record->latitude,
                        $record->longitude,
                        $record->notes,
                    ]));
                }
            });

            fclose($stream);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
        ]);
    }

    private function spreadsheetSafe(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return preg_match('/^[\s\x00-\x1F]*[=+\-@]/u', $value) === 1
            ? "'".$value
            : $value;
    }
}
