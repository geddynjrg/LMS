<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportService
{

    protected array $statusFilterMap = [
        'financed_loans_filter'  => 'financed',
        'pending_loans_filter'   => 'pending',
        'approved_loans_filter'  => 'approved',
        'deferred_loans_filter'  => 'deferred',
        'cancelled_loans_filter' => 'cancelled',
    ];

    /**
     * Main entry point: builds query, applies filters, gets count & results, formats
     *
     * @return array ['total' => int, 'records' => Collection, 'formatted' => array]
     */
    public function generateReport(
        array $dataColumns,
        array $loanStatusFilter,
        array $selectedFilters,
        bool $forExport = false,
        int $previewLimit = 1000
    ): array {
        // Build base query (no filters yet)
        $buildResult = $this->buildQuery($dataColumns, $selectedFilters);
        $query = $buildResult['query'];
        $queryType = $buildResult['type'];

        if (!$query) {
            return [
                'total' => 0,
                'records' => collect(),
                'formatted' => []
            ];
        }

        // Apply all filters (centralized)
        $this->applyFilters($query, $selectedFilters, $loanStatusFilter);

        if ($forExport) return [
            'query' => $query,
            'query_type' => $queryType,
        ];

        $total = $query->get()->count();

        // Fetch results
        $records = $this->getPreviewResults($query, $previewLimit);

        // Format results
        $formatted = $this->formatResults($records, $dataColumns, $queryType);

        return [
            'total'     => $total,
            'records'   => $records,
            'formatted' => $formatted,
            'type'      => $queryType
        ];
    }


    /**
     * Checks if the selected columns require a loan-centric query
     * (anything that depends on group_loans and loan_phases.)
    */

    public function requiresLoanCentricQuery(array $dataColumns): bool
    {
        if (empty($dataColumns)) {
            return false;
        }

        foreach ($dataColumns as $col) {
            // Direct prefix matches
            if (str_starts_with($col, 'group_loans.') ||
                str_starts_with($col, 'loan_phase.') ||
                str_starts_with($col, 'loan_phases.')) {
                return true;
            }

            // Known calculated columns that depend on group_loans
            if (in_array($col, ['arrears', 'repayment_date', 'defaulter_status'])) {
                return true;
            }

            // Column map deep check
            if (isset($this->columnMap[$col])) {
                $def = $this->columnMap[$col];
                $select = $def['select'] ?? '';

                if (in_array('loan_phases', $def['joins'] ?? []) ||
                    str_contains($select, 'gl.') ||
                    str_contains($select, 'lp.')) {
                    return true;
                }
            }
        }

        return false;
    }


    /**
     * Main method to build the appropriate Eloquent query based on selected columns
     */
    public function buildQuery(array $dataColumns, array $filters): array
    {
        if (empty($dataColumns)) {
            return ['query' => null, 'type' => 'unknown'];
        }

        if ($this->requiresLoanCentricQuery($dataColumns)) {
            return [
                'query' => $this->buildLoanCentricBase($dataColumns, $filters),
                'type'  => 'loan_centric'
            ];
        }

        $primaryEntity = $this->determinePrimaryEntity($dataColumns);

        if ($primaryEntity === 'members') {
            return [
                'query' => $this->buildMemberCentricBase($dataColumns, $filters),
                'type'  => 'member_centric'
            ];
        }

        return [
            'query' => $this->buildGroupCentricBase($dataColumns, $filters),
            'type'  => 'group_centric'
        ];
    }

    /**
     * Determines whether member-centric or group-centric query is more appropriate
     */
    private function determinePrimaryEntity(array $dataColumns): string
    {
        $counts = [
            'groups'  => 0,
            'members' => 0,
        ];

        foreach ($dataColumns as $column) {
            if (str_starts_with($column, 'members.')) {
                $counts['members']++;
            } elseif (str_starts_with($column, 'groups.') || str_starts_with($column, 'location.')) {
                $counts['groups']++;
            } elseif (str_starts_with($column, 'group_members.')) {
                $counts['members']++;
            }
        }

        arsort($counts);

        return array_key_first($counts) ?? 'groups';
    }

    /**
     * Builds a member-centric report query using Query Builder.
     * One row per member — multiple loans/groups flattened with GROUP_CONCAT.
     */
    private function buildMemberCentricBase(array $dataColumns, array $filters): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('members as m');

        $query->leftJoin('group_members as gm', 'm.member_Id', '=', 'gm.member_Id')
              ->leftJoin('group_loans as gl', 'gm.group_loans_id', '=', 'gl.id')
              ->leftJoin('groups as g', 'gl.group_id', '=', 'g.id')
              ->leftJoin('wards as w', 'g.ward_id', '=', 'w.id');

        // ── 1. Always include primary key for grouping & identification ────────
        $selects = ['m.member_Id', 'm.id'];
        $groupBy = ['m.id', 'm.member_Id'];


        // ── 2. Add user-selected member columns ────────────────────────────────
        $memberColumns = array_filter($dataColumns, fn($col) => str_starts_with($col, 'members.'));
        foreach ($memberColumns as $col) {
            // Remove table prefix for clean alias
            $field = str_replace('members.', '', $col);
            $selects[] = "m.{$field} as {$field}";
            if ($field !== 'member_Id'){
                $groupBy[] = "m.{$field}";
            }

        }

        if (in_array('group_members.role', $dataColumns)) {
            $selects[] = 'gm.role as group_member_role';
            $groupBy[] = 'gm.role';
        }

        $needsWard = in_array('location.ward_name', $dataColumns) || !empty($filters['filterWard']);

        $needsConstituency = in_array('location.constituency_name', $dataColumns) || !empty($filters['filterConstituency']);

        $needsCounty= in_array('location.county_name', $dataColumns) || !empty($filters['filterCounty']);

        if ($needsWard || $needsConstituency || $needsCounty) {
            $this->addSelect($selects, $dataColumns, 'location.ward_name', DB::raw('MAX(w.ward_name) as ward_name'));
        }

        if ($needsConstituency || $needsCounty) {
            $query->leftJoin('constituencies as c', 'w.constituency_code', '=', 'c.constituency_code');

            $this->addSelect($selects, $dataColumns, 'location.constituency_name', DB::raw('MAX(c.constituency_name) as constituency_name'));
        }

        if ($needsCounty) {
            $query->leftJoin('counties as co', 'c.county_code', '=', 'co.county_code');

            $this->addSelect($selects, $dataColumns, 'location.county_name', DB::raw('MAX(co.county_name) as county_name'));
        }

        $groupMap = [
            'groups.name'            => 'MAX(g.name) as group_name',
            'groups.code'            => 'MAX(g.code) as group_code',
            'groups.identifier'      => 'MAX(g.identifier) as group_identifier',
            'groups.reg_number'      => 'MAX(g.reg_number) as group_reg_number',
            'groups.date_registered' => 'MAX(g.date_registered) as group_date_registered',
            'groups.bank'            => 'MAX(g.bank) as group_bank',
            'groups.account'         => 'MAX(g.account) as group_account',
            'groups.location'        => 'MAX(g.location) as group_location',
            'groups.box'             => 'MAX(g.box) as group_box',
        ];

        $groupKeys = array_keys($groupMap);

        if ($this->hasAny($dataColumns, $groupKeys)) {
            foreach ($groupMap as $key => $sql) {
                $this->addSelect($selects, $dataColumns, $key, DB::raw($sql));
            }
        }

        $query->groupBy($groupBy);

        $query->orderBy('m.name');

        $query->select($selects);

        return $query;

    }

    /**
     * Builds a group-centric report query using Query Builder.
     * One row per group — multiple members/loans flattened with GROUP_CONCAT.
     */
    private function buildGroupCentricBase(array $dataColumns, array $filters): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('groups as g');

        $query->leftJoin('group_loans as gl', 'g.id', '=', 'gl.group_id');

        $selects = ['g.id'];

        $groupBy = ['g.id'];

        $groupColumns = array_filter($dataColumns, fn($col) => str_starts_with($col, 'groups.'));

        foreach ($groupColumns as $col) {
            $field = str_replace('groups.', '', $col);
            $selects[] = "g.{$field} as {$field}";
            $groupBy[] = "g.{$field}";
        }

        $needsWard = in_array('location.ward_name', $dataColumns) || !empty($filters['filterWard']);

        $needsConstituency = in_array('location.constituency_name', $dataColumns) || !empty($filters['filterConstituency']);

        $needsCounty= in_array('location.county_name', $dataColumns) || !empty($filters['filterCounty']);

        if ($needsWard || $needsConstituency || $needsCounty) {
            $query->leftJoin('wards as w', 'g.ward_id', '=', 'w.id');

            if (in_array('location.ward_name', $dataColumns)) {
                $selects[] = 'w.ward_name as ward_name';
                $groupBy[] = 'w.ward_name';
            }
        }

        if ($needsConstituency || $needsCounty) {
            $query->leftJoin('constituencies as c', 'w.constituency_code', '=', 'c.constituency_code');

            if (in_array('location.constituency_name', $dataColumns)) {
                $selects[] = 'c.constituency_name as constituency_name';
                $groupBy[] = 'c.constituency_name';
            }
        }

        if ($needsCounty) {
            $query->leftJoin('counties as co', 'c.county_code', '=', 'co.county_code');

            if (in_array('location.county_name', $dataColumns)) {
                $selects[] = 'co.county_name as county_name';
                $groupBy[] = 'co.county_name';
            }
        }

        $memberKeys = ['count_members', 'members.name', 'members.member_Id', 'members.number', 'members.gender', 'members.disabled', 'group_members.role'];
        if ($this->hasAny($dataColumns, $memberKeys)) {
            $query->leftJoin('group_members as gm', 'gl.id', '=', 'gm.group_loans_id')
              ->leftJoin('members as m', 'gm.member_Id', '=', 'm.member_Id');

            $this->addSelect($selects, $dataColumns, 'count_members', DB::raw("COUNT(gm.member_Id) as count_members"));
            $this->addSelect($selects, $dataColumns, 'members.name', DB::raw("GROUP_CONCAT(m.name SEPARATOR ', ') as member_names"));
            $this->addSelect($selects, $dataColumns, 'members.member_Id', DB::raw("GROUP_CONCAT(m.member_Id SEPARATOR ', ') as member_ids"));
            $this->addSelect($selects, $dataColumns, 'members.number', DB::raw("GROUP_CONCAT(m.number SEPARATOR ', ') as member_numbers"));
            $this->addSelect($selects, $dataColumns, 'members.gender', DB::raw("GROUP_CONCAT(m.gender SEPARATOR ', ') as member_genders"));
            $this->addSelect($selects, $dataColumns, 'members.disabled', DB::raw("GROUP_CONCAT(m.disabled SEPARATOR ', ') as member_disabled_status"));
            $this->addSelect($selects, $dataColumns, 'group_members.role', DB::raw("GROUP_CONCAT(gm.role SEPARATOR ', ') as member_roles"));
        }


        $query->groupBy($groupBy);

        $query->orderBy('g.name');

        $query->select($selects);

        return $query;
    }

    private function buildLoanCentricBase(array $dataColumns, array $filters): \Illuminate\Database\Query\Builder
    {
        // dd($dataColumns, $filters);
        // dd('Building loan-centric query with columns:', $dataColumns, 'and filters:', $filters);
        $query = DB::table('group_loans as gl');

        $query->leftJoin('groups as g', 'gl.group_id', '=', 'g.id')
              ->leftJoin('loan_phases as lp', 'gl.loan_phase_id', '=', 'lp.id');

        $selects = ['gl.id'];

        $groupBy = ['gl.id'];

        $loanColumns = array_filter($dataColumns, fn($col) => str_starts_with($col, 'group_loans.'));

        foreach ($loanColumns as $col) {
            $field = str_replace('group_loans.', '', $col);
            $selects[] = "gl.{$field} as {$field}";
            $groupBy[] = "gl.{$field}";
        }

        $needsWard = in_array('location.ward_name', $dataColumns) || !empty($filters['filterWard']);

        $needsConstituency = in_array('location.constituency_name', $dataColumns) || !empty($filters['filterConstituency']);

        $needsCounty= in_array('location.county_name', $dataColumns) || !empty($filters['filterCounty']);

        if ($needsWard || $needsConstituency || $needsCounty) {
            $query->leftJoin('wards as w', 'g.ward_id', '=', 'w.id');

            $this->addSelect($selects, $dataColumns, 'location.ward_name', DB::raw('MAX(w.ward_name) as ward_name'));
        }

        if ($needsConstituency || $needsCounty) {
            $query->leftJoin('constituencies as c', 'w.constituency_code', '=', 'c.constituency_code');

            $this->addSelect($selects, $dataColumns, 'location.constituency_name', DB::raw('MAX(c.constituency_name) as constituency_name'));
        }

        if ($needsCounty) {
            $query->leftJoin('counties as co', 'c.county_code', '=', 'co.county_code');

            $this->addSelect($selects, $dataColumns, 'location.county_name', DB::raw('MAX(co.county_name) as county_name'));
        }

        $this->addSelect($selects, $dataColumns, 'loan_phase.phase', DB::raw('MAX(lp.phase) as loan_phase'));

        $this->addSelect($selects, $dataColumns, 'arrears', DB::raw("GREATEST(COALESCE(gl.amount_issued, 0) - COALESCE(gl.amount_repaid, 0), 0) AS arrears"));
        if (in_array('arrears', $dataColumns)){
            if (!in_array('amount_issued', $groupBy)) $groupBy[] = 'gl.amount_issued';
            if (!in_array('amount_repaid', $groupBy)) $groupBy[] = 'gl.amount_repaid';
        }

        if (in_array('defaulter_status', $dataColumns)) {
            $this->addSelect($selects, $dataColumns, 'defaulter_status', DB::raw("IF(gl.is_defaulted = 1, 'Defaulted', 'Not Defaulted') AS defaulter_status"));
            $groupBy[] = 'is_defaulted';
        }

        if (in_array('repayment_date', $dataColumns)) {
            $months = config('reports.repayment_months', 30);
            $selects[] = DB::raw("DATE_ADD(gl.disbursement_date, INTERVAL {$months} MONTH) AS repayment_date");
            if (!in_array('disbursement_date', $groupBy)){
                $groupBy[] = 'disbursement_date';
            }
        }

        $memberKeys = ['count_members', 'members.name', 'members.member_Id', 'members.number', 'members.gender', 'members.disabled', 'group_members.role'];
        if ($this->hasAny($dataColumns, $memberKeys)) {
            $query->leftJoin('group_members as gm', 'gl.id', '=', 'gm.group_loans_id')
              ->leftJoin('members as m', 'gm.member_Id', '=', 'm.member_Id');

            $this->addSelect($selects, $dataColumns, 'count_members', DB::raw("COUNT(gm.member_Id) as count_members"));
            $this->addSelect($selects, $dataColumns, 'members.name', DB::raw("GROUP_CONCAT(m.name SEPARATOR ', ') as member_names"));
            $this->addSelect($selects, $dataColumns, 'members.member_Id', DB::raw("GROUP_CONCAT(m.member_Id SEPARATOR ', ') as member_ids"));
            $this->addSelect($selects, $dataColumns, 'members.number', DB::raw("GROUP_CONCAT(m.number SEPARATOR ', ') as member_numbers"));
            $this->addSelect($selects, $dataColumns, 'members.gender', DB::raw("GROUP_CONCAT(m.gender SEPARATOR ', ') as member_genders"));
            $this->addSelect($selects, $dataColumns, 'members.disabled', DB::raw("GROUP_CONCAT(m.disabled SEPARATOR ', ') as member_disabled_status"));
            $this->addSelect($selects, $dataColumns, 'group_members.role', DB::raw("GROUP_CONCAT(gm.role SEPARATOR ', ') as member_roles"));
        }

        $groupMap = [
            'groups.name'            => 'MAX(g.name) as group_name',
            'groups.code'            => 'MAX(g.code) as group_code',
            'groups.identifier'      => 'MAX(g.identifier) as group_identifier',
            'groups.reg_number'      => 'MAX(g.reg_number) as group_reg_number',
            'groups.date_registered' => 'MAX(g.date_registered) as group_date_registered',
            'groups.bank'            => 'MAX(g.bank) as group_bank',
            'groups.account'         => 'MAX(g.account) as group_account',
            'groups.location'        => 'MAX(g.location) as group_location',
            'groups.box'             => 'MAX(g.box) as group_box',
        ];

        $groupKeys = array_keys($groupMap);

        if ($this->hasAny($dataColumns, $groupKeys)) {
            foreach ($groupMap as $key => $sql) {
                $this->addSelect($selects, $dataColumns, $key, DB::raw($sql));
            }
        }

        $query->groupBy($groupBy);

        $query->orderBy('gl.id');

        $query->select($selects);

        return $query;
    }

    /**
     * Check if ANY of the target keys exist in the data columns array.
     */
    private function hasAny(array $dataColumns, array $targets): bool
    {
        return !empty(array_intersect($targets, $dataColumns));
    }

    /**
     * Add a column to the select array only if it was requested by the user.
     */
    private function addSelect(array &$selects, array $dataColumns, string $key, $sql): void
    {
        if (in_array($key, $dataColumns)) {
            $selects[] = $sql;
        }
    }

    private function needMembersData(array $dataColumns): void
    {
        foreach ($dataColumns as $col) {
            $query->leftJoin('group_members as gm', 'gl.id', '=', 'gm.group_loans_id');
            $query->leftJoin('members as m', 'gm.member_Id', '=', 'm.member_Id');

            if (in_array('count_members', $dataColumns)) {
                $selects[] = DB::raw("COUNT(DISTINCT gm.member_Id) as count_members");
            }

            if (in_array('members.name', $dataColumns)) {
                $selects[] = DB::raw("GROUP_CONCAT(DISTINCT m.name SEPARATOR ', ') as member_names");
            }

            if (in_array('members.member_Id', $dataColumns)) {
                $selects[] = DB::raw("GROUP_CONCAT(DISTINCT m.member_Id SEPARATOR ', ') as member_ids");
            }

            if (in_array('members.number', $dataColumns)) {
                $selects[] = DB::raw("GROUP_CONCAT(DISTINCT m.number SEPARATOR ', ') as member_numbers");
            }

            if (in_array('members.gender', $dataColumns)) {
                $selects[] = DB::raw("GROUP_CONCAT(DISTINCT m.gender SEPARATOR ', ') as member_genders");
            }

            if (in_array('members.disabled', $dataColumns)) {
                $selects[] = DB::raw("GROUP_CONCAT(DISTINCT m.disabled SEPARATOR ', ') as member_disabled_status");
            }

            if (in_array('group_members.role', $dataColumns)) {
                $selects[] = DB::raw("GROUP_CONCAT(DISTINCT gm.role SEPARATOR ', ') as member_roles");
            }
        }
    }

    /**
     * Applies all filters to the query (location, defaulter, status, dates, etc.)
     * Works for loan-centric, member-centric, and group-centric queries.
     */
    public function applyFilters(\Illuminate\Database\Query\Builder $query, array $filters, array $loanStatusFilter = ['all_loans_filter']): void
    {
        // filters
        if (!empty($filters['filterCounty'])) {
            $query->where('co.county_code', $filters['filterCounty']);
        }
        if (!empty($filters['filterConstituency'])) {
            $query->where('c.constituency_code', $filters['filterConstituency']);
        }
        if (!empty($filters['filterWard'])) {
            $query->where('w.id', $filters['filterWard']);
        }

        if (!empty($filters['filterLoanPhase'])) {
            $this->safeLeftJoin($query, 'loan_phases as lp', 'gl.loan_phase_id', '=', 'lp.id');
            $query->where('lp.id', $filters['filterLoanPhase']);
        }

        if( !empty($filters['filterGroupCategory'])) {
            $query->where('gl.category', $filters['filterGroupCategory']);
        }

        if ( !empty($filters['filterGroupActivity'])) {
            $query->where('gl.sector', $filters['filterGroupActivity']);
        }

        if (!empty($filters['dateFrom'])) {
            $query->whereDate('gl.disbursement_date', '>=', $filters['dateFrom']);
        }
        if (!empty($filters['dateTo'])) {
            $query->whereDate('gl.disbursement_date', '<=', $filters['dateTo']);
        }
        if (!empty($filters['disbursementFrom']) || !empty($filters['disbursementTo'])) {
            $query->whereNotNull('gl.disbursement_date');
        }

        if (!empty($filters['filterDefaulter']) && $filters['filterDefaulter'] === true) {
            $query->where('gl.is_defaulted', true);
        }

        $this->applyLoanStatusAndPerformanceFilters($query, $loanStatusFilter);
    }

    /**
     * Handles simple status filters + complex performance filters
     */
    private function applyLoanStatusAndPerformanceFilters(\Illuminate\Database\Query\Builder $query, array $loanStatusFilter): void
    {

        $selected = collect($loanStatusFilter);

        $statusMap = [
            'financed_loans_filter'  => 'financed',
            'pending_loans_filter'   => 'pending',
            'approved_loans_filter'  => 'approved',
            'deferred_loans_filter'  => 'deferred',
            'cancelled_loans_filter' => 'cancelled',
        ];

        // Simple loan_status filters
        $simpleStatuses = $selected->map(function($key) use ($statusMap) {
            return $statusMap[$key] ?? null;
        })->filter()->values()->all();

        if (!empty($simpleStatuses)) {
            $query->whereIn('loan_status', $simpleStatuses);
        }

        if ($selected->contains('fully_repaid')) {
            $query->whereColumn('gl.amount_repaid', '>=', 'gl.amount_issued');
        }


        if ($selected->contains('performing_not_fully_repaid')) {
            $query->where('gl.is_defaulted', 0)
                // ->where(function($q) {
                //     $q->where('gl.total_arrears', '>', 0);
                // })
                // // ensure it's not actually fully paid yet
                ->whereColumn('gl.amount_repaid', '<', 'gl.amount_issued');
        }

        if ($selected->contains('never_repaid_after_grace')) {
            $threshold = now()->subMonths(6)->toDateString();

            $query->where('gl.loan_status', 'financed')
                ->whereNotNull('gl.disbursement_date')
                ->where('gl.disbursement_date', '<=', $threshold)
                ->where('gl.amount_repaid', '=', 0);
        }

    }

    private function safeLeftJoin($query, string $table, string $first, string $operator, string $second): void
    {
        $baseQuery = method_exists($query, 'getQuery') ? $query->getQuery() : $query;

        $alreadyJoined = collect($baseQuery->joins)->contains(fn($j) => $j->table === $table);

        if (!$alreadyJoined) {
            $query->leftJoin($table, $first, $operator, $second);
        }
    }

    /**
     * Fetch limited results for on-screen preview
     */
    public function getPreviewResults(\Illuminate\Database\Query\Builder $query, int $limit): \Illuminate\Support\Collection
    {
        $previewQuery = clone $query;
        return $previewQuery->limit($limit)->get();
    }

    /**
     * Fetch ALL results for export (no limit)
     */
    public function getExportResults(\Illuminate\Database\Query\Builder $query): \Illuminate\Support\Collection
    {
        $exportQuery = clone $query;
        $results = $exportQuery->get();

        \Log::info("Full export results fetched", ['count' => $results->count()]);

        return $results;
    }

    /**
     * Monitor performance and log it (can be called from Livewire or here)
     */
    public function monitorPerformance(string $operation, float $startTime, int $recordCount = null): void
    {
        $duration = round(microtime(true) - $startTime, 2);
        $memory = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

        $message = "Performance: {$operation} took {$duration}s, Peak Memory: {$memory}MB";
        if ($recordCount !== null) {
            $message .= ", Records: {$recordCount}";
        }

        \Log::info($message);
    }

    /**
     * Get limited results for on-screen preview/display
     */
    public function getPaginatedResults(\Illuminate\Database\Query\Builder $query, int $limit = 100): \Illuminate\Support\Collection
    {
        // Clone to preserve original query
        $paginatedQuery = clone $query;

        return $paginatedQuery->limit($limit)->get();
    }

    /**
     * Get ALL results for export (no limit, full dataset)
     */
    public function getAllResultsForExport(\Illuminate\Database\Query\Builder $query): \Illuminate\Support\Collection
    {
        // Clone for safety
        $exportQuery = clone $query;

        // Optional: add orderBy if not already set
        if (empty($exportQuery->orders)) {
            $exportQuery->orderBy('g.name'); // or any default field
        }

        $results = $exportQuery->get();

        \Log::info("Export data fetched", ['count' => $results->count()]);

        return $results;
    }

    /**
     * Stream large export in chunks (memory efficient)
     */
    public function streamExport(\Illuminate\Database\Query\Builder $query, callable $callback): void
    {
        $query->chunk(500, function ($chunk) use ($callback) {
            $callback($chunk);
        });
    }

    /**
     * Format raw query results into final array (handles all query types)
     */
    public function formatResults(\Illuminate\Support\Collection $results, array $selectedColumns, string $queryType): array
    {
        $formatted = [];

        foreach ($results as $row) {
            $formattedRow = [];

            foreach ($selectedColumns as $column) {

                $formattedRow[$column] = $this->formatColumnByType($column, $row, $queryType);
            }

            $formatted[] = $formattedRow;
        }

        return $formatted;
    }

    public function formatColumnByType(string $column, object $row, string $queryType, bool $forExport = false): string
    {
        return match ($queryType) {
            'loan_centric'   => $this->formatForLoanCentric($column, $row, $forExport),
            'group_centric'  => $this->formatForGroupCentric($column, $row, $forExport),
            'member_centric' => $this->formatForMemberCentric($column, $row, $forExport),
            default          => $row->{$column} ?? 'N/A'
        };
    }

    /**
     * Format a single column value from a flat row (Query Builder stdClass)
     */
    private function formatForLoanCentric(string $column, object $row, bool $forExport = false): string
    {
        // 1. Map frontend dot-notation to actual SQL alias
        $aliasMap = [
            // Group fields
            'groups.name'                => 'group_name',
            'groups.code'                => 'group_code',
            'groups.identifier'          => 'group_identifier',
            'groups.reg_number'          => 'group_reg_number',
            'groups.date_registered'     => 'group_date_registered',
            'groups.bank'                => 'group_bank',
            'groups.account'             => 'group_account',
            'groups.location'            => 'group_location',
            'groups.box'                 => 'group_box',
            'groups.status'              => 'group_status',

            // Location fields
            'location.ward_name'         => 'ward_name',
            'location.constituency_name' => 'constituency_name',
            'location.county_name'       => 'county_name',

            // Member aggregates (flattened)
            'members.name'       => 'member_names',
            'members.member_Id'  => 'member_ids',
            'members.number'     => 'member_numbers',
            'members.gender'     => 'member_genders',
            'members.disabled'   => 'member_disabled_status',
            'group_members.role' => 'member_roles',

            // Loan fields (direct or aggregated)
            'loan_phase.phase'             => 'loan_phase',
            'group_loans.application_date' => 'application_date',
            'group_loans.disbursement_date'=> 'disbursement_date',
            'group_loans.gfs_code'         => 'gfs_code',
            'group_loans.sector'           => 'sector',
            'group_loans.category'         => 'category',
            'group_loans.loan_status'      => 'loan_status',
            'group_loans.loan_type'        => 'loan_type',
            'group_loans.loan_level'       => 'loan_level',
            'group_loans.amount_applied'   => 'amount_applied',
            'group_loans.amount_approved'  => 'amount_approved',
            'group_loans.amount_issued'    => 'amount_issued',
            'group_loans.amount_repaid'    => 'amount_repaid',
            'group_loans.amount_due'       => 'amount_due',
            'group_loans.total_arrears'    => 'total_arrears',
            'group_loans.cheque_no'        => 'cheque_no',
            'group_loans.status'           => 'status',
            'group_loans.comments'         => 'comments',

            // Calculated
            'arrears'                      => 'arrears',
            'repayment_date'               => 'repayment_date',
            'defaulter_status'             => 'defaulter_status',
        ];

        // 2. Get the actual DB alias
        $dbField = $aliasMap[$column] ?? $column;

        // 3. Extract value from flat row
        $value = $row->{$dbField} ?? null;

        // 4. Handle special formatting
        if ($value === null || $value === '') {
            return 'N/A';
        }

        // Dates
        $dateFields = ['disbursement_date', 'application_date', 'date_registered', 'repayment_date'];
        if (str_ends_with($column, '_date') || in_array($dbField, $dateFields)) {
            return \Carbon\Carbon::parse($value)->format('d/m/Y');
        }

        // Numbers / currency
        if (str_contains($column, 'amount') || in_array($dbField, ['arrears', 'total_arrears'])) {
            $num = (float) $value;
            return $forExport ? number_format($num, 2, '.', '') : number_format($num, 2);
        }

        if ($dbField === 'member_disabled_status') {
            return str_replace(['1', '0'], ['Yes', 'No'], (string)$value);
        }

        // Concatenated lists (member_names, member_roles, etc.)
        // if (str_ends_with($dbField, '_names') || str_ends_with($dbField, '_roles') || str_ends_with($dbField, '_statuses')) {
        //     return $value; // already comma-separated from GROUP_CONCAT
        // }

        // Count fields
        if (str_ends_with($dbField, '_count')) {
            return (string) (int) $value;
        }

        // Default: raw value
        return (string) $value;
    }

    private function formatForGroupCentric(string $column, object $row, bool $forExport = false): string
    {
        $dbField = $this->aliasMap[$column] ?? $column;

        if (str_starts_with($dbField, 'groups.')) {
            $dbField = str_replace('groups.', '', $dbField);
        }

        if (str_starts_with($dbField, 'location.')) {
            $dbField = str_replace('location.', '', $dbField);
        }

        $memberMap = [
            'members.name'       => 'member_names',
            'members.member_Id'  => 'member_ids',
            'members.number'     => 'member_numbers',
            'members.gender'     => 'member_genders',
            'members.disabled'   => 'member_disabled_status',
            'group_members.role' => 'member_roles',
        ];

        if (isset($memberMap[$dbField])) {
            $dbField = $memberMap[$dbField];
        }

        $value = $row->{$dbField} ?? null;

        // dd($dbField, $value, $row);

        if ($value === null || $value === '') {
            return 'N/A';
        }

        if ($dbField === 'member_disabled_status') {
            return str_replace(['1', '0'], ['Yes', 'No'], (string)$value);
        }

        return (string) $value;
    }

    private function formatForMemberCentric(string $column, object $row, bool $forExport = false): string
    {
        $dbField = $this->aliasMap[$column] ?? $column;

        $aggregateMap = [
            'groups.name'                => 'group_name',
            'groups.code'                => 'group_code',
            'groups.identifier'          => 'group_identifier',
            'groups.reg_number'          => 'group_reg_numbers',
            'groups.date_registered'     => 'group_date_registered',
            'groups.bank'                => 'group_bank',
            'groups.account'             => 'group_account',
            'groups.location'            => 'group_location',
            'groups.box'                 => 'group_box',
            'groups.status'              => 'group_status',
            // 'location.ward_name'         => 'ward_names',
            // 'location.constituency_name' => 'constituency_names',
            // 'location.county_name'       => 'county_names',
            'group_members.role'         => 'group_member_role',
        ];

        if (isset($aggregateMap[$dbField])) {
            $dbField = $aggregateMap[$dbField];
        }

        if (str_starts_with($dbField, 'members.')) {
            $dbField = str_replace('members.', '', $dbField);
        }

        if (str_starts_with($dbField, 'location.')) {
            $dbField = str_replace('location.', '', $dbField);
        }

        $value = $row->{$dbField} ?? null;

        // dd($dbField, $value, $row);

        if ($value === null || $value === '') {
            return 'N/A';
        }

        if ($dbField === 'disabled') {
            return $value ? 'Yes' : 'No';
        }

        return (string) $value;
    }

    /**
 * Unified formatter for all report types (Loan, Member, Group)
 */
