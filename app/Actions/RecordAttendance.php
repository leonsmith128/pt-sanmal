<?php

namespace App\Actions;

use App\Enums\AttendanceType;
use App\Enums\SelfieSource;
use App\Enums\WorkType;
use App\Models\AttendanceRecord;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordAttendance
{
    /**
     * SEMENTARA (belum ada keputusan bisnis): clock-in dianggap "masih terbuka"
     * selama usianya kurang dari 24 jam. Lewat dari itu dianggap lupa clock-out,
     * dan pegawai boleh clock-in lagi. Penanganan lupa clock-out lewat koreksi absensi.
     */
    public const OPEN_SESSION_HOURS = 24;

    /** Dipakai kalau absensi tidak terkait lokasi bertimezone (mis. proyek tanpa lokasi). */
    public const DEFAULT_TIMEZONE = 'Asia/Makassar';

    /** Clock-in terakhir yang belum ditutup clock-out, atau null. */
    public static function openSession(Employee $employee): ?AttendanceRecord
    {
        $latest = $employee->attendanceRecords()
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

    /** Proyek aktif yang ditugaskan ke pegawai dan masih dalam rentang penugasan. */
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

    /** Lokasi kantor yang aktif. */
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
    ): AttendanceRecord {
        return DB::transaction(function () use (
            $employee, $type, $workType, $locationId, $projectId,
            $latitude, $longitude, $accuracy, $selfie, $selfieSource
        ) {
            // Kunci baris pegawai supaya dua request bersamaan (double tap) tidak lolos bersamaan.
            Employee::query()->whereKey($employee->id)->lockForUpdate()->first();

            $open = self::openSession($employee);

            if ($type === AttendanceType::ClockIn) {
                if ($open) {
                    $this->fail('form', 'Anda sudah clock-in dan belum clock-out.');
                }

                [$workType, $location, $project] = $this->resolveTarget($employee, $workType, $locationId, $projectId);
            } else {
                if (! $open) {
                    $this->fail('form', 'Tidak ada clock-in yang perlu ditutup. Lakukan clock-in terlebih dahulu.');
                }

                // Clock-out memakai jenis kerja, lokasi, dan proyek yang sama dengan clock-in.
                $workType = $open->work_type;
                $location = $open->location;
                $project = $open->project;
            }

            $timezone = $location?->timezone ?? self::DEFAULT_TIMEZONE;
            $now = now(); // waktu server, UTC; tidak pernah mengambil waktu dari perangkat pegawai

            // Disk 'local' = storage/app/private, tidak bisa diakses langsung lewat URL publik.
            $path = $selfie->store('selfies/'.$employee->id.'/'.$now->format('Y/m'), 'local');

            return AttendanceRecord::create([
                'employee_id' => $employee->id,
                'type' => $type,
                'work_type' => $workType,
                'location_id' => $location?->id,
                'project_id' => $project?->id,
                'recorded_at' => $now,
                'timezone' => $timezone,
                'attendance_date' => $now->copy()->setTimezone($timezone)->toDateString(),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'accuracy_m' => $accuracy,
                'selfie_path' => $path,
                'selfie_source' => $selfieSource,
            ]);
        });
    }

    /** @return array{0: WorkType, 1: ?Location, 2: ?Project} */
    private function resolveTarget(Employee $employee, ?WorkType $workType, mixed $locationId, mixed $projectId): array
    {
        if ($workType === WorkType::Office) {
            $location = self::officeLocations()->find($locationId);

            if (! $location) {
                $this->fail('locationId', 'Lokasi kantor tidak valid.');
            }

            return [WorkType::Office, $location, null];
        }

        if ($workType === WorkType::Onsite) {
            $project = self::assignableProjects($employee)->with('location')->find($projectId);

            if (! $project) {
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
