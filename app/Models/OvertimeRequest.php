<?php

namespace App\Models;

use App\Enums\OvertimeApprovalStatus;
use App\Enums\OvertimeState;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

#[Fillable([
    'employee_id', 'clock_in_record_id', 'start_record_id', 'clock_out_record_id',
    'reason', 'scheduled_start_at', 'timezone', 'state', 'approval_status',
    'photo_confirmed_at', 'reminder_sent_at', 'start_due_notified_at', 'completed_at', 'cancelled_at',
    'decided_by', 'decided_at', 'decision_note', 'photo_exception_reason',
])]
class OvertimeRequest extends Model
{
    protected function casts(): array
    {
        return [
            'scheduled_start_at' => 'datetime',
            'photo_confirmed_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'start_due_notified_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'decided_at' => 'datetime',
            'state' => OvertimeState::class,
            'approval_status' => OvertimeApprovalStatus::class,
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function clockInRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class, 'clock_in_record_id');
    }

    public function startRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class, 'start_record_id');
    }

    public function clockOutRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class, 'clock_out_record_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function scopeAwaitingPhoto(Builder $query): Builder
    {
        return $query
            ->where('state', OvertimeState::AwaitingPhoto)
            ->whereNull('photo_confirmed_at');
    }

    public function isAwaitingPhoto(): bool
    {
        return $this->state === OvertimeState::AwaitingPhoto && $this->photo_confirmed_at === null;
    }

    public function isInProgress(): bool
    {
        return $this->state === OvertimeState::InProgress && $this->photo_confirmed_at !== null;
    }

    public function scheduledStartLocal(): Carbon
    {
        return $this->scheduled_start_at->copy()->setTimezone($this->timezone);
    }
}
