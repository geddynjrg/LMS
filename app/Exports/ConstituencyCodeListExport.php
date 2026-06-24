<?php

namespace App\Exports;

use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Constituency phase "Code List" export — legacy layout (pre-June 2026).
 * No BOARD REMARKS column and no financial summary block.
 */
class ConstituencyCodeListExport extends DefaultValueBinder implements
    FromQuery,
    WithMapping,
    WithHeadings,
    ShouldAutoSize,
    WithStyles,
    WithEvents,
    WithCustomStartCell,
    WithChunkReading,
    WithTitle,
    WithColumnWidths,
    WithCustomValueBinder
{
    use Exportable;

    protected $query;
    protected int $rowNumber = 0;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function startCell(): string
    {
        return 'A7';
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'S/No.',
            'CODE',
            'GROUP NAME',
            'GFS CODE',
            'APPLIED',
            'CURRENT GROUP ACTIVITY',
            'PREVIOUS LOAN AMMOUNT',
            'LOAN LEVEL',
            'RECOMMENDED',
            'CURRENT STATUS',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 7,
            'B' => 8,
            'C' => 30,
            'D' => 25,
            'E' => 15,
            'F' => 15,
            'G' => 15,
            'H' => 15,
            'I' => 15,
            'J' => 15,
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        if ($value === 0 || $value === 0.0 || $value === '0') {
            $cell->setValueExplicit(0, DataType::TYPE_NUMERIC);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function map($app): array
    {
        $this->rowNumber++;
        $businessType = $app->tempApplication->business_type;
        $currentActivity = is_array($businessType) ? ($businessType[0] ?? 'N/A') : $businessType;

        $prevAmount = '0';
        $loanLevel = 'L1';

        if ($app->linkedGroup && $app->linkedGroup->currentLoan) {
            $prevAmount = $app->linkedGroup->currentLoan->amount_issued ?? '0';
            $loanLevel = 'L' . ($app->loan_level ?? 1);
        }

        return [
            $this->rowNumber,
            $app->group_code,
            Str::upper($app->tempApplication->group_name ?? 'N/A'),
            $app->gfs_code ?? 'N/A',
            $app->amount_applied,
            Str::upper((string) $currentActivity),
            $prevAmount,
            $loanLevel,
            $app->amount_approved ?? 0,
            Str::upper($app->tempApplication->nature_of_business ?? 'N/A'),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $this->addLogo($event);

                $dataStartRow = 8;
                $highestRow = $sheet->getHighestRow();
                $highestCol = $sheet->getHighestColumn();
                $totalRow = $highestRow + 1;

                $firstRecord = $this->query->first();
                if (! $firstRecord) {
                    return;
                }

                $constituencyName = $firstRecord->reviewPhase->constituency->constituency_name ?? 'N/A';
                $constCode = $firstRecord->reviewPhase->constituency_code ?? 'N/A';
                $countyName = $firstRecord->reviewPhase->constituency->county->county_name ?? 'N/A';
                $phase = $firstRecord->reviewPhase->phase ?? 'N/A';

                $sheet->setCellValue("D{$totalRow}", 'TOTAL');
                $sheet->setCellValue("E{$totalRow}", "=SUM(E{$dataStartRow}:E{$highestRow})");
                $sheet->setCellValue("I{$totalRow}", "=SUM(I{$dataStartRow}:I{$highestRow})");

                $totalStyle = [
                    'font' => ['bold' => true],
                    'numberFormat' => ['formatCode' => '#,##0'],
                ];

                $sheet->getStyle("D{$totalRow}:I{$totalRow}")->applyFromArray($totalStyle);
                $sheet->getStyle("D{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                foreach (['E', 'G', 'I'] as $col) {
                    $sheet->getStyle("{$col}8:{$col}{$highestRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0');
                }

                $sheet->getStyle("A7:{$highestCol}{$totalRow}")->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                $sheet->getStyle("F7:F{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("H7:H{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("J7:J{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->setCellValue('A1', 'UWEZO FUND');
                $sheet->setCellValue('A2', 'CODE LIST ' . strtoupper($phase));
                $sheet->setCellValue('A3', 'Republic of Kenya');
                $sheet->setCellValue('A4', 'Uwezo Fund Oversight Board');
                $sheet->setCellValue(
                    'A5',
                    'Name of Constituency: ' . strtoupper($constituencyName) . " | IEBC Code: {$constCode} | County: " . strtoupper($countyName)
                );
                $sheet->setCellValue('A6', 'For the Month Ended: ' . now()->format('d F Y'));

                $sheet->getStyle('A1:A6')->getFont()->setBold(true);
                $sheet->getStyle('A1:A4')->getAlignment()->setHorizontal('center');

                for ($i = 1; $i <= 6; $i++) {
                    $sheet->mergeCells("A{$i}:{$highestCol}{$i}");
                    $sheet->getStyle("A{$i}")->getAlignment()->setHorizontal('center');
                    $sheet->getStyle("A{$i}")->getFont()->setBold(true);
                }
            },
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            7 => [
                'font' => ['bold' => true],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E9E9E9'],
                ],
            ],
            'C' => ['alignment' => ['wrapText' => true]],
        ];
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function title(): string
    {
        return 'CODE LIST';
    }

    private function addLogo(AfterSheet $event): void
    {
        try {
            $logoPath = public_path('images/docs/doc_logo.png');

            if (! file_exists($logoPath)) {
                return;
            }

            $drawing = new Drawing();
            $drawing->setName('Logo');
            $drawing->setDescription('Logo');
            $drawing->setPath($logoPath);
            $drawing->setHeight(75);
            $drawing->setCoordinates('A1');
            $drawing->setOffsetX(10);
            $drawing->setOffsetY(10);
            $drawing->setWorksheet($event->sheet->getDelegate());
        } catch (\Exception $e) {
            logger()->warning('Logo not found for Excel export: ' . $e->getMessage());
        }
    }
}
