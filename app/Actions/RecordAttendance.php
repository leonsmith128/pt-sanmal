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
use Carbon\CarbonInterface;
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

        if ($latest && $latest->type === AttendanceType::ClockIn) {
            // Sesi tetap terbuka sampai ada Clock Out, termasuk bila melewati tengah malam.
            // Batas 24 jam hanya dipakai untuk mengelompokkan jadwal lembur masa depan,
            // bukan untuk mengakhiri sesi secara otomatis.
            return $latest;
        }

        return null;
    }

    /**
     * Whether this employee already started a session on the given local attendance date.
     * Approved timestamp corrections are respected without mutating the original event.
     */
    public static function hasClockInOnDate(Employee $employee, string $date): bool
    {
        return AttendanceRecord::query()
            ->where('employee_id', $employee->id)
            ->where('type', AttendanceType::ClockIn)
            ->where(function (Builder $query) use ($date): void {
                $query->where(function (Builder $original) use ($date): void {
                    $original->whereDate('attendance_date', $date)
                        ->whereDoesntHave('approvedCorrection');
                })->orWhereHas('approvedCorrection', fn (Builder $correction) => $correction
                    ->whereDate('attendance_date', $date));
            })
            ->exists();
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
        ?float $latitude,
        ?float $longitude,
        ?float $accuracy,
        UploadedFile $selfie,
        SelfieSource $selfieSource,
        ?string $addressSnapshot = null,
        ?CarbonInterface $capturedAt = null,
    ): AttendanceRecord {
        if ($selfieSource !== SelfieSource::Camera) {
            $this->fail('selfieSource', 'Foto absensi harus diambil langsung dari kamera.');
        }

        // Validasi ulang agar koordinat yang dikirim tetap lengkap dan memenuhi
        // batas akurasi. Seluruh bukti lokasi boleh kosong jika GPS gagal.
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
                $latitude, $longitude, $accuracy, $selfie, $selfieSource, $addressSnapshot, $capturedAt, &$storedPath
            ) {
                Employee::query()->whereKey($employee->id)->lockForUpdate()->firstOrFail();

                $open = self::openSession($employee);
                $overtime = null;

                if ($type === AttendanceType::ClockIn) {
                    if ($open) {
                        $this->fail('form', 'Anda sudah clock-in dan belum clock-out.');
                    }

                    $today = now(self::DEFAULT_TIMEZONE)->toDateString();

                    if (self::hasClockInOnDate($employee, $today)) {
                        $this->fail('form', 'Anda sudah menggunakan sesi absensi untuk tanggal ini. Setelah Clock Out, Anda tidak dapat Clock In lagi pada tanggal yang sama.');
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
                    'captured_at' => $capturedAt,
                    'timezone' => $timezone,
                    'attendance_date' => (
                        $type === AttendanceType::ClockOut
                            ? $open->attendance_date
                            : $now->copy()->setTimezone($timezone)
                    )->toDateString(),
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'accuracy_m' => $accuracy,
                    'location_status' => $latitude !== null && $longitude !== null && $accuracy !== null ? 'captured' : 'unavailable',
                    'address_snapshot' => $addressSnapshot,
                    'selfie_path' => $storedPath,
                    'selfie_source' => $selfieSource,
                ]);

                if ($type === AttendanceType::ClockIn) {
                    // Kaitkan pengajuan yang dibuat sebelum clock-in ke sesi pada tanggal target.
                    // Kaitkan jadwal yang mulai dalam jendela sesi teknis (maks. 24 jam),
                    // termasuk jadwal setelah tengah malam pada sesi yang sama.
                    $sessionDate = $record->attendance_date->toDateString();
                    $dayStartUtc = now(self::DEFAULT_TIMEZONE)->setDate(
                        (int) substr($sessionDate, 0, 4),
                        (int) substr($sessionDate, 5, 2),
                        (int) substr($sessionDate, 8, 2),
                    )->startOfDay()->utc();
                    $dayEndUtc = $dayStartUtc->copy()->addDay();

                    $scheduledOvertime = OvertimeRequest::query()
                        ->where('employee_id', $employee->id)
                        ->whereNull('clock_in_record_id')
                        ->where(function (Builder $query) use ($sessionDate, $dayStartUtc, $dayEndUtc): void {
                            $query->whereDate('session_date', $sessionDate)
                                ->orWhere(function (Builder $legacy) use ($dayStartUtc, $dayEndUtc): void {
                                    $legacy->whereNull('session_date')
                                        ->where('scheduled_start_at', '>=', $dayStartUtc)
                                        ->where('scheduled_start_at', '<', $dayEndUtc);
                                });
                        })
                        // Kaitkan juga pengajuan yang sudah dibatalkan sebelum clock-in.
                        // Riwayat tersebut tetap menghabiskan jatah satu pengajuan per sesi.
                        ->orderBy('created_at')
                        ->orderBy('id')
                        ->lockForUpdate()
                        ->first();

                    $scheduledOvertime?->update([
                        'clock_in_record_id' => $record->id,
                        'session_date' => $sessionDate,
                    ]);
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
