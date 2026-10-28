<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $title_id
 * @property string|null $employee_number
 * @property string|null $phone
 * @property string|null $employment_status
 * @property \Illuminate\Support\Carbon|null $joined_at
 * @property-read User $user
 * @property-read Title|null $title
 */
#[Fillable(['user_id', 'title_id', 'employee_number', 'phone', 'employment_status', 'joined_at'])]
class Employee extends Model
{
    protected function casts(): array
    {
        return ['joined_at' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_assignments')
            ->withPivot(['starts_on', 'ends_on'])
            ->withTimestamps();
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function overtimeRequests(): HasMany
    {
        return $this->hasMany(OvertimeRequest::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function attendanceCorrections(): HasMany
    {
        return $this->hasMany(AttendanceCorrection::class);
    }

    public function dailyStatusCorrections(): HasMany
    {
        return $this->hasMany(DailyStatusCorrection::class);
    }
}
