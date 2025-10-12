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
use Illuminate\Support\Facades\Auth;

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
    public $isCategorySelected = true;

    // Updated available columns with new structure
    public $availableColumns = [
        'phase' => [
            // This will be populated dynamically
        ],
        'ward' => [
            // This will be populated dynamically
        ],
        'group' => [
            'group_name' => 'Group Name',
            'group_identifier' => 'Group Identifier',
            'group_gfs_code' => 'GFS Code',
            'group_reg_number' => 'Registration Number',
            'group_date_registered' => 'Date Registered',
            'group_bank_name' => 'Bank Name',
            'group_bank_account' => 'Bank Account',
            'group_category' => 'Group Category',
            'group_activity' => 'Group Activity',
        ],
        'loan' => [
            'loan_amount' => 'Loan Amount',
            'amount_repaid' => 'Amount Repaid',
            'arrears' => 'Arrears',
            'disbursement_date' => 'Disbursement Date',
            'loan_status' => 'Loan Status',
            'application_date' => 'Application Date',
        ],
        'member' => [
            'member_Id' => 'Member ID',
            'name' => 'Full Name',
            'number' => 'Phone Number',
            'gender' => 'Gender',
            'disabled' => 'PWD Status',
            'member_role' => 'Member Role',
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

    protected function ensureSelectedColumnsIsArray()
    {
        // Force selectedColumns to be an array
        if (!is_array($this->selectedColumns)) {
            if (is_bool($this->selectedColumns)) {
                $this->selectedColumns = [];
            } elseif (is_string($this->selectedColumns)) {
                $this->selectedColumns = [$this->selectedColumns];
            } else {
                $this->selectedColumns = [];
            }
        }
    }

    public function mount(Constituency $constituency)
    {
        $this->constituency = $constituency;
        $this->constituencyName = $constituency->constituency_name;
        $this->constituencyCode = $constituency->constituency_code;

        $this->cacheTtl = config('cache-config.reports.ttl', 1800);
        $this->cachePrefix = config('cache-config.reports.prefix', 'dynamic_reports');

        $this->constituencies = Constituency::orderBy('constituency_name')->get();

        // Set default selected columns - ensure it's always an array
        $this->selectedColumns = [
            'member_Id', 'name', 'number', 'gender', 'group_name', 'loan_amount', 'arrears'
        ];

        // Automatically set the selected constituency from authenticated user
        $this->selectedConstituency = auth()->user()->current_constituency_code;

        // Update dynamic columns immediately
        $this->updateDynamicColumns();
    }

    public function hydrate()
    {
        $this->ensureSelectedColumnsIsArray();
    }

    public function updating($property, $value)
    {
        // Ensure selectedColumns is always an array when updating
        if ($property === 'selectedColumns') {
            $this->ensureSelectedColumnsIsArray();
        }
    }

    public function updated($property, $value)
    {
        // Ensure selectedColumns is always an array after updating
        if ($property === 'selectedColumns') {
            $this->ensureSelectedColumnsIsArray();
        }
    }

    // Get the authenticated user's constituency
    public function getUserConstituency()
    {
        return auth()->user()->current_constituency_code;
    }

    // Get the authenticated user's constituency name
    public function getUserConstituencyName()
    {
        return auth()->user()->currentConstituency->constituency_name ?? 'Unknown Constituency';
    }

    // Update dynamic columns based on user's constituency
    protected function updateDynamicColumns()
    {
        $userConstituency = $this->getUserConstituency();

        if (!$userConstituency) {
            return;
        }

        // Get phases for the user's constituency
        $phases = LoanPhase::where('constituency_code', $userConstituency)
            ->orderBy('phase')
            ->get();

        // Get wards for the user's constituency
        $wards = Wards::where('constituency_code', $userConstituency)
            ->orderBy('ward_name')
            ->get();

        // Update phase columns dynamically
        $phaseColumns = [];
        foreach ($phases as $phase) {
            $key = 'phase_' . str_replace(' ', '_', strtolower($phase->phase));
            $phaseColumns[$key] = $phase->phase;
        }
        $this->availableColumns['phase'] = $phaseColumns;

        // Update ward columns dynamically
        $wardColumns = [];
        foreach ($wards as $ward) {
            $key = 'ward_' . str_replace(' ', '_', strtolower($ward->ward_name));
            $wardColumns[$key] = $ward->ward_name;
        }
        $this->availableColumns['ward'] = $wardColumns;
    }

    // Get dynamic columns for display
    public function getDynamicColumns($type)
    {
        $userConstituency = $this->getUserConstituency();

        if (!$userConstituency) {
            return [];
        }

        if ($type === 'phase') {
            return LoanPhase::where('constituency_code', $userConstituency)
                ->orderBy('phase')
                ->get();
        }

        if ($type === 'ward') {
            return Wards::where('constituency_code', $userConstituency)
                ->orderBy('ward_name')
                ->get();
        }

        return [];
    }

    // Optimized category selection check with type safety
    public function isCategorySelected($category)
    {
        if (!isset($this->availableColumns[$category])) {
            return false;
        }

        $this->ensureSelectedColumnsIsArray();

        $categoryColumns = array_keys($this->availableColumns[$category]);
        $selectedInCategory = array_intersect($categoryColumns, $this->selectedColumns);

        return count($selectedInCategory) === count($categoryColumns);
    }

    // Check if all columns are selected
    public function isAllSelected()
    {
        $this->ensureSelectedColumnsIsArray();
        $allColumns = $this->getAllAvailableColumns();

        return count($this->selectedColumns) === count($allColumns);
    }

    // Toggle entire category
    public function toggleCategory($category)
    {
        if (!isset($this->availableColumns[$category])) {
            return;
        }

        $this->ensureSelectedColumnsIsArray();

        $categoryColumns = array_keys($this->availableColumns[$category]);

        if ($this->isCategorySelected($category)) {
            // Remove all columns from this category
            $this->selectedColumns = array_values(array_diff($this->selectedColumns, $categoryColumns));
        } else {
            // Add all columns from this category
            $this->selectedColumns = array_values(array_unique(array_merge($this->selectedColumns, $categoryColumns)));
        }
    }

    // Toggle all columns
    public function toggleAllColumns()
    {
        $this->ensureSelectedColumnsIsArray();
        $allColumns = $this->getAllAvailableColumns();

        if (count($this->selectedColumns) === count($allColumns)) {
            $this->selectedColumns = [];
        } else {
            $this->selectedColumns = $allColumns;
        }
    }

    // Clear all columns
    public function clearAllColumns()
    {
        $this->selectedColumns = [];
    }

    // Reset to default columns
    public function resetToDefault()
    {
        $this->selectedColumns = ['member_Id', 'name', 'number', 'gender', 'group_name', 'loan_amount', 'arrears'];
    }

    // Remove single column
    public function removeColumn($column)
    {
        $this->ensureSelectedColumnsIsArray();
        $this->selectedColumns = array_values(array_filter($this->selectedColumns, function ($col) use ($column) {
            return $col !== $column;
        }));
    }

    // Helper method to get all available columns
    private function getAllAvailableColumns()
    {
        return array_merge(
            array_keys($this->availableColumns['phase']),
            array_keys($this->availableColumns['ward']),
            array_keys($this->availableColumns['group']),
            array_keys($this->availableColumns['loan']),
            array_keys($this->availableColumns['member'])
        );
    }

    // Get column category for display
    public function getColumnCategory($column)
    {
        foreach ($this->availableColumns as $category => $columns) {
            if (array_key_exists($column, $columns)) {
                return $category;
            }
        }
        return 'member';
    }

    public function generateReport()
    {
        $this->ensureSelectedColumnsIsArray();

        $userConstituency = $this->getUserConstituency();

        if (!$userConstituency) {
            $this->dispatch('report-error', [
                'message' => 'No constituency assigned to your account. Please contact administrator.'
            ]);
            return;
        }

        $this->validate([
            'selectedColumns' => 'required|array|min:1',
        ]);

        $this->loading = true;
        $this->reportGenerated = false;

        $cacheKey = $this->getCacheKey();
        $cacheTags = ['reports', "constituency:{$userConstituency}"];

        try {
            $this->reportData = Cache::tags($cacheTags)->remember($cacheKey, $this->cacheTtl, function() {
                return $this->fetchReportData();
            });

            $this->totalRecords = count($this->reportData);
            $this->reportGenerated = true;

            $this->dispatch('report-generated', [
                'message' => "Report generated successfully with {$this->totalRecords} records",
                'constituency' => $this->getUserConstituencyName()
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
        $userConstituency = $this->getUserConstituency();

        if (!$userConstituency) {
            return [];
        }

        // Start with GroupMember as the base model since it connects members to loans
        $query = GroupMember::with([
            'member',
            'groupLoan.loanPhase',
            'groupLoan.group.ward',
            'groupLoan.loanReceipts'
        ])->whereHas('groupLoan.loanPhase', function($query) use ($userConstituency) {
            $query->where('constituency_code', $userConstituency);
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

        // Handle dynamic phase columns
        if (str_starts_with($column, 'phase_')) {
            $phaseName = str_replace('_', ' ', substr($column, 6));
            return $loanPhase && $loanPhase->phase === $phaseName ? 'Yes' : 'No';
        }

        // Handle dynamic ward columns
        if (str_starts_with($column, 'ward_')) {
            $wardName = str_replace('_', ' ', substr($column, 5));
            return $ward && $ward->ward_name === $wardName ? 'Yes' : 'No';
        }

        switch ($column) {
            // Group columns
            case 'group_name':
                return $group->name ?? 'N/A';
            case 'group_identifier':
                return $group->identifier ?? 'N/A';
            case 'group_gfs_code':
                return $group->gfs_code ?? 'N/A';
            case 'group_reg_number':
                return $group->reg_number ?? 'N/A';
            case 'group_date_registered':
                return $group->date_registered ?
                    \Carbon\Carbon::parse($group->date_registered)->format('d/m/Y') : 'N/A';
            case 'group_bank_name':
                return $group->bank ?? 'N/A';
            case 'group_bank_account':
                return $group->account ?? 'N/A';
            case 'group_category':
                return $group->category ?? 'N/A';
            case 'group_activity':
                return $group->activity ?? 'N/A';

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
            case 'application_date':
                return $groupLoan->application_date ?
                    \Carbon\Carbon::parse($groupLoan->application_date)->format('d/m/Y') : 'N/A';

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
            case 'member_role':
                return $groupMember->role ?? 'N/A';

            default:
                return 'N/A';
        }
    }

    private function getCacheKey()
    {
        $this->ensureSelectedColumnsIsArray();
        $selectedColumns = $this->selectedColumns;
        sort($selectedColumns);
        $columnsHash = md5(implode('', $selectedColumns));
        $userConstituency = $this->getUserConstituency();
        return "{$this->cachePrefix}_{$userConstituency}_{$columnsHash}";
    }

    public function refreshReport()
    {
        $cacheKey = $this->getCacheKey();
        $userConstituency = $this->getUserConstituency();
        $cacheTags = ['reports', "constituency:{$userConstituency}"];

        Cache::tags($cacheTags)->forget($cacheKey);

        $this->generateReport();
    }

    public function exportToExcel()
    {
        if (empty($this->reportData)) {
            $this->dispatch('export-error', ['message' => 'No data to export']);
            return;
        }

        $userConstituency = $this->getUserConstituency();
        $fileName = "members_report_{$userConstituency}_" . now()->format('Y_m_d_His') . '.csv';

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

        $userConstituency = $this->getUserConstituency();
        $fileName = "members_report_{$userConstituency}_" . now()->format('Y_m_d_His') . '.txt';

        return response()->streamDownload(function () {
            echo $this->generatePDFContent();
        }, $fileName);
    }

    private function generateExcelContent()
    {
        $this->ensureSelectedColumnsIsArray();
        $headers = array_map(function($col) {
            return $this->availableColumns[$this->getColumnCategory($col)][$col] ?? $col;
        }, $this->selectedColumns);

        $output = "\"" . implode("\",\"", $headers) . "\"\n";

        foreach ($this->reportData as $row) {
            $output .= "\"" . implode("\",\"", array_values($row)) . "\"\n";
        }

        return $output;
    }

    // Add these computed properties to your Livewire component
    public function getCategoryPhaseSelectedProperty()
    {
        return $this->isCategorySelected('phase');
    }

    public function getCategoryWardSelectedProperty()
    {
        return $this->isCategorySelected('ward');
    }

    public function getCategoryGroupSelectedProperty()
    {
        return $this->isCategorySelected('group');
    }

    public function getCategoryLoanSelectedProperty()
    {
        return $this->isCategorySelected('loan');
    }

    public function getCategoryMemberSelectedProperty()
    {
        return $this->isCategorySelected('member');
    }

    private function generatePDFContent()
    {
        $this->ensureSelectedColumnsIsArray();
        $content = "MEMBERS REPORT - " . $this->getUserConstituencyName() . "\n";
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

    public function render()
    {
        $this->ensureSelectedColumnsIsArray();

        // Update dynamic columns on every render to ensure they're current
        $this->updateDynamicColumns();

        return view('livewire.reports.dynamic-reports');
    }
}
