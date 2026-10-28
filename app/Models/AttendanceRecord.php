<?php

namespace App\Models;

use App\Enums\AttendanceType;
use App\Enums\SelfieSource;
use App\Enums\WorkType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $employee_id
 * @property AttendanceType $type
 * @property WorkType $work_type
 * @property int|null $location_id
 * @property int|null $project_id
 * @property \Carbon\Carbon $recorded_at Stored in UTC.
 * @property string $timezone
 * @property \Carbon\Carbon $attendance_date Local attendance date.
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string|null $accuracy_m
 * @property string|null $address_snapshot
 * @property string|null $selfie_path
 * @property SelfieSource|null $selfie_source
 * @property string|null $notes
 * @property-read Employee $employee
 * @property-read Location|null $location
 * @property-read Project|null $project
 */
#[Fillable([
    'employee_id', 'type', 'work_type', 'location_id', 'project_id',
    'recorded_at', 'timezone', 'attendance_date',
    'latitude', 'longitude', 'accuracy_m', 'address_snapshot',
    'selfie_path', 'selfie_source', 'notes',
])]
class AttendanceRecord extends Model
{
    protected function casts(): array
    {
        return [
            'type' => AttendanceType::class,
            'work_type' => WorkType::class,
            'recorded_at' => 'datetime',
            'attendance_date' => 'date',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'accuracy_m' => 'decimal:2',
            'selfie_source' => SelfieSource::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** Lembur yang dimulai oleh event ini, jika ada. */
    public function overtimeStarted(): HasOne
    {
        return $this->hasOne(OvertimeRequest::class, 'start_record_id');
    }

    /** Waktu absen dalam zona waktu saat absen dilakukan, untuk tampilan. */
    public function recordedAtLocal(): CarbonInterface
    {
        return $this->recorded_at->copy()->setTimezone($this->timezone);
    }
}
