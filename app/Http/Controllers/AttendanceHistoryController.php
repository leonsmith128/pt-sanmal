<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
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

    public function export(Request $request): StreamedResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        abort_unless($user->is_active && $employee, 403, 'Akun ini belum terhubung dengan data pegawai aktif.');

        $filters = $this->validatedFilters($request);
        $records = $this->attendanceQuery($employee, $filters)
            ->orderByRaw("COALESCE((SELECT ac.requested_recorded_at FROM attendance_corrections ac WHERE ac.attendance_record_id = attendance_records.id AND ac.status = 'approved' ORDER BY ac.reviewed_at DESC LIMIT 1), attendance_records.recorded_at) ".$filters['direction'])
            ->orderBy('id', $filters['direction'])
            ->get();

        $filename = 'riwayat-absensi-'.now()->format('Ymd-His').'.csv';

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
                    $record->effectiveRecordedAtLocal()->format('Y-m-d H:i:s'),
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

    private function attendanceQuery(Employee $employee, array $filters): HasMany
    {
        return $employee->attendanceRecords()
            ->with(['location', 'project', 'approvedCorrection'])
            ->when($filters['from'] !== '' || $filters['to'] !== '', function ($query) use ($filters): void {
                $from = $filters['from'] !== '' ? $filters['from'] : null;
                $to = $filters['to'] !== '' ? $filters['to'] : null;
                $effectiveDate = "COALESCE((SELECT ac.attendance_date FROM attendance_corrections ac WHERE ac.attendance_record_id = attendance_records.id AND ac.status = 'approved' ORDER BY ac.reviewed_at DESC LIMIT 1), attendance_records.attendance_date)";
                $sessionDate = "(SELECT COALESCE((SELECT ac2.attendance_date FROM attendance_corrections ac2 WHERE ac2.attendance_record_id = ci.id AND ac2.status = 'approved' ORDER BY ac2.reviewed_at DESC LIMIT 1), ci.attendance_date) FROM attendance_records ci WHERE ci.employee_id = attendance_records.employee_id AND ci.type = 'clock_in' AND ci.recorded_at <= attendance_records.recorded_at AND ci.recorded_at >= DATE_SUB(attendance_records.recorded_at, INTERVAL 24 HOUR) ORDER BY ci.recorded_at DESC LIMIT 1)";

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
            });
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
        return preg_match('/^[\s\x00-\x1F]*[=+\-@]/u', $value) === 1 ? "'".$value : $value;
    }
}
