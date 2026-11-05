<?php

namespace App\Exports;

use App\Support\ReportCell;
use Illuminate\Support\Collection;

/**
 * Workbook Permohonan Pegawai: satu baris per permohonan pada Ringkasan dan Detail.
 */
class LeaveRequestReport
{
    private const TIMEZONE = 'Asia/Makassar';

    private const SUMMARY_HEADINGS = [
        'Nomor Permohonan', 'Jenis', 'Tanggal Pengajuan (WITA)', 'Tanggal Mulai', 'Tanggal Selesai',
        'Status Persetujuan', 'Status Operasional Lembur',
    ];

    private const DETAIL_HEADINGS = [
        'Nomor Permohonan', 'Jenis', 'Tanggal Pengajuan (WITA)', 'Tanggal Mulai', 'Tanggal Selesai',
        'Ringkasan / Alasan', 'Status Persetujuan', 'Status Operasional Lembur',
        'Pemroses', 'Waktu Keputusan (WITA)', 'Catatan Keputusan',
    ];

    /** @param Collection<int, object> $rows Baris dari LeaveRequestController::filteredRows(). */
    public static function workbook(Collection $rows): ReportWorkbook
    {
        $summary = [];
        $detail = [];

        foreach ($rows as $row) {
            $values = [
                'Nomor Permohonan' => ReportCell::text($row->reference),
                'Jenis' => ReportCell::text($row->type_label),
                'Tanggal Pengajuan (WITA)' => self::dateTime($row->submitted_at),
                'Tanggal Mulai' => ReportCell::text($row->period_start),
                'Tanggal Selesai' => ReportCell::text($row->period_end),
                'Ringkasan / Alasan' => ReportCell::text($row->summary),
                'Status Persetujuan' => ReportCell::text($row->status_label),
                'Status Operasional Lembur' => ReportCell::text($row->operational_status),
                'Pemroses' => ReportCell::text($row->reviewer),
                'Waktu Keputusan (WITA)' => self::dateTime($row->decided_at),
                'Catatan Keputusan' => ReportCell::text($row->decision_note),
            ];

            $summary[] = array_map(fn (string $heading): string => $values[$heading], self::SUMMARY_HEADINGS);
            $detail[] = array_map(fn (string $heading): string => $values[$heading], self::DETAIL_HEADINGS);
        }

        return new ReportWorkbook(
            new ReportSheet('Ringkasan', self::SUMMARY_HEADINGS, $summary),
            new ReportSheet('Detail', self::DETAIL_HEADINGS, $detail),
        );
    }

    private static function dateTime(mixed $value): string
    {
        return $value === null
            ? ReportCell::BLANK
            : $value->copy()->setTimezone(self::TIMEZONE)->format('Y-m-d H:i:s');
    }
}
