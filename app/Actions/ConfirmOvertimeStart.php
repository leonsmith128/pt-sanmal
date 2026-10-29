<?php

namespace App\Actions;

use App\Enums\AttendanceType;
use App\Enums\OvertimeState;
use App\Enums\SelfieSource;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Support\AttendanceEvidence;
use App\Support\AttendanceSelfie;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmOvertimeStart
{
    public function handle(
        Employee $employee,
        OvertimeRequest $overtime,
        float $latitude,
        float $longitude,
        ?float $accuracy,
        ?string $addressSnapshot,
        UploadedFile $selfie,
        SelfieSource $selfieSource = SelfieSource::Camera,
    ): AttendanceRecord {
        if ($selfieSource !== SelfieSource::Camera) {
            $this->fail('selfieSource', 'Foto lembur harus diambil langsung dari kamera.');
        }

        AttendanceEvidence::validate(['latitude'=>$latitude,'longitude'=>$longitude,'accuracy'=>$accuracy,'address_snapshot'=>$addressSnapshot,'selfie'=>$selfie]);

        $storedPath = null;

        try {
            return DB::transaction(function () use (
                $employee, $overtime, $latitude, $longitude, $accuracy,
                $addressSnapshot, $selfie, $selfieSource, &$storedPath
            ) {
                Employee::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();

                $locked = OvertimeRequest::query()
                    ->whereKey($overtime->id)
                    ->where('employee_id', $employee->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! in_array($locked->state, [OvertimeState::Scheduled, OvertimeState::AwaitingPhoto], true)
                    || $locked->start_record_id !== null) {
                    $this->fail('form', 'Pengajuan ini tidak lagi menunggu konfirmasi foto lembur.');
                }

                if ($locked->scheduled_start_at->isFuture()) {
                    $this->fail('form', 'Waktu lembur belum tiba. Foto konfirmasi belum dapat dikirim.');
                }

                $clockIn = $locked->clockInRecord()->with(['location', 'project'])->firstOrFail();

                $open = RecordAttendance::openSession($employee);
                if (! $open || $open->id !== $clockIn->id) {
                    $this->fail('form', 'Sesi kerja sudah tidak aktif. Hubungi admin untuk peninjauan.');
                }

                $now = now();
                $storedPath = AttendanceSelfie::store($selfie, $employee->id, $now);

                $record = AttendanceRecord::create([
                    'employee_id' => $employee->id,
                    'type' => AttendanceType::OvertimeStart,
                    'work_type' => $clockIn->work_type,
                    'location_id' => $clockIn->location_id,
                    'project_id' => $clockIn->project_id,
                    'recorded_at' => $now,
                    'timezone' => $locked->timezone,
                    'attendance_date' => $now->copy()->setTimezone($locked->timezone)->toDateString(),
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'accuracy_m' => $accuracy,
                    'address_snapshot' => $addressSnapshot,
                    'selfie_path' => $storedPath,
                    'selfie_source' => $selfieSource,
                ]);

                $old = [
                    'state' => $locked->state->value,
                    'photo_confirmed_at' => null,
                    'start_record_id' => null,
                ];

                $locked->update([
                    'state' => OvertimeState::InProgress,
                    'photo_confirmed_at' => $now,
                    'start_record_id' => $record->id,
                ]);

                AuditLog::record(
                    auditable: $locked,
                    action: 'overtime.photo_confirmed',
                    oldValues: $old,
                    newValues: [
                        'state' => $locked->state->value,
                        'photo_confirmed_at' => $locked->photo_confirmed_at->toISOString(),
                        'start_record_id' => $record->id,
                    ],
                );

                return $record;
            });
        } catch (\Throwable $exception) {
            try { AttendanceSelfie::delete($storedPath); }
            catch (\Throwable $cleanupException) { report($cleanupException); }
            throw $exception;
        }
    }

    private function fail(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
