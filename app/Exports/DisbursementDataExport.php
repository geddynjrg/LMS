<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use Illuminate\Support\Str;
class DisbursementDataExport implements
    FromQuery,
    WithMapping,
    WithHeadings,
    WithStyles,
    WithEvents,
    WithCustomStartCell,
    WithChunkReading,
    WithTitle,
    WithColumnWidths
{
    protected $query;
    protected $rowNumber = 0;

    public function __construct($query) {
        $this->query = $query;
    }

    public function startCell(): string {
        return 'A7';
    }

    public function query() {
        return $this->query;
    }

    public function columnWidths(): array {
        return [
            'A' => 7, 'B' => 8, 'C' => 30, 'D' => 15, 'E' => 15, 'F' => 15, 'G' => 20, 'H' => 15,
        ];
    }

    public function headings(): array {
        return [
            'S/No.', 'CODE', 'GROUP NAME', 'NATURE OF GROUP', 'WARD',
            'RECOMMENDED', 'BANK AND BRANCH', 'ACCOUNT NO.'
        ];
    }

    public function map($app): array {
        $this->rowNumber++;

        $wardName = $app->tempApplication->ward->ward_name ?? 'NOT ASSIGNED';
        $bankDetails = $app->tempApplication->bank_name .",".$app->tempApplication->bank_branch;

        return [
            $this->rowNumber,
            $app->group_code,
            Str::upper($app->tempApplication->group_name ?? 'N/A'),
            Str::upper($app->tempApplication->nature_of_group),
            Str::upper($wardName),
            $app->amount_approved ?: '0',
            Str::upper($bankDetails),
            $app->tempApplication->bank_account_number,
        ];
    }

    public function registerEvents(): array {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $this->addLogo($event);

                $highestRow = $sheet->getHighestRow();
                $highestCol = $sheet->getHighestColumn();
                $totalRow = $highestRow + 1;
                $dataStartRow = 8;

                $firstRecord = $this->query->first();

                $constituencyName = $firstRecord->tempApplication->constituency->constituency_name ?? 'N/A';
                $constCode = $firstRecord->tempApplication->constituency_code ?? 'N/A';
                $countyName = $firstRecord->tempApplication->constituency->county->county_name ?? 'N/A';
                $phase = $firstRecord->reviewPhase->phase ?? 'N/A';


                $sheet->setCellValue("E{$totalRow}", "TOTAL");

                $sheet->setCellValue("F{$totalRow}", "=SUM(F{$dataStartRow}:F{$highestRow})");

                $totalStyle = [
                    'font' => ['bold' => true],
                    'numberFormat' => ['formatCode' => '#,##0']
                ];

                $sheet->getStyle("F{$totalRow}:H{$totalRow}")->applyFromArray($totalStyle);
                $sheet->getStyle("F{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);


                $sheet->getStyle("F8:F{$highestRow}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');

                $sheet->getStyle("A7:{$highestCol}{$totalRow}")->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // Define the Header Rows
                $sheet->setCellValue('A1', 'UWEZO FUND');
                $sheet->setCellValue('A2', 'DISBURSEMENT DATA FORM ' . strtoupper($phase));
                $sheet->setCellValue('A3', 'Republic of Kenya');
                $sheet->setCellValue('A4', 'Uwezo Fund Oversight Board');
                $sheet->setCellValue('A5', "Name of Constituency: " . strtoupper($constituencyName) . " | IEBC Code: $constCode | County: " . strtoupper($countyName));

                $sheet->setCellValue('A6', "For the Month Ended: " . now()->format('d F Y'));

                // Styling the Header
                $sheet->getStyle('A1:A6')->getFont()->setBold(true);
                $sheet->getStyle('A1:A4')->getAlignment()->setHorizontal('center');


                // Merge Header Rows across the width of the table (A to J)
                for($i = 1; $i <= 6; $i++) {
                    $sheet->mergeCells("A{$i}:{$highestCol}{$i}");
                    $sheet->getStyle("A{$i}")->getAlignment()->setHorizontal('center');
                    $sheet->getStyle("A{$i}")->getFont()->setBold(true);
                }
            },
        ];
    }

    public function styles(Worksheet $sheet) {
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
                    'startColor' => ['rgb' => 'E9E9E9']
                ]
            ],
            'C' => ['alignment' => ['wrapText' => true]],
            'G' => ['alignment' => ['wrapText' => true]],
        ];
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function title(): string
    {
        return 'DISBURSEMENT DATA FORM';
    }

    private function addLogo(AfterSheet $event)
    {
        try {
            $logoPath = public_path('images/docs/doc_logo.png');

            $usedLogoPath = null;

            if (file_exists($logoPath)) {
                $usedLogoPath = $logoPath;
            }

            if ($usedLogoPath) {
                $drawing = new Drawing();
                $drawing->setName('Logo');
                $drawing->setDescription('Logo');
                $drawing->setPath($usedLogoPath);
                $drawing->setHeight(75); // Adjust height as needed
                $drawing->setCoordinates('A1');
                $drawing->setOffsetX(5);
                $drawing->setOffsetY(5);
                $drawing->setWorksheet($event->sheet->getDelegate());
            }

        } catch (\Exception $e) {
            // Logo not found or error loading - continue without logo
            logger()->warning('Logo not found for Excel export: ' . $e->getMessage());
        }
    }
}
