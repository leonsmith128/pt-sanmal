<?php

namespace App\Enums;

enum AttendanceType: string
{
    case ClockIn = 'clock_in';
    case ClockOut = 'clock_out';

    public function label(): string
    {
        return match ($this) {
            self::ClockIn => 'Masuk',
            self::ClockOut => 'Pulang',
        };
    }
}
