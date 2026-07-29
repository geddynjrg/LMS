<?php

namespace App\Exports;

use App\Models\ConstituencyAllocationProfile;
use App\Models\LoanPhase;
use App\Support\CodeListRecordMapper;
use App\Support\ConstituencyAllocationFields;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CodeListExport extends DefaultValueBinder implements
    FromQuery,
    WithMapping,
    WithHeadings,
    WithStyles,
    WithEvents,
    WithCustomStartCell,
    WithChunkReading,
    WithTitle,
    WithColumnWidths,
    WithCustomValueBinder
{
    use Exportable;

    private const FONT_NAME = 'Tahoma';
    private const FONT_SIZE = 12;
    private const DATA_START_ROW = 8;
    private const HEADER_ROW = 7;
    private const LAST_DATA_COLUMN = 'K';

    protected $query;
    protected int $rowNumber = 0;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function startCell(): string
    {
        return 'A' . self::HEADER_ROW;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'S/No.',
            'CODE',
            'GROUP NAME',
            'GFS CODE',
            'APPLIED',
            'CURRENT GROUP ACTIVITY',
            'PREVIOUS LOAN AMOUNT',
            'LOAN LEVEL',
            'RECOMMENDED',
            'CURRENT STATUS',
            'BOARD REMARKS',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 6,
            'B' => 8,
            'C' => 28,
            'D' => 24,
            'E' => 12,
            'F' => 14,
            'G' => 14,
            'H' => 9.86,
            'I' => 19,
            'J' => 12,
            'K' => 14,
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        if (is_numeric($value)) {
            $cell->setValueExplicit((float) $value, DataType::TYPE_NUMERIC);

            return true;
        }

        if ($value === 0 || $value === 0.0 || $value === '0') {
            $cell->setValueExplicit(0, DataType::TYPE_NUMERIC);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function map($app): array
    {
        $this->rowNumber++;

        return [
            $this->rowNumber,
            CodeListRecordMapper::groupCode($app),
            CodeListRecordMapper::groupName($app),
            CodeListRecordMapper::gfsCode($app),
            CodeListRecordMapper::amountApplied($app),
            CodeListRecordMapper::currentActivity($app),
            CodeListRecordMapper::previousLoanAmount($app),
            CodeListRecordMapper::loanLevel($app),
            CodeListRecordMapper::amountApproved($app),
            CodeListRecordMapper::currentStatus($app),
            CodeListRecordMapper::boardRemarks($app),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();

                $firstRecord = $this->query->first();
                if (! $firstRecord) {
                    return;
                }

                $phase = CodeListRecordMapper::resolvePhase($firstRecord);
                if (! $phase) {
                    return;
                }

                $phase->loadMissing('constituency.county');
                $constituencyName = $phase->constituency->constituency_name ?? 'N/A';
                $constCode = $phase->constituency_code ?? 'N/A';
                $countyName = $phase->constituency->county->county_name ?? 'N/A';
                $phaseLabel = $phase->phase ?? 'N/A';

                $highestRow = max($sheet->getHighestRow(), self::DATA_START_ROW);
                $totalRow = $highestRow + 1;

                $sheet->getParent()->getDefaultStyle()->getFont()
                    ->setName(self::FONT_NAME)
                    ->setSize(self::FONT_SIZE);

                $this->writeDocumentHeader($sheet, $phaseLabel, $constituencyName, $constCode, $countyName);
                $this->addLogo($sheet);
                $this->writeTotalsRow($sheet, $highestRow, $totalRow);
                $this->styleMainTable($sheet, $highestRow, $totalRow);

                $summaryEndRow = $this->writeFinancialSummary(
                    $sheet,
                    $constCode,
                    $totalRow,
                    $phaseLabel,
                    $phase
                );

                $this->applyWorkbookFont($sheet, max($summaryEndRow, $totalRow));
            },
        ];
    }

    private function writeDocumentHeader(
        Worksheet $sheet,
        string $phaseLabel,
        string $constituencyName,
        string $constCode,
        string $countyName,
    ): void {
        for ($row = 1; $row <= 6; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(18);
        }
        $sheet->getRowDimension(1)->setRowHeight(22);

        $sheet->setCellValue('B1', 'UWEZO FUND');
        $sheet->setCellValue('B2', 'PROCESSED CODE LIST ' . strtoupper($phaseLabel));
        $sheet->setCellValue('B3', 'Republic of Kenya');
        $sheet->setCellValue('B4', 'Uwezo Fund Oversight Board');
        $sheet->setCellValue(
            'B5',
            'Name of Constituency: ' . strtoupper($constituencyName) . ' | IEBC Code: ' . $constCode . ' | County: ' . strtoupper($countyName)
        );
        $sheet->setCellValue('B6', 'For the Month Ended: ' . now()->format('d F Y'));

        foreach (range(1, 6) as $row) {
            $sheet->mergeCells("B{$row}:" . self::LAST_DATA_COLUMN . "{$row}");
            $sheet->getStyle("B{$row}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("B{$row}")->getFont()->setBold(true);
        }
    }

    private function writeTotalsRow(Worksheet $sheet, int $highestRow, int $totalRow): void
    {
        $dataStartRow = self::DATA_START_ROW;

        $sheet->setCellValue("C{$totalRow}", 'TOTAL');
        $sheet->setCellValue("E{$totalRow}", "=SUM(E{$dataStartRow}:E{$highestRow})");
        $sheet->setCellValue("G{$totalRow}", "=SUM(G{$dataStartRow}:G{$highestRow})");
        $sheet->setCellValue("I{$totalRow}", "=SUM(I{$dataStartRow}:I{$highestRow})");

        $sheet->getStyle("C{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("C{$totalRow}:I{$totalRow}")->getFont()->setBold(true);
    }

    private function styleMainTable(Worksheet $sheet, int $highestRow, int $totalRow): void
    {
        $sheet->getRowDimension(self::HEADER_ROW)->setRowHeight(50.25);

        $sheet->getStyle('A' . self::HEADER_ROW . ':' . self::LAST_DATA_COLUMN . self::HEADER_ROW)->applyFromArray([
            'font' => ['bold' => true, 'name' => self::FONT_NAME, 'size' => self::FONT_SIZE],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E9E9E9'],
            ],
        ]);

        $sheet->getStyle('A' . self::HEADER_ROW . ':' . self::LAST_DATA_COLUMN . $totalRow)
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach (['E', 'G', 'I'] as $column) {
            $sheet->getStyle("{$column}" . self::DATA_START_ROW . ":{$column}{$totalRow}")
                ->getNumberFormat()
                ->setFormatCode('#,##0');
        }

        foreach (['A', 'B', 'H', 'J', 'K'] as $column) {
            $sheet->getStyle("{$column}" . self::DATA_START_ROW . ":{$column}{$highestRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        $sheet->getStyle('C' . self::DATA_START_ROW . ':C' . $highestRow)
            ->getAlignment()
            ->setWrapText(true)
            ->setVertical(Alignment::VERTICAL_TOP);

        $sheet->getStyle('D' . self::DATA_START_ROW . ':D' . $highestRow)
            ->getAlignment()
            ->setWrapText(true);
    }

    private function writeFinancialSummary(
        Worksheet $sheet,
        string $constituencyCode,
        int $totalRow,
        string $phaseLabel,
        LoanPhase $phase,
    ): int {
        app(\App\Services\Constituency\ConstituencyAllocationService::class)
            ->syncAllSubmittedPhasesForConstituency($constituencyCode, $phase);

        $profile = ConstituencyAllocationProfile::query()
            ->where('constituency_code', $constituencyCode)
            ->first();

        $labelCol = 'G';
        $valueCol = 'I';
        $row = $totalRow + 2;

        if ($profile) {
            foreach ($profile->normalizedAllocationYears() as $allocationYear) {
                $sheet->setCellValue("{$labelCol}{$row}", ConstituencyAllocationFields::allocationLabelForYears(
                    (int) $allocationYear['year_start'],
                    (int) $allocationYear['year_end']
                ));
                $sheet->setCellValue("{$valueCol}{$row}", (float) $allocationYear['amount']);
                $row++;
            }

            $sheet->setCellValue("{$labelCol}{$row}", 'Total Allocations');
            $sheet->setCellValue("{$valueCol}{$row}", $profile->totalAllocations());
            $sheet->getStyle("{$labelCol}{$row}:{$valueCol}{$row}")->getFont()->setBold(true);
            $row += 2;

            foreach ($profile->normalizedTransferLevels() as $transfer) {
                $sheet->setCellValue("{$labelCol}{$row}", ConstituencyAllocationFields::transferLabelForLevel((int) $transfer['level']));
                $sheet->setCellValue("{$valueCol}{$row}", (float) $transfer['amount']);
                $row++;
            }

            $sheet->setCellValue("{$labelCol}{$row}", 'Total Transfers');
            $sheet->setCellValue("{$valueCol}{$row}", $profile->totalTransfers());
            $sheet->getStyle("{$labelCol}{$row}:{$valueCol}{$row}")->getFont()->setBold(true);
            $row++;

            $portfolioRow = $row;
            $sheet->setCellValue("{$labelCol}{$row}", 'Total Loan Portfolio');
            $sheet->setCellValue("{$valueCol}{$row}", $profile->totalLoanPortfolio());
            $sheet->getStyle("{$labelCol}{$row}:{$valueCol}{$row}")->getFont()->setBold(true);
            $row += 2;

            $phaseAmountRows = [];
            foreach ($profile->normalizedPhaseIssuedAmounts() as $phaseRow) {
                $sheet->setCellValue("{$labelCol}{$row}", ConstituencyAllocationFields::phaseIssuedLabel((int) $phaseRow['phase']));
                $sheet->setCellValue("{$valueCol}{$row}", (float) $phaseRow['amount']);
                $phaseAmountRows[] = $row;
                $row++;
            }

            $sheet->setCellValue("{$labelCol}{$row}", 'TOTAL APPROVALS');
            $sheet->setCellValue("{$valueCol}{$row}", $profile->totalApprovals());
            $approvalsRow = $row;
            $sheet->getStyle("{$labelCol}{$row}:{$valueCol}{$row}")->getFont()->setBold(true);
            $row += 2;

            $sheet->setCellValue("{$labelCol}{$row}", 'BALANCE IN THE MAIN ACCOUNT');
            $sheet->setCellValue("{$valueCol}{$row}", "={$valueCol}{$portfolioRow}-{$valueCol}{$approvalsRow}");
            $sheet->getStyle("{$labelCol}{$row}:{$valueCol}{$row}")->getFont()->setBold(true);
            $row += 2;

            if ($profile->computedBalanceMainAccount() < 0) {
                $sheet->setCellValue("{$labelCol}{$row}", 'NB* REQUIRES TRANSFER');
                $sheet->getStyle("{$labelCol}{$row}")->getFont()->setBold(true);
                $sheet->getStyle("{$labelCol}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->mergeCells("{$labelCol}{$row}:{$valueCol}{$row}");
            }

            $summaryStart = $totalRow + 2;
            $sheet->getStyle("{$labelCol}{$summaryStart}:{$valueCol}{$row}")
                ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            foreach (range($summaryStart, $row) as $summaryRow) {
                $sheet->getStyle("{$valueCol}{$summaryRow}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0');
                $sheet->getStyle("{$valueCol}{$summaryRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }
        } else {
            $sheet->setCellValue("{$labelCol}{$row}", 'Actual Amount Issued in ' . $phaseLabel);
            $sheet->setCellValue("{$valueCol}{$row}", "={$valueCol}{$totalRow}");
            $sheet->getStyle("{$valueCol}{$row}")->getNumberFormat()->setFormatCode('#,##0');
        }

        $sheet->getColumnDimension($labelCol)->setWidth(30);

        return $row;
    }

    private function applyWorkbookFont(Worksheet $sheet, int $lastRow): void
    {
        $sheet->getStyle('A1:' . self::LAST_DATA_COLUMN . $lastRow)->getFont()
            ->setName(self::FONT_NAME)
            ->setSize(self::FONT_SIZE);
    }

    private function addLogo(Worksheet $sheet): void
    {
        try {
            $logoPath = public_path('images/docs/doc_logo.png');

            if (! file_exists($logoPath)) {
                return;
            }

            $drawing = new Drawing();
            $drawing->setName('Uwezo Fund Logo');
            $drawing->setDescription('Republic of Kenya / Uwezo Fund');
            $drawing->setPath($logoPath);
            $drawing->setHeight(88);
            $drawing->setCoordinates('A1');
            $drawing->setOffsetX(6);
            $drawing->setOffsetY(4);
            $drawing->setWorksheet($sheet);

            $sheet->getColumnDimension('A')->setWidth(16);
        } catch (\Exception $e) {
            logger()->warning('Logo not found for Excel export: ' . $e->getMessage());
        }
    }

    public function styles(Worksheet $sheet): array
    {
        return [];
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function title(): string
    {
        return 'PROCESSED CODE LIST';
    }
}
