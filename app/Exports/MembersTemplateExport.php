<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class MembersTemplateExport implements WithHeadings, WithEvents
{
    protected $phases;
    protected $yesNo = ['Yes', 'No'];
    protected $loan_types = ['New', 'Resubmission', 'Refinancing', 'Reallocation'];
    protected $gender = ['Male', 'Female'];
    protected $roles = ['Chairperson', 'Secretary', 'Treasurer', 'Member'];

    public function __construct($phases)
    {
        $this->phases = is_array($phases) ? $phases : $phases->toArray();
    }

    public function headings(): array
    {
        return [
            'S/No*',
            'Phase*',
            'Loan Type*',
            'Group Name*',
            'GFS Code*',
            'Member Name*',
            'Member ID*',
            'Member Role*',
            'Phone Number*',
            'Gender*',
            'Disabled*',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestColumn = $event->sheet->getHighestColumn();
                $rowLimit = 2000;

                // 1. Style the header row
                $this->styleHeader($sheet);

                // 2. Add dropdown for Phase column
                if (!empty($this->phases)) {
                    $this->addDropdown($sheet, 'B', $this->phases, $rowLimit);
                }

                $this->addDropdown($sheet, 'C', $this->loan_types, $rowLimit);

                $this->addDropdown($sheet, 'H', $this->roles, $rowLimit);

                $this->addDropdown($sheet, 'J', $this->gender, $rowLimit);

                // 3. Add dropdown for Disabled column (Yes/No)
                $this->addDropdown($sheet, 'K', $this->yesNo, $rowLimit);


                // 4. Format columns
                $this->formatColumns($sheet, $rowLimit);

                $snVal = $sheet->getCell('A2')->getDataValidation();
                $snVal->setType(DataValidation::TYPE_WHOLE)
                    ->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL)
                    ->setAllowBlank(true)
                    ->setShowErrorMessage(true)
                    ->setErrorStyle(DataValidation::STYLE_STOP)
                    ->setErrorTitle('Invalid S/No')
                    ->setError('The S/No must be a whole number (e.g., 1, 2, 3).')
                    ->setFormula1(1) // Minimum value allowed is 1
                    ->setSqref("A2:A{$rowLimit}");

                $this->applyStyles($sheet, $highestColumn, $rowLimit);

                // 5. PROTECT THE SHEET
                $this->protectSheet($sheet, $highestColumn, $rowLimit);

                // 7. Add instructions
                // $this->addInstructions($sheet);
            },
        ];
    }

    /**
     * Style the header row
     */
    private function styleHeader(Worksheet $sheet): void
    {
        $headerRange = 'A1:K1';

        // Header background color (dark blue)
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF1E3A5F');

        // Header font color (white) and bold
        $sheet->getStyle($headerRange)->getFont()
            ->setBold(true)
            ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));

        // Header alignment (center)
        $sheet->getStyle($headerRange)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Add borders to header
        $sheet->getStyle($headerRange)->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF000000'));
    }

    /**
     * Apply styles to the worksheet
     */
    private function applyStyles(Worksheet $sheet, string $highestColumn, int $maxRows): void
    {
        $startRow = 2; // Data starts at row 2
        $fullRange = "A1:{$highestColumn}{$maxRows}";
        $headerRange = "A1:{$highestColumn}1";
        $dataRange = "A2:{$highestColumn}{$maxRows}";

        // Main Body Style - including text wrapping
        $sheet->getStyle($fullRange)->applyFromArray([
            'font' => ['name' => 'Tahoma', 'size' => 10],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFCCCCCC'],
                ],
            ],
            'alignment' => [
                'wrapText' => true, // Enable text wrapping
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Header Style
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1E3A5F'], // Dark blue
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        // Style GFS Code column as text
        $sheet->getStyle("E{$startRow}:E{$maxRows}")
            ->getNumberFormat()
            ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

        // Set specific column widths
        foreach (range('A', $highestColumn) as $col) {
            if (in_array($col, ['D', 'E', 'F'])) {
                // Disable auto-size for these and set a fixed width
                $sheet->getColumnDimension($col)->setAutoSize(false);
                $sheet->getColumnDimension($col)->setWidth(24);
            } else {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        // Set row height to accommodate wrapped text
        for ($row = 2; $row <= $maxRows; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(-1); // -1 = auto-height
        }
    }

    /**
     * Format columns properly
     */
    private function formatColumns(Worksheet $sheet, int $rowLimit): void
    {
        // Format S/No column as numbers
        $sheet->getStyle("A2:A{$rowLimit}")
            ->getNumberFormat()
            ->setFormatCode('0');

        // Format all other data columns as text
        $sheet->getStyle("B2:K{$rowLimit}")
            ->getNumberFormat()
            ->setFormatCode('@');
    }

    /**
     * Add dropdown validation to a column
     */
    private function addDropdown(Worksheet $sheet, string $column, array $options, int $rowLimit): void
    {
        $list = '"' . implode(',', $options) . '"';
        $validation = $sheet->getCell("{$column}2")->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setAllowBlank(false)
            ->setShowDropDown(true)
            ->setShowErrorMessage(true)
            ->setErrorTitle('Invalid Selection')
            ->setError('Please select a value from the dropdown list.')
            ->setFormula1($list)
            ->setSqref("{$column}2:{$column}{$rowLimit}");
    }

    /**
     * Protect the sheet - lock structure, unlock data entry cells
     */
    private function protectSheet(Worksheet $sheet, string $highestColumn, int $rowLimit): void
    {
        // Unlock ALL cells first (simpler approach)
        $sheet->getStyle("A2:{$highestColumn}{$rowLimit}")
            ->getProtection()
            ->setLocked(Protection::PROTECTION_UNPROTECTED);

        // Lock the header row (row 1)
        $sheet->getStyle("A1:{$highestColumn}1")
            ->getProtection()
            ->setLocked(Protection::PROTECTION_PROTECTED);

        $sheet->getStyle("A2:{$highestColumn}{$rowLimit}")
                ->getProtection()
                ->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);


        // Protect the sheet
        $sheet->getProtection()->setSheet(true);
        $sheet->getProtection()->setPassword('');

        $sheet->freezePane('A2');
        // Disable all formatting/structural changes
        // $sheet->getProtection()->setFormatCells(false);
        // $sheet->getProtection()->setFormatColumns(false);
        // $sheet->getProtection()->setFormatRows(false);
        // $sheet->getProtection()->setInsertRows(false);
        // $sheet->getProtection()->setDeleteRows(false);
        // $sheet->getProtection()->setInsertColumns(false);
        // $sheet->getProtection()->setDeleteColumns(false);
        // $sheet->getProtection()->setSort(false);
        // $sheet->getProtection()->setAutoFilter(false);
    }

    /**
     * Add instructions for users
     */
    private function addInstructions(Worksheet $sheet): void
    {
        $sheet->setCellValue('M1', '📋 INSTRUCTIONS:');
        $sheet->setCellValue('M2', '• Fill in your data starting from row 2');
        $sheet->setCellValue('M3', '• S/No should be sequential numbers (1, 2, 3...)');
        $sheet->setCellValue('M4', '• Phase: Select from dropdown');
        $sheet->setCellValue('M5', '• Disabled: Select Yes or No from dropdown');
        $sheet->setCellValue('M6', '• All other fields: Free text');
        $sheet->setCellValue('M7', '• Phone format: 07XXXXXXXX or 01XXXXXXXX');
        $sheet->setCellValue('M8', '• GFS Code must exist in system');

        $sheet->getStyle('M1:M8')->getFont()->setBold(true);
        $sheet->getStyle('M1')->getFont()->setSize(12);
        $sheet->getStyle('M1')->getFont()->setUnderline(true);

        // Auto-size instructions column
        $sheet->getColumnDimension('M')->setAutoSize(true);
    }
}
