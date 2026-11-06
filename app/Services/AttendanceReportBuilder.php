<?php

namespace App\Services;

use App\Data\Attendance\AttendanceFilterData;
use App\Enums\AttendanceType;
use App\Exports\ReportSheet;
use App\Exports\ReportWorkbook;
use App\Filters\AttendanceRecordFilter;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Support\AttendanceSelfie;
use App\Support\ReportCell;
use App\Support\ReportPhotoThumbnail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;
use Throwable;

/**
 * Menyusun workbook absensi (Ringkasan + Detail), satu baris per pegawai per tanggal sesi.
 * Dipakai oleh Riwayat Absensi Pegawai, Kehadiran Admin, dan Rekap Harian Admin.
 */
class AttendanceReportBuilder
{
    public const MAX_ROWS = 10000;

    public const MAX_EMBEDDED_PHOTOS = 200;

    private const TIMEZONE = 'Asia/Makassar';

    private const SUMMARY_HEADINGS = [
        'ID Pegawai', 'Nomor Pegawai', 'Nama Pegawai', 'Tanggal Laporan (WITA)', 'Status Akhir',
        'Clock In Efektif', 'Clock Out Efektif', 'Total Durasi', 'Total Lembur Final',
        'Jenis Kerja', 'Status Koreksi',
    ];

    /** @var list<list<string>> */
    private array $summaryRows = [];

    /** @var list<list<string>> */
    private array $detailRows = [];

    /** @var list<BaseDrawing> */
    private array $drawings = [];

    /** @var list<string> */
    private array $detailHeadings = [];

    private int $photoCount = 0;

    private int $photoAttempts = 0;

    private float $statusDurationMs = 0;

    private float $rowAssemblyDurationMs = 0;

    private float $photoProcessingDurationMs = 0;

    public function __construct(private readonly DailyAttendanceStatusService $statusService) {}

    /**
     * @param  Builder<Employee>  $employeesQuery
     * @param  ?string  $reportingTimezone  Sama dengan parameter DailyAttendanceStatusService (null = tanggal absensi tersimpan).
     * @param  bool  $skipNoRecord  Lewati tanggal tanpa catatan (perilaku halaman Riwayat Absensi Pegawai).
     */
    public function build(
        Builder $employeesQuery,
        Carbon $start,
        Carbon $end,
        ?string $reportingTimezone = self::TIMEZONE,
        AttendanceFilterData $filters = new AttendanceFilterData,
        bool $includeOvertime = true,
        bool $skipNoRecord = false,
    ): ReportWorkbook {
        $this->summaryRows = [];
        $this->detailRows = [];
        $this->drawings = [];
        $this->photoCount = 0;
        $this->photoAttempts = 0;
        $this->statusDurationMs = 0;
        $this->rowAssemblyDurationMs = 0;
        $this->photoProcessingDurationMs = 0;

        $buildStartedAt = hrtime(true);
        $summaryHeadings = $this->withoutOvertime(self::SUMMARY_HEADINGS, $includeOvertime);
        $this->detailHeadings = $this->withoutOvertime($this->allDetailHeadings(), $includeOvertime);

        $recordFilter = new AttendanceRecordFilter($filters);
        $lastMonth = $end->copy()->startOfMonth();

        // Rentang diproses per bulan agar memori status harian tetap terbatas.
        for ($month = $start->copy()->startOfMonth(); $month->lte($lastMonth); $month->addMonth()) {
            $rangeStart = $month->copy()->max($start);
            $rangeEnd = $month->copy()->endOfMonth()->min($end);

            (clone $employeesQuery)->with('user')->orderBy('id')->chunkById(100, function (Collection $employees) use (
                $rangeStart, $rangeEnd, $reportingTimezone, $recordFilter, $includeOvertime, $skipNoRecord, $summaryHeadings
            ): void {
                /** @var Collection<int, Employee> $employees */
                $statusStartedAt = hrtime(true);
                $statuses = $this->statusService->forEmployeesDateRange($employees, $rangeStart, $rangeEnd, $reportingTimezone);
                $this->statusDurationMs += (hrtime(true) - $statusStartedAt) / 1_000_000;
                $overtime = $includeOvertime ? $this->finalOvertimeMinutes($employees, $rangeStart, $rangeEnd) : collect();

                foreach ($employees as $employee) {
                    for ($day = $rangeStart->copy(); $day->lte($rangeEnd); $day->addDay()) {
                        $date = $day->toDateString();

                        // Tidak ada baris sebelum tanggal bergabung.
                        if ($employee->joined_at && $date < $employee->joined_at->toDateString()) {
                            continue;
                        }

                        $daily = $statuses[$employee->id][$date] ?? null;

                        if ($daily === null || ($skipNoRecord && $daily['status'] === 'no_record')) {
                            continue;
                        }

                        // Filter menentukan apakah hari ini ikut; setelah ikut, semua event hari itu diekspor.
                        if (! $recordFilter->matchesDay($employee, $daily['events'])) {
                            continue;
                        }

                        $rowStartedAt = hrtime(true);
                        $this->appendDay($employee, $date, $daily, $overtime->get($employee->id.'_'.$date), $includeOvertime, $summaryHeadings);
                        $this->rowAssemblyDurationMs += (hrtime(true) - $rowStartedAt) / 1_000_000;
                    }
                }
            });
        }

        Log::info('Attendance report workbook prepared.', [
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
            'status_duration_ms' => round($this->statusDurationMs, 2),
            'row_assembly_duration_ms' => round($this->rowAssemblyDurationMs, 2),
            'photo_processing_duration_ms' => round($this->photoProcessingDurationMs, 2),
            'photo_attempts' => $this->photoAttempts,
            'photos_embedded' => $this->photoCount,
            'detail_rows' => count($this->detailRows),
            'build_duration_ms' => round((hrtime(true) - $buildStartedAt) / 1_000_000, 2),
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1_048_576, 2),
        ]);

