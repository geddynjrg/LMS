<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DynamicReportChunkedExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithChunkReading, WithCustomValueBinder, WithEvents, WithStyles
{
    private $selectedColumns;
    private $filters;
    private $constituency;
    private $component;
    private $reportName;
    private $county;
    private $data = [];
    private $serialNumbers = [];
    private $dataRows = 0;

    public function __construct($selectedColumns, $filters, $constituency, $component = null, $reportName = null)
    {
        $this->selectedColumns = $selectedColumns;
        $this->filters = $filters;
        $this->constituency = $constituency;
        $this->component = $component;
        $this->reportName = $reportName;
        $this->county = $constituency->county ?? (object)['county_name' => 'UNKNOWN COUNTY'];

        // Pre-load data for serial numbers
        $this->preloadData();
    }

    private function preloadData()
    {
        // Get the query and data to determine row count for serial numbers
        $query = $this->component->buildSmartQuery();
        $this->component->applySmartFilters($query, $this->constituency);

        // Get all data for mapping (this is for small datasets, for large datasets we'll handle differently)
        $this->data = $query->get();
        $this->dataRows = count($this->data) + 7;

        // Generate serial numbers
        $this->serialNumbers = range(1, count($this->data));
    }

    public function query()
    {
        $query = $this->component->buildSmartQuery();
        $this->component->applySmartFilters($query, $this->constituency);
        return $query;
    }

    // In your DynamicReportWithTemplateExport class
    public function map($row): array
    {
        // Apply defaulter filter if enabled
        if ($this->filters['filterDefaulter'] ?? false) {
            $calculatedFields = $this->component->getCalclatedFieldsForLoan(
                $row->disbursement_date,
                $row->amount_issued,
                $row->amount_repaid
            );

            // Skip non-defaulters (same logic as formatResults)
            if (($calculatedFields['amount_due'] - $row->amount_repaid) <= 0) {
                return []; // Return empty array to skip this row
            }
        }

        $mappedRow = [];

        // Add serial number as first column
        $currentIndex = count($mappedRow);
        $serialNumber = $this->serialNumbers[$currentIndex] ?? ($currentIndex + 1);
        $mappedRow[] = $serialNumber;

        // Add the actual data columns
        foreach ($this->selectedColumns as $column) {
            if (array_key_exists($column, $this->filters['filterOptions'] ?? [])) {
                continue;
            }
            $mappedRow[] = $this->component->formatColumnValue($column, $row, true);
        }

        return $mappedRow;
    }

    public function chunkSize(): int
    {
        return 500;
    }

    private function autoSizeColumns(AfterSheet $event)
    {
        $dataColumns = array_filter($this->selectedColumns, function($column) {
            return !array_key_exists($column, $this->filters['filterOptions'] ?? []);
        });

        // Auto-size serial number column (Column A)
        $event->sheet->getColumnDimension('A')->setAutoSize(true);

        $colIndex = 2; // Start from column B (after serial number)

        foreach ($dataColumns as $column) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
            $columnDimension = $event->sheet->getColumnDimension($columnLetter);

            // Auto-size based on content
            $columnDimension->setAutoSize(true);

            // Apply maximum widths based on column type
            $calculatedWidth = $columnDimension->getWidth();

            if (str_contains($column, 'name') || str_contains($column, 'group')) {
                // Names and groups can be wider but not too wide
                if ($calculatedWidth > 40) {
                    $columnDimension->setAutoSize(false);
                    $columnDimension->setWidth(40);
                }
            } elseif (str_contains($column, 'description') || str_contains($column, 'comments')) {
                // Descriptions and comments limited
                if ($calculatedWidth > 30) {
                    $columnDimension->setAutoSize(false);
                    $columnDimension->setWidth(30);
                }
            } elseif (str_contains($column, 'date')) {
                // Dates have fixed width
                $columnDimension->setAutoSize(false);
                $columnDimension->setWidth(15);
            } elseif (str_contains($column, 'amount') || str_contains($column, 'arrears')) {
                // Amount columns
                $columnDimension->setAutoSize(false);
                $columnDimension->setWidth(18);
            } elseif (str_contains($column, 'code') || str_contains($column, 'gfs')) {
                // Code columns can be wider
                if ($calculatedWidth > 25) {
                    $columnDimension->setAutoSize(false);
                    $columnDimension->setWidth(25);
                }
            } else {
                // Default maximum width
                if ($calculatedWidth > 20) {
                    $columnDimension->setAutoSize(false);
                    $columnDimension->setWidth(20);
                }
            }

            $colIndex++;
        }
    }

    // public function headings(): array
    // {
    //     $dataColumns = array_filter($this->selectedColumns, function($column) {
    //         return !array_key_exists($column, $this->filters['filterOptions'] ?? []);
    //     });

    //     $columnHeaders = ['SNo.']; // Start with serial number column
    //     foreach ($dataColumns as $column) {
    //         $columnHeaders[] = $this->getColumnLabel($column);
    //     }

    //     // Build the official template structure
    //     return [
    //         // Official Government Header Structure
    //         ['REPUBLIC OF KENYA'],
    //         ['MINISTRY OF COOPERATIVES AND MSMES DEVELOPMENT'],
    //         ['UWEZO FUND OVERSIGHT BOARD'],
    //         ['COUNTY: ' . strtoupper($this->county->county_name)],
    //         [$this->reportName ?: 'DYNAMIC REPORT SUBMISSION FORM'],
    //         ['CONSTITUENCY: ' . strtoupper($this->constituency->constituency_name) . ' AS AT ' . now()->format('jS F, Y')],
    //         [''], // Empty row for spacing
    //         $columnHeaders // Actual column headers (includes SNo.)
    //     ];
    // }

    public function headings(): array
    {
        $dataColumns = array_filter($this->selectedColumns, function($column) {
            return !array_key_exists($column, $this->filters['filterOptions'] ?? []);
        });

        $columnHeaders = ['SNo.']; // Start with serial number column
        foreach ($dataColumns as $column) {
            $columnHeaders[] = $this->getColumnLabel($column);
        }

        $totalColumns = count($columnHeaders);

        return [
            // Row 1: REPUBLIC OF KENYA (merged across all columns)
            array_fill(0, $totalColumns, 'REPUBLIC OF KENYA'),

            // Row 2: MINISTRY OF COOPERATIVES AND MSMES DEVELOPMENT (merged)
            array_fill(0, $totalColumns, 'MINISTRY OF COOPERATIVES AND MSMES DEVELOPMENT'),

            // Row 3: UWEZO FUND OVERSIGHT BOARD (merged)
            array_fill(0, $totalColumns, 'UWEZO FUND OVERSIGHT BOARD'),

            // Row 4: Split header - each part in specific columns
            array_fill(0, $totalColumns, $this->reportName ?: 'DYNAMIC REPORT SUBMISSION FORM'),

            // Row 5: Report name (merged)
            $this->createPositionedHeaderRow($totalColumns),

            // Row 6: Empty row for spacing
            array_fill(0, $totalColumns, ''),

            // Row 7: Column headers
            $columnHeaders
        ];
    }

    private function createPositionedHeaderRow($totalColumns)
    {
        $row = array_fill(0, $totalColumns, '');

        // A5:1 - COUNTY
        $row[1] = 'COUNTY: ' . strtoupper($this->county->county_name);

        // A5:2 - CONSTITUENCY
        $row[2] = 'CONSTITUENCY: ' . strtoupper($this->constituency->constituency_name);

        // A5:3 - F/Y // if now we are in 2025 any month from 1st jul - 30th june 2026 then F/Y is 2025/2026
        $row[3] = 'F/Y: ' . (now()->month >= 7 ? now()->format('Y') . '/' . now()->addYear()->format('Y') : now()->subYear()->format('Y') . '/' . now()->format('Y'));

        // A5:lastcolumn - AS AT
        $row[$totalColumns - 1] = 'AS AT ' . now()->format('jS F, Y');

        return $row;
    }

    public function styles(Worksheet $sheet)
    {
        // Merge header cells for the official look
        $lastColumn = $this->getLastDataColumn();

        // Adjust for serial number column
        $lastColumnWithSerial = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(
            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($lastColumn) + 1
        );

        $sheet->mergeCells('A1:' . $lastColumnWithSerial . '1');
        $sheet->mergeCells('A2:' . $lastColumnWithSerial . '2');
        $sheet->mergeCells('A3:' . $lastColumnWithSerial . '3');
        $sheet->mergeCells('A4:' . $lastColumnWithSerial . '4');
        // $sheet->mergeCells('A4:' . $lastColumnWithSerial . '5');
        // $sheet->mergeCells('A4:' . $lastColumnWithSerial . '6');

        return [
            // Main title - REPUBLIC OF KENYA
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ],
            // MINISTRY OF COOPERATIVES AND MSMES DEVELOPMENT
            2 => [
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ],
            // UWEZO FUND OVERSIGHT BOARD
            3 => [
                'font' => ['bold' => true, 'size' => 12],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ],
            // Row 5: Report name
            4 => [
                'font' => ['bold' => true, 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]
            ],
            // Constituency line
            5 => [
                'font' => ['bold' => true, 'size' => 10],
                'alignment' => ['wrapText' => true,'vertical' => Alignment::VERTICAL_CENTER]
            ],


            // Column headers (row 6)
            7 => [
                'font' => ['bold' => true, 'size' => 11],
                'alignment' => ['wrapText' => true, 'horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D9E1F2'] // Light blue background
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ]
            ],
            // Data rows (starting from row 7)
            'A8:' . $lastColumnWithSerial . $this->dataRows  => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
                'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER]
            ],
            // Serial number column alignment
            'A8:A' . $this->dataRows => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
            ],
            // Amount columns alignment
            // $this->getAmountColumnsRange() => [
            //     'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
            // ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Set column widths automatically
                $this->setColumnWidths($event);
                //$this->autoSizeColumns($event);

                // Set row heights for headers
                $event->sheet->getRowDimension(1)->setRowHeight(25);
                $event->sheet->getRowDimension(2)->setRowHeight(25);
                $event->sheet->getRowDimension(3)->setRowHeight(25);
                $event->sheet->getRowDimension(4)->setRowHeight(25);
                $event->sheet->getRowDimension(6)->setRowHeight(25);

                // Add logo (if available)
                $this->addLogo($event);

                // Add serial numbers to all rows
                $this->addSerialNumbersToAllRows($event);

                // Explicitly set cell data types for numeric columns
                //$this->setCellDataTypes($event);
            },
        ];
    }

    private function getLastDataColumn()
    {
        $dataColumnCount = count(array_filter($this->selectedColumns, function($column) {
            return !array_key_exists($column, $this->filters['filterOptions'] ?? []);
        }));

        if ($dataColumnCount === 0) return 'A';

        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($dataColumnCount);
    }

    private function setColumnWidths(AfterSheet $event)
    {
        // Set serial number column width
        $event->sheet->getColumnDimension('A')->setWidth(8);

        $dataColumns = array_filter($this->selectedColumns, function($column) {
            return !array_key_exists($column, $this->filters['filterOptions'] ?? []);
        });

        $columnIndex = 2; // Start from column B (after serial number)

        foreach ($dataColumns as $column) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);

            // Set widths based on column type
            if (str_contains($column, 'name') || str_contains($column, 'group')) {
                $event->sheet->getColumnDimension($columnLetter)->setWidth(30);
            } elseif (str_contains($column, 'date')) {
                $event->sheet->getColumnDimension($columnLetter)->setWidth(15);
            } elseif (str_contains($column, 'amount') || str_contains($column, 'arrears')) {
                $event->sheet->getColumnDimension($columnLetter)->setWidth(18);
            } elseif (str_contains($column, 'code') || str_contains($column, 'gfs')) {
                $event->sheet->getColumnDimension($columnLetter)->setWidth(25);
            } else {
                $event->sheet->getColumnDimension($columnLetter)->setWidth(15);
            }

            $columnIndex++;
        }
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
                $drawing->setHeight(50); // Adjust height as needed
                $drawing->setCoordinates('A1');
                $drawing->setOffsetX(10);
                $drawing->setOffsetY(10);
                $drawing->setWorksheet($event->sheet->getDelegate());

                // Adjust header row height to accommodate logo
                $event->sheet->getRowDimension(1)->setRowHeight(60);
            }

        } catch (\Exception $e) {
            // Logo not found or error loading - continue without logo
            logger()->warning('Logo not found for Excel export: ' . $e->getMessage());
        }
    }

    private function addSerialNumbersToAllRows(AfterSheet $event)
    {
        $dataCount = count($this->data);
        if ($dataCount === 0) return;

        // Add serial numbers to all data rows (starting from row 8)
        for ($i = 0; $i < $dataCount; $i++) {
            $rowNumber = $i + 8; // Data starts at row 8
            $serialNumber = $i + 1;
            $event->sheet->setCellValue("A{$rowNumber}", $serialNumber);

            // Style the serial number cells
            $event->sheet->getStyle("A{$rowNumber}")->applyFromArray([
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
            'groups.identifier' => 'Group Identifier',
            'groups.reg_number' => 'Registration Number',
            'groups.gfs_code' => 'G.F.S.CODE',
            'groups.date_registered' => 'Date Registered',
            'groups.bank' => 'Bank Name',
            'groups.account' => 'Bank Account',
            'groups.category' => 'Category',
            'groups.activity' => 'Sector',
            'groups.location' => 'Location',
            'groups.box' => 'Box Number',
            'groups.status' => 'Group Status',
            'ward.ward_name' => 'Ward',
            'group_loans.application_date' => 'Application Date',
            'group_loans.disbursement_date' => 'Date of Disbursement',
            'group_loans.amount_applied' => 'Amount Applied',
            'group_loans.amount_approved' => 'Amount Approved (KShs)',
            'group_loans.amount_issued' => 'Actual Amount Issued (KShs)',
            'group_loans.amount_repaid' => 'Amount Repaid',
            'group_loans.loan_status' => 'Loan Status',
            'group_loans.level' => 'Loan Level',
            'group_loans.cheque_no' => 'Cheque Number',
            'group_loans.status' => 'Application Status',
            'group_loans.comments' => 'Comments',
            'loan_phase.phase' => 'Loan Phase',
            'arrears' => 'Outstanding Balance',
            'amount_due' => 'Amount Due',
            'cumulative_arrears' => 'Cumulative Arrears',
            'repayment_date' => 'Date of Repayment Completion',
        ];

        return $columnLabels[$column] ?? $column;
    }
}
