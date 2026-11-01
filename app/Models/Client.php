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
 */
#[Fillable(['code', 'name', 'address', 'phone'])]
class Client extends Model
{
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
