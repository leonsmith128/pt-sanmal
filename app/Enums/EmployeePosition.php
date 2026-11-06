<?php

namespace App\Enums;

enum EmployeePosition: string
{
    case Technician = 'technician';
    case ProjectCoordinator = 'project_coordinator';

    public function label(): string
    {
        return match ($this) {
            self::Technician => 'Teknisi',
            self::ProjectCoordinator => 'Koordinator Proyek',
        };
    }
}
