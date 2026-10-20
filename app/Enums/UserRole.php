<?php

namespace App\Enums;

enum UserRole: string
{
    case Superadmin = 'superadmin';
    case Admin = 'admin';
    case Employee = 'employee';

    public function label(): string
    {
        return match ($this) {
            self::Superadmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Employee => 'Pegawai',
        };
    }

    /** Hanya admin dan employee yang wajib punya profil pegawai. */
    public function requiresEmployeeProfile(): bool
    {
        return $this !== self::Superadmin;
    }
}