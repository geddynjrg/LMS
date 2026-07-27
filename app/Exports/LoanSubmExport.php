<?php

namespace App\Exports;

use App\Models\Constituency;
use App\Services\Reports\LoanSubmissionFormCalculator;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Carbon\Carbon;

class LoanSubmExport implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths, WithEvents
{
    protected $constituency;
    protected $phases;
    protected $calculator;
    protected $levelOrder;

    public function __construct(Constituency $constituency, $phases, ?LoanSubmissionFormCalculator $calculator = null)
    {
        $this->constituency = $constituency;
        $this->phases = $phases;
        $this->calculator = $calculator ?? app(LoanSubmissionFormCalculator::class);
        $this->levelOrder = LoanSubmissionFormCalculator::LOAN_TYPE_ORDER;
    }

    public function collection()
    {
        $data = collect();
        $i = 1;

        foreach ($this->phases as $phase) {
            foreach ($this->levelOrder as $level) {
                $levelLoans = $phase->grouploans->filter(function ($grouploan) use ($level) {
                    return $this->calculator->resolveLoanType($grouploan) === $level;
                });

                if ($levelLoans->isNotEmpty()) {
                    $data->push([
                        'S/N' => $phase->phase . ' - ' . $level,
                        'Date of Disbursement' => '',
                        'Name of the group/institution' => '',
                        'G.F.S.CODE' => '',
                        'Sector' => '',
                        'Category' => '',
                        'Amount Approved' => '',
                        'Actual Amount Issued' => '',
                        'Variance' => '',
                        'Amount Due' => '',
                        'Cummulative Amount repaid' => '',
                        'Amount in arrears' => '',
                        'Outstanding Loan Balance' => '',
                        'Date of Repayment completion' => '',
                    ]);

                    foreach ($levelLoans as $grouploan) {
                        $amountIssued = $grouploan->amount_issued ?: 0;
                        $amountRepaid = $grouploan->amount_repaid ?: 0;
                        $amountDue = $grouploan->amount_due ?? $this->calculator->analyzeLoan($grouploan, $phase)['amount_due'];
                        $arrears = $grouploan->arrears ?? $this->calculator->analyzeLoan($grouploan, $phase)['arrears'];

                        $data->push([
                            'S/N' => $i++,
                            'Date of Disbursement' => $grouploan->disbursement_date ? Carbon::parse($grouploan->disbursement_date)->format('Y-m-d') : 'N/A',
                            'Name of the group/institution' => $grouploan->group->name ?? 'N/A',
                            'G.F.S.CODE' => $grouploan->gfs_code ?? 'N/A',
                            'Sector' => $grouploan->sector ?? 'N/A',
                            'Category' => $grouploan->category ?? 'N/A',
                            'Amount Approved' => $grouploan->amount_approved ?: '0',
                            'Actual Amount Issued' => $amountIssued ?: '0',
                            'Variance' => (($grouploan->amount_approved ?: 0) - $amountIssued) ?: '0',
                            'Amount Due' => round($amountDue, 2) ?: '0',
                            'Cummulative Amount repaid' => $amountRepaid ?: '0',
                            'Amount in arrears' => round($arrears, 2) ?: '0',
                            'Outstanding Loan Balance' => ($amountIssued - $amountRepaid) ?: '0',
                            'Date of Repayment completion' => $grouploan->repayment_completion_date
                                ?? $this->calculator->repaymentCompletionDate($grouploan, $phase)
                                ?? 'N/A',
                        ]);
                    }

                    // Add subtotal
                    $data->push([
                        'S/N' => 'SubTotal ' . $phase->phase . ' - ' . $level,
                        'Date of Disbursement' => '',
                        'Name of the group/institution' => '',
                        'G.F.S.CODE' => '',
                        'Sector' => '',
                        'Category' => '',
                        'Amount Approved' => $levelLoans->sum('amount_approved') ?: '0',
                        'Actual Amount Issued' => $levelLoans->sum('amount_issued') ?: '0',
                        'Variance' => ($levelLoans->sum('amount_approved') - $levelLoans->sum('amount_issued')) ?: '0',
                        'Amount Due' => $levelLoans->sum(fn ($loan) => $loan->amount_due ?? $this->calculator->analyzeLoan($loan, $phase)['amount_due']) ?: '0',
                        'Cummulative Amount repaid' => $levelLoans->sum('amount_repaid') ?: '0',
                        'Amount in arrears' => $levelLoans->sum(fn ($loan) => $loan->arrears ?? $this->calculator->analyzeLoan($loan, $phase)['arrears']) ?: '0',
                        'Outstanding Loan Balance' => ($levelLoans->sum('amount_issued') - $levelLoans->sum('amount_repaid'))?: '0',
                        'Date of Repayment completion' => '',
                    ]);
                }
            }
        }

        // Add grand totals
        $allLoans = $this->phases->flatMap->grouploans;
        $data->push([
            'S/N' => 'GRAND TOTALS',
            'Date of Disbursement' => '',
            'Name of the group/institution' => '',
            'G.F.S.CODE' => '',
            'Sector' => '',
            'Category' => '',
            'Amount Approved' => $allLoans->sum('amount_approved'),
            'Actual Amount Issued' => $allLoans->sum('amount_issued'),
            'Variance' => $allLoans->sum('amount_approved') - $allLoans->sum('amount_issued'),
            'Amount Due' => $allLoans->sum(function ($loan) {
                $loanPhase = $this->phases->first(fn ($item) => $item->grouploans->contains('id', $loan->id));

                return $loan->amount_due ?? $this->calculator->analyzeLoan($loan, $loanPhase)['amount_due'];
            }),
            'Cummulative Amount repaid' => $allLoans->sum('amount_repaid'),
            'Amount in arrears' => $allLoans->sum(function ($loan) {
                $loanPhase = $this->phases->first(fn ($item) => $item->grouploans->contains('id', $loan->id));

                return $loan->arrears ?? $this->calculator->analyzeLoan($loan, $loanPhase)['arrears'];
            }),
            'Outstanding Loan Balance' => $allLoans->sum('amount_issued') - $allLoans->sum('amount_repaid'),
            'Date of Repayment completion' => '',
        ]);

        return $data;
    }

