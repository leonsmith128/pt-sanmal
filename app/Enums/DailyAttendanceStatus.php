<?php

namespace App\Enums;

enum DailyAttendanceStatus: string
{
    case Present = 'present';
    case Incomplete = 'incomplete';
    case Permission = 'permission';
    case Sick = 'sick';
    case Leave = 'leave';
    case Absent = 'absent';
    case NeedsReview = 'needs_review';
    case NoRecord = 'no_record';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Hadir',
            self::Incomplete => 'Absensi belum lengkap',
            self::Permission => 'Izin',
            self::Sick => 'Sakit',
            self::Leave => 'Cuti',
            self::Absent => 'Tidak hadir',
            self::NeedsReview => 'Perlu ditinjau',
            self::NoRecord => 'Belum ada data',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Present => 'green',
            self::Incomplete => 'amber',
            self::Permission => 'sky',
            self::Sick => 'amber',
            self::Leave => 'purple',
            self::Absent => 'red',
            self::NeedsReview => 'red',
            self::NoRecord => 'zinc',
        };
    }

    /** Status yang dapat dipilih untuk koreksi admin. */
    public static function correctionOptions(): array
    {
        return [
            self::Present,
            self::Permission,
            self::Sick,
            self::Leave,
            self::Absent,
        ];
    }
}
