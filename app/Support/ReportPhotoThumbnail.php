<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;

/**
 * Membuat thumbnail foto persegi 120x120 px untuk di-embed pada worksheet.
 */
class ReportPhotoThumbnail
{
    public const SIZE = 120;

    private const MAX_SOURCE_PIXELS = 20_000_000;

    public static function drawing(string $binary, string $coordinates, string $name): ?MemoryDrawing
    {
        $info = @getimagesizefromstring($binary);

        if ($info === false || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > self::MAX_SOURCE_PIXELS) {
            return null;
        }

        $source = @imagecreatefromstring($binary);

        if ($source === false) {
            return null;
        }

        $thumbnail = imagecreatetruecolor(self::SIZE, self::SIZE);

        if ($thumbnail === false) {
            imagedestroy($source);

            return null;
        }

        // Crop persegi dari tengah agar foto tidak terdistorsi.
        $side = min($info[0], $info[1]);
        imagecopyresampled(
            $thumbnail,
            $source,
            0,
            0,
            intdiv($info[0] - $side, 2),
            intdiv($info[1] - $side, 2),
            self::SIZE,
            self::SIZE,
            $side,
            $side,
        );
        imagedestroy($source);

        $drawing = new MemoryDrawing;
        $drawing->setName($name);
        $drawing->setImageResource($thumbnail);
        $drawing->setRenderingFunction(MemoryDrawing::RENDERING_JPEG);
        $drawing->setMimeType(MemoryDrawing::MIMETYPE_DEFAULT);
        $drawing->setWidth(self::SIZE);
        $drawing->setHeight(self::SIZE);
        $drawing->setCoordinates($coordinates);
        $drawing->setOffsetX(2);
        $drawing->setOffsetY(2);

        return $drawing;
    }
}
