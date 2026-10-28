<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyStatusCorrection extends Model
{
    protected $fillable = [
        'employee_id',
        'attendance_date',
        'status',
        'reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return ['attendance_date' => 'date'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
