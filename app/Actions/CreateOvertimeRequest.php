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

            // Satu sesi kerja hanya boleh memiliki satu pengajuan lembur.
            // Pemeriksaan mencakup semua status, termasuk dibatalkan/selesai, agar
            // pengajuan tidak dapat dibuat ulang dalam sesi yang sama.
            $existingForSession = $openSession
                ? OvertimeRequest::query()
                    ->where('employee_id', $employee->id)
                    ->where('clock_in_record_id', $openSession->id)
                    ->exists()
                : false;

            // Sebelum clock-in, pengajuan belum dapat dikaitkan ke ID sesi.
            // Batasi satu pengajuan pada tanggal target yang belum terikat sesi.
            $existingUnlinkedForDate = OvertimeRequest::query()
                ->where('employee_id', $employee->id)
                ->whereNull('clock_in_record_id')
                ->where('scheduled_start_at', '>=', $dayStartUtc)
                ->where('scheduled_start_at', '<', $nextDayStartUtc)
                ->exists();

            if ($existingForSession || $existingUnlinkedForDate) {
                $this->fail('form', 'Anda hanya dapat mengajukan lembur satu kali dalam satu sesi kerja.');
            }

            $overtime = OvertimeRequest::create([
                'employee_id' => $employee->id,
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
