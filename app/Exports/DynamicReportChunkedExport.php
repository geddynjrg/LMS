<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class DynamicReportChunkedExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithChunkReading, WithCustomValueBinder
{
    private $selectedColumns;
    private $filters;
    private $constituency;
    private $component;

    public function __construct($selectedColumns, $filters, $constituency, $component = null)
    {
        $this->selectedColumns = $selectedColumns;
        $this->filters = $filters;
        $this->constituency = $constituency;
        $this->component = $component;
    }

    public function query()
    {
        // Build query WITHOUT getting results - this is key!
        $query = $this->component->buildSmartQuery();
        $this->component->applySmartFilters($query, $this->constituency);

        // Log the query for debugging
        logger()->info("Export Query Built", [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings()
        ]);

        return $query;
    }

    public function map($row): array
    {
        // Format ONE row at a time (memory efficient)
        $mappedRow = [];
        foreach ($this->selectedColumns as $column) {
            if (array_key_exists($column, $this->filters['filterOptions'] ?? [])) {
                continue;
            }
            $mappedRow[] = $this->component->formatColumnValue($column, $row);
        }
        return $mappedRow;
    }

    public function chunkSize(): int
    {
        // Adjust based on your server memory
        // 1000 is good for most cases, but test with your data
        return 100;
    }

    public function headings(): array
    {
        $headings = [];
        foreach ($this->selectedColumns as $column) {
            if (array_key_exists($column, $this->filters['filterOptions'] ?? [])) {
                continue;
            }
            $headings[] = $this->getColumnLabel($column);
        }
        return $headings;
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
            'groups.name' => 'Group Name',
            'groups.identifier' => 'Group Identifier',
            'groups.reg_number' => 'Registration Number',
            'groups.gfs_code' => 'GFS Code',
            'groups.date_registered' => 'Date Registered',
            'groups.bank' => 'Bank Name',
            'groups.account' => 'Bank Account',
            'groups.category' => 'Group Category',
            'groups.activity' => 'Group Activity',
            'groups.location' => 'Location',
            'groups.box' => 'Box Number',
            'groups.status' => 'Group Status',
            'ward.ward_name' => 'Ward',
            'group_loans.application_date' => 'Application Date',
            'group_loans.disbursement_date' => 'Disbursement Date',
            'group_loans.amount_applied' => 'Amount Applied',
            'group_loans.amount_approved' => 'Amount Approved',
            'group_loans.amount_issued' => 'Amount Issued',
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
