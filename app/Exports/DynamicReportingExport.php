<?php

namespace App\Exports;

use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Services\ReportService;

class DynamicReportingExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithChunkReading,
    ShouldQueue,
    WithEvents,
    WithStyles
{
    protected $query;
    protected $queryType;
    protected $dataColumns;
    protected $reportService;
    private $reportName;
    private $county;
    private $constituency;

    public function __construct(
        $query,
        array $dataColumns,
        string $queryType,
        ReportService $reportService,
        string $reportName,
        $county = null,
        $constituency = null
    ) {
        $this->query         = $query;
        $this->queryType     = $queryType;
        $this->dataColumns   = $dataColumns;
        $this->reportService = $reportService;
        $this->reportName    = $reportName;
        $this->county        = $county;
        $this->constituency  = $constituency;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        $columnHeaders = ['SNo.']; // Column A = Serial Number

        foreach ($this->dataColumns as $column) {
            $columnHeaders[] = $this->getColumnLabel($column);
        }

        $totalColumns = count($columnHeaders);

        return [
            // Row 1
            array_fill(0, $totalColumns, 'REPUBLIC OF KENYA'),

            // Row 2
            array_fill(0, $totalColumns, 'MINISTRY OF COOPERATIVES AND MSMES DEVELOPMENT'),

            // Row 3
            array_fill(0, $totalColumns, 'UWEZO FUND OVERSIGHT BOARD'),

            // Row 4 - Report name
            array_fill(0, $totalColumns, $this->reportName ?: 'DYNAMIC REPORT SUBMISSION FORM'),

            // Row 5 - COUNTY | CONSTITUENCY | F/Y | AS AT
            $this->createPositionedHeaderRow($totalColumns),

            // Row 6 - Empty spacing
            array_fill(0, $totalColumns, ''),

            // Row 7 - Actual column headers
            $columnHeaders
        ];
    }

    private function createPositionedHeaderRow($totalColumns)
    {
        $row = array_fill(0, $totalColumns, '');

        // COUNTY (column A)
        $row[0] = 'COUNTY: ' . strtoupper($this->county->county_name ?? 'N/A');

        // CONSTITUENCY (column B)
        $row[1] = 'CONSTITUENCY: ' . strtoupper($this->constituency->constituency_name ?? 'N/A');

        // F/Y (column C)
        $currentYear = now()->year;
        $fy = now()->month >= 7
            ? $currentYear . '/' . ($currentYear + 1)
            : ($currentYear - 1) . '/' . $currentYear;
        $row[2] = 'F/Y: ' . $fy;

        // AS AT (last column)
        $row[$totalColumns - 1] = 'AS AT ' . now()->format('jS F, Y');

        return $row;
    }

    public function map($row): array
    {
        $mapped = [''];

        foreach ($this->dataColumns as $column) {
            $mapped[] = $this->reportService->formatColumnByType($column, $row, $this->queryType, true);
        }

        return $mapped;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $this->getLastDataColumn();
        $lastColumnWithSerial = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($lastColumn) + 1
        );

        // Merge header rows
        $sheet->mergeCells("A1:{$lastColumnWithSerial}1");
        $sheet->mergeCells("A2:{$lastColumnWithSerial}2");
        $sheet->mergeCells("A3:{$lastColumnWithSerial}3");
        $sheet->mergeCells("A4:{$lastColumnWithSerial}4");
        $sheet->mergeCells("A5:{$lastColumnWithSerial}5");

        return [
            // Row 1–3: Main titles
            'A8:' . $lastColumnWithSerial . $sheet->getHighestRow() => [
                'alignment' => [
                    'wrapText' => true,
                    'vertical' => Alignment::VERTICAL_TOP
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ]
            ],
            1 => [
                'font' => ['bold' => true, 'size' => 14],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ],
            2 => [
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ],
            3 => [
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ],
            // Row 4: Report name
            4 => [
                'font' => ['bold' => true, 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ],
            // Row 5: County / Constituency / FY / As At
            5 => [
                'font' => ['bold' => true, 'size' => 10],
                'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER]
            ],
            // Row 7: Column headers
            7 => [
                'font' => ['bold' => true, 'size' => 11],
                'alignment' => ['wrapText' => true, 'horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9E1F2']
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ]
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->setColumnWidths($event);
                $this->addLogo($event);
                $this->addSerialNumbersToAllRows($event);
            },
        ];
    }

    private function setColumnWidths(AfterSheet $event)
    {
        // Serial No column
        $event->sheet->getColumnDimension('A')->setWidth(6);

        $colIndex = 2; // B onwards

        foreach ($this->dataColumns as $column) {
            $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $dimension = $event->sheet->getColumnDimension($letter);

            // Fixed widths (no auto-size to prevent truncation)
            $dimension->setAutoSize(false);

            if (str_contains($column, 'name') || str_contains($column, 'group') || str_contains($column, 'location')) {
                $dimension->setWidth(40); // Wide for names/groups/locations
            } elseif (str_contains($column, 'date') || str_contains($column, 'registered')) {
                $dimension->setWidth(18);
            } elseif (str_contains($column, 'amount') || str_contains($column, 'arrears') || str_contains($column, 'balance')) {
                $dimension->setWidth(18);
            } elseif (str_contains($column, 'code') || str_contains($column, 'gfs') || str_contains($column, 'number') || str_contains($column, 'id')) {
                $dimension->setWidth(20);
            } else {
                $dimension->setWidth(25); // Default
            }

            $colIndex++;
        }
    }

    private function addSerialNumbersToAllRows(AfterSheet $event)
    {
        $highestRow = $event->sheet->getHighestRow();

        for ($row = 8; $row <= $highestRow; $row++) {
            $serial = $row - 7;
            $event->sheet->setCellValue("A{$row}", $serial);

            $event->sheet->getStyle("A{$row}")->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ]
            ]);
        }
    }

    private function addLogo(AfterSheet $event)
    {
        try {
            $logoPath = public_path('images/docs/doc_logo.png');
            if (file_exists($logoPath)) {
                $drawing = new Drawing();
                $drawing->setName('Logo');
                $drawing->setDescription('Uwezo Fund Logo');
                $drawing->setPath($logoPath);
                $drawing->setHeight(75);
                $drawing->setCoordinates('A1');
                $drawing->setOffsetX(10);
                $drawing->setOffsetY(10);
                $drawing->setWorksheet($event->sheet->getDelegate());
            }
        } catch (\Exception $e) {
            // Silent fail
        }
    }

    private function getLastDataColumn()
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($this->dataColumns) + 1);
    }

    private function getColumnLabel($column)
    {
        $columnLabels = [
            'members.member_Id' => 'Member ID',
            'members.name' => 'Member Name',
            'members.number' => 'Phone Number',
            'members.gender' => 'Gender',
            'members.disabled' => 'PWD Status',
            'group_members.role' => 'Member Role',
            'groups.name' => 'Name of the group/institution',
            'groups.code' => 'Group Code',
            'groups.identifier' => 'Group Identifier',
            'groups.reg_number' => 'Registration Number',
            'group_loans.gfs_code' => 'G.F.S.CODE',
            'groups.date_registered' => 'Date Registered',
            'groups.bank' => 'Bank Name',
            'groups.account' => 'Bank Account',
            'group_loans.category' => 'Category',
            'group_loans.sector' => 'Sector',
            'groups.location' => 'Location',
            'groups.box' => 'Box Number',
            'groups.status' => 'Group Status',
            'location.ward_name' => 'Ward',
            'location.constituency_name' => 'Constituency',
            'location.county_name' => 'County',
            'group_loans.application_date' => 'Application Date',
            'group_loans.disbursement_date' => 'Date of Disbursement',
            'group_loans.amount_applied' => 'Amount Applied',
            'group_loans.amount_approved' => 'Amount Approved (KShs)',
            'group_loans.amount_issued' => 'Actual Amount Issued (KShs)',
            'group_loans.amount_repaid' => 'Amount Repaid',
            'group_loans.loan_status' => 'Loan Status',
            'group_loans.loan_level' => 'Loan Level',
            'group_loans.loan_type' => 'Loan Type',
            'group_loans.cheque_no' => 'Cheque Number',
            'group_loans.status' => 'Application Status',
            'group_loans.comments' => 'Comments',
            'loan_phase.phase' => 'Loan Phase',
            'group_loans.total_arrears' => 'Outstanding Balance',
            'group_loans.amount_due' => 'Amount Due',
            'arrears' => 'Cumulative Arrears',
            'defaulter_status' => 'Defaulter Status',
            'repayment_date' => 'Date of Repayment Completion',
        ];

        return $columnLabels[$column] ?? $column;
    }

    // 'location.ward_name' => 'Ward',
    //         'location.constituency_name' => 'Constituency',
    //         'location.county_name' => 'County',
}
