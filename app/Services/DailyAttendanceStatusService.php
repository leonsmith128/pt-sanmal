<?php

namespace App\Services;

use App\Enums\DailyAttendanceStatus;
use App\Enums\LeaveRequestStatus;
use App\Models\AttendanceRecord;
use App\Models\DailyStatusCorrection;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DailyAttendanceStatusService
{
    /**
     * Rekap harian menggabungkan bukti absensi aktual, permohonan yang disetujui,
     * dan koreksi administratif. Service ini tidak membuat event clock-in/out.
     *
     * @return array{status:string,label:string,events:Collection,leaveRequests:Collection,correction:?DailyStatusCorrection,needs_review:bool,reason:?string}
     */
    public function forEmployeeDate(Employee $employee, Carbon|string $date): array
    {
        $day = Carbon::parse($date)->toDateString();
        $batch = $this->forEmployeesDateRange(collect([$employee]), $day, $day);

        return $batch[$employee->id][$day];
    }

    /**
     * Mengambil status harian untuk banyak pegawai dan rentang tanggal sekaligus dalam 3 query tetap (O(1)),
     * mencegah masalah performa N+1 query.
     *
     * @param  Collection<int, Employee>  $employees
     * @return array<int, array<string, array{status:string,label:string,events:Collection,leaveRequests:Collection,correction:?DailyStatusCorrection,needs_review:bool,reason:?string}>>
     */
    public function forEmployeesDateRange(Collection $employees, Carbon|string $startDate, Carbon|string $endDate): array
    {
        $start = Carbon::parse($startDate)->toDateString();
        $end = Carbon::parse($endDate)->toDateString();
        $employeeIds = $employees->pluck('id')->filter()->all();

        if (empty($employeeIds)) {
            return [];
        }

        // Query 1: Ambil event satu hari sebelum/sesudah rentang untuk memasangkan
        // clock-in dan clock-out sesi lintas tengah malam (batas sesi teknis 24 jam).
        $rawEvents = AttendanceRecord::query()
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('attendance_date', '>=', Carbon::parse($start)->subDay()->toDateString())
            ->whereDate('attendance_date', '<=', Carbon::parse($end)->addDay()->toDateString())
            ->with('approvedCorrection')
            ->orderBy('employee_id')
            ->get()
            ->sortBy(fn (AttendanceRecord $event) => $event->effectiveRecordedAt()->timestamp)
            ->values();

        $eventsByEmployeeAndDate = collect();
        $sessionDateByEmployee = [];

        foreach ($rawEvents as $event) {
            $employeeId = $event->employee_id;
            $eventDate = $event->effectiveAttendanceDate();

            if ($event->type->value === 'clock_in') {
                $sessionDateByEmployee[$employeeId] = $eventDate;
                $assignedDate = $eventDate;
            } elseif (in_array($event->type->value, ['overtime_start', 'clock_out'], true)) {
                $assignedDate = $sessionDateByEmployee[$employeeId] ?? $eventDate;
            } else {
                $assignedDate = $eventDate;
            }

            if ($assignedDate >= $start && $assignedDate <= $end) {
                $key = $employeeId.'_'.$assignedDate;
                $eventsByEmployeeAndDate[$key] ??= collect();
                $eventsByEmployeeAndDate[$key]->push($event);
            }

            if ($event->type->value === 'clock_out') {
                unset($sessionDateByEmployee[$employeeId]);
            }
        }

        // Query 2: Ambil permohonan izin/cuti/sakit yang sudah disetujui
        $leaveRequestsByEmployee = LeaveRequest::query()
            ->with('leaveType')
            ->whereIn('employee_id', $employeeIds)
            ->where('status', LeaveRequestStatus::Approved)
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->get()
            ->groupBy('employee_id');

        // Query 3: Ambil koreksi administratif
        $correctionsByEmployeeAndDate = DailyStatusCorrection::query()
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('attendance_date', '>=', $start)
            ->whereDate('attendance_date', '<=', $end)
            ->get()
            ->keyBy(fn (DailyStatusCorrection $c) => $c->employee_id.'_'.$c->attendance_date->format('Y-m-d'));

        $result = [];
        $carbonStart = Carbon::parse($start);
        $carbonEnd = Carbon::parse($end);

        foreach ($employees as $employee) {
            $result[$employee->id] = [];
            $employeeLeaves = $leaveRequestsByEmployee->get($employee->id) ?? collect();

            for ($day = $carbonStart->copy(); $day->lte($carbonEnd); $day->addDay()) {
                $dayStr = $day->toDateString();
                $key = $employee->id.'_'.$dayStr;

                $events = $eventsByEmployeeAndDate->get($key) ?? collect();
                $correction = $correctionsByEmployeeAndDate->get($key);

                $relevantLeaves = $employeeLeaves->filter(
                    fn (LeaveRequest $l) => $l->start_date->toDateString() <= $dayStr && $l->end_date->toDateString() >= $dayStr
                )->values();

                $result[$employee->id][$dayStr] = $this->resolveStatus($events, $relevantLeaves, $correction);
            }
        }

        return $result;
    }

    /**
     * Evaluasi aturan bisnis untuk menentukan status akhir satu hari.
     *
     * @param  Collection<int, AttendanceRecord>  $events
     * @param  Collection<int, LeaveRequest>  $leaveRequests
     * @return array{status:string,label:string,events:Collection,leaveRequests:Collection,correction:?DailyStatusCorrection,needs_review:bool,reason:?string}
     */
    public function resolveStatus(Collection $events, Collection $leaveRequests, ?DailyStatusCorrection $correction): array
    {
        // Koreksi dibuat secara eksplisit oleh admin dan berisi alasan; ini adalah
        // keputusan final untuk hari tersebut, bukan event absensi buatan.
        if ($correction) {
            $status = $correction->status instanceof DailyAttendanceStatus
                ? $correction->status
                : (DailyAttendanceStatus::tryFrom((string) $correction->status) ?? DailyAttendanceStatus::Present);

            return [
                'status' => $status->value,
                'label' => $status->label(),
                'events' => $events,
                'leaveRequests' => $leaveRequests,
                'correction' => $correction,
                'needs_review' => false,
                'reason' => $correction->reason,
            ];
        }

        $leaveStatuses = $leaveRequests->map(
            fn (LeaveRequest $request) => $this->statusFromLeaveType($request->leaveType?->code, $request->leaveType?->name),
        )->unique();

        if ($leaveStatuses->count() > 1) {
            return $this->reviewResult($events, $leaveRequests, null, 'Terdapat permohonan dengan jenis status berbeda yang disetujui pada tanggal ini.');
        }

        if ($leaveRequests->isNotEmpty() && $events->isNotEmpty()) {
            return $this->reviewResult($events, $leaveRequests, null, 'Terdapat absensi aktual dan permohonan yang disetujui pada tanggal yang sama.');
        }

        if ($leaveRequests->isNotEmpty()) {
            $request = $leaveRequests->first();
            $status = $this->statusFromLeaveType($request->leaveType?->code, $request->leaveType?->name);

            return [
                'status' => $status->value,
                'label' => $status->label(),
                'events' => $events,
                'leaveRequests' => $leaveRequests,
                'correction' => null,
                'needs_review' => false,
                'reason' => null,
            ];
        }

        if ($events->isEmpty()) {
            $status = DailyAttendanceStatus::NoRecord;

            return [
                'status' => $status->value,
                'label' => $status->label(),
                'events' => $events,
                'leaveRequests' => $leaveRequests,
                'correction' => null,
                'needs_review' => false,
                'reason' => null,
            ];
        }

        $hasIn = $events->contains(fn (AttendanceRecord $event) => $event->type->value === 'clock_in');
        $hasOut = $events->contains(fn (AttendanceRecord $event) => $event->type->value === 'clock_out');
        $status = $hasIn && $hasOut ? DailyAttendanceStatus::Present : DailyAttendanceStatus::Incomplete;

        return [
            'status' => $status->value,
            'label' => $status->label(),
            'events' => $events,
            'leaveRequests' => $leaveRequests,
            'correction' => null,
            'needs_review' => false,
            'reason' => null,
        ];
    }

    private function reviewResult(Collection $events, Collection $leaveRequests, ?DailyStatusCorrection $correction, string $reason): array
    {
        return [
            'status' => DailyAttendanceStatus::NeedsReview->value,
            'label' => DailyAttendanceStatus::NeedsReview->label(),
            'events' => $events,
            'leaveRequests' => $leaveRequests,
            'correction' => $correction,
            'needs_review' => true,
            'reason' => $reason,
        ];
    }

    private function statusFromLeaveType(?string $code, ?string $name): DailyAttendanceStatus
    {
        $value = mb_strtolower($code ?: $name ?: '');

        return match (true) {
            str_contains($value, 'sakit'), str_contains($value, 'sick') => DailyAttendanceStatus::Sick,
            str_contains($value, 'cuti'), str_contains($value, 'leave') => DailyAttendanceStatus::Leave,
            default => DailyAttendanceStatus::Permission,
        };
    }

    public function label(DailyAttendanceStatus|string $status): string
    {
        if ($status instanceof DailyAttendanceStatus) {
            return $status->label();
        }

        return DailyAttendanceStatus::tryFrom($status)?->label() ?? 'Belum ada data';
    }
}
