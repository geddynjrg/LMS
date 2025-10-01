<?php

namespace App\Exports;

use App\Models\LoanPhase;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
class LoanSubmExport  implements FromView, WithStyles, WithTitle, WithEvents
{
    protected $loanPhases;
    protected $totalAmountApproved;
    protected $totalAmountIssued;
    protected $totalAmountRepaid;

    public function __construct()
    {
        $code = session('constituency')->constituency_code;

        $this->loanPhases = LoanPhase::where('constituency_code', $code)
        ->with(['grouploans' => function($query) {
            $query->whereIn('loan_status', ['financed', 'cancelled', 'approved']);
        }, 'grouploans.group'])
        ->orderBy('id')
        ->get()
        ->map(function ($phase) {
            $phase->grouploans = $phase->grouploans->sortBy(function ($grouploan) {
                return array_search($grouploan->level, ['New', 'Resubmission', 'Refinancing']);
            });
            return $phase;
        });

         // Calculate total amount across all phases
        $this->totalAmountIssued = $this->loanPhases->flatMap(function ($phase) {
            return $phase->grouploans;
        })->sum('amount_issued');

        $this->totalAmountRepaid = $this->loanPhases->flatMap(function ($phase) {
            return $phase->grouploans;
        })->sum('amount_repaid');

        $this->totalAmountApproved = $this->loanPhases->flatMap(function ($phase) {
            return $phase->grouploans;
        })->sum('amount_approved');
    }

    public function view(): View
    {
        return view('exports.loan_submision', [
            'phases' => $this->loanPhases,
            'totalAmountApproved' => $this->totalAmountApproved,
            'totalAmountIssued' => $this->totalAmountIssued,
            'totalAmountRepaid'=> $this->totalAmountRepaid,
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'name' => 'Times New Roman', 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['wrapText' => true, 'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['rgb' => '00BCD4'] // Cyan background for header
                ]
            ],
            'A:J' => [
                'alignment' => ['wrapText' => true],
                'font' => ['name' => 'Calibri', 'size' => 8]
            ],
            'A1:J1' => [
                'font' => ['name' => 'Times New Roman', 'size' => 10]
            ],
        ];
    }


    public function title(): string
    {
        return 'Loan Subm(F.5) Codelist Return';
    }

 
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Set column widths
                $columnWidths = [
                    'A' => 7,
                    'B' => 14,
                    'C' => 22,
                    'D' => 28,
                    'E' => 12,
                    'F' => 11,
                    'G' => 13,
                    'H' => 12,
                    'I' => 12,
                    'J' => 13,
                ];

                foreach ($columnWidths as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                // Apply borders to the entire sheet
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                $sheet->getStyle("A1:$highestColumn$highestRow")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => '000000']
                        ]
                    ]
                ]);

                // Apply styles to the first column starting from row 2
                for ($row = 2; $row <= $highestRow; $row++) {
                    $sheet->getStyle("A$row")->applyFromArray([
                        'font' => ['bold' => true],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'color' => ['rgb' => '00BCD4']
                        ]
                    ]);
                }

                // Apply styles to subtotal and total rows
                for ($row = 2; $row <= $highestRow; $row++) {
                    $cellValue = $sheet->getCell("B$row")->getValue();
                    if (strpos($cellValue, 'Phase') !== false || strpos($cellValue, 'Totals') !== false) {
                        $sheet->getStyle("A$row:J$row")->applyFromArray([
                            'font' => ['bold' => true, 'name' => 'Calibri', 'size' => 10, 'color' => ['rgb' => '00040A']],
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'color' => ['rgb' => '9FABBF'] // Grey background for subtotals and totals
                            ],
                            'alignment' => [
                                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                            ]
                        ]);
                    }
                }
            }
        ];
    }
}
