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
        $message = match ($this->event) {
            'reminder' => 'Lembur Anda dijadwalkan sekitar 15 menit lagi. Siapkan diri dan perangkat untuk konfirmasi foto.',
            'due' => 'Jadwal lembur Anda telah tiba. Ambil foto Absen Lembur untuk mengonfirmasi pelaksanaan.',
            'cancelled' => 'Pengajuan lembur ini telah dibatalkan. Anda tidak perlu lagi melakukan konfirmasi foto untuk jadwal ini.',
            default => 'Ada pembaruan pada pengajuan lembur Anda.',
        };

        return [
            'title' => match ($this->event) {
                'reminder' => 'Pengingat lembur',
                'due' => 'Waktu lembur telah tiba',
                'cancelled' => 'Pengajuan lembur dibatalkan',
                default => 'Pembaruan lembur',
            },
            'message' => $message,
            'event' => $this->event,
            'overtime_request_id' => $this->overtimeRequest->id,
            'scheduled_start_at' => $this->overtimeRequest->scheduled_start_at->toISOString(),
            'url' => $this->event === 'cancelled'
                ? route('overtime-requests.index')
                : route('attendance'),
        ];
    }
}
