<?php

namespace App\Enums;

enum ProjectType: string
{
    case Calibration = 'calibration';
    case Training = 'training';

    public function label(): string
    {
        return match ($this) {
            self::Calibration => 'Jasa Kalibrasi',
            self::Training => 'Pelatihan',
        };
    }
}