    public function headings(): array
    {
        $columnHeaders = [
            'S/No.',
            'Date of Disbursement',
            'Name of the group/institution',
            'G.F.S.CODE',
            'Sector',
            'Category',
            'Amount Approved (KShs)',
            'Actual Amount Issued (KShs)',
            'Variance in Amounts (KShs)',
            'Amount Due (KShs)',
            'Cummulative Amount repaid (KShs)',
            'Amount cummulatively in arrears (KShs)',
            'Outstanding Loan Balance (KShs)',
            'Date of Repayment completion'
        ];

        return [
            // Row 1: REPUBLIC OF KENYA (merged across all columns)
            array_fill(0, 14, 'REPUBLIC OF KENYA'),

            // Row 2: MINISTRY OF CO-OPERATIVES AND MSMES DEVELOPMENT (merged)
            array_fill(0, 14, 'MINISTRY OF COOPERATIVES AND MICRO, SMALL AND MEDIUM ENTERPRISES (MSMEs) DEVELOPMENT'),

            // Row 3: UWEZO FUND OVERSIGHT BOARD (merged)
            array_fill(0, 14, 'UWEZO FUND OVERSIGHT BOARD'),

            // Row 4: Split header - each part in specific columns
            array_fill(0, 14, 'UWEZO FUND LOAN REPAYMENT SUBMISSION FORM'),

            // Row 5: Empty row for spacing
            array_fill(0, 14, ''),

            // Row 6: Report name (merged)
            $this->createPositionedHeaderRow(14),



            // Row 7: Column headers
            $columnHeaders

        ];
    }

    private function createPositionedHeaderRow($totalColumns)
    {
        $row = array_fill(0, $totalColumns, '');

        // A5:2 - COUNTY
        $row[1] = 'COUNTY: ' . strtoupper($this->constituency->county->county_name);

        // A5:4 - CONSTITUENCY
        $row[3] = 'CONSTITUENCY: ' . strtoupper($this->constituency->constituency_name);

        // A5:3 - F/Y // if now we are in 2025 any month from 1st jul - 30th june 2026 then F/Y is 2025/2026
        $row[11] = 'F/Y: ' . (now()->month >= 7 ? now()->format('Y') . '/' . now()->addYear()->format('Y') : now()->subYear()->format('Y') . '/' . now()->format('Y'));

        // A5:lastcolumn - AS AT
        $row[6] = 'AS AT ' . now()->format('jS F, Y');

        return $row;
    }

