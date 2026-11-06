<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'temporary_password_encrypted'])]
class EmployeeAccountCredential extends Model
{
    protected $hidden = ['temporary_password_encrypted'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
