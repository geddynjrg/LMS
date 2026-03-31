<?php

namespace App\Livewire\Hq;

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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
// excels
use Maatwebsite\Excel\Facades\Excel;

// exports
use App\Exports\DynamicReportChunkedExport;
use App\Exports\DynamicReportingExport;

use App\Services\LocationService;
use App\Services\Loan\LoanPhaseService;
use App\Services\ReportService;

class GenerateReport extends Component
{
    protected LocationService $locationService;
    protected LoanPhaseService $phaseService;
    protected ReportService $reportService;

    // Performance and resource management
    public $performanceMode = false;
    public $chunkSize = 500;
    public $memoryLimitMB = 128;
    public $maxDisplayRecords = 1000;

    // Location filters

    public $selectedConstituency = '';
    public $selectedCounty = '';
    public $selectedWard = '';
    public $wards = [];
    public $county = null;
    public $constituency = null;
    public $constituencyName = '';
    public $constituencyCode = '';

    // Pagination and limits
    public $displayLimit = 10;
    public $totalAvailableRecords = 0;
    public $totalActualRecords = 0;
    public $chunkedProcessing = false;
    public $showingLimitedData = false;

    // Report naming
    public $reportName = '';

    // Filter properties
    public $filterCounty = '';
    public $filterConstituency = '';
    public $filterWard = '';
    public $filterLoanPhase = '';
    public $filterGroupCategory = '';
    public $filterGroupActivity = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $filterDefaulter = false;
    public $filterLoanStatus = ['all_loans_filter'];

    public $countyOptions = [];
    public $constituencyOptions = [];
    public $wardsOptions = [];
    public $phaseOptions = [];

    public $selectedLoanStatusFilters = [];

    public $filterFinancedLoans = false;
    public $filterApprovedLoans = false;
    public $filterDeferredLoans = false;
    public $filterCancelledLoans = false;
    public $filterPendingLoans = false;

    // Updated available columns with requested changes
    public $availableColumns = [
        'members' => [
            'members.member_Id' => 'Member ID',
            'members.name' => 'Member Name',
            'members.number' => 'Phone Number',
            'members.gender' => 'Gender',
            'members.disabled' => 'PWD Status',
            'group_members.role' => 'Member Role', // Added member_role field
        ],
        'groups' => [
            'groups.name' => 'Group Name',
            'groups.identifier' => 'Group Identifier',
            'groups.reg_number' => 'Registration Number',
            'groups.date_registered' => 'Date Registered',
            'groups.bank' => 'Bank Name',
            'groups.account' => 'Bank Account',
            'groups.location' => 'Location',
            'groups.box' => 'Box Number',
            'location.ward_name'           => 'Ward',
            'location.constituency_name'   => 'Constituency',
            'location.county_name'         => 'County',
        ],
        'loans' => [
            'loan_phase.phase' => 'Loan Phase',
            'group_loans.gfs_code' => 'GFS Code',
            'group_loans.sector' => 'Group Sector',
            'group_loans.category' => 'Group Category',
            'group_loans.loan_status' => 'Loan Status',
            'group_loans.loan_type' => 'Loan Type',
            'group_loans.loan_level' => 'Loan Level',
            'group_loans.application_date' => 'Application Date',
            'group_loans.disbursement_date' => 'Disbursement Date',
            'group_loans.amount_applied' => 'Amount Applied',
            'group_loans.amount_approved' => 'Amount Approved',
            'group_loans.amount_issued' => 'Amount Issued',
            'group_loans.amount_repaid' => 'Amount Cummulatively Repaid',
            'group_loans.amount_due' => 'Amount Due',
            'group_loans.total_arrears' => 'Amount Cummulatively in Arrears',
            'arrears' => 'Outstanding Balance',
            'defaulter_status' => 'Defaulter Status',
            'group_loans.cheque_no' => 'Cheque Number',
            'group_loans.status' => 'Application Status',
            'group_loans.comments' => 'Comments',
            'repayment_date' => 'Date of Repayment Completion', // Added repayment date field
        ],
    ];

    // filters
    public $filterOptions = [
        'all_loans_filter' => 'All Loans',
        'financed_loans_filter' => 'Financed Loans',
        'pending_loans_filter' => 'Pending Disbursements',
        // 'approved_loans_filter' => 'Approved Loans',
        // 'deferred_loans_filter' => 'Deferred Loans',
        'cancelled_loans_filter' => 'Cancelled Loans',
        'never_repaid_after_grace'   => 'Never Repaid (Past 6-Month Grace Period)',
        'fully_repaid'               => 'Fully Repaid Loans',
        'performing_not_fully_repaid'=> 'Active / Performing (Not Fully Repaid)',
    ];

    public $selectedColumns = [];
    public $reportData = [];
    public $loading = false;
    public $exportLoading = false;
    public $reportGenerated = false;
    public $isDirty = true;
    public $totalRecords = 0;

    // Dynamic data for filters
    public $loanPhases = [];
    public $allWards = [];
    public $constituencies = [];
    public $allCounties = [];
    public $groupCategories = [
        ['category' => 'Youth'],
        ['category' => 'Women'],
        ['category' => 'PWD'],
    ];

    public $groupActivities = [
        ['activity' => 'Trade'],
        ['activity' => 'Service'],
        ['activity' => 'Manufacturing'],
        ['activity' => 'Agriculture'],
        ['activity' => 'Other']
    ];

    public $loanStatuses = ['pending', 'deferred', 'approved', 'financed', 'cancelled'];

    // Cache configuration
    protected $cacheTtl;
    protected $cachePrefix;

    public function boot(ReportService $reportService, LoanPhaseService $phaseService, LocationService $locationService)
    {
        $this->reportService = $reportService;
        $this->phaseService = $phaseService;
        $this->locationService = $locationService;
    }

    public function mount()
    {
        $this->filterLoanStatus = ['all_loans_filter'];


        $this->cacheTtl = config('cache-config.reports.ttl', 1800);
        $this->cachePrefix = config('cache-config.reports.prefix', 'dynamic_reports');


        // Load filter data
       $this->loadCountyOptions();
    }

    public function hydrate()
    {
        $this->ensureSelectedColumnsIsArray();
    }

    #[Computed]
    private function loadCountyOptions()
    {
        $this->countyOptions = $this->locationService->getAllCounties()
            ->map(fn($c) => [
                'id' => $c->county_code,
                'name' => $c->county_name
            ])
            ->toArray();
    }

    public function updatedFilterCounty($value)
    {
        $this->filterConstituency = '';
        $this->filterWard = '';
        $this->filterLoanPhase = '';

        $this->reset('constituencyOptions');
        $this->reset('wardsOptions');
        $this->reset('phaseOptions');

        if (!empty($value)) {
            $this->loadConstituencies($value);
        }
    }

    public function updatedFilterConstituency($value)
    {
        $this->filterWard = '';
        $this->filterLoanPhase = '';

        $this->reset('wardsOptions');

        if (!empty($value)) {
            $this->loadWards($value);
            $this->loadPhases($value);
        }

    }

    private function loadConstituencies($countyCode = null)
    {
        if (!$countyCode) {
            $this->constituencyOptions = [];
            return;
        }

        $this->constituencyOptions = $this->locationService->getAllConstituencies()
            ->where('county_code', $countyCode)
            ->map(fn($c) => [
                'id' => $c->constituency_code,
                'name' => Str::ucfirst(Str::lower($c->constituency_name))
            ])
            ->values()
            ->toArray();
    }

    private function loadWards($constituencyCode = null)
    {
        if (!$constituencyCode) {
            $this->wardsOptions = [];
            return;
        }

        $this->wardsOptions = $this->locationService->getAllWards()
            ->where('constituency_code', $constituencyCode)
            ->map(fn($w) => [
                'id' => $w->id,
                'name' => Str::ucfirst(Str::lower($w->ward_name)),
            ])
            ->values()
            ->toArray();
    }

    private function loadPhases($constituencyCode = null)
    {
        if (!$constituencyCode) {
            $this->phaseOptions = [];
            return;
        }

        $this->phaseOptions = $this->phaseService->getPhasesForConstituencyByStatus(
            $constituencyCode,
            'disbursed'
        );
    }

