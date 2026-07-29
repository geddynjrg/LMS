<?php

namespace App\Exports;

use App\Models\LoanPhase;
use App\Support\CodeListRecordMapper;
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
    protected ?LoanPhase $phase;
    protected int $rowNumber = 0;

    public function __construct($query, ?LoanPhase $phase = null)
    {
        $this->query = $query;
        $this->phase = $phase;
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

        return [
            $this->rowNumber,
            CodeListRecordMapper::groupCode($app),
            CodeListRecordMapper::groupName($app),
            CodeListRecordMapper::gfsCode($app),
            CodeListRecordMapper::amountApplied($app),
            CodeListRecordMapper::currentActivity($app),
            CodeListRecordMapper::previousLoanAmount($app),
            CodeListRecordMapper::loanLevel($app),
            CodeListRecordMapper::amountApproved($app),
            CodeListRecordMapper::currentStatus($app),
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
                $hasDataRows = $highestRow >= $dataStartRow;
                $totalRow = $hasDataRows ? $highestRow + 1 : $highestRow;

                $metadata = $this->resolveDocumentMetadata();
                if ($metadata === null) {
                    return;
                }

                if ($hasDataRows) {
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
                        $sheet->getStyle("{$col}{$dataStartRow}:{$col}{$highestRow}")
                            ->getNumberFormat()
                            ->setFormatCode('#,##0');
                    }

                    $sheet->getStyle("F{$dataStartRow}:F{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("H{$dataStartRow}:H{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("J{$dataStartRow}:J{$highestRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                $sheet->getStyle("A7:{$highestCol}{$totalRow}")->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                $sheet->setCellValue('A1', 'UWEZO FUND');
                $sheet->setCellValue('A2', 'CODE LIST ' . strtoupper($metadata['phase']));
                $sheet->setCellValue('A3', 'Republic of Kenya');
                $sheet->setCellValue('A4', 'Uwezo Fund Oversight Board');
                $sheet->setCellValue(
                    'A5',
                    'Name of Constituency: ' . strtoupper($metadata['constituency_name'])
                    . " | IEBC Code: {$metadata['constituency_code']} | County: "
                    . strtoupper($metadata['county_name'])
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

    /**
     * @return array{phase: string, constituency_name: string, constituency_code: string, county_name: string}|null
     */
    private function resolveDocumentMetadata(): ?array
    {
        $firstRecord = (clone $this->query)->first();
        if ($firstRecord?->reviewPhase) {
            $phase = $firstRecord->reviewPhase;

            return [
                'phase' => $phase->phase ?? 'N/A',
                'constituency_name' => $phase->constituency->constituency_name ?? 'N/A',
                'constituency_code' => $phase->constituency_code ?? 'N/A',
                'county_name' => $phase->constituency->county->county_name ?? 'N/A',
            ];
        }

        $historicalPhase = CodeListRecordMapper::resolvePhase($firstRecord);
        if ($historicalPhase) {
            $historicalPhase->loadMissing('constituency.county');

            return [
                'phase' => $historicalPhase->phase ?? 'N/A',
                'constituency_name' => $historicalPhase->constituency->constituency_name ?? 'N/A',
                'constituency_code' => $historicalPhase->constituency_code ?? 'N/A',
                'county_name' => $historicalPhase->constituency->county->county_name ?? 'N/A',
            ];
        }

        if ($this->phase) {
            $this->phase->loadMissing('constituency.county');

            return [
                'phase' => $this->phase->phase ?? 'N/A',
                'constituency_name' => $this->phase->constituency->constituency_name ?? 'N/A',
                'constituency_code' => $this->phase->constituency_code ?? 'N/A',
                'county_name' => $this->phase->constituency->county->county_name ?? 'N/A',
            ];
        }

        return null;
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
