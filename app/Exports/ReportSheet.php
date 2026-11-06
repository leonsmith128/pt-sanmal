<?php

namespace App\Exports;

use Generator;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;

/**
 * Satu worksheet laporan: header tebal berlatar, AutoFilter, freeze header, lebar kolom wajar.
 * Semua nilai ditulis sebagai teks (StringValueBinder) sehingga tidak ada nilai yang dieksekusi sebagai formula.
 */
class ReportSheet extends StringValueBinder implements FromGenerator, WithCustomValueBinder, WithDrawings, WithEvents, WithTitle
{
    private const MIN_WIDTH = 10;

    private const MAX_WIDTH = 60;

    private const PHOTO_WIDTH = 18;

    private const PHOTO_ROW_HEIGHT = 92;

    /**
     * @param  list<string>  $headings
     * @param  list<list<string>>  $rows
     * @param  list<string>  $linkHeadings  Kolom yang berisi URL peta (dibuat hyperlink).
     * @param  list<BaseDrawing>  $drawings
     * @param  list<string>  $photoHeadings  Kolom tempat thumbnail foto di-embed.
     */
    public function __construct(
        private readonly string $title,
        private readonly array $headings,
        private readonly array $rows,
        private readonly array $linkHeadings = [],
        private readonly array $drawings = [],
        private readonly array $photoHeadings = [],
    ) {
    }

    public function title(): string
    {
        return $this->title;
    }

    public function generator(): Generator
    {
        yield $this->headings;

        foreach ($this->rows as $row) {
            yield $row;
        }
    }

    public function drawings(): array
    {
        return $this->drawings;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = Coordinate::stringFromColumnIndex(max(1, count($this->headings)));
                $lastRow = count($this->rows) + 1;

                $sheet->getStyle('A1:'.$lastColumn.'1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);
                $sheet->setAutoFilter('A1:'.$lastColumn.$lastRow);
                $sheet->freezePane('A2');

                $longest = array_map(
                    static fn (string $heading): int => mb_strlen($heading),
                    $this->headings,
                );
                $linkColumns = [];

                foreach ($this->linkHeadings as $heading) {
                    $index = array_search($heading, $this->headings, true);

                    if ($index !== false) {
                        $linkColumns[$index] = Coordinate::stringFromColumnIndex($index + 1);
                    }
                }

                // Hitung lebar sampel dan pasang seluruh hyperlink dalam satu lintasan baris.
                foreach ($this->rows as $rowIndex => $row) {
                    if ($rowIndex < 200) {
                        foreach ($longest as $columnIndex => $length) {
                            $longest[$columnIndex] = max($length, mb_strlen((string) ($row[$columnIndex] ?? '')));
                        }
                    }

                    foreach ($linkColumns as $index => $column) {
                        $url = $row[$index] ?? '';

                        if (str_starts_with($url, 'https://')) {
                            $sheet->getCell($column.($rowIndex + 2))->getHyperlink()->setUrl($url);
                        }
                    }
                }

                $contentWidths = array_map(
                    static fn (int $length): int => max(self::MIN_WIDTH, min(self::MAX_WIDTH, $length + 2)),
                    $longest,
                );

                foreach ($this->headings as $index => $heading) {
                    $column = Coordinate::stringFromColumnIndex($index + 1);
                    $sheet->getColumnDimension($column)->setWidth(
                        in_array($heading, $this->photoHeadings, true) ? self::PHOTO_WIDTH : $contentWidths[$index],
                    );
                }

                foreach ($this->drawings as $drawing) {
                    [, $row] = Coordinate::coordinateFromString($drawing->getCoordinates());
                    $sheet->getRowDimension((int) $row)->setRowHeight(self::PHOTO_ROW_HEIGHT);
                }
            },
        ];
    }
}
