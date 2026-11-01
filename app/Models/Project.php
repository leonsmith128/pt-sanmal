<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * @property int $id
 * @property int $client_id
 * @property string $code
 * @property string $name
 * @property ProjectType $project_type
 * @property int $location_id
 * @property string|null $description
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property ProjectStatus $status
 * @property-read Client $client
 * @property-read Location $location
 */
#[Fillable(['client_id', 'code', 'name', 'project_type', 'location_id', 'description', 'start_date', 'end_date', 'status'])]
class Project extends Model
{
    protected static function booted(): void
    {
        static::saving(function (self $project): void {
            $relationshipChanged = $project->exists
                && ($project->isDirty('client_id') || $project->isDirty('location_id'));

            if ($relationshipChanged && $project->attendanceRecords()->exists()) {
                throw ValidationException::withMessages([
                    'location_id' => 'Client dan lokasi Project tidak dapat diubah setelah memiliki riwayat absensi.',
                ]);
            }

            $mustValidateLocation = ! $project->exists
                || $relationshipChanged
                || ($project->isDirty('status') && $project->status === ProjectStatus::Active);

            if (! $mustValidateLocation) {
                return;
            }

            $location = Location::query()
                ->whereKey($project->location_id)
                ->where('client_id', $project->client_id)
                ->where('type', 'site')
                ->when(
                    ! $project->exists || $relationshipChanged || $project->status === ProjectStatus::Active,
                    fn (Builder $query) => $query->where('is_active', true),
                )
                ->first();

            if ($location === null) {
                throw ValidationException::withMessages([
                    'location_id' => 'Project harus menggunakan Site aktif milik Client yang dipilih.',
                ]);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'project_type' => ProjectType::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => ProjectStatus::class,
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
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
