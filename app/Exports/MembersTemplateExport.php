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
    private const DATA_ROW_LIMIT = 50000;

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
                $rowLimit = self::DATA_ROW_LIMIT;

                $this->styleHeader($sheet);

                if (!empty($this->phases)) {
                    $this->addDropdown($sheet, 'B', $this->phases, $rowLimit);
                }

                $this->addDropdown($sheet, 'C', $this->loan_types, $rowLimit);
                $this->addDropdown($sheet, 'H', $this->roles, $rowLimit);
                $this->addDropdown($sheet, 'J', $this->gender, $rowLimit);
                $this->addDropdown($sheet, 'K', $this->yesNo, $rowLimit);

                $this->formatColumns($sheet, $rowLimit);
                $this->addMemberIdValidation($sheet, $rowLimit);

                $snVal = $sheet->getCell('A2')->getDataValidation();
                $snVal->setType(DataValidation::TYPE_WHOLE)
                    ->setOperator(DataValidation::OPERATOR_GREATERTHANOREQUAL)
                    ->setAllowBlank(true)
                    ->setShowErrorMessage(true)
                    ->setErrorStyle(DataValidation::STYLE_STOP)
                    ->setErrorTitle('Invalid S/No')
                    ->setError('The S/No must be a whole number (e.g., 1, 2, 3).')
                    ->setFormula1(1)
                    ->setSqref("A2:A{$rowLimit}");

                $this->applyStyles($sheet, $highestColumn, $rowLimit);
                $this->prepareEditableDataArea($sheet, $highestColumn, $rowLimit);
            },
        ];
    }

    private function styleHeader(Worksheet $sheet): void
    {
        $headerRange = 'A1:K1';

        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF1E3A5F');

        $sheet->getStyle($headerRange)->getFont()
            ->setBold(true)
            ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFFFF'));

        $sheet->getStyle($headerRange)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle($headerRange)->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF000000'));
    }

    private function applyStyles(Worksheet $sheet, string $highestColumn, int $maxRows): void
    {
        $fullRange = "A1:{$highestColumn}{$maxRows}";
        $headerRange = "A1:{$highestColumn}1";

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

        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1E3A5F'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        $sheet->getStyle("E2:E{$maxRows}")
            ->getNumberFormat()
            ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

        foreach (range('A', $highestColumn) as $col) {
            if (in_array($col, ['D', 'E', 'F'])) {
                $sheet->getColumnDimension($col)->setAutoSize(false);
                $sheet->getColumnDimension($col)->setWidth(24);
            } else {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        for ($row = 2; $row <= $maxRows; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(-1);
        }
    }

    private function formatColumns(Worksheet $sheet, int $rowLimit): void
    {
        $sheet->getStyle("A2:A{$rowLimit}")
            ->getNumberFormat()
            ->setFormatCode('0');

        $sheet->getStyle("B2:K{$rowLimit}")
            ->getNumberFormat()
            ->setFormatCode('@');
    }

    private function addMemberIdValidation(Worksheet $sheet, int $rowLimit): void
    {
        $validation = $sheet->getCell('G2')->getDataValidation();
        $validation->setType(DataValidation::TYPE_CUSTOM)
            ->setAllowBlank(false)
            ->setShowInputMessage(true)
            ->setShowErrorMessage(true)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setPromptTitle('Member ID')
            ->setPrompt('National ID: 7-8 digits only (maximum 10 characters). No letters.')
            ->setErrorTitle('Invalid Member ID')
            ->setError('National ID must be 7-8 digits only, with a maximum of 10 characters.')
            ->setFormula1('AND(LEN(G2)<=10,LEN(G2)>=7,ISNUMBER(--G2),OR(LEN(G2)=7,LEN(G2)=8))')
            ->setSqref("G2:G{$rowLimit}");
    }

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
     * Keep data cells editable (no worksheet protection — avoids Excel undo/edit lock issues).
     */
    private function prepareEditableDataArea(Worksheet $sheet, string $highestColumn, int $rowLimit): void
    {
        $sheet->getStyle("A1:{$highestColumn}{$rowLimit}")
            ->getProtection()
            ->setLocked(Protection::PROTECTION_UNPROTECTED);

        $sheet->getProtection()->setSheet(false);
        $sheet->freezePane('A2');
    }
}
