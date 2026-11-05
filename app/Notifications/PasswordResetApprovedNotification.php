<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetApprovedNotification extends Notification
{
    public function __construct(
        public readonly string $resetUrl,
        public readonly string $expiresAt,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Permintaan reset password SanMal disetujui')
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Permintaan reset password akun SanMal Anda telah disetujui.')
            ->action('Buat password baru', $this->resetUrl)
            ->line('Tautan berlaku sampai '.$this->expiresAt.'.')
            ->line('Jika Anda tidak mengajukan permintaan ini, segera hubungi administrator.');
    }
}
