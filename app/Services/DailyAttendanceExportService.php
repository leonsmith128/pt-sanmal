<?php

namespace App\Services;

use App\Enums\AttendanceType;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DailyAttendanceExportService
{
    public function stream(Builder $employeesQuery, Carbon $start, Carbon $end, DailyAttendanceStatusService $statusService, array $filters = []): void
    {
        $stream = fopen('php://output', 'w');

        if ($stream === false) {
            return;
        }

        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, [
            'ID Pegawai', 'Nomor Pegawai', 'Nama Pegawai', 'Tanggal', 'Status Akhir',
            'Clock In Efektif', 'Clock Out Efektif', 'Total Durasi',
            'Waktu Asli Clock In', 'Waktu Asli Clock Out',
            'Detail Sesi', 'Event Lain (efektif / asli)', 'Jenis Kerja',
            'Kode Client', 'Client', 'Kode Project', 'Project', 'Location', 'Sumber Snapshot',
            'Status Koreksi', 'Catatan Absensi / Koreksi / Status',
        ]);

        $monthStart = $start->copy()->startOfMonth();
        $lastMonth = $end->copy()->startOfMonth();

        for ($month = $monthStart; $month->lte($lastMonth); $month->addMonth()) {
            $rangeStart = $month->copy()->max($start);
            $rangeEnd = $month->copy()->endOfMonth()->min($end);

            $employeesQuery->with('user')->orderBy('id')->chunkById(100, function (Collection $employees) use (
                $statusService, $rangeStart, $rangeEnd, $filters, $stream
            ): void {
                /** @var Collection<int, Employee> $employees */
                $statuses = $statusService->forEmployeesDateRange($employees, $rangeStart, $rangeEnd, 'Asia/Makassar');

                foreach ($employees as $employee) {
                    for ($day = $rangeStart->copy(); $day->lte($rangeEnd); $day->addDay()) {
                        if ($employee->joined_at && $day->toDateString() < $employee->joined_at->toDateString()) {
                            continue;
                        }

                        $date = $day->toDateString();
                        $daily = $statuses[$employee->id][$date] ?? null;

                        if ($daily === null) {
                            continue;
                        }

                        $events = $daily['events'];
                        if (($filters['attendance_type'] ?? '') !== '') {
                            $events = $events->filter(fn (AttendanceRecord $event) => $event->type->value === $filters['attendance_type']);
                            if ($events->isEmpty()) {
                                continue;
                            }
                        }
                        if (($filters['work_type'] ?? '') !== '') {
                            $events = $events->filter(fn (AttendanceRecord $event) => $event->work_type->value === $filters['work_type']);
                            if ($events->isEmpty()) {
                                continue;
                            }
                        }

                        $allEvents = $daily['events'];
                        $sessions = $allEvents->groupBy(fn (AttendanceRecord $event) => $event->session_anchor_id ?? $event->id);
                        $clockIns = collect();
                        $clockOuts = collect();
                        $durations = 0;
                        $sessionDetails = [];
                        $originalIns = [];
                        $originalOuts = [];
                        $correctionNotes = [];
                        $otherEvents = [];
                        $attendanceNotes = [];

                        foreach ($sessions as $sessionEvents) {
                            $in = $sessionEvents->first(fn (AttendanceRecord $event) => $event->type === AttendanceType::ClockIn);
                            $out = $sessionEvents->first(fn (AttendanceRecord $event) => $event->type === AttendanceType::ClockOut);

                            if ($in) {
                                $effectiveIn = $in->effectiveRecordedAtLocal();
                                $clockIns->push($effectiveIn->format('H:i'));
                                $originalIns[] = $in->recordedAtLocal()->format('Y-m-d H:i:s');
                                if ($in->approvedCorrection) {
                                    $correctionNotes[] = 'Clock In: '.$in->approvedCorrection->reason;
                                }
                            }

                            if ($out) {
                                $effectiveOut = $out->effectiveRecordedAtLocal();
                                $clockOuts->push($effectiveOut->format('H:i'));
                                $originalOuts[] = $out->recordedAtLocal()->format('Y-m-d H:i:s');
                                if ($out->approvedCorrection) {
                                    $correctionNotes[] = 'Clock Out: '.$out->approvedCorrection->reason;
                                }
                            }

                            if ($in && $out) {
                                $minutes = (int) $in->effectiveRecordedAt()->diffInMinutes($out->effectiveRecordedAt(), true);
                                $durations += $minutes;
                                $sessionDetails[] = $effectiveIn->format('Y-m-d H:i').'–'.$effectiveOut->format('Y-m-d H:i');
                            } elseif ($in) {
                                $sessionDetails[] = $in->effectiveRecordedAtLocal()->format('Y-m-d H:i').'–belum Clock Out';
                            } elseif ($out) {
                                $sessionDetails[] = 'Clock Out '.$out->effectiveRecordedAtLocal()->format('Y-m-d H:i').' (tanpa Clock In)';
                            }
                        }

                        $workTypes = $allEvents->pluck('work_type')->map(fn ($type) => $type->label())->unique()->implode('; ');
                        $clientCodes = $allEvents->pluck('client_code_snapshot')->filter()->unique()->implode('; ');
                        $clientNames = $allEvents->pluck('client_name_snapshot')->filter()->unique()->implode('; ');
                        $projectCodes = $allEvents->pluck('project_code_snapshot')->filter()->unique()->implode('; ');
                        $projectNames = $allEvents->pluck('project_name_snapshot')->filter()->unique()->implode('; ');
                        $locationNames = $allEvents->pluck('location_name_snapshot')->filter()->unique()->implode('; ');
                        $snapshotSources = $allEvents->pluck('snapshot_source')->filter()->unique()->implode('; ');

                        $correctionStatuses = [];
                        foreach ($allEvents as $event) {
                            if (! in_array($event->type, [AttendanceType::ClockIn, AttendanceType::ClockOut], true)) {
                                $otherEvents[] = $event->type->label().': '.$event->effectiveRecordedAtLocal()->format('Y-m-d H:i')
                                    .' (asli: '.$event->recordedAtLocal()->format('Y-m-d H:i').')';
                            }
                            if ($event->notes) {
                                $attendanceNotes[] = $event->type->label().': '.$event->notes;
                            }

                            $eventCorrections = $event->corrections
                                ->merge($event->sessionCorrections)
                                ->unique('id');

                            foreach ($eventCorrections as $correction) {
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
                        $notes = collect([$daily['reason'], $leaveLabels ? 'Permohonan: '.$leaveLabels : null])
                            ->merge($attendanceNotes)
                            ->merge($correctionNotes)
                            ->filter()
                            ->unique()
                            ->implode(' | ');

                        $values = [
                            $employee->id,
                            $employee->employee_number ?? '',
                            $employee->user?->name ?? '',
                            $date,
                            $daily['label'],
                            $clockIns->implode('; '),
                            $clockOuts->implode('; '),
                            $durations > 0 ? intdiv($durations, 60).'j '.($durations % 60).'m' : '',
                            implode('; ', $originalIns),
                            implode('; ', $originalOuts),
                            implode('; ', $sessionDetails),
                            implode('; ', $otherEvents),
                            $workTypes,
                            $clientCodes,
                            $clientNames,
                            $projectCodes,
                            $projectNames,
                            $locationNames,
                            $snapshotSources,
                            collect($correctionStatuses)->unique()->implode('; '),
                            $notes,
                        ];

                        fputcsv($stream, array_map($this->spreadsheetSafe(...), $values));
                    }
                }
            });
        }

        fclose($stream);
    }

    private function spreadsheetSafe(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return preg_match('/^[\s\x00-\x1F]*[=+\-@]/u', $value) === 1 ? "'".$value : $value;
    }
}
