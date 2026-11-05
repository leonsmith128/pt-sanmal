<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmployeeAccountActivationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $activationUrl,
        public readonly string $expiresAt,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Aktivasi akun pegawai SanMal')
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Akun pegawai SanMal Anda telah dibuat.')
            ->line('Gunakan tautan berikut untuk menetapkan password akun Anda.')
            ->action('Aktifkan akun', $this->activationUrl)
            ->line('Tautan berlaku sampai '.$this->expiresAt.'.')
            ->line('Jika Anda tidak mengharapkan email ini, silakan hubungi administrator.');
    }
}
