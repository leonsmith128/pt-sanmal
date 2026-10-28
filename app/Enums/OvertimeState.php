<?php

namespace App\Enums;

enum OvertimeState: string
{
    case Scheduled = 'scheduled';
    case AwaitingPhoto = 'awaiting_photo';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NeedsReview = 'needs_review';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Terjadwal',
            self::AwaitingPhoto => 'Menunggu foto mulai lembur',
            self::InProgress => 'Lembur berjalan',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
            self::NeedsReview => 'Perlu ditinjau admin',
        };
    }
}
