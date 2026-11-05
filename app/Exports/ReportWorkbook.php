<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Satu workbook dengan tepat dua worksheet: Ringkasan dan Detail.
 */
class ReportWorkbook implements WithMultipleSheets
{
    public function __construct(
        private readonly ReportSheet $summary,
        private readonly ReportSheet $detail,
    ) {}

    public function sheets(): array
    {
        return [$this->summary, $this->detail];
    }
}