public function formatSingleColumn(string $column, object $row, bool $forExport = false): string
{
    // 1. The "Master Map" - This bridges the gap between your old Eloquent paths
    // and your new flat Query Builder aliases.
    $aliasMap = [
        // Group fields
        'groups.name'                => 'group_name',
        'groups.code'                => 'group_code',
        'groups.identifier'          => 'group_identifier',
        'groups.reg_number'          => 'group_reg_number',
        'groups.date_registered'     => 'group_date_registered',
        'groups.bank'                => 'group_bank',
        'groups.account'             => 'group_account',
        'groups.location'            => 'group_location',
        'groups.box'                 => 'group_box',
        'groups.status'              => 'group_status',

        // Location fields
        'location.ward_name'         => 'ward_name',
        'location.constituency_name' => 'constituency_name',
        'location.county_name'       => 'county_name',
        'ward.ward_name'             => 'ward_name', // Support both naming styles

        // Member Aggregates (Results of GROUP_CONCAT)
        'members.name'               => 'member_names',
        'members.member_Id'          => 'member_ids',
        'members.number'             => 'member_numbers',
        'members.gender'             => 'member_genders',
        'members.disabled'           => 'member_disabled_status',
        'group_members.role'         => 'member_roles',
        'count_members'              => 'count_members',

        // Loan Phase
        'loan_phase.phase'           => 'phase',
    ];

    // 2. Resolve the DB field name
    $dbField = $aliasMap[$column] ?? $column;

    // Remove table prefix if it's a direct loan column (e.g. group_loans.sector -> sector)
    if (str_starts_with($dbField, 'group_loans.')) {
        $dbField = str_replace('group_loans.', '', $dbField);
    }

    // 3. Extract the value
    $value = $row->{$dbField} ?? null;

    if ($value === null || $value === '') {
        return 'N/A';
    }

    // 4. SMART FORMATTING

    // Date Logic
    $dateFields = ['disbursement_date', 'application_date', 'date_registered', 'repayment_date'];
    if (str_ends_with($column, '_date') || in_array($dbField, $dateFields)) {
        try {
            return \Carbon\Carbon::parse($value)->format('d/m/Y');
        } catch (\Exception $e) {
            return $value;
        }
    }

    // Currency/Numeric Logic
    if (str_contains($column, 'amount') || in_array($dbField, ['arrears', 'total_arrears', 'cumulative_arrears'])) {
        $num = (float) $value;
        return $forExport ? number_format($num, 2, '.', '') : number_format($num, 2);
    }

    // Boolean Logic (Specifically for "Disabled" status)
    if ($dbField === 'member_disabled_status' || $dbField === 'disabled') {
        // If it's a GROUP_CONCAT, replace 1/0 with Yes/No
        if (str_contains($value, ',')) {
            return str_replace(['1', '0'], ['Yes', 'No'], $value);
        }
        return $value ? 'Yes' : 'No';
    }

    // Default: Return the value as a string
    return (string) $value;
}

}
