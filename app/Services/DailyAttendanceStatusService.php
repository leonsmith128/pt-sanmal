<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\DailyStatusCorrection;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Support\Carbon;

class DailyAttendanceStatusService
{
    /**
     * Rekap harian menggabungkan bukti absensi aktual, permohonan yang disetujui,
     * dan koreksi administratif. Service ini tidak membuat event clock-in/out.
     *
     * @return array{status:string,label:string,events:\Illuminate\Support\Collection,leaveRequests:\Illuminate\Support\Collection,correction:?DailyStatusCorrection,needs_review:bool,reason:?string}
     */
    public function forEmployeeDate(Employee $employee, Carbon|string $date): array
    {
        $day = Carbon::parse($date)->toDateString();

        $events = AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $day)
            ->orderBy('recorded_at')
            ->get();

        $leaveRequests = LeaveRequest::query()
            ->with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereDate('start_date', '<=', $day)
            ->whereDate('end_date', '>=', $day)
            ->get();

        $correction = DailyStatusCorrection::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $day)
            ->first();

        // Koreksi dibuat secara eksplisit oleh admin dan berisi alasan; ini adalah
        // keputusan final untuk hari tersebut, bukan event absensi buatan.
        if ($correction) {
            return [
                'status' => $correction->status,
                'label' => $this->label($correction->status),
                'events' => $events,
                'leaveRequests' => $leaveRequests,
                'correction' => $correction,
                'needs_review' => false,
                'reason' => $correction->reason,
            ];
        }

        if ($leaveRequests->count() > 1) {
            return $this->reviewResult($events, $leaveRequests, null, 'Terdapat lebih dari satu permohonan yang disetujui pada tanggal ini.');
        }

        if ($leaveRequests->isNotEmpty() && $events->isNotEmpty()) {
            return $this->reviewResult($events, $leaveRequests, null, 'Terdapat absensi aktual dan permohonan yang disetujui pada tanggal yang sama.');
        }

        if ($leaveRequests->isNotEmpty()) {
            $request = $leaveRequests->first();
            $status = $this->statusFromLeaveType($request->leaveType?->code, $request->leaveType?->name);

            return [
                'status' => $status,
                'label' => $this->label($status),
                'events' => $events,
                'leaveRequests' => $leaveRequests,
                'correction' => null,
                'needs_review' => false,
                'reason' => null,
            ];
        }

        if ($events->isEmpty()) {
            return [
                'status' => 'no_record',
                'label' => 'Belum ada data',
                'events' => $events,
                'leaveRequests' => $leaveRequests,
                'correction' => null,
                'needs_review' => false,
                'reason' => null,
            ];
        }

        $hasIn = $events->contains(fn (AttendanceRecord $event) => $event->type->value === 'clock_in');
        $hasOut = $events->contains(fn (AttendanceRecord $event) => $event->type->value === 'clock_out');
        $status = $hasIn && $hasOut ? 'present' : 'incomplete';

        return [
            'status' => $status,
            'label' => $this->label($status),
            'events' => $events,
            'leaveRequests' => $leaveRequests,
            'correction' => null,
            'needs_review' => false,
            'reason' => null,
        ];
    }

    private function reviewResult($events, $leaveRequests, ?DailyStatusCorrection $correction, string $reason): array
    {
        return [
            'status' => 'needs_review',
            'label' => $this->label('needs_review'),
            'events' => $events,
            'leaveRequests' => $leaveRequests,
            'correction' => $correction,
            'needs_review' => true,
            'reason' => $reason,
        ];
    }

    private function statusFromLeaveType(?string $code, ?string $name): string
    {
        $value = mb_strtolower($code ?: $name ?: '');
        return match (true) {
            str_contains($value, 'sakit'), str_contains($value, 'sick') => 'sick',
            str_contains($value, 'cuti'), str_contains($value, 'leave') => 'leave',
            default => 'permission',
        };
    }

    public function label(string $status): string
    {
        return match ($status) {
            'present' => 'Hadir',
            'incomplete' => 'Absensi belum lengkap',
            'permission' => 'Izin',
            'sick' => 'Sakit',
            'leave' => 'Cuti',
            'absent' => 'Tidak hadir',
            'needs_review' => 'Perlu ditinjau',
            default => 'Belum ada data',
        };
    }
}
