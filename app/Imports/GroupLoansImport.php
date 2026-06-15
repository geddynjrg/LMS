<?php
// app/Imports/GroupLoansImport.php

namespace App\Imports;

use App\Models\GroupLoan;
use App\Models\LoanPhase;
use App\Models\Group;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GroupLoansImport implements ToCollection, WithChunkReading, WithBatchInserts
{
    private $constituencyCode;
    private $importedCount = 0;
    private $skippedCount = 0;
    private $errors = [];
    private $totalRows = 0;
    private $processedRows = 0;

    // Cache data for performance
    private $loanPhasesCache = [];
    private $groupsCache = [];
    private $existingLoansCache = [];

    // Valid values for enums
    private $validLevels = ['refinancing', 'new', 'resubmission', 'reallocation'];
    private $validLoanStatuses = ['pending', 'deferred', 'approved', 'financed', 'cancelled'];

    public function __construct($constituencyCode)
    {
        $this->constituencyCode = $constituencyCode;
        $this->loadLoanPhasesCache();
        $this->loadGroupsCache();
        $this->loadExistingLoansCache();
    }

    /**
     * Load loan phases for the constituency
     */
    private function loadLoanPhasesCache()
    {
        $phases = LoanPhase::where('constituency_code', $this->constituencyCode)->get();


        foreach ($phases as $phase) {
            $this->loanPhasesCache[strtolower(trim($phase->phase))] = $phase->id;
        }
    }

    /**
     * Load groups for the constituency
     */
    private function loadGroupsCache()
    {
        $groups = Group::whereHas('ward.constituency', function($query) {
            $query->where('constituency_code', $this->constituencyCode);
        })->get(['id', 'name', 'code']);

        foreach ($groups as $group) {
            $key = strtolower(trim($group->name)) . '|' . strtolower(trim($group->code));
            $this->groupsCache[$key] = $group->id;

            // Also cache by code only for fallback lookup
            $this->groupsCache['code|' . strtolower(trim($group->code))] = $group->id;
        }
    }

    /**
     * Load existing loans to prevent duplicates
     */
    private function loadExistingLoansCache()
    {
        $existingLoans = GroupLoan::whereHas('group.ward.constituency', function($query) {
            $query->where('constituency_code', $this->constituencyCode);
        })->get(['loan_phase_id', 'group_id', 'level']);

        foreach ($existingLoans as $loan) {
            $key = $loan->loan_phase_id . '|' . $loan->group_id . '|' . strtolower($loan->level);
            $this->existingLoansCache[$key] = true;
        }
    }

    /**
     * Process in chunks to reduce memory usage
     */
    public function chunkSize(): int
    {
        return 500;
    }

    /**
     * Insert in batches for better performance
     */
    public function batchSize(): int
    {
        return 100;
    }

    public function collection(Collection $rows)
    {
        $this->totalRows = $rows->count() - 1; // Exclude header
        $this->processedRows = 0;

        // Validate column headers first
        if (!$this->validateHeaders($rows->first())) {
            throw new \Exception('Invalid Excel template. Please download the correct template format.');
        }

        $this->updateProgress(5, 'Starting group loans import...');

        // First pass: Validate all rows
        $validationErrors = $this->validateAllRows($rows);

        if (!empty($validationErrors)) {
            $errorMessages = implode("\n", array_slice($validationErrors, 0, 20));
            if (count($validationErrors) > 20) {
                $errorMessages .= "\n... and " . (count($validationErrors) - 20) . " more errors";
            }
            throw new \Exception("Validation errors found:\n" . $errorMessages);
        }

        $this->updateProgress(20, 'All data validated. Creating group loans...');

        // Batch insert collection
        $loansToInsert = [];

        foreach ($rows as $index => $row) {
            if ($index === 0) continue;

            $this->processedRows++;

            // Update progress
            if ($this->processedRows % 50 === 0 || $this->processedRows === 1 || $this->processedRows === $this->totalRows) {
                $progress = min(90, 20 + (($this->processedRows / $this->totalRows) * 70));
                $this->updateProgress(
                    (int) $progress,
                    "Processing {$this->processedRows} of {$this->totalRows} rows..."
                );
            }

            // Extract row data
            $sno = $row[0] ?? null;
            $phaseName = $row[1] ?? null;
            $level = $row[2] ?? null;
            $groupName = $row[3] ?? null;
            $groupCode = $row[4] ?? null;
            $loanStatus = $row[5] ?? null;
            $disbursementDate = $row[6] ?? null;
            $chequeNo = $row[7] ?? null;
            $amountApplied = $row[8] ?? null;
            $amountApproved = $row[9] ?? null;
            $amountIssued = $row[10] ?? null;
            $amountRepaid = $row[11] ?? null;

            // Get loan phase ID
            $loanPhaseId = $this->getLoanPhaseId($phaseName);
            if ($loanPhaseId === null) {
                $this->skippedCount++;
                continue;
            }

            // Get group ID
            $groupId = $this->getGroupId($groupName, $groupCode);
            if ($groupId === null) {
                $this->skippedCount++;
                continue;
            }

            // Check for duplicate loan
            $normalizedLevel = strtolower(trim($level));
            $loanKey = $loanPhaseId . '|' . $groupId . '|' . $normalizedLevel;

            if (isset($this->existingLoansCache[$loanKey])) {
                $this->skippedCount++;
                continue;
            }

            // Prepare loan data
            $loanData = [
                'loan_phase_id' => $loanPhaseId,
                'group_id' => $groupId,
                'level' => $this->normalizeLevel($level),
                'loan_status' => $this->normalizeLoanStatus($loanStatus),
                'disbursement_date' => $this->parseDate($disbursementDate),
                'cheque_no' => $chequeNo,
                'amount_applied' => $this->parseAmount($amountApplied),
                'amount_approved' => $this->parseAmount($amountApproved),
                'amount_issued' => $this->parseAmount($amountIssued),
                'amount_repaid' => $this->parseAmount($amountRepaid),
                'status' => 'Active', // Default status
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Set application date (optional field)
            if (isset($row[12])) {
                $loanData['application_date'] = $this->parseDate($row[12]);
            }

            // Set comments (optional field)
            if (isset($row[13])) {
                $loanData['comments'] = $row[13];
            }

            $loansToInsert[] = $loanData;

            // Add to cache to prevent duplicates in same import
            $this->existingLoansCache[$loanKey] = true;
            $this->importedCount++;

            // Batch insert when reach batch size
            if (count($loansToInsert) >= $this->batchSize()) {
                GroupLoan::insert($loansToInsert);
                $loansToInsert = [];
            }
        }

        // Insert any remaining records
        if (!empty($loansToInsert)) {
            GroupLoan::insert($loansToInsert);
        }

        $this->updateProgress(95, 'Finalizing import...');
        $this->clearProgress();

        if ($this->importedCount === 0) {
            throw new \Exception('No new group loans were imported. All loans already exist or data was invalid.');
        }
    }

    /**
     * Validate all rows before processing
     */
    private function validateAllRows(Collection $rows)
    {
        $errors = [];
        $seenInFile = []; // Track duplicates within the file

        foreach ($rows as $index => $row) {
            if ($index === 0) continue;

            $rowNumber = $index + 1;
            $sno = $row[0] ?? null;
            $phaseName = $row[1] ?? null;
            $level = $row[2] ?? null;
            $groupName = $row[3] ?? null;
            $groupCode = $row[4] ?? null;
            $loanStatus = $row[5] ?? null;
            $disbursementDate = $row[6] ?? null;
            $chequeNo = $row[7] ?? null;
            $amountApplied = $row[8] ?? null;
            $amountApproved = $row[9] ?? null;
            $amountIssued = $row[10] ?? null;
            $amountRepaid = $row[11] ?? null;

            // Validate mandatory fields
            if (empty($sno) || !is_numeric($sno)) {
                $errors[] = "Row {$rowNumber}: S/No. is required and must be a number";
            }

            if (empty($phaseName)) {
                $errors[] = "Row {$rowNumber}: Phase name is required";
            } elseif (!$this->isValidLoanPhase($phaseName)) {
                $errors[] = "Row {$rowNumber}: Phase '{$phaseName}' does not exist in this constituency";
            }

            if (empty($groupName)) {
                $errors[] = "Row {$rowNumber}: Group name is required";
            }

            if (empty($groupCode)) {
                $errors[] = "Row {$rowNumber}: Group code is required";
            }

            // Validate group exists
            if (!empty($groupName) && !empty($groupCode) && !$this->isValidGroup($groupName, $groupCode)) {
                $errors[] = "Row {$rowNumber}: Group '{$groupName}' with code '{$groupCode}' does not exist in this constituency";
            }

            if (empty($level)) {
                $errors[] = "Row {$rowNumber}: Level is required";
            } elseif (!$this->isValidLevel($level)) {
                $errors[] = "Row {$rowNumber}: Level must be one of: New, Refinancing, Resubmission, Reallocation";
            }

            if (empty($loanStatus)) {
                $errors[] = "Row {$rowNumber}: Loan status is required";
            } elseif (!$this->isValidLoanStatus($loanStatus)) {
                $errors[] = "Row {$rowNumber}: Loan status must be one of: pending, deferred, approved, financed, cancelled";
            }

            // Validate disbursement date for financed loans
            $normalizedLoanStatus = strtolower(trim($loanStatus));
            if ($normalizedLoanStatus === 'financed' && empty($disbursementDate)) {
                $errors[] = "Row {$rowNumber}: Disbursement date is required for financed loans";
            }

            if (!empty($disbursementDate) && !$this->isValidDate($disbursementDate)) {
                $errors[] = "Row {$rowNumber}: Disbursement date must be in YYYY-MM-DD format";
            }

            // Validate amounts
            if (!$this->isValidAmount($amountApplied, false)) {
                $errors[] = "Row {$rowNumber}: Must be a valid number";
            }

            if (!$this->isValidAmount($amountApproved)) {
                $errors[] = "Row {$rowNumber}: Amount approved is required and must be a valid number";
            }

            if (!$this->isValidAmount($amountIssued, false)) {
                $errors[] = "Row {$rowNumber}: Amount issued is required and Must be a valid number";
            }

            if (!$this->isValidAmount($amountRepaid, false)) {
                $errors[] = "Row {$rowNumber}: Amount repaid is required and Must be a valid number";
            }

            // Check for duplicates within file
            if (!empty($phaseName) && !empty($groupCode) && !empty($level)) {
                $loanPhaseId = $this->getLoanPhaseId($phaseName);
                $groupId = $this->getGroupId($groupName, $groupCode);

                if ($loanPhaseId && $groupId) {
                    $fileKey = $loanPhaseId . '|' . $groupId . '|' . strtolower(trim($level));
                    if (isset($seenInFile[$fileKey])) {
                        $errors[] = "Row {$rowNumber}: Duplicate loan found for group '{$groupName}' in phase '{$phaseName}' with level '{$level}'";
                    }
                    $seenInFile[$fileKey] = true;
                }
            }

            // Check against existing loans
            if (!empty($phaseName) && !empty($groupCode) && !empty($level)) {
                $loanPhaseId = $this->getLoanPhaseId($phaseName);
                $groupId = $this->getGroupId($groupName, $groupCode);

                if ($loanPhaseId && $groupId) {
                    $loanKey = $loanPhaseId . '|' . $groupId . '|' . strtolower(trim($level));
                    if (isset($this->existingLoansCache[$loanKey])) {
                        $errors[] = "Row {$rowNumber}: Loan already exists for group '{$groupName}' in phase '{$phaseName}' with level '{$level}'";
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Validate column headers
     */
    private function validateHeaders($headerRow)
    {
        Log::info("validating header");
        if (empty($headerRow) || count($headerRow) < 12) {
            dd(count($headerRow));
            return false;
        }

        $expectedHeaders = [
            's/no.*', 'phase*', 'level*', 'group*', 'code*', 'loan_status*',
            'disbursement_date*', 'cheque_no', 'amount_applied', 'amount_approved*',
            'amount_issued*', 'amount_repaid*'
        ];

        $actualHeaders = array_map(function($header) {
            return strtolower(trim($header ?? ''));
        }, $headerRow->toArray());



        // Check if we have at least the mandatory headers
        for ($i = 0; $i < 12; $i++) {
            if (!isset($actualHeaders[$i]) || $actualHeaders[$i] !== $expectedHeaders[$i]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Helper methods
     */
    private function isValidLoanPhase($phaseName)
    {
        return $this->getLoanPhaseId($phaseName) !== null;
    }

    private function getLoanPhaseId($phaseName)
    {
        $normalizedPhaseName = strtolower(trim($phaseName));
        return $this->loanPhasesCache[$normalizedPhaseName] ?? null;
    }

    private function isValidGroup($groupName, $groupCode)
    {
        return $this->getGroupId($groupName, $groupCode) !== null;
    }

    private function getGroupId($groupName, $groupCode)
    {
        // Only allow exact matches - no fuzzy matching
        $primaryKey = strtolower(trim($groupName)) . '|' . strtolower(trim($groupCode));

        if (isset($this->groupsCache[$primaryKey])) {
            return $this->groupsCache[$primaryKey];
        }

        // No fallbacks - if exact match fails, return null
        // Log::warning("Group not found - exact match required", [
        //     'input_name' => $groupName,
        //     'input_code' => $groupCode,
        //     'constituency' => $this->constituencyCode,
        //     'available_matches' => $this->getSimilarGroups($groupCode) // For error messages
        // ]);

        return null;
    }
    private function getSimilarGroups($groupCode)
    {
        $similar = [];
        $normalizedCode = strtolower(trim($groupCode));

        foreach ($this->groupsCache as $key => $groupId) {
            if (str_contains($key, '|' . $normalizedCode)) {
                list($name, $code) = explode('|', $key);
                $similar[] = [
                    'name' => $name,
                    'code' => $code
                ];
            }
        }

        return $similar;
    }

    private function isValidLevel($level)
    {
        return in_array(strtolower(trim($level)), $this->validLevels);
    }

    private function normalizeLevel($level)
    {
        $normalized = strtolower(trim($level));
        return match($normalized) {
            'new' => 'New',
            'refinancing' => 'Refinancing',
            'resubmission' => 'Resubmission',
            'reallocation' => 'Reallocation',
            default => 'New'
        };
    }

    private function isValidLoanStatus($loanStatus)
    {
        return in_array(strtolower(trim($loanStatus)), $this->validLoanStatuses);
    }

    private function normalizeLoanStatus($loanStatus)
    {
        return strtolower(trim($loanStatus));
    }

    private function isValidDate($date)
    {
        if (empty($date) || strtolower($date) === 'null') {
            return true;
        }

        try {
            // Handle Excel serial dates
            if (is_numeric($date)) {
                $base = Carbon::createFromFormat('Y-m-d', '1899-12-30');
                $converted = $base->copy()->addDays((int) floor($date));
                return $converted->format('Y-m-d') === $converted->format('Y-m-d'); // Validate conversion
            }

            $parsedDate = Carbon::createFromFormat('Y-m-d', $date);
            return $parsedDate && $parsedDate->format('Y-m-d') === $date;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function parseDate($dateString)
    {
        if (empty($dateString) || strtolower($dateString) === 'null') {
            return null;
        }

        try {
            // Handle Excel serial dates
            if (is_numeric($dateString)) {
                $base = Carbon::createFromFormat('Y-m-d', '1899-12-30');
                return $base->copy()->addDays((int) floor($dateString));
            }

            return Carbon::createFromFormat('Y-m-d', $dateString);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function isValidAmount($amount, $isRequired = true)
    {
        if (!$isRequired && (is_null($amount) || $amount === '')) {
            return true;
        }

        // If required and value is empty/null, it's invalid
        if ($isRequired && (is_null($amount) || $amount === '')) {
            return false;
        }

        // Handle the case where amount is 0 (zero)
        if ($amount === 0 || $amount === '0') {
            return true;
        }

        // Remove commas and spaces, then check if numeric
        $cleanedAmount = str_replace([',', ' ', 'KES', 'ksh'], '', (string)$amount);

        // Check if it's numeric and non-negative
        return is_numeric($cleanedAmount) && floatval($cleanedAmount) >= 0;
    }

    private function parseAmount($amount)
    {
        if (!$this->isValidAmount($amount, false)) {
            return 0;
        }

        // Handle zero values
        if ($amount === 0 || $amount === '0') {
            return 0;
        }

        // Handle empty values for non-required fields
        if (is_null($amount) || $amount === '') {
            return 0;
        }

        // Remove commas and spaces, then convert to integer
        $cleanedAmount = str_replace([',', ' ', 'KES', 'ksh'], '', (string)$amount);
        return (int) $cleanedAmount;
    }

    /**
     * Progress tracking methods
     */
    private function updateProgress($progress, $message)
    {
        static $lastProgress = 0;

        if (abs($progress - $lastProgress) >= 5 || $progress === 100) {
            $progressKey = "import_progress_{$this->constituencyCode}_group_loans";
            Cache::put($progressKey, [
                'progress' => $progress,
                'message' => $message,
                'updated_at' => now()
            ], 60);
            $lastProgress = $progress;
        }
    }

    private function clearProgress()
    {
        $progressKey = "import_progress_{$this->constituencyCode}_group_loans";
        Cache::forget($progressKey);
    }

    public function getImportStats()
    {
        return [
            'imported' => $this->importedCount,
            'skipped' => $this->skippedCount,
            'total' => $this->importedCount + $this->skippedCount
        ];
    }
}
