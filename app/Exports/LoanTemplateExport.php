<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use App\Support\GfsExcelColumn;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Protection;


class LoanTemplateExport implements WithHeadings, WithEvents
{
    private const DATA_ROW_LIMIT = 10000;

    protected $wards;
    protected $phases;
    protected $loan_types = ['New', 'Resubmission', 'Refinancing', 'Reallocation'];
    protected $categories = ['Youth', 'Women', 'PWD'];
    protected $sectors    = ['Trade', 'Service', 'Manufacturing', 'Agriculture', 'Other'];

    public function __construct($wards, $phases)
    {
        $this->wards  = is_array($wards)  ? $wards  : $wards->toArray();
        $this->phases = is_array($phases) ? $phases : $phases->toArray();
    }

    public function headings(): array
    {
        return [
            'S/No*',
            'Date of Disbursement*',
            'Name of Group*',
            'Ward*',
            'Loan Phase*',
            'Loan Type*',
            'GFS Code*',
            'Identifier',
            'Category*',
            'Sector*',
            'Amount Approved*',
            'Amount Issued*',
            'Amount Repaid*',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $limit = self::DATA_ROW_LIMIT;
                $highestColumn = $event->sheet->getHighestColumn();

                // 1. HEADER & TOTALS (Rows 1 and 2)
                $sheet->insertNewRowBefore(2, 1);
                $sheet->setCellValue("J2", "TOTALS:");
                $sheet->setCellValue("K2", "=SUBTOTAL(109, K3:K{$limit})");
                $sheet->setCellValue("L2", "=SUBTOTAL(109, L3:L{$limit})");
                $sheet->setCellValue("M2", "=SUBTOTAL(109, M3:M{$limit})");

                $sheet->getStyle("A2:M2")->getFont()->setBold(true);
                $sheet->getStyle("K2:M2")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("A2:M2")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFF3F4F6');

                // 2. DATA VALIDATIONS (Applied to the whole range at once)
                $this->applyDynamicValidations($sheet, $limit);

                // 3. GLOBAL STYLING & DROPDOWNS
                $this->applyStyles($sheet, $highestColumn, $limit);

                $this->addDropdown($sheet, 'D', $this->wards, $limit);
                $this->addDropdown($sheet, 'E', $this->phases, $limit);
                $this->addDropdown($sheet, 'F', $this->loan_types, $limit);
                $this->addDropdown($sheet, 'I', $this->categories, $limit);
                $this->addDropdown($sheet, 'J', $this->sectors, $limit);

                $this->prepareEditableDataArea($sheet, $highestColumn, $limit);

                // Freeze Rows 1 and 2 (Headings and Totals)
                $sheet->freezePane('A3');
            },
        ];
    }

    private function applyDynamicValidations(Worksheet $sheet, int $limit)
    {
        $snVal = $sheet->getCell('A3')->getDataValidation();
        $snVal->setType(DataValidation::TYPE_WHOLE)
            ->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL)
            ->setAllowBlank(true)
            ->setShowErrorMessage(true)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setErrorTitle('Invalid S/No')
            ->setError('The S/No must be a whole number (e.g., 1, 2, 3).')
            ->setFormula1(1) // Minimum value allowed is 1
            ->setSqref("A3:A{$limit}");

        $dateVal = $sheet->getCell('B3')->getDataValidation();
        $dateVal->setType(DataValidation::TYPE_DATE)
            ->setOperator(DataValidation::OPERATOR_BETWEEN)
            ->setAllowBlank(false)
            ->setShowErrorMessage(true)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setErrorTitle('Invalid Disbursement Date')
            ->setError('Date must be between 2013-01-01 and today.')
            // Excel dates in formulas are best handled as numbers or specific strings
            ->setFormula1('DATE(2013,1,1)')
            ->setFormula2('TODAY()')
            ->setSqref("B2:B{$limit}");

        // Name Length
        $nameVal = $sheet->getCell('C3')->getDataValidation();
        $nameVal->setType(DataValidation::TYPE_TEXTLENGTH)
            ->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL)
            ->setAllowBlank(false)
            ->setShowErrorMessage(true)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setError('The Group Name is too short (min 3 characters).')
            ->setFormula1(3)
            ->setSqref("C2:C{$limit}");

        GfsExcelColumn::apply($sheet, 'G', 3, $limit);
    }

    private function applyStyles(Worksheet $sheet, string $highestColumn, int $maxRows): void
    {
        $summaryRow = 101;
        $fullRange = "A1:{$highestColumn}" . ($maxRows + 1); // +1 to include summary row
        $headerRange = "A1:{$highestColumn}1";

        // Main Body Style
        $sheet->getStyle($fullRange)->applyFromArray([
            'font' => ['name' => 'Tahoma', 'size' => 10],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFCCCCCC'],
                ],
            ],
            'alignment' => [
                'wrapText' => true,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Header Style
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF4B5563'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Fixed widths are significantly faster than autosize on large template ranges.
        $columnWidths = [
            'A' => 8,   // S/No
            'B' => 18,  // Date of Disbursement
            'C' => 30,  // Name of Group
            'D' => 22,  // Ward
            'E' => 20,  // Loan Phase
            'F' => 18,  // Loan Type
            'G' => 20,  // GFS Code
            'H' => 18,  // Identifier
            'I' => 16,  // Category
            'J' => 18,  // Sector
            'K' => 20,  // Amount Approved
            'L' => 20,  // Amount Issued
            'M' => 20,  // Amount Repaid
        ];

        foreach ($columnWidths as $column => $width) {
            $sheet->getColumnDimension($column)->setAutoSize(false);
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $sheet->getStyle("A{$summaryRow}:M{$summaryRow}")->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFF3F4F6'],
            ],
        ]);
    }

    private function addDropdown(Worksheet $sheet, string $column, array $options, int $maxRows): void
    {
        if (empty($options)) return;

        $list = '"' . implode(',', array_map('strval', $options)) . '"';
        $validation = $sheet->getCell("{$column}2")->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setAllowBlank(false)
            ->setShowDropDown(true)
            ->setShowErrorMessage(true)
            ->setError('Please select a valid option from the list.')
            ->setFormula1($list)
            ->setSqref("{$column}2:{$column}{$maxRows}");
    }

    /**
     * Keep data cells editable (no worksheet protection — avoids Excel lock errors past row limit).
     */
    private function prepareEditableDataArea(Worksheet $sheet, string $highestColumn, int $rowLimit): void
    {
        $sheet->getStyle("A1:{$highestColumn}{$rowLimit}")
            ->getProtection()
            ->setLocked(Protection::PROTECTION_UNPROTECTED);

        $sheet->getProtection()->setSheet(false);
    }
}