    public function title(): string
    {
        return strtoupper($this->constituency->constituency_name).'_Loan Submission Report';
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:N1');
        $sheet->mergeCells('A2:N2');
        $sheet->mergeCells('A3:N3');
        $sheet->mergeCells('A4:N4');

        // Set default font and size
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(8);

        return [
            // Main headers (row 1)
            1 => [
                'font' => ['bold' => true, 'name' => 'Times New Roman', 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ],
            // MINISTRY OF COOPERATIVES AND MSMES DEVELOPMENT
            2 => [
                'font' => ['bold' => true, 'name' => 'Times New Roman', 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ],
            // UWEZO FUND OVERSIGHT BOARD
            3 => [
                'font' => ['bold' => true, 'name' => 'Times New Roman', 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ],
            // Row 5: Report name
            4 => [
                'font' => ['bold' => true, 'name' => 'Times New Roman', 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ],
            // Constituency line Times New Roman
            6 => [
                'font' => ['bold' => true, 'name' => 'Times New Roman', 'size' => 10],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]
            ],
            7 => [
                'font' => ['bold' => true, 'name' => 'Tahoma', 'size' => 9],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                ],
            ],
            // Formula headers (row 2)
            8 => [
                'font' => ['bold' => true, 'name' => 'Tahoma', 'size' => 9],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                ],
            ],
            // 'A8:N8' => [
            //     'font' => ['name' => 'Tahoma'],
            //     'alignment' => [
            //         'wrapText' => true,
            //     ],
            // ],

        ];
    }

    // public function styles(Worksheet $sheet)
    // {
    //     $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

    //     return [
    //         // Header style
    //         1 => [
    //             'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    //             'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '3498DB']],
    //             'alignment' => ['horizontal' => 'center', 'vertical' => 'center']
    //         ],

    //         // Section headers
    //         'A:A' => ['alignment' => ['horizontal' => 'left']],
    //         'B:N' => ['alignment' => ['horizontal' => 'center']],

    //         // Numeric columns
    //         'G:N' => ['alignment' => ['horizontal' => 'right']],
    //     ];
    // }

    public function columnWidths(): array
    {
        return [
            'A' => 8,  // S/N
            'B' => 12, // Date of Disbursement
            'C' => 30, // Name of group
            'D' => 25, // GFS CODE
            'E' => 12, // Sector
            'F' => 12, // Category
            'G' => 15, // Amount Approved
            'H' => 15, // Amount Issued
            'I' => 15, // Variance
            'J' => 15, // Amount Due
            'K' => 15, // Amount Repaid
            'L' => 15, // Arrears
            'M' => 15, // Outstanding Balance
            'N' => 12, // Repayment completion
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                $event->sheet->getRowDimension(1)->setRowHeight(15);
                $event->sheet->getRowDimension(2)->setRowHeight(15);
                $event->sheet->getRowDimension(3)->setRowHeight(15);
                $event->sheet->getRowDimension(4)->setRowHeight(15);
                $event->sheet->getRowDimension(6)->setRowHeight(15);

                // Add logo (if available)
                $this->addLogo($event);

                // Add formula headers row
                $sheet->insertNewRowBefore(8, 1);
                $sheet->setCellValue('A8', '');
                $sheet->setCellValue('B8', '');
                $sheet->setCellValue('C8', '');
                $sheet->setCellValue('D8', '');
                $sheet->setCellValue('E8', '');
                $sheet->setCellValue('F8', '');
                $sheet->setCellValue('G8', '');
                $sheet->setCellValue('H8', 'a');
                $sheet->setCellValue('I8', '');
                $sheet->setCellValue('J8', 'b= (a/24 *No of months lapsed since expiry of grace period)');
                $sheet->setCellValue('K8', 'c');
                $sheet->setCellValue('L8', 'd=b-c');
                $sheet->setCellValue('M8', 'e=a-c');
                $sheet->setCellValue('N8', '');

                $currentRow = 9;
                while ($currentRow <= $highestRow+1) {
                    $snoValue = $sheet->getCell('A' . $currentRow)->getValue();

                    // Check if this is a phase header (like "Phase 1")
                    if (is_string($snoValue) && str_starts_with($snoValue, 'Phase') !== false) {
                        // Merge cells for phase header
                        $sheet->mergeCells('A' . $currentRow . ':N' . $currentRow);
                        $sheet->getStyle('A' . $currentRow . ':N' . $currentRow)->applyFromArray([
                            'font' => ['bold' => true, 'size' => 9],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D3D3D3']],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                            ],
                        ]);
                    }

                    // Check if this is a subtotal row
                    if (is_string($snoValue) && str_starts_with($snoValue, 'SubTotal') !== false) {

                        $sheet->getStyle('A' . $currentRow . ':N' . $currentRow)->applyFromArray([
                            'font' => ['bold' => true],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D3D3D3']],
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                            ],
                        ]);
                        $sheet->getStyle('A' . $currentRow . ':F' . $currentRow)->applyFromArray([
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        ]);
                        $sheet->mergeCells('A' . $currentRow . ':F' . $currentRow);

                    }

                    // Check if this is a grand total row
                    if (is_string($snoValue) && str_starts_with($snoValue, 'GRAND') !== false) {
                        $sheet->mergeCells('A' . $currentRow . ':F' . $currentRow);
                        $sheet->getStyle('A' . $currentRow . ':N' . $currentRow)->applyFromArray([
                            'font' => ['bold' => true, 'size' => 9],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D3D3D3']],
                            'borders' => [
                                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                            ],
                        ]);
                        $sheet->getStyle('A' . $currentRow . ':F' . $currentRow)->applyFromArray([
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        ]);
                    }

                    // Apply borders to all data rows
                    $sheet->getStyle('A' . $currentRow . ':N' . $currentRow)->applyFromArray([
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                        ],
                    ]);

                    // Apply number formatting for numeric columns
                    $numericColumns = ['G', 'H', 'I', 'J', 'K', 'L', 'M'];
                    foreach ($numericColumns as $column) {
                        $cellValue = $sheet->getCell($column . $currentRow)->getValue();
                        if (is_numeric($cellValue)) {
                            $sheet->getStyle($column . $currentRow)->getNumberFormat()->setFormatCode('#,##0');
                        }
                    }

                    $currentRow++;
                }

                // $sheet->getStyle("A1:$highestColumn$highestRow")->applyFromArray([
                //     'borders' => [
                //         'allBorders' => [
                //             'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                //             'color' => ['rgb' => '000000']
                //         ]
                //     ]
                // ]);
                $sheet->getStyle("A10:A$highestRow")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D3D3D3']],
                ]);

                $sheet->getSTyle("A10:N$highestRow")->applyFromArray([
                    'font' => ['name' => 'Tahoma'],
                    'alignment' => [
                        'wrapText' => true,
                    ],
                ]);
            },
        ];
    }

    private function addLogo(AfterSheet $event)
    {
        try {
            $logoPath = public_path('images/docs/doc_logo.png'); // Adjust path as needed
            // $logoPath2 = public_path('images/coat-of-arms.png'); // Kenyan coat of arms
            // $logoPath3 = storage_path('app/public/logo.png'); // Another possible location

            $usedLogoPath = null;

            // Check which logo file exists
            if (file_exists($logoPath)) {
                $usedLogoPath = $logoPath;
            }

            if ($usedLogoPath) {
                $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                $drawing->setName('Logo');
                $drawing->setDescription('Logo');
                $drawing->setPath($usedLogoPath);
                $drawing->setHeight(55); // Adjust height as needed
                $drawing->setCoordinates('A1');
                $drawing->setOffsetX(10);
                $drawing->setOffsetY(10);
                $drawing->setWorksheet($event->sheet->getDelegate());
            }

        } catch (\Exception $e) {
            // Logo not found or error loading - continue without logo
            logger()->warning('Logo not found for Excel export: ' . $e->getMessage());
        }
    }
}
