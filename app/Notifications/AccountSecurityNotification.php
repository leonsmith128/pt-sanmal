<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountSecurityNotification extends Notification
{
    public function __construct(
        public readonly string $subject,
        public readonly string $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->greeting('Halo '.($notifiable->name ?? 'Pengguna SanMal').',')
            ->line($this->message)
            ->line('Jika Anda tidak melakukan aktivitas ini, segera hubungi administrator SanMal.');
    }
}
