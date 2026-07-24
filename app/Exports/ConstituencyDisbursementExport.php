<?php

namespace App\Exports;

use App\Models\Constituency;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ConstituencyDisbursementExport implements FromCollection, WithHeadings, WithStyles, WithEvents, WithTitle, WithColumnWidths, WithCustomStartCell
{
    protected Constituency $constituency;
    protected Collection $loans;

    public function __construct(Constituency $constituency, Collection $loans)
    {
        $this->constituency = $constituency;
        $this->loans = $loans;
    }

    public function startCell(): string
    {
        return 'A8';
    }

    public function collection(): Collection
    {
        return $this->loans->values()->map(function ($loan, $index) {
            $group = $loan->group;
            $wardName = $group?->ward?->ward_name ?? 'NOT ASSIGNED';

            return [
                $index + 1,
                $group?->code ?? $loan->gfs_code ?? 'N/A',
                Str::upper($group?->name ?? 'N/A'),
                Str::upper($loan->loanPhase?->phase ?? 'N/A'),
                Str::upper($loan->loan_type ?? 'N/A'),
                Str::upper($wardName),
                $loan->disbursement_date ? $loan->disbursement_date->format('Y-m-d') : 'N/A',
                $loan->amount_issued ?: '0',
                Str::upper(trim(($group?->bank ?? 'N/A') . ($group?->account ? ' / ' . $group->account : ''))),
            ];
        });
    }

    public function headings(): array
    {
        return [
            'S/No.',
            'CODE',
            'GROUP NAME',
            'PHASE',
            'LOAN TYPE',
            'WARD',
            'DISBURSEMENT DATE',
            'AMOUNT ISSUED (KShs)',
            'BANK / ACCOUNT',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 7,
            'B' => 10,
            'C' => 30,
            'D' => 12,
            'E' => 14,
            'F' => 18,
            'G' => 16,
            'H' => 18,
            'I' => 28,
        ];
    }

    public function title(): string
    {
        return 'DISBURSEMENT REPORT';
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
            'I' => ['alignment' => ['wrapText' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $this->addLogo($event);

                $highestRow = $sheet->getHighestRow();
                $highestCol = $sheet->getHighestColumn();
                $dataStartRow = 8;
                $totalRow = $highestRow + 1;

                $sheet->setCellValue('A1', 'UWEZO FUND');
                $sheet->setCellValue('A2', 'CONSTITUENCY DISBURSEMENT REPORT');
                $sheet->setCellValue('A3', 'Republic of Kenya');
                $sheet->setCellValue('A4', 'Uwezo Fund Oversight Board');
                $sheet->setCellValue(
                    'A5',
                    'Name of Constituency: ' . strtoupper($this->constituency->constituency_name)
                    . ' | IEBC Code: ' . $this->constituency->constituency_code
                    . ' | County: ' . strtoupper($this->constituency->county->county_name ?? 'N/A')
                );
                $sheet->setCellValue('A6', 'For the Month Ended: ' . now()->format('d F Y'));

                for ($i = 1; $i <= 6; $i++) {
                    $sheet->mergeCells("A{$i}:{$highestCol}{$i}");
                    $sheet->getStyle("A{$i}")->getAlignment()->setHorizontal('center');
                    $sheet->getStyle("A{$i}")->getFont()->setBold(true);
                }

                $sheet->setCellValue("G{$totalRow}", 'TOTAL');
                $sheet->setCellValue("H{$totalRow}", "=SUM(H{$dataStartRow}:H{$highestRow})");
                $sheet->getStyle("H{$totalRow}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("G{$totalRow}:H{$totalRow}")->getFont()->setBold(true);

                $sheet->getStyle("H{$dataStartRow}:H{$highestRow}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');

                $sheet->getStyle("A7:{$highestCol}{$totalRow}")->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);
            },
        ];
    }

    private function addLogo(AfterSheet $event): void
    {
        try {
            $logoPath = public_path('images/docs/doc_logo.png');

            if (!file_exists($logoPath)) {
                return;
            }

            $drawing = new Drawing();
            $drawing->setName('Logo');
            $drawing->setDescription('Logo');
            $drawing->setPath($logoPath);
            $drawing->setHeight(75);
            $drawing->setCoordinates('A1');
            $drawing->setOffsetX(5);
            $drawing->setOffsetY(5);
            $drawing->setWorksheet($event->sheet->getDelegate());
        } catch (\Exception $e) {
            logger()->warning('Logo not found for Excel export: ' . $e->getMessage());
        }
    }
}
