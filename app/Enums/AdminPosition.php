<?php

namespace App\Enums;

enum AdminPosition: string
{
    case StaffAdministration = 'staff_administration';
    case Supervisor = 'supervisor';

    public function label(): string
    {
        return match ($this) {
            self::StaffAdministration => 'Staf Administrasi',
            self::Supervisor => 'Supervisor',
        };
    }
}
