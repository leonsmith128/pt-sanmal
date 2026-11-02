<?php

namespace App\Enums;

enum ProjectType: string
{
    case Service = 'service';
    case Training = 'training';

    public function label(): string
    {
        return match ($this) {
            self::Service => 'Jasa',
            self::Training => 'Pelatihan',
        };
    }
}