    public function updated($property, $value)
    {
        // 1. List your standalone filter strings
        $filters = [
            'filterCounty',
            'filterConstituency',
            'filterWard',
            'filterLoanPhase',
            'filterDefaulter',
            'filterGroupCategory',
            'filterLoanStatus',
            'filterGroupActivity'
        ];

        // 2. Check if the property is in the list OR starts with 'selectedColumns'
        if (in_array($property, $filters) || str_starts_with($property, 'selectedColumns')) {
            $this->ensureSelectedColumnsIsArray();
            // dd($property, $value);
            $this->isDirty = true;
        }

        $filterOptions = [
            'all_loans_filter',
            'financed_loans_filter',
            'never_repaid_after_grace',
        ];

        if ($property === 'filterLoanStatus') {
            if (!in_array($value, $filterOptions)) {
                $this->filterDefaulter = false;
            };
            $this->filterLoanStatus = [$value] ?: [];
        }

        if ($property === 'filterDefaulter' && $value === true) {
            if (empty(array_intersect($this->filterLoanStatus, $filterOptions))) {
                $this->filterLoanStatus = ['all_loans_filter'];
            }
        }
    }

    public function updatedDateFrom($value)
    {
        $this->validateDate($value, 'from');
    }

    public function updatedDateTo($value)
    {
        $this->validateDate($value, 'to');
    }

    protected function validateDate($value, $label)
    {
        $this->isDirty = true;
        if ($value && \Carbon\Carbon::parse($value)->isFuture()) {

            $this->dispatch('swal:toast',
                title: 'Invalid Date 😕',
                message: 'The selected date cannot be in the future. Resetting to today\'s date.',
                type: 'error'
            );

            if ($label === 'from') {
                $this->dateFrom = now()->format('Y-m-d');
            } else {
                $this->dateTo = now()->format('Y-m-d');
            }
        }
    }

    protected function ensureSelectedColumnsIsArray()
    {
        if (!is_array($this->selectedColumns)) {
            $this->selectedColumns = [];
        }
    }

    public function getSelectedColumnLabelsProperty()
    {
        // 1. Flatten all available columns into one master list
        $allLabels = collect($this->availableColumns)
            ->flatMap(fn($cols) => $cols)
            ->toArray();

        // 2. Map the current selectedColumns to their labels
        return collect($this->selectedColumns)
            ->reject(function ($column) {
                // Skip columns that belong to the 'filters' (Loan Status) group
                return array_key_exists($column, $this->filterOptions);
            })
            ->mapWithKeys(function ($column) use ($allLabels) {
                return [$column => $allLabels[$column] ?? $column];
            })
            ->toArray();
    }

    // get all available columns - used in isAllSelected and toggleAllColumns
    private function getAllAvailableColumns()
    {
        $allColumns = [];
        foreach ($this->availableColumns as $columns) {
            $allColumns = array_merge($allColumns, array_keys($columns));
        }
        return $allColumns;
    }

    // Get column category for display - used in the UI to group columns
    public function getColumnCategory($column)
    {
        // Check if it's a filter option first
        if (array_key_exists($column, $this->filterOptions)) {
            return 'filters';
        }

        foreach ($this->availableColumns as $category => $columns) {
            if (array_key_exists($column, $columns)) {
                return $category;
            }
        }
        return 'unknown';
    }

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

    public function isAllSelected()
    {
        $this->ensureSelectedColumnsIsArray();
        $allColumns = $this->getAllAvailableColumns();

        return count($this->selectedColumns) === count($allColumns);
    }

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

    public function clearAllColumns()
    {
        $this->selectedColumns = [];
        $this->isDirty = true;
        $this->reportGenerated = false;
    }

    public function removeColumn($column)
    {
        $this->ensureSelectedColumnsIsArray();
        $this->selectedColumns = array_values(array_filter($this->selectedColumns, function ($col) use ($column) {
            return $col !== $column;
        }));
    }

    public function resetToDefault()
    {
        $this->selectedColumns = ['financed_loans_filter']; // Default to financed loans filter only
    }

