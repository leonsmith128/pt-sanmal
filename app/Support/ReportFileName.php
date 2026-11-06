<?php

namespace App\Support;

use App\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Nama file ekspor: sanmal_{jenis}_{periode}_{tanggal-ekspor}.xlsx (admin)
 * dan sanmal_{jenis}_{nama}_{nomor}_{periode}_{tanggal-ekspor}.xlsx (pegawai).
 */
class ReportFileName
{
    private const MAX_PART_LENGTH = 40;

    public static function admin(string $report, string $period): string
    {
        return self::join(['sanmal', $report, $period, self::today()]);
    }

    public static function employee(string $report, Employee $employee, string $period): string
    {
        $name = $employee->user?->name ?: 'pegawai';
        $number = $employee->employee_number ?: 'id'.$employee->id;

        return self::join(['sanmal', $report, $name, $number, $period, self::today()]);
    }

    public static function period(Carbon|string $start, Carbon|string $end): string
    {
        return Carbon::parse($start)->format('Ymd').'-'.Carbon::parse($end)->format('Ymd');
    }

    private static function today(): string
    {
        return Carbon::now('Asia/Makassar')->format('Ymd');
    }

    /** @param list<string> $parts */
    private static function join(array $parts): string
    {
        return implode('_', array_map(self::part(...), $parts)).'.xlsx';
    }

    private static function part(string $value): string
    {
        $slug = mb_substr(Str::slug($value), 0, self::MAX_PART_LENGTH);

        return $slug === '' ? 'x' : $slug;
    }
}
