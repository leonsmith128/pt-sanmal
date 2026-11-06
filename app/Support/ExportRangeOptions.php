<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Opsi rentang untuk tombol Export: Hari ini, Minggu ini, Bulan ini, dan rentang sesuai filter halaman.
 * Hanya menyusun data (label, URL, deskripsi); halaman menentukan rute, filter tetap, dan teks.
 */
final class ExportRangeOptions
{
    private const TIMEZONE = 'Asia/Makassar';

    /**
     * @param  array<string, mixed>  $params  Filter tetap yang ikut di setiap opsi (nilai kosong dibuang).
     * @param  string  $subject  Objek laporan untuk deskripsi, mis. "absensi seluruh pegawai".
     * @param  string  $relation  Kata penghubung deskripsi, mis. "pada" atau "yang periodenya beririsan dengan".
     * @param  ?string  $customFrom  Awal rentang filter halaman; bersama $customTo membentuk opsi kustom.
     * @param  ?string  $customFallback  Deskripsi opsi tanpa rentang; null berarti opsi kustom tidak ditampilkan.
     * @param  ?string  $customDescription  Menggantikan deskripsi bawaan opsi kustom berentang.
     * @return list<array{label: string, href: string, description: string, icon: string, divider_before?: bool}>
     */
    public static function make(
        string $route,
        array $params = [],
        string $subject = 'absensi',
        string $relation = 'pada',
        ?string $customFrom = null,
        ?string $customTo = null,
        string $customLabel = 'Sesuai filter tanggal',
        ?string $customFallback = null,
        ?string $customDescription = null,
        ?Carbon $today = null,
    ): array {
        $today = ($today ?? Carbon::now(self::TIMEZONE))->copy()->startOfDay();
        $params = array_filter($params, fn (mixed $value): bool => $value !== '' && $value !== null);

        $ranges = [
            [__('Hari ini'), $today, $today, 'calendar-days', 'tanggal'],
            [__('Minggu ini'), $today->copy()->startOfWeek(Carbon::MONDAY), $today->copy()->endOfWeek(Carbon::SUNDAY), 'calendar-days', 'rentang'],
            [__('Bulan ini'), $today->copy()->startOfMonth(), $today->copy()->endOfMonth(), 'calendar', 'rentang'],
        ];

        $options = [];
        $seen = [];

        foreach ($ranges as [$label, $from, $to, $icon, $kind]) {
            $seen[] = $from->toDateString().'|'.$to->toDateString();
            $options[] = [
                'label' => $label,
                'href' => route($route, [...$params, 'from' => $from->toDateString(), 'to' => $to->toDateString()]),
                'description' => $kind === 'tanggal'
                    ? "Laporan {$subject} {$relation} tanggal ".$from->translatedFormat('d M Y').'. Seluruh filter aktif tetap diterapkan.'
                    : "Laporan {$subject} {$relation} rentang ".$from->translatedFormat('d M Y').' sampai '.$to->translatedFormat('d M Y').'. Seluruh filter aktif tetap diterapkan.',
                'icon' => $icon,
            ];
        }

        if (filled($customFrom) && filled($customTo)) {
            // Rentang yang sama dengan opsi di atas tidak ditampilkan dua kali.
            if (! in_array($customFrom.'|'.$customTo, $seen, true)) {
                $options[] = [
                    'label' => $customLabel,
                    'href' => route($route, [...$params, 'from' => $customFrom, 'to' => $customTo]),
                    'description' => $customDescription
                        ?? 'Rentang tanggal mengikuti filter aktif: '.Carbon::parse($customFrom)->translatedFormat('d M Y')
                            .' sampai '.Carbon::parse($customTo)->translatedFormat('d M Y').'. Seluruh filter tambahan yang aktif tetap diterapkan.',
                    'icon' => 'arrow-down-tray',
                    'divider_before' => true,
                ];
            }
        } elseif ($customFallback !== null) {
            $options[] = [
                'label' => $customLabel,
                'href' => route($route, $params),
                'description' => $customFallback,
                'icon' => 'arrow-down-tray',
                'divider_before' => true,
            ];
        }

        return $options;
    }
}
