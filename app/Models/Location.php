<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
