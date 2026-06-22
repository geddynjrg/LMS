<?php

namespace App\Exports;

use App\Models\Constituency;
use App\Models\ConstituencyAllocationProfile;
use App\Support\ConstituencyAllocationFields;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ConstituencyAllocationTemplateExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(
        protected Constituency $constituency,
        protected ?ConstituencyAllocationProfile $profile = null,
    ) {}

    public function array(): array
    {
        $rows = [
            ['UWEZO FUND — CONSTITUENCY ALLOCATION PROFILE'],
            ['Constituency Code', $this->constituency->constituency_code],
            ['Constituency Name', $this->constituency->constituency_name],
            ['County', $this->constituency->county?->county_name ?? ''],
            [],
            ['ALLOCATIONS BY YEAR', 'Amount (KES)'],
        ];

        foreach (ConstituencyAllocationFields::allocationLabels() as $key => $label) {
            $rows[] = [$label, $this->profile?->{$key} ?? 0];
        }

        $rows[] = ['Total Allocations', $this->profile?->totalAllocations() ?? 0];
        $rows[] = [];
        $rows[] = ['TRANSFERS', 'Amount (KES)'];

        foreach ($this->profile?->normalizedTransferLevels() ?? ConstituencyAllocationFields::defaultTransferLevels() as $transfer) {
            $rows[] = [
                ConstituencyAllocationFields::transferLabelForLevel((int) $transfer['level']),
                $transfer['amount'] ?? 0,
            ];
        }

        $rows[] = ['Total Transfers', $this->profile?->totalTransfers() ?? 0];
        $rows[] = ['Total Loan Portfolio', $this->profile?->totalLoanPortfolio() ?? 0];
        $rows[] = [];
        $rows[] = ['PHASE ISSUED AMOUNTS', 'Amount (KES)'];

        $phaseRows = $this->profile?->normalizedPhaseIssuedAmounts() ?? ConstituencyAllocationFields::defaultPhaseIssuedAmounts();

        foreach ($phaseRows as $phaseRow) {
            $rows[] = [
                ConstituencyAllocationFields::phaseIssuedLabel((int) $phaseRow['phase']),
                $phaseRow['amount'] ?? 0,
            ];
        }

        $rows[] = ['TOTAL APPROVALS', $this->profile?->totalApprovals() ?? 0];
        $rows[] = [];
        $rows[] = ['Balance in the Main Account', $this->profile?->balance_main_account ?? ''];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $allocationCount = count(ConstituencyAllocationFields::allocationKeys());
        $transferCount = count($this->profile?->normalizedTransferLevels() ?? ConstituencyAllocationFields::defaultTransferLevels());
        $phaseCount = count($this->profile?->normalizedPhaseIssuedAmounts() ?? ConstituencyAllocationFields::defaultPhaseIssuedAmounts());

        $transfersHeaderRow = 6 + $allocationCount + 3;
        $phasesHeaderRow = $transfersHeaderRow + $transferCount + 5;

        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            6 => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E9E9E9']],
            ],
            $transfersHeaderRow => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E9E9E9']],
            ],
            $phasesHeaderRow => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E9E9E9']],
            ],
        ];
    }

    public function title(): string
    {
        return 'Allocations';
    }
}
