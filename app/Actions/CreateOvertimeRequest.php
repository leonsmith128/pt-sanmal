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
    public function handle(Employee $employee, string $reason, string $scheduledStartLocal, string $scheduledEndLocal): OvertimeRequest
    {
        return DB::transaction(function () use ($employee, $reason, $scheduledStartLocal, $scheduledEndLocal) {
            Employee::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();

            // Pengajuan dapat dibuat sebelum clock-in. Zona waktu mengikuti lokasi sesi
            // jika sesi sudah ada; jika belum, gunakan zona waktu operasional default.
            $clockIn = RecordAttendance::openSession($employee);
            $timezone = $clockIn?->timezone ?: RecordAttendance::DEFAULT_TIMEZONE;

            try {
                $scheduledStart = Carbon::createFromFormat('!Y-m-d\TH:i', $scheduledStartLocal, $timezone);
                $scheduledEnd = Carbon::createFromFormat('!Y-m-d\TH:i', $scheduledEndLocal, $timezone);
            } catch (\Throwable) {
                $this->fail('scheduled_start_at', 'Format jadwal lembur tidak valid.');
            }

            if (! $scheduledStart || ! $scheduledEnd || $scheduledStart->lt(now($timezone)->startOfMinute())) {
                $this->fail('scheduled_start_at', 'Waktu mulai lembur tidak boleh di masa lalu.');
            }

            if ($scheduledEnd->lte($scheduledStart)) {
                $this->fail('scheduled_end_at', 'Waktu selesai harus setelah waktu mulai. Untuk lembur lintas tengah malam, pilih tanggal selesai berikutnya.');
            }

            if ($clockIn && $scheduledStart->utc()->lt($clockIn->recorded_at)) {
                $this->fail('scheduled_start_at', 'Waktu mulai lembur tidak boleh sebelum waktu absen masuk.');
            }

            $existing = OvertimeRequest::query()
                ->where('employee_id', $employee->id)
                ->whereNotIn('state', [OvertimeState::Completed, OvertimeState::Cancelled])
                ->where(function ($query) use ($scheduledStart, $scheduledEnd) {
                    $query->whereBetween('scheduled_start_at', [$scheduledStart->utc(), $scheduledEnd->utc()])
                        ->orWhereBetween('scheduled_end_at', [$scheduledStart->utc(), $scheduledEnd->utc()])
                        ->orWhere(function ($overlap) use ($scheduledStart, $scheduledEnd) {
                            $overlap->where('scheduled_start_at', '<=', $scheduledStart->utc())
                                ->where('scheduled_end_at', '>=', $scheduledEnd->utc());
                        });
                })->exists();

            if ($existing) {
                $this->fail('form', 'Anda sudah memiliki pengajuan lembur aktif pada rentang waktu tersebut.');
            }

            $overtime = OvertimeRequest::create([
                'employee_id' => $employee->id,
                'clock_in_record_id' => $clockIn?->id,
                'reason' => $reason,
                'scheduled_start_at' => $scheduledStart->utc(),
                'scheduled_end_at' => $scheduledEnd->utc(),
                'timezone' => $timezone,
                'state' => $scheduledStart->utc()->lte(now()) ? OvertimeState::AwaitingPhoto : OvertimeState::Scheduled,
                'approval_status' => OvertimeApprovalStatus::Pending,
            ]);

            AuditLog::record(
                auditable: $overtime,
                action: 'overtime.requested',
                newValues: [
                    'scheduled_start_at' => $overtime->scheduled_start_at->toISOString(),
                    'scheduled_end_at' => $overtime->scheduled_end_at->toISOString(),
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
