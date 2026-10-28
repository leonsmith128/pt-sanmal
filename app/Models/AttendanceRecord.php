<?php

namespace App\Models;

use App\Enums\AttendanceType;
use App\Enums\SelfieSource;
use App\Enums\WorkType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

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
    public function overtimeStarted(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(OvertimeRequest::class, 'start_record_id');
    }

    /** Waktu absen dalam zona waktu saat absen dilakukan, untuk tampilan. */
    public function recordedAtLocal(): CarbonInterface
    {
        return $this->recorded_at->copy()->setTimezone($this->timezone);
    }
}
