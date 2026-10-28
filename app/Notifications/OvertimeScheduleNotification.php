<?php

namespace App\Notifications;

use App\Models\OvertimeRequest;
use Illuminate\Notifications\Notification;

class OvertimeScheduleNotification extends Notification
{
    public function __construct(
        public readonly OvertimeRequest $overtimeRequest,
        public readonly string $event,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        $isReminder = $this->event === 'reminder';

        return [
            'title' => $isReminder ? 'Pengingat lembur' : 'Waktu lembur telah tiba',
            'message' => $isReminder
                ? 'Lembur Anda dijadwalkan sekitar 15 menit lagi. Siapkan diri dan perangkat untuk konfirmasi foto.'
                : 'Jadwal lembur Anda telah tiba. Ambil foto Absen Lembur untuk mengonfirmasi pelaksanaan.',
            'event' => $this->event,
            'overtime_request_id' => $this->overtimeRequest->id,
            'scheduled_start_at' => $this->overtimeRequest->scheduled_start_at->toISOString(),
            'url' => route('attendance'),
        ];
    }
}
