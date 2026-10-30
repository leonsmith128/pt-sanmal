<?php

namespace App\\Http\\Controllers;

use App\\Enums\\AttendanceType;
use App\\Enums\\WorkType;
use App\\Models\\AttendanceRecord;
use Illuminate\\Http\\Request;
use Illuminate\\Validation\\Rule;
use Symfony\\Component\\HttpFoundation\\StreamedResponse;

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
            ->with(['employee.user', 'location', 'project'])
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('attendance_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('attendance_date', '<=', $to))
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
            ->when($filters['attendance_type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['work_type'] ?? null, fn ($query, $type) => $query->where('work_type', $type))
            ->when(trim($filters['search'] ?? '') !== '', function ($query) use ($filters): void {
                $term = trim($filters['search']);
                $query->whereHas('employee.user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$term.'%'))
                    ->orWhereHas('employee', fn ($employeeQuery) => $employeeQuery->where('employee_number', 'like', '%'.$term.'%'))
                    ->orWhere('notes', 'like', '%'.$term.'%');
            })
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->get();

        $filename = 'aktivitas-absensi-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($records): void {
            $stream = fopen('php://output', 'w');
            if ($stream === false) {
                return;
            }

            fwrite($stream, "\\xEF\\xBB\\xBF");
            fputcsv($stream, ['Waktu', 'Nomor Pegawai', 'Nama', 'Jenis Absensi', 'Jenis Kerja', 'Lokasi / Proyek', 'Latitude', 'Longitude', 'Catatan']);

            foreach ($records as $record) {
                fputcsv($stream, array_map($this->spreadsheetSafe(...), [
                    $record->recordedAtLocal()->format('Y-m-d H:i:s'),
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

            fclose($stream);
        }, $filename, [
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
}
