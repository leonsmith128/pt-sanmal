<?php

namespace App\Console\Commands;

use App\Enums\OvertimeState;
use App\Models\AuditLog;
use App\Models\OvertimeRequest;
use App\Notifications\OvertimeScheduleNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RefreshOvertimeStates extends Command
{
    protected $signature = 'overtime:refresh-states';

    protected $description = 'Kirim pengingat lembur dan perbarui status saat jadwal tiba.';

    public function handle(): int
    {
        $now = now();

        OvertimeRequest::query()
            ->where('state', OvertimeState::Scheduled)
            ->where('scheduled_start_at', '>', $now)
            ->where('scheduled_start_at', '<=', $now->copy()->addMinutes(15))
            ->whereNull('reminder_sent_at')
            ->orderBy('id')
            ->chunkById(100, function ($requests): void {
                foreach ($requests as $request) {
                    DB::transaction(function () use ($request): void {
                        $locked = OvertimeRequest::query()
                            ->whereKey($request->id)
                            ->lockForUpdate()
                            ->first();

                        if (! $locked
                            || $locked->state !== OvertimeState::Scheduled
                            || $locked->reminder_sent_at !== null
                            || $locked->scheduled_start_at->lte(now())
                            || $locked->scheduled_start_at->gt(now()->addMinutes(15))) {
                            return;
                        }

                        $locked->loadMissing('employee.user');
                        $locked->employee?->user?->notify(new OvertimeScheduleNotification($locked, 'reminder'));
                        $locked->update(['reminder_sent_at' => now()]);

                        AuditLog::record(
                            auditable: $locked,
                            action: 'overtime.reminder_sent',
                            newValues: ['reminder_sent_at' => $locked->reminder_sent_at?->toISOString()],
                        );
                    });
                }
            });

        OvertimeRequest::query()
            ->whereIn('state', [OvertimeState::Scheduled, OvertimeState::AwaitingPhoto])
            ->where('scheduled_start_at', '<=', now())
            ->whereNull('start_due_notified_at')
            ->whereNull('start_record_id')
            ->orderBy('id')
            ->chunkById(100, function ($requests): void {
                foreach ($requests as $request) {
                    DB::transaction(function () use ($request): void {
                        $locked = OvertimeRequest::query()
                            ->whereKey($request->id)
                            ->lockForUpdate()
                            ->first();

                        if (! $locked
                            || ! in_array($locked->state, [OvertimeState::Scheduled, OvertimeState::AwaitingPhoto], true)
                            || $locked->scheduled_start_at->isFuture()
                            || $locked->start_due_notified_at !== null
                            || $locked->start_record_id !== null) {
                            return;
                        }

                        $oldState = $locked->state->value;
                        $locked->update([
                            'state' => OvertimeState::AwaitingPhoto,
                            'start_due_notified_at' => now(),
                        ]);

                        $locked->loadMissing('employee.user');
                        $locked->employee?->user?->notify(new OvertimeScheduleNotification($locked, 'due'));

                        AuditLog::record(
                            auditable: $locked,
                            action: 'overtime.schedule_reached',
                            oldValues: ['state' => $oldState],
                            newValues: [
                                'state' => $locked->state->value,
                                'start_due_notified_at' => $locked->start_due_notified_at?->toISOString(),
                            ],
                        );
                    });
                }
            });

        return self::SUCCESS;
    }
}
