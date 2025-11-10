<?php
// app/Imports/MembersImport.php

namespace App\Imports;

use App\Models\Member;
use App\Models\GroupMember;
use App\Models\LoanPhase;
use App\Models\Group;
use App\Models\GroupLoan;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MembersImport implements ToCollection, WithChunkReading, WithBatchInserts
{
    private $constituencyCode;
    private $importedMembersCount = 0;
    private $importedGroupMembersCount = 0;
    private $skippedMembersCount = 0;
    private $skippedGroupMembersCount = 0;
    private $errors = [];
    private $totalRows = 0;
    private $processedRows = 0;

    // Cache data for performance
    private $loanPhasesCache = [];
    private $groupsCache = [];
    private $groupLoansCache = [];
    private $existingMembersCache = [];
    private $existingGroupMembersCache = [];

    // Valid values
    private $validGenders = ['male', 'female'];
    private $validDisabled = ['yes', 'no', '1', '0', 'true', 'false'];
    private $validLevels = ['refinancing', 'new', 'resubmission', 'reallocation'];
    private $validRoles = ['chairperson', 'secretary', 'tresurer', 'member'];

    public function __construct($constituencyCode)
    {
        $this->constituencyCode = $constituencyCode;
        $this->loadLoanPhasesCache();
        $this->loadGroupsCache();
        $this->loadGroupLoansCache();
        $this->loadExistingMembersCache();
        $this->loadExistingGroupMembersCache();
    }

    /**
     * Load loan phases for the constituency
     */
    private function loadLoanPhasesCache()
    {
        $phases = LoanPhase::where('constituency_code', $this->constituencyCode)->get(['id', 'phase', 'constituency_code']);

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
        }
    }

    /**
     * Load group loans for the constituency
     */
    private function loadGroupLoansCache()
    {
        $groupLoans = GroupLoan::whereHas('loanPhase', function($query) {
            $query->where('constituency_code', $this->constituencyCode);
        })->get(['id', 'loan_phase_id', 'group_id', 'level']);

        foreach ($groupLoans as $loan) {
            $key = $loan->loan_phase_id . '|' . $loan->group_id . '|' . strtolower($loan->level);
            $this->groupLoansCache[$key] = $loan->id;
        }
    }

    /**
     * Load existing members to avoid duplicates
     */
    private function loadExistingMembersCache()
    {
        $existingMembers = Member::all(['member_Id', 'name']);
        foreach ($existingMembers as $member) {
            $this->existingMembersCache[strtolower(trim($member->member_Id))] = true;
        }
    }

    /**
     * Load existing group members to avoid duplicates
     */
    private function loadExistingGroupMembersCache()
    {
        $existingGroupMembers = GroupMember::whereHas('groupLoan.loanPhase', function($query) {
            $query->where('constituency_code', $this->constituencyCode);
        })->get(['member_Id', 'group_loans_id']);

        foreach ($existingGroupMembers as $groupMember) {
            $key = $groupMember->member_Id . '|' . $groupMember->group_loans_id;
            $this->existingGroupMembersCache[$key] = true;
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

        $this->updateProgress(5, 'Starting members import...');

        // First pass: Validate all rows
        $validationErrors = $this->validateAllRows($rows);

        if (!empty($validationErrors)) {
            $errorMessages = implode("\n", array_slice($validationErrors, 0, 20));
            if (count($validationErrors) > 20) {
                $errorMessages .= "\n... and " . (count($validationErrors) - 20) . " more errors";
            }
            throw new \Exception("Validation errors found:\n" . $errorMessages);
        }

        $this->updateProgress(20, 'All data validated. Creating members and group associations...');

        // Batch insert collections
        $membersToInsert = [];
        $groupMembersToInsert = [];
        $processedMemberIds = []; // Track members processed in this import

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
            $memberName = $row[5] ?? null;
            $memberId = $row[6] ?? null;
            $role = $row[7] ?? null;
            $number = $row[8] ?? null;
            $gender = $row[9] ?? null;
            $disabled = $row[10] ?? null;

            // Get group loan ID
            $groupLoanId = $this->getGroupLoanId($phaseName, $level, $groupName, $groupCode);
            if ($groupLoanId === null) {
                $this->skippedGroupMembersCount++;
                continue;
            }

            // Check if member already exists in database
            $memberExists = isset($this->existingMembersCache[strtolower(trim($memberId))]);

            // Check if group member association already exists
            $groupMemberKey = $memberId . '|' . $groupLoanId;
            $groupMemberExists = isset($this->existingGroupMembersCache[$groupMemberKey]);

            if ($groupMemberExists) {
                $this->skippedGroupMembersCount++;
                continue;
            }

            // Add member to members table if they don't exist
            if (!$memberExists && !isset($processedMemberIds[$memberId])) {
                $membersToInsert[] = [
                    'name' => $memberName,
                    'member_Id' => $memberId,
                    'number' => $number,
                    'gender' => $this->normalizeGender($gender),
                    'disabled' => $this->normalizeDisabled($disabled),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $processedMemberIds[$memberId] = true;
                $this->importedMembersCount++;
            } elseif (!$memberExists) {
                // Member is already queued for insertion in this batch
                $this->importedMembersCount++;
            } else {
                $this->skippedMembersCount++;
            }

            // Add group member association
            $groupMembersToInsert[] = [
                'member_Id' => $memberId,
                'group_loans_id' => $groupLoanId,
                'role' => $this->normalizeRole($role),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $this->importedGroupMembersCount++;

            // Add to cache to prevent duplicates in same import
            $this->existingGroupMembersCache[$groupMemberKey] = true;

            // Batch insert when reach batch size
            if (count($membersToInsert) >= $this->batchSize()) {
                Member::insert($membersToInsert);
                $membersToInsert = [];
            }

            if (count($groupMembersToInsert) >= $this->batchSize()) {
                GroupMember::insert($groupMembersToInsert);
                $groupMembersToInsert = [];
            }
        }

        // Insert any remaining records
        if (!empty($membersToInsert)) {
            Member::insert($membersToInsert);
        }

        if (!empty($groupMembersToInsert)) {
            GroupMember::insert($groupMembersToInsert);
        }

        $this->updateProgress(95, 'Finalizing import...');
        $this->clearProgress();

        if ($this->importedGroupMembersCount === 0) {
            throw new \Exception('No new group members were imported. All members already exist or data was invalid.');
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
            $memberName = $row[5] ?? null;
            $memberId = $row[6] ?? null;
            $role = $row[7] ?? null;
            $number = $row[8] ?? null;
            $gender = $row[9] ?? null;
            $disabled = $row[10] ?? null;

            // Validate mandatory fields
            if (empty($sno) || !is_numeric($sno)) {
                $errors[] = "Row {$rowNumber}: S/No. is required and must be a number";
            }

            if (empty($phaseName)) {
                $errors[] = "Row {$rowNumber}: Phase name is required";
            } elseif (!$this->isValidLoanPhase($phaseName)) {
                $errors[] = "Row {$rowNumber}: Phase '{$phaseName}' does not exist in this constituency";
            }

            if (empty($level)) {
                $errors[] = "Row {$rowNumber}: Level is required";
            } elseif (!$this->isValidLevel($level)) {
                $errors[] = "Row {$rowNumber}: Level must be one of: New, Refinancing, Resubmission, Reallocation";
            }

            if (empty($groupName)) {
                $errors[] = "Row {$rowNumber}: Group name is required";
            }

            if (empty($groupCode)) {
                $errors[] = "Row {$rowNumber}: Group code is required";
            }

            // Validate group exists and get group loan
            $groupLoanId = $this->getGroupLoanId($phaseName, $level, $groupName, $groupCode);
            if ($groupLoanId === null) {
                $errors[] = "Row {$rowNumber}: Group loan not found for '{$groupName}' with code '{$groupCode}' in phase '{$phaseName}' with level '{$level}'";
            }

            if (empty($memberName)) {
                $errors[] = "Row {$rowNumber}: Member name is required";
            }

            if (empty($memberId)) {
                $errors[] = "Row {$rowNumber}: Member ID is required";
            } elseif (!preg_match('/^\d+$/', $memberId)) {
                $errors[] = "Row {$rowNumber}: Member ID must contain only numbers";
            }

            if (empty($role)) {
                $errors[] = "Row {$rowNumber}: Role is required";
            } elseif (!$this->isValidRole($role)) {
                $errors[] = "Row {$rowNumber}: Role must be one of: Chairperson, Secretary, Tresurer, Member";
            }

            if (empty($gender)) {
                $errors[] = "Row {$rowNumber}: Gender is required";
            } elseif (!$this->isValidGender($gender)) {
                $errors[] = "Row {$rowNumber}: Gender must be Male or Female";
            }

            if (empty($disabled)) {
                $errors[] = "Row {$rowNumber}: Disabled field is required";
            } elseif (!$this->isValidDisabled($disabled)) {
                $errors[] = "Row {$rowNumber}: Disabled must be Yes or No";
            }

            // Validate phone number format if provided
            if (!empty($number) && !$this->isValidPhoneNumber($number)) {
                $errors[] = "Row {$rowNumber}: Phone number must be a valid format";
            }

            // Check for duplicate group member associations within file
            if ($groupLoanId && $memberId) {
                $fileKey = $memberId . '|' . $groupLoanId;
                if (isset($seenInFile[$fileKey])) {
                    $errors[] = "Row {$rowNumber}: Duplicate member '{$memberName}' found for the same group loan";
                }
                $seenInFile[$fileKey] = true;
            }

            // Check against existing group member associations
            if ($groupLoanId && $memberId) {
                $groupMemberKey = $memberId . '|' . $groupLoanId;
                if (isset($this->existingGroupMembersCache[$groupMemberKey])) {
                    $errors[] = "Row {$rowNumber}: Member '{$memberName}' already exists in this group loan";
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
        if (empty($headerRow) || count($headerRow) < 11) {
            return false;
        }

        $expectedHeaders = [
            's/no.*', 'phase*', 'level*', 'group*', 'code*', 'name*', 'member_id*',
            'role*', 'number*', 'gender*', 'disabled*'
        ];

        $actualHeaders = array_map(function($header) {
            return strtolower(trim($header ?? ''));
        }, $headerRow->toArray());

        // Check if we have at least the mandatory headers
        for ($i = 0; $i < 11; $i++) {
            Log::info('actual headers: '. $actualHeaders[$i] . "\n expected headers: " . $expectedHeaders[$i]);
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

    private function getGroupId($groupName, $groupCode)
    {
        $key = strtolower(trim($groupName)) . '|' . strtolower(trim($groupCode));
        return $this->groupsCache[$key] ?? null;
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
            'reallocation' => 'Reallocation', // Map to closest match
            default => 'New'
        };
    }

    private function getGroupLoanId($phaseName, $level, $groupName, $groupCode)
    {
        $loanPhaseId = $this->getLoanPhaseId($phaseName);
        $groupId = $this->getGroupId($groupName, $groupCode);
        $normalizedLevel = $this->normalizeLevel($level);

        if ($loanPhaseId && $groupId) {
            $key = $loanPhaseId . '|' . $groupId . '|' . strtolower($normalizedLevel);
            return $this->groupLoansCache[$key] ?? null;
        }

        return null;
    }

    private function isValidRole($role)
    {
        return in_array(strtolower(trim($role)), $this->validRoles);
    }

    private function normalizeRole($role)
    {
        $normalized = strtolower(trim($role));
        return match($normalized) {
            'chairperson' => 'Chairperson',
            'secretary' => 'Secretary',
            'tresurer' => 'Tresurer',
            'member' => 'Member',
            default => 'Member'
        };
    }

    private function isValidGender($gender)
    {
        return in_array(strtolower(trim($gender)), $this->validGenders);
    }

    private function normalizeGender($gender)
    {
        return ucfirst(strtolower(trim($gender)));
    }

    private function isValidDisabled($disabled)
    {
        return in_array(strtolower(trim($disabled)), $this->validDisabled);
    }

    private function normalizeDisabled($disabled)
    {
        $normalized = strtolower(trim($disabled));
        return in_array($normalized, ['yes', '1', 'true']);
    }

    private function isValidPhoneNumber($number)
    {
        // Basic phone number validation - adjust as needed
        $cleaned = preg_replace('/[^0-9]/', '', $number);
        return strlen($cleaned) >= 9 && strlen($cleaned) <= 12;
    }

    /**
     * Progress tracking methods
     */
    private function updateProgress($progress, $message)
    {
        static $lastProgress = 0;

        if (abs($progress - $lastProgress) >= 5 || $progress === 100) {
            $progressKey = "import_progress_{$this->constituencyCode}_members";
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
        $progressKey = "import_progress_{$this->constituencyCode}_members";
        Cache::forget($progressKey);
    }

    public function getImportStats()
    {
        return [
            'imported_members' => $this->importedMembersCount,
            'imported_group_members' => $this->importedGroupMembersCount,
            'skipped_members' => $this->skippedMembersCount,
            'skipped_group_members' => $this->skippedGroupMembersCount,
            'total_processed' => $this->importedGroupMembersCount + $this->skippedGroupMembersCount
        ];
    }
}
