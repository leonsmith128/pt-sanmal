<?php

namespace App\\Http\\Controllers;

use Illuminate\\Http\\Request;
use Illuminate\\Http\\StreamedResponse;
use Illuminate\\View\\View;

class AttendanceHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $employee = $user->employee;

        abort_unless($user->is_active && $employee, 403, 'Akun ini belum terhubung dengan data pegawai aktif.');

        $filters = $this->validatedFilters($request);
        $records = $this->attendanceQuery($employee, $filters)
            ->orderBy('recorded_at', $filters['direction'])
            ->orderBy('id', $filters['direction'])
            ->paginate(20)
            ->withQueryString();

        return view('attendance-history.index', [
            'records' => $records,
            'filters' => $filters,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        abort_unless($user->is_active && $employee, 403, 'Akun ini belum terhubung dengan data pegawai aktif.');

        $filters = $this->validatedFilters($request);
        $records = $this->attendanceQuery($employee, $filters)
            ->orderBy('recorded_at', $filters['direction'])
            ->orderBy('id', $filters['direction'])
            ->get();

        $filename = 'riwayat-absensi-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($records): void {
            $stream = fopen('php://output', 'w');
            // BOM membuat Excel lebih konsisten membaca karakter UTF-8.
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [
                'Waktu', 'Aktivitas', 'Jenis Kerja', 'Lokasi / Proyek',
                'Alamat', 'Latitude', 'Longitude', 'Foto Tersedia',
            ]);

            foreach ($records as $record) {
                fputcsv($stream, array_map($this->spreadsheetSafe(...), [
                    $record->recordedAtLocal()->format('Y-m-d H:i:s'),
                    $record->type->label(),
                    $record->work_type->label(),
                    $record->project?->name ?? $record->location?->name ?? '',
                    $record->address_snapshot,
                    $record->latitude,
                    $record->longitude,
                    $record->selfie_path ? 'Ya' : 'Tidak',
                ]));
            }

            fclose($stream);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function attendanceQuery($employee, array $filters)
    {
        return $employee->attendanceRecords()
            ->with(['location', 'project'])
            ->when($filters['from'] !== '', fn ($query) => $query->whereDate('attendance_date', '>=', $filters['from']))
            ->when($filters['to'] !== '', fn ($query) => $query->whereDate('attendance_date', '<=', $filters['to']));
    }

    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
        ], [
            'to.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
        ]);

        return [
            'from' => isset($validated['from']) ? date('Y-m-d', strtotime($validated['from'])) : '',
            'to' => isset($validated['to']) ? date('Y-m-d', strtotime($validated['to'])) : '',
            'direction' => $validated['direction'] ?? 'desc',
        ];
    }

    private function spreadsheetSafe(mixed $value): string
    {
        $value = (string) ($value ?? '');

        // Cegah teks yang tidak tepercaya dieksekusi sebagai formula spreadsheet.
        return preg_match('/^[\\s\\x00-\\x1F]*[=+\\-@]/u', $value) === 1 ? "'" . $value : $value;
    }
}
