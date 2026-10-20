<?php

namespace App\Models;

use App\Enums\AttendanceType;
use App\Enums\WorkType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $employee_id
 * @property AttendanceType $type
 * @property WorkType $work_type
 * @property int|null $location_id
 * @property int|null $project_id
 * @property Carbon $recorded_at  Disimpan UTC
 * @property string $timezone
 * @property Carbon $attendance_date  Tanggal lokal sesuai zona waktu lokasi
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string|null $accuracy_m
 * @property string|null $selfie_path
 * @property string|null $notes
 */

#[Fillable([
    'employee_id', 'type', 'work_type', 'location_id', 'project_id',
    'recorded_at', 'timezone', 'attendance_date',
    'latitude', 'longitude', 'accuracy_m', 'selfie_path', 'notes',
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

    /** Waktu absen dalam zona waktu saat absen dilakukan, untuk tampilan. */
    public function recordedAtLocal(): Carbon
    {
        return $this->recorded_at->copy()->setTimezone($this->timezone);
    }
}