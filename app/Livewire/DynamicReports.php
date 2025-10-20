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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DynamicReports extends Component
{
    // Performance and resource management
    public $performanceMode = false;
    public $chunkSize = 500;
    public $memoryLimitMB = 128;
    public $maxDisplayRecords = 1000;

    // Location filters
    public $constituencies = [];
    public $selectedConstituency = '';
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
    public $filterLoanPhase = '';
    public $filterWard = '';
    public $filterDefaulter = false;
    public $filterGroupCategory = '';
    public $filterLoanStatus = '';
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
            'groups.gfs_code' => 'GFS Code',
            'groups.date_registered' => 'Date Registered',
            'groups.bank' => 'Bank Name',
            'groups.account' => 'Bank Account',
            'groups.category' => 'Group Category',
            'groups.activity' => 'Group Activity',
            'groups.location' => 'Location',
            'groups.box' => 'Box Number',
            'groups.status' => 'Group Status',
            'ward.ward_name' => 'Ward', // Added ward field in groups
        ],
        'loans' => [
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
            'loan_phase.phase' => 'Loan Phase', // Added loan phase field
            'arrears' => 'Arrears', // Added arrears field
            'amount_due' => 'Amount Due', // Added amount due field
            'cumulative_arrears' => 'Cumulative Arrears', // Added cumulative arrears field
            'repayment_date' => 'Date of Repayment Completion', // Added repayment date field
        ],
    ];

    // filters
    public $filterOptions = [
        'financed_loans_filter' => 'Financed Loans',
        'pending_loans_filter' => 'Pending Loans',
        'approved_loans_filter' => 'Approved Loans',
        'deferred_loans_filter' => 'Deferred Loans',
        'cancelled_loans_filter' => 'Cancelled Loans',
    ];

    public $selectedColumns = [];
    public $reportData = [];
    public $loading = false;
    public $reportGenerated = false;
    public $totalRecords = 0;

    // Dynamic data for filters
    public $loanPhases = [];
    public $allWards = [];
    public $groupCategories = ['Youth', 'Women', 'PWD'];
    public $loanStatuses = ['pending', 'deferred', 'approved', 'financed', 'cancelled'];

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

        // NO DEFAULT SELECTED COLUMNS - let users choose what they want
        $this->selectedColumns = ['financed_loans_filter']; // Default to financed loans filter only

        // Automatically set the selected constituency from authenticated user
        $this->selectedConstituency = $this->constituency->constituency_code;

        // Load filter data
        $this->loadFilterData();
    }

    public function hydrate()
    {
        $this->ensureSelectedColumnsIsArray();
    }

    public function updating($property, $value)
    {
        logger()->info("Updating property: {$property} to value: " . print_r($value, true));
        if ($property === 'selectedColumns') {
            $this->ensureSelectedColumnsIsArray();
        }
    }

    public function updated($property, $value)
    {
        if ($property === 'selectedColumns') {
            $this->ensureSelectedColumnsIsArray();
        }
    }

    protected function ensureSelectedColumnsIsArray()
    {
        if (!is_array($this->selectedColumns)) {
            $this->selectedColumns = [];
        }
    }

    protected function loadFilterData()
    {
       $userConstituency = $this->constituency;

        if ($userConstituency) {
            $cacheKey = "filters_{$userConstituency->constituency_code}";

            $this->loanPhases = Cache::remember($cacheKey . '_phases', 3600, function() use ($userConstituency) {
                return LoanPhase::where('constituency_code', $userConstituency->constituency_code)
                    ->orderBy('phase')
                    ->get();
            });

            $this->allWards = Cache::remember($cacheKey . '_wards', 3600, function() use ($userConstituency) {
                return Wards::where('constituency_code', $userConstituency->constituency_code)
                    ->orderBy('ward_name')
                    ->get();
            });
        }
    }

    // section for managing column selections
    // Helper method to get all available columns - used in isAllSelected and toggleAllColumns
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

    // Toggle entire category - add or remove all columns in that category - wire click="toggleCategory('members')"
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

    // Optimized category selection check
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

    // Check if all columns are selected - for "Select All" checkbox
    public function isAllSelected()
    {
        $this->ensureSelectedColumnsIsArray();
        $allColumns = $this->getAllAvailableColumns();

        return count($this->selectedColumns) === count($allColumns);
    }

    // Toggle all columns - select or deselect all
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

    // Clear all columns - wire click="clearAllColumns"
    public function clearAllColumns()
    {
        $this->selectedColumns = [];
    }

    // Remove single column - wire click="removeColumn('members.name')"
    public function removeColumn($column)
    {
        $this->ensureSelectedColumnsIsArray();
        $this->selectedColumns = array_values(array_filter($this->selectedColumns, function ($col) use ($column) {
            return $col !== $column;
        }));
    }

    // Reset to default columns - wire click="resetToDefault"
    public function resetToDefault()
    {
        $this->selectedColumns = ['financed_loans_filter']; // Default to financed loans filter only
    }

    public function generateReport()
    {
        $this->ensureSelectedColumnsIsArray();

        $userConstituency = $this->constituency;

        if (!$userConstituency) {
            $this->dispatch('report-error', [
                'message' => 'No constituency assigned to your account. Please contact administrator.'
            ]);
            return;
        }

        // Validate that we have at least one data column (not just filters)
        $dataColumns = array_filter($this->selectedColumns, function($col) {
            return !array_key_exists($col, $this->filterOptions);
        });

        if (empty($dataColumns)) {
            $this->dispatch('report-error', [
                'message' => 'Please select at least one data field to include in the report.'
            ]);
            return;
        }

        // Log selected columns for debugging
        logger()->info('generateReport() - Selected columns for report: ' . json_encode($this->selectedColumns));
        logger()->info('generateReport() - Data columns only: ' . json_encode($dataColumns));

        $this->loading = true;
        $this->reportGenerated = false;
        $this->showingLimitedData = true; // Reset

        try {
            logger()->info("Attempting to generate report");
            $this->reportData = $this->fetchReportData(false);

            $this->totalRecords = count($this->reportData);
            $this->reportGenerated = true;

            // Prepare success message based on data size
            $message = "Report '{$this->reportName}' generated successfully";


            $message .= ". Showing first {$this->displayLimit} of {$this->totalAvailableRecords} records. Use export to get all data.";


            $this->dispatch('report-generated', [
                'message' => $message,
                'constituency' => $this->constituencyName ?? 'unknown',
                'limited' => true
            ]);

        } catch (\Exception $e) {
            logger()->error('Report generation failed: ' . $e->getMessage());
            $this->dispatch('report-error', [
                'message' => 'Error generating report: ' . $e->getMessage()
            ]);
        }

        $this->loading = false;
    }

    private function fetchReportData($forExport = false)
    {
        $userConstituency = $this->constituency;
        $startTime = microtime(true);
        if (!$userConstituency) {
            return [];
        }

        // Build the base query based on selected columns and their order
        $query = $this->buildSmartQuery();

        if (!$query) {
            return [];
        }

        // Apply filters
        $this->applySmartFilters($query, $userConstituency);

        try {

            // Get total count first
            $this->totalAvailableRecords = $this->getOptimizedCount($query);
            $this->totalActualRecords = $this->totalAvailableRecords;
            logger()->info("Total available records: " . $this->totalAvailableRecords);

            // For large datasets, use optimized formatting
            // if ($this->totalAvailableRecords > 1000 && !$forExport) {
            //     logger()->info("Using chunked data fetching for large dataset: " . $this->totalAvailableRecords);
            //     $this->showingLimitedData = true;
            //     return $this->fetchDataWithChunking($query, $forExport);
            // }

            // Get the results
            if ($forExport) {
                logger()->info("Export data count for export: " . $query->count());
                // For exports: get ALL data (no limits)
                $results = $query->get();
                logger()->info("Export data retrieved for export: " . $results->count());
            } else {
                $this->showingLimitedData = true;
                $results = $query->limit($this->displayLimit)->get();
            }

            $this->monitorPerformance(
                $forExport ? 'Export' : 'Report Generation',
                $startTime,
                count($results)
            );
            logger()->info("Data retrieval completed. Records fetched: " . json_encode($results));
            // Format the results with calculated fields
            return $this->formatResults($results);
        } catch (\Exception $e) {
            logger()->error('Report generation error: ' . $e->getMessage());
            return [];
        }
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

    private function fetchDataWithChunking($query, $forExport = false)
    {
        $limit = $forExport ? null : $this->displayLimit;
        $chunkSize = $this->chunkSize;
        $formattedData = [];
        $processed = 0;
        $memoryWarningSent = false;

        $queryInfo = $this->getQueryType($query);
        $chunkColumn = $queryInfo['chunk_column'];

        logger()->info("Starting chunked data fetching. Chunk size: {$chunkSize}, Limit: " . ($limit ?? 'none'));

        // Use chunkById for better performance with large datasets
        $query->chunkById($chunkSize, function ($chunk) use (&$formattedData, &$processed, $limit, &$memoryWarningSent) {

            // Format this chunk
            $chunkData = $this->formatChunk($chunk);
            $formattedData = array_merge($formattedData, $chunkData);
            $processed += $chunk->count();

            logger()->info("Processed chunk: {$processed} records so far");

            // Check memory usage
            $memoryUsage = memory_get_usage(true) / 1024 / 1024; // MB
            if ($memoryUsage > ($this->memoryLimitMB * 0.8) && !$memoryWarningSent) {
                logger()->warning("High memory usage: {$memoryUsage}MB");
                $memoryWarningSent = true;
            }

            // Force garbage collection every few chunks
            if ($processed % ($this->chunkSize * 5) === 0) {
                gc_collect_cycles();
                logger()->info("Garbage collection triggered at {$processed} records");
            }

            // Stop if we've reached the display limit
            if ($limit && $processed >= $limit) {
                logger()->info("Reached display limit of {$limit} records");
                return false; // Stop chunking
            }

        }, $chunkColumn); // Use the primary key for chunking

        logger()->info("Chunked processing completed. Total processed: " . count($formattedData));

        return $formattedData;
    }

    private function formatChunk($chunk)
    {
        $chunkData = [];

        foreach ($chunk as $item) {
            $row = [];
            foreach ($this->selectedColumns as $column) {
                if (array_key_exists($column, $this->filterOptions)) {
                    continue;
                }
                $row[$column] = $this->formatColumnValue($column, $item);
            }
            $chunkData[] = $row;
        }

        return $chunkData;
    }

    private function buildSmartQuery()
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

        logger()->info("Data columns: " . json_encode($dataColumns));
        logger()->info("Selected loan status filters: " . json_encode($this->selectedLoanStatusFilters));

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
            'group_loans.amount_applied',
            'group_loans.amount_approved',
            'group_loans.amount_issued',
            'group_loans.amount_repaid',
            'group_loans.loan_status',
            'group_loans.level',
            'group_loans.cheque_no',
            'group_loans.status',
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
            'group_loans.loan_phase_id'
        ];

        $dataColumns = array_unique(array_merge($dataColumns, $requiredColumns));
        $query = GroupLoan::with([
            'group' => function($query) {
                $query->select(['id', 'ward_id', 'name', 'category', 'identifier', 'reg_number', 'status', 'gfs_code', 'date_registered', 'bank', 'account', 'activity', 'location', 'box']);
            },
            'group.ward' => function($query) {
                $query->select(['id', 'ward_name', 'constituency_code']);
            },
            'loanPhase' => function($query) {
                $query->select(['id', 'phase']);
            },
            'groupMembers' => function($query) {
                $query->select(['id', 'member_Id', 'group_loans_id', 'role']);
                // Remove the whereHas for now to debug
            },
            'groupMembers.member' => function($query) {
                $query->select(['id', 'member_Id', 'name', 'number', 'gender', 'disabled']);
            }
        ])->select($dataColumns);

        $query->when(true, function($q) {
            logger()->info('Loan centric query with relationships:', [
                'sql' => $q->toSql(),
                'bindings' => $q->getBindings(),
                'with_relationships' => array_keys($q->getEagerLoads())
            ]);
        });

        return $query;
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
                    'id', 'group_id', 'loan_phase_id', 'amount_issued',
                    'amount_repaid', 'loan_status', 'application_date',
                    'disbursement_date', 'amount_applied', 'amount_approved'
                ]);
            },
            'groupMembers.groupLoan.group' => function($query) {
                $query->select(['id', 'ward_id', 'name', 'category', 'identifier', 'reg_number', 'status', 'gfs_code', 'date_registered', 'bank', 'account', 'activity', 'location', 'box']);
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
                    'id', 'group_id', 'loan_phase_id', 'amount_issued',
                    'amount_repaid', 'loan_status', 'application_date',
                    'disbursement_date', 'amount_applied', 'amount_approved'
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










    private function applySmartFilters($query, $userConstituency)
    {
        logger()->info("Applying smart filters");
        // Get the query type to apply appropriate filters
        $queryType = $this->getQueryType($query);

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
    private function getSelectColumns()
    {
        $selectColumns = [];

        foreach ($this->selectedColumns as $column) {
            // Skip filter options - they don't represent actual columns
            if (array_key_exists($column, $this->filterOptions)) {
                continue;
            }

            switch ($column) {
                case 'arrears':
                    $selectColumns[] = DB::raw('(group_loans.amount_issued - group_loans.amount_repaid) as arrears');
                    break;
                case 'cumulative_arrears':
                    $selectColumns[] = DB::raw('(
                        SELECT SUM(gl.amount_issued - gl.amount_repaid)
                        FROM group_loans gl
                        WHERE gl.group_id = groups.id
                        AND (gl.amount_issued - gl.amount_repaid) > 0
                    ) as cumulative_arrears');
                    break;
                case 'amount_due':
                    $selectColumns[] = DB::raw('(group_loans.amount_issued - group_loans.amount_repaid) as amount_due');
                    break;
                case 'repayment_date':
                    // This would need your business logic for repayment date calculation
                    $selectColumns[] = DB::raw('NULL as repayment_date'); // Placeholder
                    break;
                default:
                    $selectColumns[] = $column;
                    break;
            }
        }

        return $selectColumns;
    }

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
            $query->whereHas('group', function($q) {
                $q->where('category', $this->filterGroupCategory);
            });
        }

        // Apply loan status filter
        if ($this->filterLoanStatus) {
            $query->where('loan_status', $this->filterLoanStatus);
        }

        // Apply defaulter filter
        if ($this->filterDefaulter) {
            $query->whereRaw('(amount_issued - amount_repaid) > 0');
        }

        // Apply loan status filters from filter options
        $this->applyLoanStatusFilters($query);
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
            $query->whereHas('groupMembers.groupLoan.group', function($q) {
                $q->where('category', $this->filterGroupCategory);
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
            $query->where('category', $this->filterGroupCategory);
        }
    }

    private function formatColumnValue($column, $item)
    {
        // Handle different query types
        if ($item instanceof GroupLoan) {
            return $this->formatLoanCentricColumn($column, $item);
        } elseif ($item instanceof Member) {
            return $this->formatMemberCentricColumn($column, $item);
        } elseif ($item instanceof Group) {
            return $this->formatGroupCentricColumn($column, $item);
        }

        return 'N/A';
    }

    private function formatLoanCentricColumn($column, $item)
    {
        // $item is a GroupLoan instance with relationships loaded
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
            case 'groups.category':
                return $item->group->category ?? 'N/A';
            case 'groups.activity':
                return $item->group->activity ?? 'N/A';
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
                return $item->amount_applied ? number_format($item->amount_applied, 2) : '0.00';
            case 'group_loans.amount_approved':
                return $item->amount_approved ? number_format($item->amount_approved, 2) : '0.00';
            case 'group_loans.amount_issued':
                return $item->amount_issued ? number_format($item->amount_issued, 2) : '0.00';
            case 'group_loans.amount_repaid':
                return $item->amount_repaid ? number_format($item->amount_repaid, 2) : '0.00';
            case 'group_loans.loan_status':
                return $item->loan_status ?? 'N/A';
            case 'group_loans.level':
                return $item->level ?? 'N/A';
            case 'group_loans.cheque_no':
                return $item->cheque_no ?? 'N/A';
            case 'group_loans.status':
                return $item->status ?? 'N/A';
            case 'group_loans.comments':
                return $item->comments ?? 'N/A';

            // Member columns - handle multiple members by showing first member or aggregating
            case 'members.member_Id':
                if ($item->groupMembers->isNotEmpty()) {
                    $memberIds = $item->groupMembers->pluck('member.member_Id')->filter()->unique();
                    return $memberIds->join(', ');
                }
                return 'N/A';
            case 'members.name':
                if ($item->groupMembers->isNotEmpty()) {
                    $memberNames = $item->groupMembers->pluck('member.name')->filter()->unique();
                    return $memberNames->join(', ');
                }
                return 'N/A';
            case 'members.number':
                if ($item->groupMembers->isNotEmpty()) {
                    $memberNumbers = $item->groupMembers->pluck('member.number')->filter()->unique();
                    return $memberNumbers->join(', ');
                }
                return 'N/A';
            case 'members.gender':
                if ($item->groupMembers->isNotEmpty()) {
                    $genders = $item->groupMembers->pluck('member.gender')->filter()->unique();
                    return $genders->join(', ');
                }
                return 'N/A';
            case 'members.disabled':
                if ($item->groupMembers->isNotEmpty()) {
                    $disabledStatus = $item->groupMembers->map(function($gm) {
                        return isset($gm->member->disabled) ? ($gm->member->disabled ? 'Yes' : 'No') : 'N/A';
                    })->unique();
                    return $disabledStatus->join(', ');
                }
                return 'N/A';
            case 'group_members.role':
                if ($item->groupMembers->isNotEmpty()) {
                    $roles = $item->groupMembers->pluck('role')->filter()->unique();
                    return $roles->join(', ');
                }
                return 'N/A';

            // Calculated fields
            case 'arrears':
                $arrears = ($item->amount_issued ?? 0) - ($item->amount_repaid ?? 0);
                return number_format(max($arrears, 0), 2);
            case 'amount_due':
                $amountDue = ($item->amount_issued ?? 0) - ($item->amount_repaid ?? 0);
                return number_format(max($amountDue, 0), 2);
            case 'cumulative_arrears':
                // This would need to be calculated per group, not per loan
                return $item->cumulative_arrears ?? '0.00';
            case 'repayment_date':
                if ($item->disbursement_date) {
                    return \Carbon\Carbon::parse($item->disbursement_date)->addMonths(12)->format('d/m/Y');
                }
                return 'N/A';

            default:
                return $item->{$this->getColumnAlias($column)} ?? 'N/A';
        }
    }

    private function formatMemberCentricColumn($column, $item)
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

            case 'groups.gfs_code':
                return $this->getMemberGroupInfo($item, 'gfs_code', false); // ← No unique

            case 'groups.date_registered':
                return $this->getMemberGroupInfo($item, 'date_registered', false); // ← No unique

            case 'groups.bank':
                return $this->getMemberGroupInfo($item, 'bank', false); // ← No unique

            case 'groups.account':
                return $this->getMemberGroupInfo($item, 'account', false); // ← No unique

            case 'groups.category':
                return $this->getMemberGroupInfo($item, 'category', false); // ← No unique

            case 'groups.activity':
                return $this->getMemberGroupInfo($item, 'activity', false); // ← No unique

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
                    return $groupMember->groupLoan->group->category ?? null;
                case 'identifier':
                    return $groupMember->groupLoan->group->identifier ?? null;
                case 'reg_number':
                    return $groupMember->groupLoan->group->reg_number ?? null;
                case 'gfs_code':
                    return $groupMember->groupLoan->group->gfs_code ?? null;
                case 'date_registered':
                    return $groupMember->groupLoan->group->date_registered ? \Carbon\Carbon::parse($groupMember->groupLoan->group->date_registered)->format('d/m/Y') : null;
                case 'bank':
                    return $groupMember->groupLoan->group->bank ?? null;
                case 'account':
                    return $groupMember->groupLoan->group->account ?? null;
                case 'activity':
                    return $groupMember->groupLoan->group->activity ?? null;
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

    private function formatGroupCentricColumn($column, $item)
    {

        switch ($column) {
            case 'groups.name':
                return $item->name ?? 'N/A';
            case 'groups.identifier':
                return $item->identifier ?? 'N/A';
            case 'groups.reg_number':
                return $item->reg_number ?? 'N/A';
            case 'groups.category':
                return $item->category ?? 'N/A';
            case 'groups.activity':
                return $item->activity ?? 'N/A';
            case 'groups.box':
                return $item->box ?? 'N/A';
            case 'groups.status':
                return $item->status ?? 'N/A';
            case 'groups.gfs_code':
                return $item->gfs_code ?? 'N/A';
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


    private function getTablesNeeded()
    {
        $tables = [];

        foreach ($this->selectedColumns as $column) {
            if (str_contains($column, '.')) {
                $table = explode('.', $column)[0];
                $tables[] = $table;
            } elseif (in_array($column, ['arrears', 'cumulative_arrears'])) {
                // Calculated fields need group_loans
                $tables[] = 'group_loans';
            }
        }

        return array_unique($tables);
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

    private function applyFilters($query, $userConstituency)
    {
        $tablesNeeded = $this->getTablesNeeded();

        // Always filter by constituency based on available tables
        if (in_array('loan_phases', $tablesNeeded)) {
            $query->where('loan_phases.constituency_code', $userConstituency);
        } elseif (in_array('wards', $tablesNeeded)) {
            $query->where('wards.constituency_code', $userConstituency);
        }

        // Apply other filters
        if ($this->filterLoanPhase) {
            $query->where('loan_phases.phase', $this->filterLoanPhase);
        }

        if ($this->filterWard) {
            $query->where('wards.ward_name', $this->filterWard);
        }

        if ($this->filterDefaulter) {
            $query->whereRaw('(group_loans.amount_issued - group_loans.amount_repaid) > 0');
        }

        if ($this->filterGroupCategory) {
            $query->where('groups.category', $this->filterGroupCategory);
        }

        if ($this->filterLoanStatus) {
            $query->where('group_loans.loan_status', $this->filterLoanStatus);
        }

        // Apply the loan status filters based on the selected filter options
        $this->applyLoanStatusFilters($query);
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
        return $results->map(function ($item) {
            $row = [];

            foreach ($this->selectedColumns as $column) {
                // Skip filter options in the output - they should not appear as columns
                if (array_key_exists($column, $this->filterOptions)) {
                    continue;
                }

                $row[$column] = $this->formatColumnValue($column, $item);
            }

            return $row;
        })->toArray();
    }

    private function formatColumnValue2($column, $item)
    {
        $value = $item->{$this->getColumnAlias($column)} ?? 'N/A';

        // Format specific column types
        if (in_array($column, ['group_loans.amount_applied', 'group_loans.amount_approved',
                              'group_loans.amount_issued', 'group_loans.amount_repaid',
                              'arrears', 'cumulative_arrears'])) {
            return $value !== 'N/A' ? number_format($value, 2) : '0.00';
        }

        if (str_contains($column, 'date')) {
            return $value !== 'N/A' && $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : 'N/A';
        }

        if ($column === 'members.disabled') {
            return $value === 1 ? 'Yes' : ($value === 0 ? 'No' : 'N/A');
        }

        return $value;
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
            $this->filterLoanPhase,
            $this->filterWard,
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
        $this->filterLoanPhase = '';
        $this->filterWard = '';
        $this->filterDefaulter = false;
        $this->filterGroupCategory = '';
        $this->filterLoanStatus = '';
    }

    // public function exportToExcel()
    // {
    //     if (empty($this->reportData)) {
    //         $this->dispatch('export-error', ['message' => 'No data to export']);
    //         return;
    //     }

    //     // Generate fresh data for export (without limits)
    //     $exportData = $this->fetchReportData(true); // true = for export

    //     if (empty($exportData)) {
    //         $this->dispatch('export-error', ['message' => 'No data available for export']);
    //         return;
    //     }

    //     $fileName = $this->reportName ?: 'dynamic_report';
    //     $fileName = Str::slug($fileName) . '_' . now()->format('Y_m_d_His') . '.csv';

    //     return response()->streamDownload(function () use ($exportData) {
    //         echo $this->generateExcelContent($exportData);
    //     }, $fileName);
    // }

    public function exportToExcel()
    {
        logger()->info("Exporting to Excel...");
        $this->ensureSelectedColumnsIsArray();

        if (empty($this->selectedColumns)) {
            $this->dispatch('export-error', ['message' => 'No columns selected for export']);
            return;
        }
        $userConstituency = $this->constituency;
        $this->loading = true;

        // try {
        //     // Generate fresh data for export (without limits)
        //     $exportData = $this->fetchReportData(true); // true = for export
        //     logger()->info("Export data count in exportToExcel(): " . count($exportData));
        //     if (empty($exportData)) {
        //         $this->dispatch('export-error', ['message' => 'No data available for export']);
        //         $this->loading = false;
        //         return;
        //     }

        //     $fileName = $this->constituencyName . '_' . ($this->reportName ?: 'dynamic_report');
        //     $fileName = Str::slug($fileName) . '_' . now()->format('Y_m_d_His') . '.csv';

        //     $this->dispatch('export-success', [
        //         'message' => "Export completed! Downloaded " . count($exportData) . " records."
        //     ]);

        //     $this->loading = false;

        //     return response()->streamDownload(function () use ($exportData) {
        //         echo $this->generateExcelContent($exportData);
        //     }, $fileName);

        // } catch (\Exception $e) {
        //     logger()->error('Export error: ' . $e->getMessage());
        //     $this->dispatch('export-error', [
        //         'message' => 'Export failed: ' . $e->getMessage()
        //     ]);
        //     $this->loading = false;
        // }
        try {
            $fileName = $this->constituencyName . '_' . ($this->reportName ?: 'dynamic_report');
            $fileName = Str::slug($fileName) . '_' . now()->format('Y_m_d_His') . '.csv';

            $this->dispatch('export-started', [
                'message' => "Starting export process..."
            ]);

            $this->loading = false;

            return response()->streamDownload(function () use ($userConstituency) {
                $this->streamLargeReportToCsv($userConstituency);
            }, $fileName);

        } catch (\Exception $e) {
            logger()->error('Export error: ' . $e->getMessage());
            $this->dispatch('export-error', [
                'message' => 'Export failed: ' . $e->getMessage()
            ]);
            $this->loading = false;
        }
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

    private function buildOptimizedQuery()
    {
        $this->ensureSelectedColumnsIsArray();

        $dataColumns = array_filter($this->selectedColumns, function($col) {
            return !array_key_exists($col, $this->filterOptions);
        });

        if (empty($dataColumns)) {
            return null;
        }

        // Use database-level formatting for better performance
        $selectColumns = $this->getOptimizedSelectColumns($dataColumns);

        // Determine query type based on selected columns
        $needsLoanData = $this->needsLoanData($dataColumns);
        $selectedLoanStatusFilters = array_filter($this->selectedColumns, function($col) {
            return array_key_exists($col, $this->filterOptions);
        });

        if ($needsLoanData || !empty($selectedLoanStatusFilters)) {
            return $this->buildOptimizedLoanCentricQuery($selectColumns);
        } else {
            return $this->buildOptimizedNonLoanQuery($dataColumns);
        }
    }

    private function getOptimizedSelectColumns($dataColumns)
    {
        $selectColumns = [];

        foreach ($dataColumns as $column) {
            switch ($column) {
                case 'arrears':
                    $selectColumns[] = DB::raw('(group_loans.amount_issued - group_loans.amount_repaid) as arrears');
                    break;
                case 'amount_due':
                    $selectColumns[] = DB::raw('(group_loans.amount_issued - group_loans.amount_repaid) as amount_due');
                    break;
                case 'members.disabled':
                    // Database-level formatting
                    $selectColumns[] = DB::raw("CASE WHEN members.disabled = 1 THEN 'Yes' WHEN members.disabled = 0 THEN 'No' ELSE 'N/A' END as members_disabled");
                    break;
                case 'group_loans.application_date':
                case 'group_loans.disbursement_date':
                    // Database-level date formatting
                    $alias = str_replace('.', '_', $column);
                    $selectColumns[] = DB::raw("DATE_FORMAT({$column}, '%d/%m/%Y') as {$alias}");
                    break;
                default:
                    $selectColumns[] = $column;
                    break;
            }
        }

        return $selectColumns;
    }

    private function buildOptimizedLoanCentricQuery($selectColumns)
    {
        logger()->info("Building optimized loan-centric query");

        $query = GroupLoan::query();

        // Select only needed columns
        if (!empty($selectColumns)) {
            $query->select($selectColumns);
        }

        // Eager load only necessary relationships
        $query->with([
            'group.ward',
            'loanPhase',
            'groupMembers.member' => function($query) {
                $query->select(['id', 'member_Id', 'name', 'number', 'gender', 'disabled']);
            }
        ]);

        return $query;
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

    public function exportToPDF()
    {
        if (empty($this->reportData)) {
            $this->dispatch('export-error', ['message' => 'No data to export']);
            return;
        }
        $fileName = $this->reportName ?: 'dynamic_report';
        $fileName = Str::slug($fileName) . '_' . now()->format('Y_m_d_His') . '.txt';

        return response()->streamDownload(function () {
            echo $this->generatePDFContent();
        }, $fileName);
    }

    // private function generateExcelContent($exportData = null)
    // {
    //     logger()->info("generating excel content...: ". ($exportData ? count($exportData) : 'no export data'));
    //     $dataToExport = $exportData ?? $this->reportData;

    //     $this->ensureSelectedColumnsIsArray();

    //     // Filter out the filter options from headers
    //     $displayColumns = array_filter($this->selectedColumns, function($col) {
    //         return !array_key_exists($col, $this->filterOptions);
    //     });

    //     $headers = array_map(function($col) {
    //         return $this->availableColumns[$this->getColumnCategory($col)][$col] ?? $col;
    //     }, $displayColumns);

    //     $output = "\"" . implode("\",\"", $headers) . "\"\n";

    //     foreach ($dataToExport as $row) {
    //         $output .= "\"" . implode("\",\"", array_values($row)) . "\"\n";
    //     }

    //     return $output;
    // }
    private function generateExcelContent($exportData = null)
    {
        logger()->info("generating excel content...: ". ($exportData ? count($exportData) : 'no export data'));
        $dataToExport = $exportData ?? $this->reportData;

        $this->ensureSelectedColumnsIsArray();

        // Filter out the filter options from headers
        $displayColumns = array_filter($this->selectedColumns, function($col) {
            return !array_key_exists($col, $this->filterOptions);
        });

        $headers = array_map(function($col) {
            return $this->availableColumns[$this->getColumnCategory($col)][$col] ?? $col;
        }, $displayColumns);

        $output = "\"" . implode("\",\"", $headers) . "\"\n";

        foreach ($dataToExport as $row) {
            $output .= "\"" . implode("\",\"", array_values($row)) . "\"\n";
        }

        return $output;
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
        $this->ensureSelectedColumnsIsArray();
        $this->loadFilterData();

        return view('livewire.reports.dynamic-reports');
    }
}
