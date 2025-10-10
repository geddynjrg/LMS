<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Member;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\GroupLoan;
use App\Models\Constituency;
use App\Models\Wards;
use App\Models\LoanPhase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class DynamicReports extends Component
{
    public $constituencies = [];
    public $selectedConstituency = '';

    public $selectedWard = '';
    public $wards = [];
    public $county = null;
    public $constituency = null;
    public $constituencyName = '';
    public $constituencyCode = '';


    // Available columns with proper table relationships
    public $availableColumns = [
        'member' => [
            'member_Id' => 'Member ID',
            'name' => 'Full Name',
            'number' => 'Phone Number',
            'gender' => 'Gender',
            'disabled' => 'PWD Status',
        ],
        'group' => [
            'group_name' => 'Group Name',
            'group_code' => 'Group Code',
            'group_category' => 'Group Category',
            'group_activity' => 'Group Activity',
            'ward_name' => 'Ward Name',
        ],
        'loan' => [
            'loan_amount' => 'Loan Amount',
            'amount_repaid' => 'Amount Repaid',
            'arrears' => 'Arrears',
            'disbursement_date' => 'Disbursement Date',
            'loan_status' => 'Loan Status',
            'loan_phase' => 'Loan Phase',
            'application_date' => 'Application Date',
        ],
        'repayment' => [
            'total_repayments' => 'Total Repayments',
            'last_repayment_date' => 'Last Repayment Date',
            'last_repayment_amount' => 'Last Repayment Amount',
        ]
    ];

    public $selectedColumns = [];
    public $reportData = [];
    public $loading = false;
    public $reportGenerated = false;
    public $totalRecords = 0;

    // Cache configuration
    protected $cacheTtl;
    protected $cachePrefix;

    public function mount(Constituency $constituency)
    {
        $this->constituency = $constituency;
        $this->constituencyName = $constituency->constituency_name;
        $this->constituencyCode = $constituency->constituency_code;


        $this->cacheTtl = config('cache-config.reports.ttl', 1800);
        $this->cachePrefix = config('cache-config.reports.prefix', 'dynamic_reports');

        $this->constituencies = Constituency::orderBy('constituency_name')->get();

        // Set default selected columns
        $this->selectedColumns = [
            'member_Id', 'name', 'number', 'gender', 'group_name', 'loan_amount', 'arrears'
        ];
    }

    public function generateReport()
    {
        $this->validate([
            'selectedConstituency' => 'required|exists:constituencies,constituency_code',
            'selectedColumns' => 'required|array|min:1',
        ]);

        $this->loading = true;
        $this->reportGenerated = false;

        $cacheKey = $this->getCacheKey();
        $cacheTags = ['reports', "constituency:{$this->selectedConstituency}"];

        try {
            $this->reportData = Cache::tags($cacheTags)->remember($cacheKey, $this->cacheTtl, function() {
                return $this->fetchReportData();
            });

            $this->totalRecords = count($this->reportData);
            $this->reportGenerated = true;

            $this->dispatch('report-generated', [
                'message' => "Report generated successfully with {$this->totalRecords} records",
                'constituency' => $this->getSelectedConstituencyName()
            ]);

        } catch (\Exception $e) {
            $this->dispatch('report-error', [
                'message' => 'Error generating report: ' . $e->getMessage()
            ]);
        }

        $this->loading = false;
    }

    private function fetchReportData()
    {
        // Start with GroupMember as the base model since it connects members to loans
        $query = GroupMember::with([
            'member',
            'groupLoan.loanPhase',
            'groupLoan.group.ward',
            'groupLoan.loanReceipts'
        ])->whereHas('groupLoan.loanPhase', function($query) {
            $query->where('constituency_code', $this->selectedConstituency);
        });

        $groupMembers = $query->get();

        return $groupMembers->map(function (GroupMember $groupMember) {
            return $this->formatRowData($groupMember);
        })->filter()->values()->toArray();
    }

    private function formatRowData(GroupMember $groupMember)
    {
        $member = $groupMember->member;
        $groupLoan = $groupMember->groupLoan;
        $group = $groupLoan->group ?? null;
        $ward = $group->ward ?? null;
        $loanPhase = $groupLoan->loanPhase ?? null;
        $loanReceipts = $groupLoan->loanReceipts ?? collect();

        $row = [];

        foreach ($this->selectedColumns as $column) {
            $row[$column] = $this->getColumnValue($column, [
                'member' => $member,
                'group' => $group,
                'ward' => $ward,
                'groupLoan' => $groupLoan,
                'loanPhase' => $loanPhase,
                'loanReceipts' => $loanReceipts,
                'groupMember' => $groupMember
            ]);
        }

        return $row;
    }

    private function getColumnValue($column, $data)
    {
        extract($data);

        switch ($column) {
            // Member columns
            case 'member_Id':
                return $member->member_Id ?? 'N/A';
            case 'name':
                return $member->name ?? 'N/A';
            case 'number':
                return $member->number ?? 'N/A';
            case 'gender':
                return $member->gender ?? 'N/A';
            case 'disabled':
                return isset($member->disabled) ? ($member->disabled ? 'Yes' : 'No') : 'N/A';

            // Group columns
            case 'group_name':
                return $group->name ?? 'N/A';
            case 'group_code':
                return $group->code ?? 'N/A';
            case 'group_category':
                return $group->category ?? 'N/A';
            case 'group_activity':
                return $group->activity ?? 'N/A';
            case 'ward_name':
                return $ward->ward_name ?? 'N/A';

            // Loan columns
            case 'loan_amount':
                return $groupLoan->amount_issued ? number_format($groupLoan->amount_issued, 2) : '0.00';
            case 'amount_repaid':
                return $groupLoan->amount_repaid ? number_format($groupLoan->amount_repaid, 2) : '0.00';
            case 'arrears':
                $arrears = ($groupLoan->amount_issued ?? 0) - ($groupLoan->amount_repaid ?? 0);
                return number_format(max($arrears, 0), 2);
            case 'disbursement_date':
                return $groupLoan->disbursement_date ?
                    \Carbon\Carbon::parse($groupLoan->disbursement_date)->format('d/m/Y') : 'N/A';
            case 'loan_status':
                return $groupLoan->loan_status ?? 'N/A';
            case 'loan_phase':
                return $loanPhase->phase ?? 'N/A';
            case 'application_date':
                return $groupLoan->application_date ?
                    \Carbon\Carbon::parse($groupLoan->application_date)->format('d/m/Y') : 'N/A';

            // Repayment columns
            case 'total_repayments':
                return $loanReceipts->count();
            case 'last_repayment_date':
                $lastReceipt = $loanReceipts->sortByDesc('transaction_date')->first();
                return $lastReceipt ?
                    \Carbon\Carbon::parse($lastReceipt->transaction_date)->format('d/m/Y') : 'N/A';
            case 'last_repayment_amount':
                $lastReceipt = $loanReceipts->sortByDesc('transaction_date')->first();
                return $lastReceipt ? number_format($lastReceipt->amount, 2) : '0.00';

            default:
                return 'N/A';
        }
    }

    public function exportToExcel()
    {
        if (empty($this->reportData)) {
            $this->dispatch('export-error', ['message' => 'No data to export']);
            return;
        }

        $fileName = "members_report_{$this->selectedConstituency}_" . now()->format('Y_m_d_His') . '.csv';

        return response()->streamDownload(function () {
            echo $this->generateExcelContent();
        }, $fileName);
    }

    public function exportToPDF()
    {
        if (empty($this->reportData)) {
            $this->dispatch('export-error', ['message' => 'No data to export']);
            return;
        }

        $fileName = "members_report_{$this->selectedConstituency}_" . now()->format('Y_m_d_His') . '.txt';

        return response()->streamDownload(function () {
            echo $this->generatePDFContent();
        }, $fileName);
    }

    private function generateExcelContent()
    {
        $headers = array_map(function($col) {
            return $this->availableColumns[$this->getColumnCategory($col)][$col] ?? $col;
        }, $this->selectedColumns);

        $output = "\"" . implode("\",\"", $headers) . "\"\n";

        foreach ($this->reportData as $row) {
            $output .= "\"" . implode("\",\"", array_values($row)) . "\"\n";
        }

        return $output;
    }

    private function generatePDFContent()
    {
        $content = "MEMBERS REPORT - " . $this->getSelectedConstituencyName() . "\n";
        $content .= "Generated on: " . now()->format('d/m/Y H:i:s') . "\n";
        $content .= "Total Records: " . $this->totalRecords . "\n\n";

        $headers = array_map(function($col) {
            return $this->availableColumns[$this->getColumnCategory($col)][$col] ?? $col;
        }, $this->selectedColumns);

        // Create a simple table format
        $columnWidths = array_fill(0, count($headers), 15);

        $content .= implode(" | ", array_map(function($header, $width) {
            return str_pad($header, $width);
        }, $headers, $columnWidths)) . "\n";

        $content .= str_repeat("-", array_sum($columnWidths) + (count($headers) * 3)) . "\n";

        foreach (array_slice($this->reportData, 0, 50) as $row) { // Limit to first 50 for PDF
            $content .= implode(" | ", array_map(function($value, $width) {
                return str_pad(substr($value, 0, $width - 1), $width);
            }, array_values($row), $columnWidths)) . "\n";
        }

        if ($this->totalRecords > 50) {
            $content .= "\n... and " . ($this->totalRecords - 50) . " more records\n";
        }

        return $content;
    }

    private function getColumnCategory($column)
    {
        foreach ($this->availableColumns as $category => $columns) {
            if (array_key_exists($column, $columns)) {
                return $category;
            }
        }
        return 'member';
    }

    private function getCacheKey()
    {
        sort($this->selectedColumns);
        $columnsHash = md5(implode('', $this->selectedColumns));
        return "{$this->cachePrefix}_{$this->selectedConstituency}_{$columnsHash}";
    }

    private function getSelectedConstituencyName()
    {
        $constituencies = collect($this->constituencies);
        $constituency = $constituencies->firstWhere('constituency_code', $this->selectedConstituency);
        return $constituency ? $constituency->constituency_name : 'Unknown';
    }

    public function refreshReport()
    {
        $cacheKey = $this->getCacheKey();
        $cacheTags = ['reports', "constituency:{$this->selectedConstituency}"];

        Cache::tags($cacheTags)->forget($cacheKey);

        $this->generateReport();
    }

    public function render()
    {
        return view('livewire.reports.dynamic-reports');
    }
}