    public function generateReport()
    {
        $this->ensureSelectedColumnsIsArray();

        $this->loading = true;
        $this->reportGenerated = false;
        $this->isDirty = true;

        $this->showingLimitedData = true; // Reset

        try {
            $this->validateReportParameters();

            $this->reportData = $this->fetchReportData(false);

            $this->totalRecords = count($this->reportData);
            $this->reportGenerated = true;
            $this->isDirty = false;

            // Prepare success message based on data size
            $message = "Report '{$this->reportName}' generated successfully";


            $message .= ". Showing first {$this->displayLimit} of {$this->totalAvailableRecords} records. Use export to get all data.";


            $this->dispatch('report-generated', [
                'message' => $message,
                'constituency' => $this->constituencyName ?? 'unknown',
                'limited' => true
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('swal:toast',
                title: 'Invalid Selection 😕',
                message: 'You must select at least one data field to generate a report.',
                type: 'error'
            );
        } catch (\Exception $e) {
            logger()->error('Report generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            dd($e->getMessage(), $e->getTraceAsString());

            $this->dispatch('swal:toast',
                title: 'Error 😕',
                message: 'An unexpected error occurred while generating the report.',
                type: 'error'
            );
        }

        $this->loading = false;
    }

    /**
     * Returns only the real data columns (excludes filter keys)
     * @return array
     */
    private function getSelectedDataColumns(): array
    {
        return array_filter($this->selectedColumns, function ($col) {
            return !array_key_exists($col, $this->filterOptions);
        });
    }

    /**
     * Returns only the selected loan status / performance filters
     * @return array
     */
    private function getSelectedStatusFilters(): array
    {
        return array_filter($this->selectedColumns, function ($col) {
            return array_key_exists($col, $this->filterOptions);
        });
    }

    private function validateReportParameters()
    {
        // Validate that we have at least one data column (not just filters)
        $dataColumns = $this->getSelectedDataColumns();

        if (empty($dataColumns)) {
            $validator = Validator::make([], []); // Empty validator just to throw the exception
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }

    // private function fetchReportData($forExport = false)
    // {
    //     $startTime = microtime(true);

    //     // Build the base query based on selected columns and their order
    //     $query = $this->buildSmartQuery();

    //     if (!$query) {
    //         return [];
    //     }

    //     // Apply filters
    //     $this->applySmartFilters($query, $userConstituency);



    //     try {
    //         // Get total count first
    //         $this->totalAvailableRecords = $this->getOptimizedCount($query);
    //         $this->totalActualRecords = $this->totalAvailableRecords;
    //         logger()->info("Total available records: " . $this->totalAvailableRecords);

    //         // For large datasets, use optimized formatting
    //         // if ($this->totalAvailableRecords > 1000 && !$forExport) {
    //         //     logger()->info("Using chunked data fetching for large dataset: " . $this->totalAvailableRecords);
    //         //     $this->showingLimitedData = true;
    //         //     return $this->fetchDataWithChunking($query, $forExport);
    //         // }

    //         // Get the results
    //         if ($forExport) {
    //             logger()->info("Export data count for export: " . $query->count());
    //             // For exports: get ALL data (no limits)
    //             $results = $query->get();
    //             logger()->info("Export data retrieved for export: " . $results->count());
    //         } else {
    //             $this->showingLimitedData = true;
    //             $results = $query->limit($this->displayLimit)->get();
    //         }

    //         $this->monitorPerformance(
    //             $forExport ? 'Export' : 'Report Generation',
    //             $startTime,
    //             count($results)
    //         );

    //         logger()->info("Data retrieval completed. Records fetched: " . json_encode($results));
    //         // Format the results with calculated fields
    //         return $this->formatResults($results);
    //     } catch (\Exception $e) {
    //         logger()->error('Report generation error: ' . $e->getMessage());
    //         return [];
    //     }
    // }
    private function fetchReportData($forExport = false)
    {
        $startTime = microtime(true);

        $result = $this->reportService->generateReport(
            dataColumns: $this->getDataColumns(),
            loanStatusFilter: $this->filterLoanStatus ?: ['all_loans_filter'],
            selectedFilters: $this->getSelectedFilters(),
            forExport: $forExport,
            previewLimit: $this->displayLimit ?? 1000
        );

        // dd($result);

        $this->totalAvailableRecords = $result['total'];
        $this->totalActualRecords = $result['total'];
        $this->showingLimitedData = !$forExport && $result['total'] > ($this->displayLimit ?? 1000);

        $this->reportService->monitorPerformance(
            $forExport ? 'Export' : 'Report Generation',
            $startTime,
            $result['records']->count()
        );

        return $result['formatted'];
    }

    // Helpers (keep or move to trait)
    private function getDataColumns(): array
    {
        return array_filter($this->selectedColumns, fn($col) =>
            !array_key_exists($col, $this->filterOptions ?? [])
        );
    }

    private function getSelectedLoanStatusFilters(): array
    {
        return array_filter($this->selectedColumns, fn($col) =>
            array_key_exists($col, $this->filterOptions ?? [])
        );
    }

    private function getOptimizedCount($query)
    {
        // Use faster counting method for large tables
        try {
            return $query->count();
        } catch (\Exception $e) {
            logger()->warning('Fast count failed, using fallback: ' . $e->getMessage());
            // Fallback for complex queries
            return $query->toBase()->getCountForPagination();
        }
    }



    public function buildSmartQuery()
    {
        $this->ensureSelectedColumnsIsArray();

        // Get only data columns (exclude filters)
        $dataColumns = array_filter($this->selectedColumns, function($col) {
            return !array_key_exists($col, $this->filterOptions);
        });

        // Get selected loan status filters
        $this->selectedLoanStatusFilters = array_filter($this->selectedColumns, function($col) {
            return array_key_exists($col, $this->filterOptions);
        });


        if (empty($dataColumns)) {
            return null;
        }

        // Check if we need loan-related data
        $needsLoanData = $this->needsLoanData($dataColumns);

        // If any loan status filters are selected OR we need loan data, use loan-centric query
        if ($needsLoanData) {
            logger()->info("Building loan-centric query");
            return $this->buildLoanCentricQuery($dataColumns);
        } else {
            logger()->info("Building non-loan query");
            // Otherwise, determine the most appropriate base table
            return $this->buildNonLoanQuery($dataColumns);
        }
    }

    private function needsLoanData($dataColumns)
    {
        // Define loan-related columns and tables
        $loanColumns = [
            'group_loans.application_date',
            'group_loans.disbursement_date',
            'group_loans.gfs_code',
            'group_loans.sector',
            'group_loans.category',
            'group_loans.amount_applied',
            'group_loans.amount_approved',
            'group_loans.amount_issued',
            'group_loans.amount_repaid',
            'group_loans.loan_status',
            'group_loans.amount_due',
            'group_loans.total_arrears',
            'group_loans.loan_level',
            'group_loans.cheque_no',
            'group_loans.status',
            'group_loans.loan_type',
            'group_loans.comments',
            'loan_phase.phase',
            'arrears',
            'amount_due',
            'cumulative_arrears',
            'repayment_date',
        ];

        // Check if any selected column is loan-related
        foreach ($dataColumns as $column) {
            if (in_array($column, $loanColumns) || str_starts_with($column, 'group_loans.') || $column === 'loan_phase.phase') {
                return true;
            }
        }

        return false;
    }

    private function buildLoanCentricQuery($dataColumns)
    {
        $dataColumns = array_filter($dataColumns, function($col) {
            return str_starts_with($col, 'group_loans.');
        });
        // Always include foreign keys for relationships to work
        $requiredColumns = [
            'group_loans.id',
            'group_loans.group_id',
            'group_loans.loan_phase_id',
            'group_loans.disbursement_date',
        ];



        $dataColumns = array_unique(array_merge($dataColumns, $requiredColumns));
        // $query = GroupLoan::with([
        //     'group' => function($query) {
        //         $query->select(['id', 'ward_id', 'name', 'category', 'identifier', 'reg_number', 'status', 'gfs_code', 'date_registered', 'bank', 'account', 'activity', 'location', 'box']);
        //     },
        //     'group.ward' => function($query) {
        //         $query->select(['id', 'ward_name', 'constituency_code']);
        //     },
        //     'loanPhase' => function($query) {
        //         $query->select(['id', 'phase']);
        //     }
        // ]);
        $withRelations = [
            'group' => function($query) {
                $query->select(['id', 'ward_id', 'name', 'identifier', 'reg_number', 'status', 'date_registered', 'bank', 'account', 'location', 'box']);
            },
            'group.ward' => function($query) {
                $query->select(['id', 'ward_name', 'constituency_code']);
            },
            'loanPhase' => function($query) {
                $query->select(['id', 'phase']);
            }
        ];

        // Only load groupMembers if member columns are selected
        $needsMembers = !empty(array_intersect($dataColumns, array_keys($this->availableColumns['members'])));
        if ($needsMembers) {
            $withRelations['groupMembers.member'] = function($query) {
                $query->select(['id', 'member_Id', 'name', 'number', 'gender', 'disabled']);
            };
        }

        return GroupLoan::with($withRelations)->select($dataColumns);
    }

    private function buildNonLoanQuery($dataColumns)
    {
        // Determine the primary table based on selected columns
        $primaryTable = $this->determinePrimaryTable($dataColumns);

        switch ($primaryTable) {
            case 'members':
                return $this->buildPureMemberQuery($dataColumns);
            case 'groups':
                return $this->buildPureGroupQuery($dataColumns);
            default:
                return $this->buildPureGroupQuery($dataColumns); // Default to groups
        }
    }

    private function determinePrimaryTable($dataColumns)
    {
        // this function determines which table (members, groups) has the most columns selected
        $tableCounts = [
            'members' => 0,
            'groups' => 0,
        ];

        foreach ($dataColumns as $column) {
            if (str_starts_with($column, 'members.')) {
                $tableCounts['members']++;
            } elseif (str_starts_with($column, 'groups.')) {
                $tableCounts['groups']++;
            } elseif (str_starts_with($column, 'group_members.')) {
                $tableCounts['members']++; // Group members relates to members
            }
        }

        // Return the table with the most columns selected
        arsort($tableCounts);
        return array_key_first($tableCounts);
    }

    private function buildPureMemberQuery($dataColumns)
    {
        $dataColumns = array_filter($dataColumns, fn($column) => str_starts_with($column, 'members.'));

        $query = Member::with([
            'groupMembers' => function($query) {
                $query->select(['id', 'member_Id', 'group_loans_id', 'role'])
                    ->whereHas('groupLoan.group.ward');
            },
            'groupMembers.groupLoan' => function($query) {
                $query->select([
                    'id', 'group_id', 'loan_phase_id', 'amount_issued', 'gfs_code', 'sector', 'category',
                    'amount_repaid', 'loan_status', 'application_date', 'loan_level', 'loan_type',
                    'disbursement_date', 'amount_applied', 'amount_approved', 'amount_due', 'total_arrears'
                ]);
            },
            'groupMembers.groupLoan.group' => function($query) {
                $query->select(['id', 'ward_id', 'name', 'identifier', 'reg_number', 'status', 'date_registered', 'bank', 'account', 'location', 'box']);
            },
            'groupMembers.groupLoan.group.ward' => function($query) {
                $query->select(['id', 'ward_name', 'constituency_code']);
            },
            'groupMembers.groupLoan.loanPhase' => function($query) {
                $query->select(['id', 'phase']);
            }
        ])->select($dataColumns);

        return $query;
    }

    private function buildPureGroupQuery($dataColumns)
    {
        $dataColumns = array_filter($dataColumns, fn($column) => str_starts_with($column, 'groups.'));

        // Always include foreign keys for relationships to work
        $requiredColumns = ['groups.ward_id', 'groups.id'];
        $dataColumns = array_unique(array_merge($dataColumns, $requiredColumns));

        $query = Group::with([
            'ward' => function($query) {
                $query->select(['id', 'ward_name', 'constituency_code']);
            },
            'groupLoans' => function($query) {
                $query->select([
                    'id', 'group_id', 'loan_phase_id', 'amount_issued', 'gfs_code', 'sector', 'category',
                    'amount_repaid', 'loan_status', 'application_date', 'loan_level', 'loan_type',
                    'disbursement_date', 'amount_applied', 'amount_approved', 'amount_due', 'total_arrears'
                ]);
            },
            'groupLoans.loanPhase' => function($query) {
                $query->select(['id', 'phase']);
            },
            'groupLoans.groupMembers' => function($query) {
                $query->select(['id', 'member_Id', 'group_loans_id', 'role']);
            },
            'groupLoans.groupMembers.member' => function($query) {
                $query->select(['id', 'member_Id', 'name', 'number', 'gender', 'disabled']);
            }
        ])->select($dataColumns);

        $query->when(true, function($q) {
            logger()->info('pure group query:', [
                'sql' => $q->toSql(),
                'bindings' => $q->getBindings()
            ]);
        });

        return $query;
    }

    public function applySmartFilters($query, $userConstituency)
    {
        logger()->info("Applying smart filters");
        // Get the query type to apply appropriate filters
        $queryType = $this->getQueryType($query);
        $this->applyDefaulterFilter($query, $queryType);

        switch ($queryType['type']) {
            case 'loan_centric':
                $this->applyLoanCentricFilters($query, $userConstituency);
                break;
            case 'member_centric':
                $this->applyMemberCentricFilters($query, $userConstituency);
                break;
            case 'group_centric':
                $this->applyGroupCentricFilters($query, $userConstituency);
                break;
        }
    }

    private function getQueryType($query)
    {
        $modelClass = get_class($query->getModel());
        logger()->info("Determining query type for model: {$modelClass}");

        switch ($modelClass) {
            case GroupLoan::class:
                return [
                    'type' => 'loan_centric',
                    'chunk_column' => 'group_loans.id'
                ];
            case Member::class:
                return [
                    'type' => 'member_centric',
                    'chunk_column' => 'members.id'
                ];
            case Group::class:
                return [
                    'type' => 'group_centric',
                    'chunk_column' => 'groups.id'
                ];
            default:
                return [
                    'type' => 'loan_centric',
                    'chunk_column' => 'group_loans.id'
                ];
        }
    }

    // Update the getSelectColumns method to handle all calculated fields

    private function applyLoanCentricFilters($query, $userConstituency)
    {
        // Always filter by constituency through loan_phases
        $query->whereHas('loanPhase', function($q) use ($userConstituency) {
            $q->where('constituency_code', $userConstituency->constituency_code);
        });

        // Apply loan phase filter
        if ($this->filterLoanPhase) {
            $query->whereHas('loanPhase', function($q) {
                $q->where('phase', $this->filterLoanPhase);
            });
        }

        // Apply ward filter
        if ($this->filterWard) {
            $query->whereHas('group.ward', function($q) {
                $q->where('ward_name', $this->filterWard);
            });
        }

        // Apply group category filter
        if ($this->filterGroupCategory) {
            $query->where('category', $this->filterGroupCategory);
        }

        if ($this->filterGroupActivity) {
            $query->where('sector', $this->filterGroupActivity);
        }

        // Apply loan status filter
        if ($this->filterLoanStatus) {
            $query->where('loan_status', $this->filterLoanStatus);
        }

        // Apply loan status filters from filter options
        if ($this->selectedLoanStatusFilters) $this->applyLoanStatusFilters($query);
    }

    private function applyMemberCentricFilters($query, $userConstituency)
    {
        logger()->info("Applying member centric filters");
        // Filter by constituency
        $query->whereHas('groupMembers.groupLoan.group.ward', function($q) use ($userConstituency) {
            $q->where('constituency_code', $userConstituency->constituency_code);
        });

        // Apply other filters...
        if ($this->filterWard) {
            $query->whereHas('groupMembers.groupLoan.group.ward', function($q) {
                $q->where('ward_name', $this->filterWard);
            });
        }
        // Apply phase filter
        if ($this->filterLoanPhase) {
            $query->whereHas('groupMembers.groupLoan.loanPhase', function($q) {
                $q->where('phase', $this->filterLoanPhase);
            });
        }

        if ($this->filterGroupCategory) {
            $query->whereHas('groupMembers.groupLoan', function($q) {
                $q->where('category', $this->filterGroupCategory);
            });
        }

        if ($this->filterGroupActivity) {
            $query->whereHas('groupMembers.groupLoan', function($q) {
                $q->where('sector', $this->filterGroupActivity);
            });
        }

        if ($this->selectedLoanStatusFilters) {
            $query->whereHas('groupMembers.groupLoan', function($q) {
                //local.INFO: Applying these loan status filters to member query{"2":"financed_loans_filter","3":"pending_loans_filter"}
                $this->applyLoanStatusFilters($q);
            });
        }
    }

    private function applyGroupCentricFilters($query, $userConstituency)
    {
        // Filter by constituency through wards
        $query->whereHas('ward', function($q) use ($userConstituency) {
            $q->where('constituency_code', $userConstituency->constituency_code);
        });

        // Apply ward filters
        if ($this->filterWard) {
            $query->whereHas('ward', function($q) {
                $q->where('ward_name', $this->filterWard);
            });
        }
        // Apply phase filter
        if ($this->filterLoanPhase) {
            $query->whereHas('groupLoans.loanPhase', function($q) {
                $q->where('phase', $this->filterLoanPhase);
            });
        }

        if ($this->selectedLoanStatusFilters) {
            $query->whereHas('groupLoans', function($q) {
                //local.INFO: Applying these loan status filters to member query{"2":"financed_loans_filter","3":"pending_loans_filter"}
                $this->applyLoanStatusFilters($q);
            });
        }

        $query->when(true, function($q) {
            logger()->info('Final Group Query:', [
                'sql' => $q->toSql(),
                'bindings' => $q->getBindings()
            ]);
        });

        // Apply group category filter
        if ($this->filterGroupCategory) {
            $query->whereHas('groupLoans', function($q) {
                $q->where('category', $this->filterGroupCategory);
            });
        }

        // Apply group activity filter
        if ($this->filterGroupActivity) {
            $query->whereHas('groupLoans', function($q) {
                $q->where('sector', $this->filterGroupCategory);
            });
        }
    }

    public function formatColumnValue($column, $item, $forExport = false)
    {
        // Handle different query types
        if ($item instanceof GroupLoan) {
            return $this->formatLoanCentricColumn($column, $item, $forExport);
        } elseif ($item instanceof Member) {
            return $this->formatMemberCentricColumn($column, $item, $forExport);
        } elseif ($item instanceof Group) {
            return $this->formatGroupCentricColumn($column, $item, $forExport);
        }

        return 'N/A';
    }

    private function formatLoanCentricColumn($column, $item, $forExport = false)
    {
        // calculate fields based on loan-centric query

        $calculatedFields = $this->getCalclatedFieldsForLoan($item->disbursement_date, $item->amount_issued, $item->amount_repaid);


        switch ($column) {
            // Group columns
            case 'groups.name':
                return $item->group->name ?? 'N/A';
            case 'groups.identifier':
                return $item->group->identifier ?? 'N/A';
            case 'groups.reg_number':
                return $item->group->reg_number ?? 'N/A';
            case 'groups.gfs_code':
                return $item->group->gfs_code ?? 'N/A';
            case 'groups.date_registered':
                return $item->group->date_registered ? \Carbon\Carbon::parse($item->group->date_registered)->format('d/m/Y') : 'N/A';
            case 'groups.bank':
                return $item->group->bank ?? 'N/A';
            case 'groups.account':
                return $item->group->account ?? 'N/A';
            case 'groups.location':
                return $item->group->location ?? 'N/A';
            case 'groups.box':
                return $item->group->box ?? 'N/A';
            case 'groups.status':
                return $item->group->status ?? 'N/A';

            // Ward columns
            case 'ward.ward_name':
                return $item->group->ward->ward_name ?? 'N/A';

            // Loan phase columns
            case 'loan_phase.phase':
                return $item->loanPhase->phase ?? 'N/A';

            // Loan columns
            case 'group_loans.application_date':
                return $item->application_date ? \Carbon\Carbon::parse($item->application_date)->format('d/m/Y') : 'N/A';
            case 'group_loans.disbursement_date':
                return $item->disbursement_date ? \Carbon\Carbon::parse($item->disbursement_date)->format('d/m/Y') : 'N/A';
            case 'group_loans.amount_applied':
                return $forExport ? ($item->amount_applied ? $item->amount_applied : '0.00') : ($item->amount_applied ? number_format($item->amount_applied, 2) : '0.00');
            case 'group_loans.amount_approved':
                return $forExport ? ($item->amount_approved ? $item->amount_approved : '0.00') : ($item->amount_approved ? number_format($item->amount_approved, 2) : '0.00');
            case 'group_loans.amount_issued':
                return $forExport ? ($item->amount_issued ? $item->amount_issued : '0.00') : ($item->amount_issued ? number_format($item->amount_issued, 2) : '0.00');
            case 'group_loans.amount_repaid':
                return $forExport ? ($item->amount_repaid ? $item->amount_repaid : '0.00') : ($item->amount_repaid ? number_format($item->amount_repaid, 2) : '0.00');
            case 'group_loans.amount_due':
                return $forExport ? ($item->amount_due ? $item->amount_due : '0.00') : ($item->amount_due ? number_format($item->amount_due, 2) : '0.00');
            case 'group_loans.total_arrears':
                return $forExport ? ($item->total_arrears ? $item->total_arrears : '0.00') : ($item->total_arrears ? number_format($item->total_arrears, 2) : '0.00');
            case 'group_loans.category':
                return $item->category ?? 'N/A';
            case 'group_loans.gfs_code':
                return $item->gfs_code ?? 'N/A';
            case 'group_loans.sector':
                return $item->sector ?? 'N/A';
            case 'group_loans.loan_status':
                return $item->loan_status ?? 'N/A';
            case 'group_loans.loan_level':
                return $item->loan_level ?? 'N/A';
            case 'group_loans.loan_type':
                return $item->loan_type ?? 'N/A';
            case 'group_loans.cheque_no':
                return $item->cheque_no ?? 'N/A';
            case 'group_loans.status':
                return $item->status ?? 'N/A';
            case 'group_loans.comments':
                return $item->comments ?? 'N/A';

            // Member columns - handle multiple members by showing all members
            // dont forget to handle cases where there are no members
            case 'members.member_Id':
                if ($item->groupMembers->isNotEmpty()) {
                    $memberIds = $item->groupMembers->pluck('member.member_Id');
                    return $memberIds->join(', ');
                }
                return 'N/A';
            case 'members.name':
                if ($item->groupMembers->isNotEmpty()) {
                    $memberNames = $item->groupMembers->pluck('member.name');
                    return $memberNames->join(', ');
                }
                return 'N/A';
            case 'members.number':
                if ($item->groupMembers->isNotEmpty()) {
                    $memberNumbers = $item->groupMembers->pluck('member.number');
                    return $memberNumbers->join(', ');
                }
                return 'N/A';
            case 'members.gender':
                if ($item->groupMembers->isNotEmpty()) {
                    $genders = $item->groupMembers->pluck('member.gender');
                    return $genders->join(', ');
                }
                return 'N/A';
            case 'members.disabled':
                if ($item->groupMembers->isNotEmpty()) {
                    $disabledStatus = $item->groupMembers->map(function($gm) {
                        return isset($gm->member->disabled) ? ($gm->member->disabled ? 'Yes' : 'No') : 'N/A';
                    });
                    return $disabledStatus->join(', ');
                }
                return 'N/A';
            case 'group_members.role':
                if ($item->groupMembers->isNotEmpty()) {
                    $roles = $item->groupMembers->pluck('role');
                    return $roles->join(', ');
                }
                return 'N/A';

            // Calculated fields
            case 'arrears':
                $arrears = max($item->amount_issued - $item->amount_repaid, 0);
                if ($forExport) {
                    return $arrears == 0 ? '0.00' : $arrears;
                }
                return number_format($arrears, 2);
            case 'cumulative_arrears':
                return $forExport ? ($item->total_arrears ? $item->total_arrears : '0.00') : ($item->total_arrears ? number_format($item->total_arrears, 2) : '0.00');
            case 'repayment_date':
                return $calculatedFields['repayment_date'] ?? 'N/A';
            default:
                return $item->{$this->getColumnAlias($column)} ?? 'N/A';
        }

    }

    private function formatMemberCentricColumn($column, $item, $forExport = false)
    {
        logger()->info("Formatting member centric column: {$column} for member ID: {$item->id}");
        switch ($column) {
            // Member columns
            case 'members.name':
                return $item->name ?? 'N/A';
            case 'members.member_Id':
                return $item->member_Id ?? 'N/A';
            case 'members.number':
                return $item->number ?? 'N/A';
            case 'members.gender':
                return $item->gender ?? 'N/A';
            case 'members.disabled':
                return isset($item->disabled) ? ($item->disabled ? 'Yes' : 'No') : 'N/A';

            // Group-related columns (can have multiple values per member)
            case 'groups.name':
                return $this->getMemberGroupInfo($item, 'name', false); // ← No unique

            case 'groups.identifier':
                return $this->getMemberGroupInfo($item, 'identifier', false); // ← No unique

            case 'groups.reg_number':
                return $this->getMemberGroupInfo($item, 'reg_number', false); // ← No unique

            case 'groups.date_registered':
                return $this->getMemberGroupInfo($item, 'date_registered', false); // ← No unique

            case 'groups.bank':
                return $this->getMemberGroupInfo($item, 'bank', false); // ← No unique

            case 'groups.account':
                return $this->getMemberGroupInfo($item, 'account', false); // ← No unique

            case 'groups.location':
                return $this->getMemberGroupInfo($item, 'location', false); // ←No unique

            case 'groups.box':
                return $this->getMemberGroupInfo($item, 'box', false); // ← No unique

            case 'groups.status':
                return $this->getMemberGroupInfo($item, 'status', false); // ← No unique

            case 'ward.ward_name':
                return $this->getMemberGroupInfo($item, 'ward_name', false); // ← No unique

            case 'group_members.role':
                if ($item->groupMembers->isNotEmpty()) {
                    $roles = $item->groupMembers->pluck('role')->filter(); // ← No unique()
                    return $roles->isNotEmpty() ? $roles->join(', ') : 'N/A';
                }
                return 'N/A';

            default:
                return $item->{$this->getColumnAlias($column)} ?? 'N/A';
        }
    }

    // Helper method to get group information
    private function getMemberGroupInfo($member, $field, $unique = false)
    {
        if ($member->groupMembers->isEmpty()) {
            return 'N/A';
        }

        logger()->info("Getting member group info for field: {$field} (unique: " . ($unique ? 'true' : 'false') . ")");
        $values = $member->groupMembers->map(function($groupMember) use ($field) {
            switch ($field) {
                case 'name':
                    return $groupMember->groupLoan->group->name ?? null;
                case 'category':
                    return $groupMember->groupLoan->category ?? null;
                case 'identifier':
                    return $groupMember->groupLoan->group->identifier ?? null;
                case 'reg_number':
                    return $groupMember->groupLoan->group->reg_number ?? null;
                case 'gfs_code':
                    return $groupMember->groupLoan->gfs_code ?? null;
                case 'date_registered':
                    return $groupMember->groupLoan->group->date_registered ? \Carbon\Carbon::parse($groupMember->groupLoan->group->date_registered)->format('d/m/Y') : null;
                case 'bank':
                    return $groupMember->groupLoan->group->bank ?? null;
                case 'account':
                    return $groupMember->groupLoan->group->account ?? null;
                case 'sector':
                    return $groupMember->groupLoan->sector ?? null;
                case 'location':
                    return $groupMember->groupLoan->group->location ?? null;
                case 'box':
                    return $groupMember->groupLoan->group->box ?? null;
                case 'status':
                    return $groupMember->groupLoan->group->status ?? null;
                case 'ward_name':
                    return $groupMember->groupLoan->group->ward->ward_name ?? null;
                default:
                    return null;
            }
        })->filter();
        logger()->info("retrived values count: " . $values->count());
        if ($unique) {
            $values = $values->unique();
            logger()->info("retrived unique values count after unique(): " . $values->count());
        }

        return $values->isNotEmpty() ? $values->join(', ') : 'N/A';
    }

    private function formatGroupCentricColumn($column, $item, $forExport = false)
    {

        switch ($column) {
            case 'groups.name':
                return $item->name ?? 'N/A';
            case 'groups.identifier':
                return $item->identifier ?? 'N/A';
            case 'groups.reg_number':
                return $item->reg_number ?? 'N/A';
            case 'groups.box':
                return $item->box ?? 'N/A';
            case 'groups.status':
                return $item->status ?? 'N/A';
            case 'groups.date_registered':
                return $item->date_registered ? \Carbon\Carbon::parse($item->date_registered)->format('d/m/Y') : 'N/A';
            case 'groups.bank':
                return $item->bank ?? 'N/A';
            case 'groups.account':
                return $item->account ?? 'N/A';
            case 'groups.location':
                return $item->location ?? 'N/A';
            case 'ward.ward_name':
                return $item->ward->ward_name ?? 'N/A';

            // load member-related columns
            case 'members.member_Id':
                return $this->getGroupMemberInfo($item, 'member_Id', false); //
            case 'members.name':
                return $this->getGroupMemberInfo($item, 'name', false); //
            case 'members.number':
                return $this->getGroupMemberInfo($item, 'number', false); //
            case 'members.gender':
                return $this->getGroupMemberInfo($item, 'gender', false); //
            case 'members.disabled':
                return $this->getGroupMemberInfo($item, 'disabled', false); //
            case 'group_members.role':
                return $this->getGroupMemberInfo($item, 'role', false); //
            default:
                return $item->{$this->getColumnAlias($column)} ?? 'N/A';
        }
    }

    private function getGroupMemberInfo($group, $field, $unique = false)
    {
        if ($group->groupLoans->isEmpty()) {
            return 'N/A';
        }

        $values = $group->groupLoans->flatMap(function($groupLoan) use ($field) {
            // Use flatMap to handle multiple group members per loan
            return $groupLoan->groupMembers->map(function($groupMember) use ($field) {
                switch ($field) {
                    case 'member_Id':
                        return $groupMember->member->member_Id ?? null;
                    case 'name':
                        return $groupMember->member->name ?? null;
                    case 'number':
                        return $groupMember->member->number ?? null;
                    case 'gender':
                        return $groupMember->member->gender ?? null;
                    case 'disabled':
                        return isset($groupMember->member->disabled) ?
                            ($groupMember->member->disabled ? 'Yes' : 'No') : null;
                    case 'role':
                        return $groupMember->role ?? null;
                    default:
                        return null;
                }
            })->filter();
        })->filter();

        if ($unique) {
            $values = $values->unique();
        }

        return $values->isNotEmpty() ? $values->join(', ') : 'N/A';
    }

    public function getCalclatedFieldsForLoan($disbursementDate, $amountIssued, $amountRepaid)
    {
        logger()->info("Calculating fields for loan with disbursement date: {$disbursementDate}, amount issued: {$amountIssued}, amount repaid: {$amountRepaid}");
        if ($amountIssued === null || $amountIssued <= 0) {
            return [
                'amount_due' => 0,
                'cumulative_arrears' => 0,
                'repayment_date' => null,
            ];
        }

        // Calculate amount due
        $amountDue = $this->calculateAmountDue($disbursementDate, $amountIssued);

        return [
            'amount_due' => $amountDue,
            'cumulative_arrears' => max($amountDue - $amountRepaid, 0),
            'repayment_date' => $disbursementDate ? \Carbon\Carbon::parse($disbursementDate)->addMonths(30)->format('d/m/Y') : null,
        ];


    }

    private function calculateAmountDue($disbursementDate, $amountIssued)
    {
        logger()->info("Calculating amount due for disbursement date: {$disbursementDate} and amount issued: {$amountIssued}");
        if (!$disbursementDate || !$amountIssued) {
            return 0;
        }

        $disbursement = \Carbon\Carbon::parse($disbursementDate);
        $now = \Carbon\Carbon::now();

        $monthsElapsed = $disbursement->diffInMonths($now);
        // add a grace month of 6 months
        $monthsElapsed = max(0, $monthsElapsed - 6);
        $totalRepaymentMonths = 24; // assuming a 24-month repayment period

        // capture fully due case if payment period has elapsed, so no need to calculate further
        if ($monthsElapsed >= $totalRepaymentMonths) {
            return $amountIssued; // fully due
        }

        $monthlyRepayment = $amountIssued / $totalRepaymentMonths;
        $amountDue = $monthlyRepayment * $monthsElapsed;

        return $amountDue;
    }


    public function reorderColumns($newOrder)
    {
        $this->ensureSelectedColumnsIsArray();

        // Filter out any filter options from the new order
        $dataColumns = array_filter($newOrder, function($column) {
            return !array_key_exists($column, $this->filterOptions);
        });

        // Get the current filter options that are selected
        $currentFilters = array_filter($this->selectedColumns, function($column) {
            return array_key_exists($column, $this->filterOptions);
        });

        // Combine data columns (in new order) with filter options
        $this->selectedColumns = array_merge($dataColumns, $currentFilters);

        // Dispatch event to show success message
        $this->dispatch('columns-reordered', ['message' => 'Columns reordered successfully']);
    }

    private function applyLoanStatusFilters($query)
    {
        $loanStatusFilters = [];

        // Check which filter options are selected and map to actual loan statuses
        if (in_array('financed_loans_filter', $this->selectedColumns)) {
            $loanStatusFilters[] = 'financed';
        }
        if (in_array('approved_loans_filter', $this->selectedColumns)) {
            $loanStatusFilters[] = 'approved';
        }
        if (in_array('deferred_loans_filter', $this->selectedColumns)) {
            $loanStatusFilters[] = 'deferred';
        }
        if (in_array('cancelled_loans_filter', $this->selectedColumns)) {
            $loanStatusFilters[] = 'cancelled';
        }
        if (in_array('pending_loans_filter', $this->selectedColumns)) {
            $loanStatusFilters[] = 'pending';
        }

        // Apply the filters if any are selected
        if (!empty($loanStatusFilters)) {
            logger()->info("Applying loan status filters: " . implode(', ', $loanStatusFilters));
            $query->whereIn('group_loans.loan_status', $loanStatusFilters);
        }
    }


    private function formatResults($results)
    {
        $formattedData = [];

        foreach ($results as $item) {
            // Only check complex defaulter cases for loans that passed initial filter
            if ($this->filterDefaulter) {
                // For loans that passed the simple filter, we still need to verify
                // the complex calculation for loans in the repayment period
                $calculatedFields = $this->getCalclatedFieldsForLoan(
                    $item->disbursement_date,
                    $item->amount_issued,
                    $item->amount_repaid
                );

                // Final verification using the exact PHP logic
                if (($calculatedFields['amount_due'] - $item->amount_repaid) <= 0) {
                    continue;
                }
            }

            $row = [];
            foreach ($this->selectedColumns as $column) {
                if (array_key_exists($column, $this->filterOptions)) {
                    continue;
                }
                $row[$column] = $this->formatColumnValue($column, $item);
            }

            $formattedData[] = $row;
        }

        return $formattedData;
    }


    private function getColumnAlias($column)
    {
        if ($column === 'arrears') return 'arrears';
        if ($column === 'cumulative_arrears') return 'cumulative_arrears';

        // Remove table prefix for object access
        return str_replace('.', '_', $column);
    }

     private function getCacheKey()
    {
        $this->ensureSelectedColumnsIsArray();
        $selectedColumns = $this->selectedColumns;
        sort($selectedColumns);
        $columnsHash = md5(implode('', $selectedColumns));
        $userConstituency = $this->constituency ?? null;

        // Include filters in cache key
        $filtersHash = md5(implode('', [
            $this->filterCounty,
            $this->filterConstituency,
            $this->filterWard,
            $this->filterLoanPhase,
            $this->filterDefaulter ? '1' : '0',
            $this->filterGroupCategory,
            $this->filterLoanStatus,
            $this->filterFinancedLoans ? '1' : '0',
            $this->filterApprovedLoans ? '1' : '0',
            $this->filterDeferredLoans ? '1' : '0',
            $this->filterCancelledLoans ? '1' : '0',
            $this->filterPendingLoans ? '1' : '0',
        ]));

        return "{$this->cachePrefix}_{$userConstituency}_{$columnsHash}_{$filtersHash}";
    }

    public function refreshReport()
    {
        $cacheKey = $this->getCacheKey();
        $userConstituency = $this->constituency ?? null;
        $cacheTags = ['reports', "constituency:{$userConstituency}"];

        Cache::tags($cacheTags)->forget($cacheKey);
    }

    public function clearFilters()
    {
        $this->reset([
            'filterCounty',
            'filterConstituency',
            'filterWard',
            'filterLoanPhase',
            'filterGroupCategory',
            'filterGroupActivity',
            'dateFrom',
            'dateTo',
            'filterDefaulter',
        ]);

        $this->isDirty = true;
        $this->reportGenerated = false;

        // dispatch event to notify filters cleared
        $this->dispatch('swal:toast',
            title: 'Filters Cleared 🧹',
            message: 'All filters have been cleared.',
            type: 'info'
        );
    }


    // In your Livewire component
    public function exportToExcel()
    {
        $this->exportLoading = true;

        try {
            $fileName = $this->generateExportFileName('xlsx');

            $this->dispatch('swal:toast',
                title: 'Exporting Data',
                message: 'Starting export process... This may take a while for large datasets.',
                type: 'info'
            );

            $dataColumns =  $this->getDataColumns();

            $result = $this->reportService->generateReport(
                dataColumns: $dataColumns,
                loanStatusFilter: $this->filterLoanStatus ?: ['all_loans_filter'],
                selectedFilters: $this->getSelectedFilters(),
                forExport: true,
            );

            $query = $result['query'];

            $queryType = $result['query_type'];

            if (!$query) {
                throw new \Exception("No data available for export");
            }

            return Excel::download(
                new DynamicReportingExport(
                    $query,                // ← pass the ready query
                    $dataColumns,          // columns to export
                    $queryType,
                    $this->reportService,   // optional: for formatting if needed
                    $this->reportName
                ),
                $fileName
            );

        } catch (\Exception $e) {
            $this->dispatch('swal:toast',
                title: 'Export Error 😕',
                message: 'Excel export failed.'. $e->getMessage(),
                type: 'error'
            );
            return null;
        } finally {
            $this->exportLoading = false;
        }
    }

    // In your Livewire component - replace the PDF methods
    public function exportToPDF()
    {
        $this->exportLoading = true;

        try {
            $fileName = $this->generateExportFileName('pdf');

            // For all datasets, use custom PDF with template
            return response()->streamDownload(function () {
                $this->generateCustomPdfWithTemplate();
            }, $fileName);

        } catch (\Exception $e) {
            logger()->error('PDF export error: ' . $e->getMessage());
            $this->dispatch('swal:toast',
                title: 'Export Error 😕',
                message: 'Excel export failed.'. $e->getMessage(),
                type: 'error'
            );
            return null;
        } finally {
            $this->exportLoading = false;
        }
    }

    private function generateCustomPdfWithTemplate()
    {
        $this->dispatch('swal:toast',
            title: 'Exporting Data',
            message: 'Starting export process... This may take a while for large datasets.',
            type: 'info'
        );

        $dataColumns =  $this->getDataColumns();

        $result = $this->reportService->generateReport(
            dataColumns: $dataColumns,
            loanStatusFilter: $this->filterLoanStatus ?: ['all_loans_filter'],
            selectedFilters: $this->getSelectedFilters(),
            forExport: true,
        );

        $query = $result['query'];

        $queryType = $result['query_type'];

        if (!$query) {
            throw new \Exception("No data available for export");
        }

        $county = $this->constituency->county ?? (object)['county_name' => 'UNKNOWN COUNTY'];

        $html = $this->generateOfficialPdfHtml($query, $county, $queryType);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'landscape'); // Use landscape for better table layout
        echo $pdf->stream();
    }

    private function generateOfficialPdfHtml($query, $county, $queryType)
    {
        $headers = $this->getExportHeaders();
        $logoPath = public_path('images/docs/doc_logo.png');

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <style>
                @page {
                    margin: 100px 20px 20px 20px; /* Increased top margin for header */
                    header: html_header;
                }
                body {
                    font-family: "DejaVu Sans", "Arial", sans-serif;
                    font-size: 10px;
                    margin: 0;
                    padding: 0;
                    line-height: 1.2;
                }
                .header {
                    position: fixed;
                    top: -80px; /* Adjusted to show full header */
                    left: 0;
                    right: 0;
                    height: 150px; /* Increased height */
                    border-bottom: 2px solid #000;
                    padding: 5px 0;
                    background: white; /* Ensure background covers content */
                    z-index: 1000;
                }
                .header-content {
                    position: relative;
                    width: 100%;
                    height: 100%;
                    text-align: center;
                }
                .logo-center {
                    display: block;
                    margin: 0 auto 3px auto;
                    height: 50px;
                    width: auto;
                    max-width: 100px;
                }
                .main-title {
                    font-size: 14px;
                    font-weight: bold;
                    margin-bottom: 1px;
                    line-height: 1.1;
                }
                .info-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 3px 0;
                }
                .info-table td {
                    width: 25%; /* Equal width for all 4 columns */
                    text-align: center;
                    font-size: 11px;
                    font-weight: bold;
                    border: none;
                    padding: 2px 5px;
                    vertical-align: top;
                }
                .info-table td:first-child {
                    text-align: left;
                }
                .info-table td:last-child {
                    text-align: right;
                }
                .report-title {
                    font-size: 11px;
                    font-weight: bold;
                    margin: 1px 0;
                    line-height: 1.1;
                    border-bottom: 2px solid #000;
                }
                .content {
                    margin-top: 70px; /* Space for header */
                    padding-top: 10px;
                }
                .data-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 5px;
                    page-break-inside: auto;
                }
                .data-table th {
                    background-color: #D9E1F2;
                    color: #000;
                    padding: 6px 4px;
                    text-align: center;
                    border: 1px solid #000;
                    font-weight: bold;
                    font-size: 8px;
                }
                .data-table td {
                    padding: 4px 3px;
                    border: 1px solid #000;
                    font-size: 7px;
                    vertical-align: middle;
                }
                .page-break {
                    page-break-after: always;
                }
                .serial-no {
                    width: 35px;
                    text-align: center;
                }
                .text-center { text-align: center; }
                .text-right { text-align: right; }
                .amount-column { text-align: right; }
            </style>
        </head>
        <body>

        <!-- Header for all pages -->
        <htmlpageheader name="header">
            <div class="header">
                <div class="header-content">';

        if ($logoPath && file_exists($logoPath)) {
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoExtension = pathinfo($logoPath, PATHINFO_EXTENSION);

            // Add logo at top-center
            $html .= '<img src="data:image/' . $logoExtension . ';base64,' . $logoData . '" class="logo-center" alt="Logo">';
        }

        $html .= '
                    <!-- Corrected header order -->
                    <div class="main-title">REPUBLIC OF KENYA</div>
                    <div class="main-title">MINISTRY OF COOPERATIVES AND MSMES DEVELOPMENT</div>
                    <div class="main-title">UWEZO FUND OVERSIGHT BOARD</div>
                    <div class="report-title">' . ($this->reportName ? strtoupper($this->reportName) : 'DYNAMIC REPORT SUBMISSION FORM') . '</div>

                    <!-- Single line with four items using table layout -->
                    <table class="info-table">
                        <tr>
                            <td>F/Y: ' . ((now()->month >= 7 ? now()->format('Y') . '/' . now()->addYear()->format('Y') : now()->subYear()->format('Y') . '/' . now()->format('Y'))) . '</td>
                            <td>AS AT ' . now()->format('jS F, Y') . '</td>
                        </tr>
                    </table>
                </div>
            </div>
        </htmlpageheader>

        <!-- Content area with proper spacing -->
        <div class="content">';

        // Table code
        $html .= '<table class="data-table">';
        $html .= '<thead><tr>';
        $html .= '<th class="serial-no">SNo.</th>';

        foreach ($headers as $header) {
            $html .= '<th>' . htmlspecialchars($header) . '</th>';
        }

        $html .= '</tr></thead><tbody>';

        // Process data in chunks
        $chunkSize = 200;
        $processed = 0;
        $serialNumber = 1;

        $query->chunk($chunkSize, function ($chunk) use (&$html, &$processed, &$serialNumber, $headers, $queryType) {
            foreach ($chunk as $item) {
                // Apply the same defaulter filter logic for PDF

                if ($processed > 0 && $processed % 40 === 0) {
                    $html .= '</tbody></table>';
                    $html .= '<div class="page-break"></div>';
                    $html .= '<table class="data-table"><thead><tr><th class="serial-no">SNo.</th>';
                    foreach ($headers as $header) {
                        $html .= '<th>' . htmlspecialchars($header) . '</th>';
                    }
                    $html .= '</tr></thead><tbody>';
                }

                $html .= '<tr>';
                $html .= '<td class="serial-no text-center">' . $serialNumber . '</td>';

                foreach ($this->selectedColumns as $column) {
                    if (array_key_exists($column, $this->filterOptions)) {
                        continue;
                    }
                    // $value = $this->formatColumnValue($column, $item);
                    $value = $this->reportService->formatColumnByType($column, $item, $queryType, true);

                    $cellClass = '';
                    if (str_contains($column, 'amount') || str_contains($column, 'arrears')) {
                        $cellClass = 'amount-column';
                    } elseif (str_contains($column, 'date')) {
                        $cellClass = 'text-center';
                    }

                    $html .= '<td class="' . $cellClass . '">' . htmlspecialchars($value) . '</td>';
                }

                $html .= '</tr>';
                $processed++;
                $serialNumber++;
            }
        });


        $html .= '</tbody></table>';
        $html .= '</div>'; // Close content div
        $html .= '</body></html>';

        return $html;
    }
    private function getSelectedFilters()
    {
        return [
            'filterCounty' => $this->filterCounty,
            'filterConstituency' => $this->filterConstituency,
            'filterWard' => $this->filterWard,
            'filterLoanPhase' => $this->filterLoanPhase,
            'filterGroupCategory' => $this->filterGroupCategory,
            'filterGroupActivity' => $this->filterGroupActivity,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'filterDefaulter' => $this->filterDefaulter,
        ];
    }

    private function generateExportFileName($extension)
    {
        $fileName = $this->constituencyName . '_' . ($this->reportName ?: 'dynamic_report');
        return Str::slug($fileName) . '_' . now()->format('Y_m_d_His') . '.' . $extension;
    }




    private function streamLargeReportToCsv($userConstituency)
    {
        // Build query
        $query = $this->buildSmartQuery();
        if (!$query) {
            echo "No data available for export.";
            return;
        }

        // Apply filters
        $this->applySmartFilters($query, $userConstituency);

        // Get total for progress tracking
        $totalRecords = $this->getOptimizedCount($query);
        logger()->info("Streaming export for {$totalRecords} records");

        // Write CSV headers
        $headers = $this->getExportHeaders();
        echo "\"" . implode("\",\"", $headers) . "\"\n";

        // Flush headers immediately
        if (ob_get_level()) {
            ob_flush();
        }
        flush();

        // Stream data in chunks
        $exportedCount = 0;
        $chunkSize = 1000; // Larger chunks for export

        $query->chunkById($chunkSize, function ($chunk) use (&$exportedCount, $totalRecords, $headers) {
            foreach ($chunk as $item) {
                $row = [];
                foreach ($this->selectedColumns as $column) {
                    if (array_key_exists($column, $this->filterOptions)) {
                        continue;
                    }
                    $row[] = $this->escapeCsvValue($this->formatColumnValue($column, $item));
                }
                echo "\"" . implode("\",\"", $row) . "\"\n";
                $exportedCount++;
            }

            // Progress tracking
            if ($totalRecords > 0) {
                $progress = round(($exportedCount / $totalRecords) * 100);
                logger()->info("Export progress: {$progress}% ({$exportedCount}/{$totalRecords})");
            }

            // Flush output buffer
            if (ob_get_level()) {
                ob_flush();
            }
            flush();

            // Check if client disconnected
            if (connection_aborted()) {
                logger()->info("Export cancelled by client after {$exportedCount} records");
                exit;
            }

        }, 'group_loans.id');

        logger()->info("Export completed successfully: {$exportedCount} records");
    }

    private function getExportHeaders()
    {
        $headers = [];
        foreach ($this->selectedColumns as $column) {
            if (array_key_exists($column, $this->filterOptions)) {
                continue;
            }
            $headers[] = $this->getColumnLabel($column);
        }
        return $headers;
    }

    private function getColumnLabel($column)
    {
        return collect($this->availableColumns)
            ->flatMap(fn($cols) => $cols)
            ->get($column, $column);
    }

    private function escapeCsvValue($value)
    {
        return str_replace('"', '""', $value ?? '');
    }

    // Add this method to monitor performance
    private function monitorPerformance($operation, $startTime, $recordCount = null)
    {
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        $memory = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

        $message = "Performance: {$operation} took {$duration}s, Memory: {$memory}MB";
        if ($recordCount) {
            $message .= ", Records: {$recordCount}";
        }

        logger()->info($message);
    }

    // Add method to check if a column is a filter option
    public function isFilterOption($column)
    {
        return array_key_exists($column, $this->filterOptions);
    }

    private function generatePDFContent()
    {
        $this->ensureSelectedColumnsIsArray();
        $content = "REPORT: " . ($this->reportName ?: 'Dynamic Report') . "\n";
        $content .= "Constituency: " . ($this->constituencyName ?? 'unknown') . "\n";
        $content .= "Generated on: " . now()->format('d/m/Y H:i:s') . "\n";
        $content .= "Total Records: " . $this->totalRecords . "\n\n";

        // Add filter information
        $hasFilters = $this->filterLoanPhase || $this->filterWard || $this->filterDefaulter ||
                     $this->filterGroupCategory || $this->filterLoanStatus ||
                     $this->filterFinancedLoans || $this->filterApprovedLoans ||
                     $this->filterDeferredLoans || $this->filterCancelledLoans || $this->filterPendingLoans;

        if ($hasFilters) {
            $content .= "Filters Applied:\n";
            if ($this->filterLoanPhase) {
                $content .= "  - Loan Phase: " . $this->filterLoanPhase . "\n";
            }
            if ($this->filterWard) {
                $content .= "  - Ward: " . $this->filterWard . "\n";
            }
            if ($this->filterDefaulter) {
                $content .= "  - Defaulters Only: Yes\n";
            }
            if ($this->filterGroupCategory) {
                $content .= "  - Group Category: " . $this->filterGroupCategory . "\n";
            }
            if ($this->filterLoanStatus) {
                $content .= "  - Loan Status: " . $this->filterLoanStatus . "\n";
            }
            if ($this->filterFinancedLoans) {
                $content .= "  - Financed Loans Only: Yes\n";
            }
            if ($this->filterApprovedLoans) {
                $content .= "  - Approved Loans Only: Yes\n";
            }
            if ($this->filterDeferredLoans) {
                $content .= "  - Deferred Loans Only: Yes\n";
            }
            if ($this->filterCancelledLoans) {
                $content .= "  - Cancelled Loans Only: Yes\n";
            }
            $content .= "\n";
        }

        // ... rest of the PDF content generation remains the same
        $headers = array_map(function($col) {
            return $this->availableColumns[$this->getColumnCategory($col)][$col] ?? $col;
        }, $this->selectedColumns);

        // Create a simple table format
        $columnWidths = array_fill(0, count($headers), 15);

        $content .= implode(" | ", array_map(function($header, $width) {
            return str_pad($header, $width);
        }, $headers, $columnWidths)) . "\n";

        $content .= str_repeat("-", array_sum($columnWidths) + (count($headers) * 3)) . "\n";

        foreach (array_slice($this->reportData, 0, 50) as $row) {
            $content .= implode(" | ", array_map(function($value, $width) {
                return str_pad(substr($value, 0, $width - 1), $width);
            }, array_values($row), $columnWidths)) . "\n";
        }

        if ($this->totalRecords > 50) {
            $content .= "\n... and " . ($this->totalRecords - 50) . " more records\n";
        }

        return $content;
    }
    public function getCategoryFiltersSelectedProperty()
    {
        return $this->isCategorySelected('filters');
    }

    // Add these computed properties for the view
    public function getCategoryMembersSelectedProperty()
    {
        return $this->isCategorySelected('members');
    }

    public function getCategoryGroupsSelectedProperty()
    {
        return $this->isCategorySelected('groups');
    }

    public function getCategoryLoansSelectedProperty()
    {
        return $this->isCategorySelected('loans');
    }

    public function render()
    {
        return view('livewire.hq.generate-report');
    }


    private function applyDefaulterFilter($query, $queryType)
    {
        if (!$this->filterDefaulter) {
            return $query;
        }

        // Apply defaulter logic based on query type
        switch ($queryType['type']) {
            case 'loan_centric':
                return $query->where(function($q){
                    $q->whereRaw('group_loans.amount_issued > COALESCE(group_loans.amount_repaid, 0)')
                    ->orWhere(function($q2) {
                        $q2->whereNotNull('group_loans.disbursement_date')
                            ->where('group_loans.amount_issued', '>', 0)
                            ->whereRaw('TIMESTAMPDIFF(MONTH, group_loans.disbursement_date, NOW()) > 6');
                    });
                });

            case 'member_centric':
                return $query->whereHas('groupMembers.groupLoan', function($q) {
                    $q->whereRaw('group_loans.amount_issued > COALESCE(group_loans.amount_repaid, 0)')
                    ->orWhere(function($q2) {
                        $q2->whereNotNull('group_loans.disbursement_date')
                            ->where('group_loans.amount_issued', '>', 0)
                            ->whereRaw('TIMESTAMPDIFF(MONTH, group_loans.disbursement_date, NOW()) > 6');
                    });
                });

            case 'group_centric':
                return $query->whereHas('groupLoans', function($q) {
                    $q->whereRaw('group_loans.amount_issued > COALESCE(group_loans.amount_repaid, 0)')
                    ->orWhere(function($q2) {
                        $q2->whereNotNull('group_loans.disbursement_date')
                            ->where('group_loans.amount_issued', '>', 0)
                            ->whereRaw('TIMESTAMPDIFF(MONTH, group_loans.disbursement_date, NOW()) > 6');
                    });
                });
        }

        return $query;
    }
}
