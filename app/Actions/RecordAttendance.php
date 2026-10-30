<?php

namespace App\Actions;

use App\Enums\AttendanceType;
use App\Enums\OvertimeState;
use App\Enums\SelfieSource;
use App\Enums\WorkType;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Location;
use App\Models\OvertimeRequest;
use App\Models\Project;
use App\Support\AttendanceEvidence;
use App\Support\AttendanceSelfie;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordAttendance
{
    public const OPEN_SESSION_HOURS = 24;

    public const DEFAULT_TIMEZONE = 'Asia/Makassar';

    /** Sesi kerja terakhir yang belum ditutup clock-out. Event mulai lembur tidak menutup sesi. */
    public static function openSession(Employee $employee): ?AttendanceRecord
    {
        $latest = $employee->attendanceRecords()
            ->whereIn('type', [AttendanceType::ClockIn->value, AttendanceType::ClockOut->value])
            ->with(['location', 'project'])
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->first();

        if ($latest
            && $latest->type === AttendanceType::ClockIn
            && $latest->recorded_at->gt(now()->subHours(self::OPEN_SESSION_HOURS))) {
            return $latest;
        }

        return null;
    }

    public static function assignableProjects(Employee $employee): Builder
    {
        $today = now(self::DEFAULT_TIMEZONE)->toDateString();

        return Project::query()
            ->active()
            ->whereHas('employees', function ($query) use ($employee, $today) {
                $query->where('employees.id', $employee->id)
                    ->where(fn ($w) => $w->whereNull('project_assignments.starts_on')
                        ->orWhere('project_assignments.starts_on', '<=', $today))
                    ->where(fn ($w) => $w->whereNull('project_assignments.ends_on')
                        ->orWhere('project_assignments.ends_on', '>=', $today));
            })
            ->orderBy('name');
    }

    public static function officeLocations(): Builder
    {
        return Location::query()->where('type', 'office')->where('is_active', true)->orderBy('name');
    }

    public function handle(
        Employee $employee,
        AttendanceType $type,
        ?WorkType $workType,
        mixed $locationId,
        mixed $projectId,
        float $latitude,
        float $longitude,
        ?float $accuracy,
        UploadedFile $selfie,
        SelfieSource $selfieSource,
        ?string $addressSnapshot = null,
    ): AttendanceRecord {
        if ($selfieSource !== SelfieSource::Camera) {
            $this->fail('selfieSource', 'Foto absensi harus diambil langsung dari kamera.');
        }

        // Validasi ulang di action agar jalur pemanggilan lain tidak dapat
        // menyimpan koordinat dengan kualitas GPS yang tidak layak.
        AttendanceEvidence::validate([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
            'address_snapshot' => $addressSnapshot,
            'selfie' => $selfie,
        ]);
        $storedPath = null;
        try {
            return DB::transaction(function () use (
                $employee, $type, $workType, $locationId, $projectId,
                $latitude, $longitude, $accuracy, $selfie, $selfieSource, $addressSnapshot, &$storedPath
            ) {
                Employee::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();

                $open = self::openSession($employee);
                $overtime = null;

                if ($type === AttendanceType::ClockIn) {
                    if ($open) {
                        $this->fail('form', 'Anda sudah clock-in dan belum clock-out.');
                    }

                    [$workType, $location, $project] = $this->resolveTarget($employee, $workType, $locationId, $projectId);
                } elseif ($type === AttendanceType::ClockOut) {
                    if ($open === null) {
                        $this->fail('form', 'Tidak ada clock-in yang perlu ditutup. Lakukan clock-in terlebih dahulu.');
                    }

                    $workType = $open->work_type;
                    $location = $open->location;
                    $project = $open->project;

                    $overtime = OvertimeRequest::query()
                        ->where('employee_id', $employee->id)
                        ->where('clock_in_record_id', $open->id)
                        ->whereNotIn('state', [OvertimeState::Completed->value, OvertimeState::Cancelled->value])
                        ->orderByDesc('scheduled_start_at')
                        ->lockForUpdate()
                        ->first();

                    if ($overtime && $overtime->start_record_id === null && $overtime->state !== OvertimeState::NeedsReview) {
                        // Pilihan C: clock-out ditolak sejak jadwal masih aktif, termasuk sebelum
                        // waktu mulai. Pegawai harus membatalkan pengajuan secara eksplisit.
                        $this->fail(
                            'form',
                            $overtime->scheduled_start_at->isFuture()
                                ? 'Anda masih memiliki jadwal lembur. Batalkan Lembur terlebih dahulu sebelum absen pulang.'
                                : 'Jadwal lembur sudah tiba. Konfirmasi Lembur atau Batalkan Lembur terlebih dahulu sebelum absen pulang.'
                        );
                    }
                } else {
                    $this->fail('form', 'Jenis event absensi tidak valid untuk proses ini.');
                }

                $timezone = $location?->timezone ?? self::DEFAULT_TIMEZONE;
                $now = now();

                $storedPath = AttendanceSelfie::store($selfie, $employee->id, $now);

                $record = AttendanceRecord::create([
                    'employee_id' => $employee->id,
                    'type' => $type,
                    'work_type' => $workType,
                    'location_id' => $location?->id,
                    'project_id' => $project?->id,
                    'recorded_at' => $now,
                    'timezone' => $timezone,
                    'attendance_date' => (
                        $type === AttendanceType::ClockOut
                            ? $open->attendance_date
                            : $now->copy()->setTimezone($timezone)
                    )->toDateString(),
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'accuracy_m' => $accuracy,
                    'address_snapshot' => $addressSnapshot,
                    'selfie_path' => $storedPath,
                    'selfie_source' => $selfieSource,
                ]);

                if ($type === AttendanceType::ClockIn) {
                    // Kaitkan pengajuan yang dibuat sebelum clock-in ke sesi pada tanggal target.
                    // Kaitkan jadwal yang mulai dalam jendela sesi teknis (maks. 24 jam),
                    // termasuk jadwal setelah tengah malam pada sesi yang sama.
                    $sessionStart = $record->recorded_at->copy();
                    $sessionEnd = $sessionStart->copy()->addHours(self::OPEN_SESSION_HOURS);

                    $scheduledOvertime = OvertimeRequest::query()
                        ->where('employee_id', $employee->id)
                        ->whereNull('clock_in_record_id')
                        ->whereIn('state', [OvertimeState::Scheduled, OvertimeState::AwaitingPhoto])
                        ->where('scheduled_start_at', '>=', $sessionStart)
                        ->where('scheduled_start_at', '<', $sessionEnd)
                        ->lockForUpdate()
                        ->first();

                    $scheduledOvertime?->update(['clock_in_record_id' => $record->id]);
                }

                if (
                    $type === AttendanceType::ClockOut
                    && $overtime
                    && ($overtime->start_record_id !== null || $overtime->state === OvertimeState::NeedsReview)
                ) {
                    $overtime->update([
                        'state' => OvertimeState::Completed,
                        'clock_out_record_id' => $record->id,
                        'completed_at' => $now,
                    ]);
                }

                return $record;
            });
        } catch (\Throwable $exception) {
            try {
                AttendanceSelfie::delete($storedPath);
            } catch (\Throwable $cleanupException) {
                report($cleanupException);
            }

            throw $exception;
        }
    }

    /** @return array{0: WorkType, 1: ?Location, 2: ?Project} */
    private function resolveTarget(Employee $employee, ?WorkType $workType, mixed $locationId, mixed $projectId): array
    {
        if ($workType === WorkType::Office) {
            $location = self::officeLocations()->find($locationId);

            if ($location === null) {
                $this->fail('locationId', 'Lokasi kantor tidak valid.');
            }

            return [WorkType::Office, $location, null];
        }

        if ($workType === WorkType::Onsite) {
            $project = self::assignableProjects($employee)->with('location')->find($projectId);

            if ($project === null) {
                $this->fail('projectId', 'Proyek tidak valid atau tidak ditugaskan kepada Anda.');
            }

            return [WorkType::Onsite, $project->location, $project];
        }

        $this->fail('workType', 'Jenis lokasi kerja wajib dipilih.');
    }

    private function fail(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
