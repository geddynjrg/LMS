<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class GroupsListExport implements
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
    protected $constituencyName;

    public function __construct($query, ?string $constituencyName = null) {
        $this->query = $query;
        $this->constituencyName = $constituencyName;
    }

    public function startCell(): string {
        return 'A7';
    }

    public function query() {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'S/No.',
            'COUNTY',
            'CONSTITUENCY',
            'WARD',
            'GROUP NAME',
            'GFS CODE',
            'CATEGORY',
            'SECTOR',
            'IDENTIFIER',
        ];
    }

    public function columnWidths(): array {
        return [
            'A' => 7, 'B' => 20, 'C' => 20, 'D' => 15, 'E' => 30, 'F' => 25, 'G' => 15, 'H' => 20, 'I' => 20, 'J' => 15,
        ];
    }

    public function map($group): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            strtoupper($group->county_name),
            strtoupper($group->constituency_name ?? 'N/A'),
            strtoupper($group->ward_name ?? 'N/A'),
            strtoupper($group->name),
            $group->gfs_code ?? '---',
            strtoupper($group->category ?? 'N/A'),
            strtoupper($group->sector ?? 'N/A'),
            strtoupper($group->identifier ?? '---'),
        ];
    }

    public function registerEvents(): array {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $this->addLogo($event);

                $highestRow = $sheet->getHighestRow();
                $highestCol = $sheet->getHighestColumn();

                $sheet->getStyle("A6:{$highestCol}{$highestRow}")->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // Define the Header Rows
                $sheet->setCellValue('A1', 'UWEZO FUND');
                $sheet->setCellValue('A2', $this->constituencyName ? $this->constituencyName . ' GROUP LIST' : 'GROUP LIST');
                $sheet->setCellValue('A3', 'Republic of Kenya');
                $sheet->setCellValue('A4', 'Uwezo Fund Oversight Board');
                $sheet->setCellValue('A5', "As At " . now()->format('F d, Y'));

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
            'E' => ['alignment' => ['wrapText' => true]],
            'F' => ['alignment' => ['wrapText' => true]],
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function title(): string
    {
        return 'GROUP LIST';
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
                $drawing->setHeight(75);
                $drawing->setCoordinates('A1');
                $drawing->setOffsetX(5);
                $drawing->setOffsetY(5);
                $drawing->setWorksheet($event->sheet->getDelegate());
            }

        } catch (\Exception $e) {
            logger()->warning('Logo not found for Excel export: ' . $e->getMessage());
        }
    }

}
