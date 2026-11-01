<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

/**
 * @property int $id
 * @property int|null $client_id
 * @property string $name
 * @property string $type
 * @property string|null $address
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string $timezone
 * @property bool $is_active
 * @property-read Client|null $client
 */
#[Fillable(['client_id', 'name', 'type', 'address', 'latitude', 'longitude', 'timezone', 'is_active'])]
class Location extends Model
{
    protected static function booted(): void
    {
        static::saving(function (self $location): void {
            $validOwnership = match ($location->type) {
                'office' => $location->client_id === null,
                'site' => $location->client_id !== null,
                default => false,
            };

            if (! $validOwnership) {
                throw ValidationException::withMessages([
                    'client_id' => 'Office tidak boleh memiliki Client, sedangkan Site wajib memiliki Client.',
                ]);
            }
        });

        static::updating(function (self $location): void {
            if ($location->projects()->exists() && ($location->isDirty('type') || $location->isDirty('client_id'))) {
                throw ValidationException::withMessages([
                    'type' => 'Jenis dan pemilik lokasi tidak dapat diubah setelah lokasi digunakan oleh Project.',
                ]);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_active' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
