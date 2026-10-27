<?php

namespace App\Enums;

enum SelfieSource: string
{
    case Camera = 'camera';
    case Upload = 'upload';

    public function label(): string
    {
        return match ($this) {
            self::Camera => 'Kamera',
            self::Upload => 'Unggah manual',
        };
    }
}
