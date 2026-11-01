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
            'ID Pegawai', 'Nama Pegawai', 'Tanggal', 'Status Akhir',
            'Clock In Efektif', 'Clock Out Efektif', 'Total Durasi',
            'Waktu Asli Clock In', 'Waktu Asli Clock Out',
            'Detail Sesi', 'Jenis Kerja', 'Lokasi / Proyek',
            'Status Koreksi', 'Catatan Koreksi / Status',
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
                $statuses = $statusService->forEmployeesDateRange($employees, $rangeStart, $rangeEnd);

                foreach ($employees as $employee) {
                    for ($day = $rangeStart->copy(); $day->lte($rangeEnd); $day->addDay()) {
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
                        $places = $allEvents->map(function (AttendanceRecord $event): string {
                            return $event->project?->name && $event->location?->name
                                ? $event->project->name.' — '.$event->location->name
                                : ($event->project?->name ?? $event->location?->name ?? '');
                        })->filter()->unique()->implode('; ');

                        $correctionStatuses = [];
                        foreach ($allEvents as $event) {
                            foreach ($event->corrections as $correction) {
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
                            ->merge($correctionNotes)->filter()->unique()->implode(' | ');

                        $values = [
                            $employee->employee_number ?? $employee->id,
                            $employee->user?->name ?? '',
                            $date,
                            $daily['label'],
                            $clockIns->implode('; '),
                            $clockOuts->implode('; '),
                            $durations > 0 ? intdiv($durations, 60).'j '.($durations % 60).'m' : '',
                            implode('; ', $originalIns),
                            implode('; ', $originalOuts),
                            implode('; ', $sessionDetails),
                            $workTypes,
                            $places,
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
