<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int $location_id
 * @property string|null $description
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property ProjectStatus $status
 * @property-read Location $location
 */
#[Fillable(['code', 'name', 'location_id', 'description', 'start_date', 'end_date', 'status'])]
class Project extends Model
{
    protected static function booted(): void
    {
        static::saving(function (self $project): void {
            if ($project->exists && $project->isDirty('location_id') && $project->attendanceRecords()->exists()) {
                throw ValidationException::withMessages([
                    'location_id' => 'Lokasi Project tidak dapat diubah setelah memiliki riwayat absensi.',
                ]);
            }

            $mustValidateLocation = ! $project->exists
                || $project->isDirty('location_id')
                || ($project->isDirty('status') && $project->status === ProjectStatus::Active);

            if (! $mustValidateLocation) {
                return;
            }

            $validLocation = Location::query()
                ->whereKey($project->location_id)
                ->where('type', 'site')
                ->where('is_active', true)
                ->exists();

            if (! $validLocation) {
                throw ValidationException::withMessages([
                    'location_id' => 'Project harus menggunakan Location Site yang aktif.',
                ]);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => ProjectStatus::class,
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ProjectStatus::Active);
    }
}
