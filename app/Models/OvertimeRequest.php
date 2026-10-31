<?php

namespace App\Models;

use App\Enums\OvertimeApprovalStatus;
use App\Enums\OvertimeState;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id',
    'session_date',
    'clock_in_record_id',
    'start_record_id',
    'clock_out_record_id',
    'reason',
    'scheduled_start_at',
    'scheduled_end_at',
    'timezone',
    'state',
    'approval_status',
    'photo_confirmed_at',
    'reminder_sent_at',
    'start_due_notified_at',
    'completed_at',
    'cancelled_at',
    'decided_by',
    'decided_at',
    'decision_seen_at',
    'decision_note',
    'photo_exception_reason',
    'finalization_source',
    'final_start_at',
    'final_end_at',
    'finalization_reason',
    'finalized_by',
    'finalized_at',
])]
class OvertimeRequest extends Model
{
    protected function casts(): array
    {
        return [
            'session_date' => 'date',
            'scheduled_start_at' => 'datetime',
            'scheduled_end_at' => 'datetime',
            'final_start_at' => 'datetime',
            'final_end_at' => 'datetime',
            'finalized_at' => 'datetime',
            'photo_confirmed_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'start_due_notified_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'decided_at' => 'datetime',
            'decision_seen_at' => 'datetime',
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

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
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

    public function scheduledStartLocal(): CarbonInterface
    {
        return $this->scheduled_start_at
            ->copy()
            ->setTimezone($this->timezone);
    }

    public function scheduledEndLocal(): ?CarbonInterface
    {
        return $this->scheduled_end_at
            ?->copy()
            ->setTimezone($this->timezone);
    }
}
