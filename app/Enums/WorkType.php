<?php

namespace App\Enums;

enum WorkType: string
{
    case Office = 'office';
    case Onsite = 'onsite';

    public function label(): string
    {
        return match ($this) {
            self::Office => 'Kantor',
            self::Onsite => 'Onsite (Proyek)',
        };
    }
}
