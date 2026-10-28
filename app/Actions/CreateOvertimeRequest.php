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
    public function handle(Employee $employee, string $reason, string $scheduledStartLocal): OvertimeRequest
    {
        return DB::transaction(function () use ($employee, $reason, $scheduledStartLocal) {
            Employee::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();

            $clockIn = RecordAttendance::openSession($employee);

            if (! $clockIn) {
                $this->fail('form', 'Anda harus memiliki sesi kerja aktif untuk mengajukan lembur.');
            }

            $timezone = $clockIn->timezone ?: RecordAttendance::DEFAULT_TIMEZONE;

            try {
                $scheduledStart = Carbon::createFromFormat('!Y-m-d\TH:i', $scheduledStartLocal, $timezone);
            } catch (\Throwable) {
                $this->fail('scheduled_start_at', 'Format waktu mulai lembur tidak valid.');
            }

            if (! $scheduledStart || $scheduledStart->lt(now($timezone)->startOfMinute())) {
                $this->fail('scheduled_start_at', 'Waktu mulai lembur tidak boleh di masa lalu.');
            }

            if ($scheduledStart->utc()->lt($clockIn->recorded_at)) {
                $this->fail('scheduled_start_at', 'Waktu mulai lembur tidak boleh sebelum waktu absen masuk.');
            }

            $existing = OvertimeRequest::query()
                ->where('employee_id', $employee->id)
                ->where('clock_in_record_id', $clockIn->id)
                ->whereNotIn('state', [OvertimeState::Completed, OvertimeState::Cancelled])
                ->exists();

            if ($existing) {
                $this->fail('form', 'Sesi kerja ini sudah memiliki pengajuan lembur yang belum selesai.');
            }

            $overtime = OvertimeRequest::create([
                'employee_id' => $employee->id,
                'clock_in_record_id' => $clockIn->id,
                'reason' => $reason,
                'scheduled_start_at' => $scheduledStart->utc(),
                'timezone' => $timezone,
                'state' => $scheduledStart->utc()->lte(now()) ? OvertimeState::AwaitingPhoto : OvertimeState::Scheduled,
                'approval_status' => OvertimeApprovalStatus::Pending,
            ]);

            AuditLog::record(
                auditable: $overtime,
                action: 'overtime.requested',
                newValues: [
                    'scheduled_start_at' => $overtime->scheduled_start_at->toISOString(),
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
