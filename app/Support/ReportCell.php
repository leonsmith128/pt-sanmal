<?php

namespace App\Support;

/**
 * Pemformat sel laporan XLSX: nilai kosong, perlindungan formula, durasi, dan koordinat.
 */
class ReportCell
{
    public const BLANK = '-';

    public static function text(mixed $value): string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return self::BLANK;
        }

        // Teks pengguna yang diawali karakter formula tidak boleh dieksekusi sebagai formula.
        return preg_match('/^[\s\x00-\x1F]*[=+\-@]/u', $value) === 1 ? "'".$value : $value;
    }

    /** @param iterable<mixed> $values */
    public static function joined(iterable $values, string $separator = '; '): string
    {
        $items = collect($values)
            ->map(fn (mixed $value): string => trim((string) $value))
            ->filter(fn (string $value): bool => $value !== '')
            ->unique()
            ->values();

        return $items->isEmpty() ? self::BLANK : self::text($items->implode($separator));
    }

    public static function duration(?int $minutes): string
    {
        return $minutes === null ? self::BLANK : intdiv($minutes, 60).'j '.($minutes % 60).'m';
    }

    /** Angka murni (mis. koordinat negatif) sudah divalidasi numerik sehingga tidak diberi awalan proteksi formula. */
    public static function number(mixed $value, int $decimals): string
    {
        return is_numeric($value) ? number_format((float) $value, $decimals, '.', '') : self::BLANK;
    }

    public static function mapUrl(mixed $latitude, mixed $longitude): ?string
    {
        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return null;
        }

        $lat = (float) $latitude;
        $lng = (float) $longitude;

        if (abs($lat) > 90 || abs($lng) > 180) {
            return null;
        }

        return 'https://www.google.com/maps?q='.number_format($lat, 7, '.', '').','.number_format($lng, 7, '.', '');
    }
}
