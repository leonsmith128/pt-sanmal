<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $address
 * @property string|null $phone
 * @property bool $is_active
 */
#[Fillable(['code', 'name', 'address', 'phone', 'is_active'])]
class Client extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }
}