        return new ReportWorkbook(
            new ReportSheet('Ringkasan', $summaryHeadings, $this->summaryRows),
            new ReportSheet(
                'Detail',
                $this->detailHeadings,
                $this->detailRows,
                ['Peta Clock In', 'Peta Clock Out'],
                $this->drawings,
                ['Foto Clock In', 'Foto Clock Out'],
            ),
        );
    }

    /**
     * Total menit lembur FINAL per pegawai per session_date. Hanya record yang sudah difinalisasi
     * dengan pasangan final_start_at < final_end_at yang valid; tidak pernah dihitung dari jam shift.
     *
     * @param  Collection<int, Employee>  $employees
     * @return Collection<string, int>
     */
    private function finalOvertimeMinutes(Collection $employees, Carbon $start, Carbon $end): Collection
    {
        return OvertimeRequest::query()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->whereDate('session_date', '>=', $start->toDateString())
            ->whereDate('session_date', '<=', $end->toDateString())
            ->whereNotNull('finalized_at')
            ->whereNotNull('final_start_at')
            ->whereNotNull('final_end_at')
            ->whereColumn('final_end_at', '>', 'final_start_at')
            ->get(['id', 'employee_id', 'session_date', 'final_start_at', 'final_end_at'])
            ->groupBy(fn (OvertimeRequest $item): string => $item->employee_id.'_'.$item->session_date->toDateString())
            ->map(fn (Collection $items): int => (int) $items->sum(
                fn (OvertimeRequest $item): int => (int) $item->final_start_at->diffInMinutes($item->final_end_at, true),
            ));
    }

    /**
     * @param  array{status:string,label:string,events:Collection,leaveRequests:Collection,correction:mixed,needs_review:bool,reason:?string}  $daily
     * @param  list<string>  $summaryHeadings
     */
    private function appendDay(Employee $employee, string $date, array $daily, ?int $overtimeMinutes, bool $includeOvertime, array $summaryHeadings): void
    {
        if (count($this->detailRows) >= self::MAX_ROWS) {
            abort(422, 'Ekspor melebihi '.number_format(self::MAX_ROWS, 0, ',', '.').' baris. Persempit rentang tanggal atau filter lalu coba lagi.');
        }

        /** @var Collection<int, AttendanceRecord> $allEvents */
        $allEvents = $daily['events'];
        $sessions = $allEvents->groupBy(fn (AttendanceRecord $event) => $event->session_anchor_id ?? $event->id);

        $clockIns = [];
        $clockOuts = [];
        $originalIns = [];
        $originalOuts = [];
        $sessionDetails = [];
        $correctionNotes = [];
        $totalMinutes = null;

        foreach ($sessions as $sessionEvents) {
            $in = $sessionEvents->first(fn (AttendanceRecord $event) => $event->type === AttendanceType::ClockIn);
            $out = $sessionEvents->first(fn (AttendanceRecord $event) => $event->type === AttendanceType::ClockOut);

            if ($in) {
                $clockIns[] = $this->wita($in->effectiveRecordedAt())->format('H:i:s');
                $originalIns[] = $this->wita($in->recorded_at)->format('Y-m-d H:i:s');

                if ($in->approvedCorrection) {
                    $correctionNotes[] = 'Clock In: '.$in->approvedCorrection->reason;
                }
            }

            if ($out) {
                $clockOuts[] = $this->wita($out->effectiveRecordedAt())->format('H:i:s');
                $originalOuts[] = $this->wita($out->recorded_at)->format('Y-m-d H:i:s');

                if ($out->approvedCorrection) {
                    $correctionNotes[] = 'Clock Out: '.$out->approvedCorrection->reason;
                }
            }

            if ($in && $out) {
                // Durasi dari timestamp aktual, bukan selisih tanggal kalender.
                $totalMinutes = ($totalMinutes ?? 0) + (int) $in->effectiveRecordedAt()->diffInMinutes($out->effectiveRecordedAt(), true);
                $sessionDetails[] = $this->wita($in->effectiveRecordedAt())->format('Y-m-d H:i:s').' – '.$this->wita($out->effectiveRecordedAt())->format('Y-m-d H:i:s');
            } elseif ($in) {
                $sessionDetails[] = $this->wita($in->effectiveRecordedAt())->format('Y-m-d H:i:s').' – belum Clock Out';
            } elseif ($out) {
                $sessionDetails[] = 'Clock Out '.$this->wita($out->effectiveRecordedAt())->format('Y-m-d H:i:s').' (tanpa Clock In)';
            }
        }

        $otherEvents = [];
        $attendanceNotes = [];
        $correctionStatuses = [];

        foreach ($allEvents as $event) {
            if (! in_array($event->type, [AttendanceType::ClockIn, AttendanceType::ClockOut], true)) {
                $otherEvents[] = $event->type->label().': '.$this->wita($event->effectiveRecordedAt())->format('Y-m-d H:i:s')
                    .' (asli: '.$this->wita($event->recorded_at)->format('Y-m-d H:i:s').')';
            }

            if ($event->notes) {
                $attendanceNotes[] = $event->type->label().': '.$event->notes;
            }

            foreach ($event->corrections->merge($event->sessionCorrections)->unique('id') as $correction) {
                $correctionStatuses[] = match ($correction->status) {
                    'pending' => 'Menunggu',
                    'approved' => 'Disetujui',
                    'rejected' => 'Ditolak',
                    'cancelled' => 'Dibatalkan',
                    default => (string) $correction->status,
                };
                $correctionNotes[] = $event->type->label().': '.$correction->reason;

                if ($correction->review_note) {
                    $correctionNotes[] = 'Catatan peninjau: '.$correction->review_note;
                }
            }
        }

        if ($daily['correction']) {
            $correctionStatuses[] = 'Koreksi status harian';
            $correctionNotes[] = $daily['correction']->reason;
        }

        $leaveLabels = $daily['leaveRequests']
            ->map(fn ($request) => $request->leaveType?->name)
            ->filter()
            ->unique()
            ->implode('; ');
        $notes = collect([$daily['reason'], $leaveLabels !== '' ? 'Permohonan: '.$leaveLabels : null])
            ->merge($attendanceNotes)
            ->merge($correctionNotes)
            ->filter()
            ->unique()
            ->implode(' | ');

        $firstIn = $allEvents->first(fn (AttendanceRecord $event) => $event->type === AttendanceType::ClockIn);
        $firstOut = $allEvents->first(fn (AttendanceRecord $event) => $event->type === AttendanceType::ClockOut);
        $row = count($this->detailRows) + 2;

        $values = [
            'ID Pegawai' => (string) $employee->id,
            'Nomor Pegawai' => ReportCell::text($employee->employee_number),
            'Nama Pegawai' => ReportCell::text($employee->user?->name),
            'Tanggal Laporan (WITA)' => $date,
            'Status Akhir' => ReportCell::text($daily['label']),
            'Clock In Efektif' => ReportCell::joined($clockIns),
            'Clock Out Efektif' => ReportCell::joined($clockOuts),
            'Total Durasi' => ReportCell::duration($totalMinutes),
            // "-" = tidak ada catatan lembur final; "0j 0m" = ada catatan final dengan total nol.
            'Total Lembur Final' => ReportCell::duration($includeOvertime ? $overtimeMinutes : null),
            'Jenis Kerja' => ReportCell::joined($allEvents->map(fn (AttendanceRecord $event) => $event->work_type->label())),
            'Status Koreksi' => ReportCell::joined($correctionStatuses),
            'Waktu Asli Clock In' => ReportCell::joined($originalIns),
            'Waktu Asli Clock Out' => ReportCell::joined($originalOuts),
            'Detail Sesi' => ReportCell::joined($sessionDetails),
            'Event Lain (efektif / asli)' => ReportCell::joined($otherEvents),
            'Catatan Absensi / Koreksi / Status' => ReportCell::text($notes),
            ...$this->administrativeContext($allEvents),
            ...$this->eventValues($firstIn, 'Clock In', $row),
            ...$this->eventValues($firstOut, 'Clock Out', $row),
        ];

        $pick = fn (array $headings): array => array_map(
            fn (string $heading): string => $values[$heading] ?? ReportCell::BLANK,
            $headings,
        );

        $this->summaryRows[] = $pick($summaryHeadings);
        $this->detailRows[] = $pick($this->detailHeadings);
    }

    /**
     * Kolom administrasi (Client/Project/Location) dibedakan dari lokasi aktual event.
     * Snapshot dipakai bila ada; data lama tanpa snapshot memakai referensi master yang diberi label jelas.
     *
     * @param  Collection<int, AttendanceRecord>  $events
     * @return array<string, string>
     */
    private function administrativeContext(Collection $events): array
    {
        $snapshot = [
            'Kode Client' => $events->pluck('client_code_snapshot'),
            'Client' => $events->pluck('client_name_snapshot'),
            'Kode Project' => $events->pluck('project_code_snapshot'),
            'Project' => $events->pluck('project_name_snapshot'),
            'Location' => $events->pluck('location_name_snapshot'),
        ];
        $hasSnapshot = collect($snapshot)->contains(fn (Collection $values): bool => $values->filter()->isNotEmpty());

        if ($hasSnapshot) {
            return [
                ...array_map(fn (Collection $values): string => ReportCell::joined($values), $snapshot),
                'Sumber Data Administrasi' => 'Snapshot saat absensi'
                    .($events->pluck('snapshot_source')->filter()->isNotEmpty() ? ' ('.ReportCell::joined($events->pluck('snapshot_source')).')' : ''),
            ];
        }

        $master = [
            'Kode Project' => ReportCell::joined($events->map(fn (AttendanceRecord $event) => data_get($event->project, 'code'))),
            'Project' => ReportCell::joined($events->map(fn (AttendanceRecord $event) => data_get($event->project, 'name'))),
            'Location' => ReportCell::joined($events->map(fn (AttendanceRecord $event) => data_get($event->location, 'name'))),
        ];
        $hasMaster = collect($master)->contains(fn (string $value): bool => $value !== ReportCell::BLANK);

        return [
            'Kode Client' => ReportCell::BLANK,
            'Client' => ReportCell::BLANK,
            ...$master,
            'Sumber Data Administrasi' => $hasMaster ? 'Referensi master saat ini (bukan snapshot historis)' : ReportCell::BLANK,
        ];
    }

    /**
     * Lokasi/foto aktual satu event. Ketiadaan data dicatat sebagai status dan tidak menggagalkan ekspor.
     *
     * @return array<string, string>
     */
    private function eventValues(?AttendanceRecord $event, string $label, int $row): array
    {
        $mapUrl = $event ? ReportCell::mapUrl($event->latitude, $event->longitude) : null;
        [$photoStatus, $embedded] = $this->addPhoto($event, $label, $row);

        $gpsStatus = match (true) {
            $event === null => 'Tidak ada event',
            $mapUrl === null => 'Tidak tersedia',
            $event->location_status === 'captured' => 'Tersedia',
            default => 'Tidak terverifikasi',
        };

        return [
            "Alamat {$label} (Snapshot)" => $event ? ReportCell::text($event->address_snapshot) : ReportCell::BLANK,
            "Latitude {$label}" => $event ? ReportCell::number($event->latitude, 7) : ReportCell::BLANK,
            "Longitude {$label}" => $event ? ReportCell::number($event->longitude, 7) : ReportCell::BLANK,
            "Accuracy {$label} (m)" => $event ? ReportCell::number($event->accuracy_m, 2) : ReportCell::BLANK,
            "Peta {$label}" => $mapUrl ?? ReportCell::BLANK,
            "Status GPS {$label}" => $gpsStatus,
            "Status Alamat {$label}" => match (true) {
                $event === null => 'Tidak ada event',
                filled($event->address_snapshot) => 'Tersedia',
                default => 'Tidak tersedia',
            },
            "Status Foto {$label}" => $photoStatus,
            "Foto {$label}" => $embedded ? '' : ReportCell::BLANK,
        ];
    }

    /**
     * Embed thumbnail 120x120 px hanya dari storage privat lokal; tidak ada akses Google Drive.
     *
     * @return array{0: string, 1: bool}
     */
    private function addPhoto(?AttendanceRecord $event, string $label, int $row): array
    {
        if ($event === null) {
            return ['Tidak ada event', false];
        }

        if (blank($event->selfie_path)) {
            return ['Tidak ada foto', false];
        }

        $this->photoAttempts++;

        try {
            $disk = Storage::disk(AttendanceSelfie::DISK);

            if (! $disk->exists($event->selfie_path)) {
                return ['Tidak tersedia di penyimpanan lokal', false];
            }

            if ($this->photoCount >= self::MAX_EMBEDDED_PHOTOS) {
                return ['Ada (tidak di-embed: batas '.self::MAX_EMBEDDED_PHOTOS.' foto)', false];
            }

            $binary = $disk->get($event->selfie_path);
            $column = Coordinate::stringFromColumnIndex(array_search('Foto '.$label, $this->detailHeadings, true) + 1);
            $photoStartedAt = hrtime(true);
            $drawing = $binary === null
                ? null
                : ReportPhotoThumbnail::drawing($binary, $column.$row, 'Foto '.$label.' '.$event->id);
            $this->photoProcessingDurationMs += (hrtime(true) - $photoStartedAt) / 1_000_000;
        } catch (Throwable) {
            return ['Gagal diproses', false];
        }

        if ($drawing === null) {
            return ['Gagal diproses', false];
        }

        $this->drawings[] = $drawing;
        $this->photoCount++;

        return ['Di-embed (120x120 px)', true];
    }

    private function wita(mixed $time): Carbon
    {
        return Carbon::parse($time)->setTimezone(self::TIMEZONE);
    }

    /** @return list<string> */
    private function allDetailHeadings(): array
    {
        return [
            'ID Pegawai', 'Nomor Pegawai', 'Nama Pegawai', 'Tanggal Laporan (WITA)', 'Status Akhir',
            'Total Durasi', 'Total Lembur Final',
            'Waktu Asli Clock In', 'Waktu Asli Clock Out', 'Detail Sesi', 'Event Lain (efektif / asli)', 'Jenis Kerja',
            'Kode Client', 'Client', 'Kode Project', 'Project', 'Location', 'Sumber Data Administrasi',
            ...$this->eventHeadings('Clock In'),
            ...$this->eventHeadings('Clock Out'),
            'Status Koreksi', 'Catatan Absensi / Koreksi / Status',
        ];
    }

    /** @return list<string> */
    private function eventHeadings(string $label): array
    {
        return [
            "Alamat {$label} (Snapshot)", "Latitude {$label}", "Longitude {$label}", "Accuracy {$label} (m)",
            "Peta {$label}", "Status GPS {$label}", "Status Alamat {$label}", "Status Foto {$label}", "Foto {$label}",
        ];
    }

    /**
     * @param  list<string>  $headings
     * @return list<string>
     */
    private function withoutOvertime(array $headings, bool $includeOvertime): array
    {
        return $includeOvertime ? $headings : array_values(array_diff($headings, ['Total Lembur Final']));
    }
}
