<?php

namespace App\Actions;

use App\Enums\OvertimeApprovalStatus;
use App\Enums\OvertimeState;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateOvertimeRequest
{
    public function handle(Employee $employee, ?string $reason, string $scheduledStartLocal): OvertimeRequest
    {
        return DB::transaction(function () use ($employee, $reason, $scheduledStartLocal) {
            Employee::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();

            // Jadwal hanya menyimpan waktu mulai. Waktu selesai berasal dari clock-out aktual.
            $openSession = RecordAttendance::openSession($employee);
            $timezone = $openSession?->timezone ?: RecordAttendance::DEFAULT_TIMEZONE;

            try {
                $scheduledStart = Carbon::createFromFormat('!Y-m-d\\TH:i', $scheduledStartLocal, $timezone);
            } catch (\Throwable) {
                $this->fail('scheduled_start_at', 'Format jadwal mulai lembur tidak valid.');
            }

            if (! $scheduledStart || $scheduledStart->lt(now($timezone)->startOfMinute())) {
                $this->fail('scheduled_start_at', 'Waktu mulai lembur tidak boleh di masa lalu.');
            }

            // Jadwal hanya ditautkan ke sesi aktif bila mulai berada dalam jendela sesi teknis.
            if ($openSession && $scheduledStart->utc()->lt($openSession->recorded_at)) {
                $this->fail('scheduled_start_at', 'Waktu mulai lembur tidak boleh sebelum waktu clock-in.');
            }

            $clockIn = $openSession
                && $scheduledStart->utc()->lt($openSession->recorded_at->copy()->addHours(RecordAttendance::OPEN_SESSION_HOURS))
                    ? $openSession
                    : null;

            $dayStartUtc = $scheduledStart->copy()->startOfDay()->utc();
            $nextDayStartUtc = $scheduledStart->copy()->addDay()->startOfDay()->utc();

            // Jika jadwal berada dalam jendela sesi aktif, gunakan tanggal sesi Clock In.
            // Jadwal yang lebih jauh menjadi sesi terencana pada tanggal lokal jadwalnya.
            $sessionDate = $clockIn
                ? $clockIn->attendance_date->toDateString()
                : $scheduledStart->toDateString();

            // Kunci satu pengajuan per sesi/tanggal target. Semua status tetap dihitung,
            // termasuk cancelled/completed, dan pengajuan pra-Clock In ikut terhitung.
            $existingForSession = OvertimeRequest::query()
                ->where('employee_id', $employee->id)
                ->where(function ($query) use ($sessionDate, $clockIn, $dayStartUtc, $nextDayStartUtc): void {
                    $query->whereDate('session_date', $sessionDate);

                    if ($clockIn) {
                        $query->orWhere('clock_in_record_id', $clockIn->id);
                    }

                    // Kompatibilitas untuk pengajuan lama sebelum session_date ditambahkan.
                    $query->orWhere(function ($legacy) use ($dayStartUtc, $nextDayStartUtc): void {
                        $legacy->whereNull('session_date')
                            ->where('scheduled_start_at', '>=', $dayStartUtc)
                            ->where('scheduled_start_at', '<', $nextDayStartUtc);
                    });
                })
                ->exists();

            if ($existingForSession) {
                $this->fail('form', 'Anda hanya dapat mengajukan lembur satu kali untuk satu sesi absensi. Pengajuan yang dibatalkan atau selesai tetap dihitung.');
            }

            $overtime = OvertimeRequest::create([
                'employee_id' => $employee->id,
                'session_date' => $sessionDate,
                'clock_in_record_id' => $clockIn?->id,
                'reason' => $reason,
                'scheduled_start_at' => $scheduledStart->utc(),
                'scheduled_end_at' => null,
                'timezone' => $timezone,
                'state' => $scheduledStart->utc()->lte(now()) ? OvertimeState::AwaitingPhoto : OvertimeState::Scheduled,
                'approval_status' => OvertimeApprovalStatus::Pending,
            ]);

            AuditLog::record(
                auditable: $overtime,
                action: 'overtime.requested',
                newValues: [
                    'scheduled_start_at' => $overtime->scheduled_start_at->toISOString(),
                    'scheduled_end_at' => null,
                    'session_date' => $overtime->session_date?->toDateString(),
                    'clock_in_record_id' => $overtime->clock_in_record_id,
                    'state' => $overtime->state->value,
                    'approval_status' => $overtime->approval_status->value,
                ],
            );

            return $overtime;
        });
    }

    private function fail(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
