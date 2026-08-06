<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use App\Models\Constituency;
use App\Models\GroupLoan;
use App\Support\GfsCode;

class MembersTemplateImport implements
    ToCollection,
    WithChunkReading,
    WithEvents
{
    private $constituency;
    private string $progressKey;

    private $cacheTag = null;
    private const MEMBER_BATCH_SIZE = 100;
    private const GROUP_MEMBER_BATCH_SIZE = 100;

    // Valid values
    private $validPhases = [];
    protected $loan_types = ['new', 'resubmission', 'refinancing', 'reallocation'];
    private $validGenders = ['male', 'female'];
    private $validDisabled = ['yes', 'no'];
    private $validRoles = ['chairperson', 'secretary', 'treasurer', 'member'];
    private $validGroupLoans = [];

    private $existingMembers = [];
    private $existingGroupMembers = [];

    private $totalRows = 0;
    private $processedRows = 0;
    private $nextRowNumber = 2;

    private $headersValidated = false;

    private $groupLoanMembers = []; // Keyed by phase|loanType|groupCode
    private $docMemberIDs = []; // Keyed by member ID
    private $groupLoanIndex = []; // Reverse lookup
    private $docGroupCodes = [];

    private $importSuccess = false;
    private $importedNewGroupsCount = 0;
    private $importedCount = 0;
    private $skippedCount = 0;
    private $errors = [];

    public function __construct(Constituency $constituency, $phases, ?string $progressKey = null)
    {
        $this->constituency = $constituency;
        $this->validPhases = is_array($phases) ? $phases : $phases->toArray();
        $this->progressKey = $progressKey
            ?? "import_progress_{$constituency->constituency_code}_members";

        $this->loadExistingData();
        $this->updateProgress(5, 'Loaded constituency reference data...');
    }

    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {

        $mapping = $this->getColumnMapping();

        if (!$this->headersValidated) {
            $headerRow = $collection->first();

            if (empty(array_filter($headerRow->toArray()))) {
                $this->errors[] = 'Invalid Excel template. Header row is empty.';
                return;
            }

            // Validate headers
            if (!$this->validateHeaders($headerRow, $mapping)) {
                $this->errors[] = 'Invalid Excel template. Please download the correct template format.';
                return;
            }

            $this->headersValidated = true;

            $dataRows = $collection->slice(1);

            $this->totalRows = max($this->totalRows, $dataRows->count());
            $this->nextRowNumber = 1;
        } else {
            $this->nextRowNumber = 2;
            $dataRows = $collection;
        }

        foreach ($dataRows as $rowIndex => $row) {

            $rowNumber = $this->nextRowNumber + $this->processedRows + $rowIndex;

            if (empty(array_filter($row->toArray()))) {
                continue;
            }

            $this->validateRow($row, $rowNumber, $mapping);

            if (count($this->errors) >= 20) {
                $this->errors[] = "... and possibly more errors. Please fix these first 20 rows before re-uploading.";
                break;
            }
        }

        $this->processedRows += $dataRows->count();

        if ($this->totalRows > 0) {
            $progress = min(85, (int) round(($this->processedRows / $this->totalRows) * 80) + 10);
            $this->updateProgress($progress, "Validating rows ({$this->processedRows} processed)...");
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function () {
                if (!empty($this->errors)) {
                    throw new \Exception("Validation errors found:\n" . implode("\n", $this->errors));
                }

                $this->validateAgainstDatabaseAfterDoc();

                if (!empty($this->errors)) {
                    throw new \Exception("Validation errors found:\n" . implode("\n", $this->errors));
                }

                $this->updateProgress(90, 'Saving members to database...');
                $this->storeInDB();
                $this->updateProgress(100, 'Import completed successfully.');

                // clear cache (persist)
                Cache::tags([$this->cacheTag])->flush();

            }
        ];
    }

    public function chunkSize(): int
    {
        return 200;
    }

    private function updateProgress(int $progress, string $message): void
    {
        static $lastProgress = 0;

        if (abs($progress - $lastProgress) < 3 && $progress < 100) {
            return;
        }

        Cache::put($this->progressKey, [
            'progress' => $progress,
            'message' => $message,
            'status' => 'running',
            'updated_at' => now(),
        ], now()->addHour());

        $lastProgress = $progress;
    }

    public function loadExistingData() : void
    {
        $this->cacheTag = config('cache.tags.groups.constituency') . $this->constituency->constituency_code;
        $groupMembersCacheKey = "group_members_history";

        $groupLoansCacheKey = "groups_loans_history";

        $this->validGroupLoans = Cache::tags([$this->cacheTag])->get($groupLoansCacheKey);

        if (!$this->validGroupLoans) {

            $groupLoans = DB::table('group_loans')->join('loan_phases', 'group_loans.loan_phase_id', '=', 'loan_phases.id')
                ->join('groups', 'group_loans.group_id', '=', 'groups.id')
                ->join('wards', 'groups.ward_id', '=', 'wards.id')
                ->where('wards.constituency_code', $this->constituency->constituency_code)
                ->whereIn('group_loans.loan_status', ['financed', 'cancelled'])
                ->select(
                    'group_loans.id as group_loan_id',
                    'groups.id as group_id',
                    'groups.code as group_code',
                    'groups.name as group_name',
                    'loan_phases.phase',
                    'group_loans.loan_type'
                )
                ->get();

            $validLoansMap = [];

            if ($groupLoans->isEmpty()) {
                $this->validGroupLoans = [];
                return;
            }

            $validLoansMap = $groupLoans->mapWithKeys(function ($item) {
                $key = $this->buildGroupLoanKey(
                    strtolower(trim($item->phase)),
                    strtolower(trim($item->loan_type)),
                    trim($item->group_code)
                );

                return [$key => [
                    'group_loan_id' => $item->group_loan_id,
                    'group_id' => $item->group_id,
                    'group_name' => $item->group_name,
                    'phase' => $item->phase,
                    'loan_type' => $item->loan_type,
                    'group_code' => $item->group_code
                ]];
            })->toArray();
            // Cache::tags([$this->cacheTag])->put($groupLoansCacheKey, $validLoansMap, now()->addMinutes(2));

            $this->validGroupLoans = $validLoansMap;
            Cache::tags([$this->cacheTag])->put($groupLoansCacheKey, $validLoansMap, now()->addMinutes(10));

            $groupLoanIds = $groupLoans->pluck('group_loan_id')->toArray();

            if (empty($groupLoanIds)) {
                \Log::info("No group loans found for constituency {$this->constituency->constituency_code}");
                return;
            }

            $this->existingGroupMembers = Cache::tags([$this->cacheTag])->get($groupMembersCacheKey);

            if (!$this->existingGroupMembers) {

                $groupMembers = DB::table('group_members')
                    ->join('members', 'group_members.member_Id', '=', 'members.member_Id')
                    ->whereIn('group_members.group_loans_id', $groupLoanIds)
                    ->select([
                        'group_members.id as pivot_id',
                        'group_members.group_loans_id',
                        'group_members.member_Id',
                        'group_members.role',
                        'members.name as member_name',
                        'members.number as phone',
                        'members.gender',
                        'members.disabled'
                    ])
                    ->get()
                    ->groupBy('group_loans_id');

                if ($groupMembers->isEmpty()) {
                    return;
                }

                // Cache::tags([$this->cacheTag])->put($groupMembersCacheKey, $groupMembers, now()->addMinutes(2));

                $this->existingGroupMembers = $groupMembers;
                Cache::tags([$this->cacheTag])->put($groupMembersCacheKey, $groupMembers, now()->addMinutes(10));
            }
        }
    }

    private function getColumnMapping(): array
    {
        return [
            0  => ['name' => 'S/No',                 'required' => true],
            1  => ['name' => 'Phase',                'required' => true],
            2  => ['name' => 'Loan Type',            'required' => true],
            3  => ['name' => 'Group Name',           'required' => true],
            4  => ['name' => 'GFS Code',             'required' => true],
            5  => ['name' => 'Member Name',          'required' => true],
            6  => ['name' => 'Member ID',            'required' => true],
            7  => ['name' => 'Member Role',          'required' => true],
            8  => ['name' => 'Phone Number',         'required' => true],
            9  => ['name' => 'Gender',               'required' => true],
            10 => ['name' => 'Disabled',             'required' => true],
        ];
    }

    private function validateHeaders($headerRow, $mapping)
    {
        $actualHeaders = $headerRow->toArray();

        foreach ($mapping as $index => $setup) {
            $rawActual = $actualHeaders[$index] ?? '';

            $cleanActual = str_replace(['*', "\xa0"], ['', ' '], $rawActual);
            $cleanActual = strtolower(trim($cleanActual));

            $expected = strtolower(trim($setup['name']));

            if ($cleanActual !== $expected) {
                return false;
            }
        }
        return true;
    }

    private function validateRow($row, $rowNumber, $mapping)
    {
        if (count($this->errors) >= 20) return;

        foreach ($mapping as $colIndex => $setup) {
            if (count($this->errors) >= 20) return;

            $value = trim($row[$colIndex] ?? '');
            $lowerValue = strtolower($value);

            // 1. Mandatory Check
            if ($setup['required'] && $value === '') {
                $this->errors[] = "Row {$rowNumber}: Mandatory field '{$setup['name']}' is missing.";
                continue;
            }

            // 2. Phase
            if ($colIndex === 1 && $value !== '') {
                if (!in_array($lowerValue, $this->validPhases)) {
                    $this->errors[] = "Row {$rowNumber}: '{$value}' is not a valid Phase for this constituency.";
                }
            }

            // 3. loan type
            if ($colIndex === 2 && $value !== '') {
                if (!in_array($lowerValue, $this->loan_types)) {
                    $this->errors[] = "Row {$rowNumber}: '{$value}' is not a valid loan type.";
                }
            }

            // 4. member name
            if ($colIndex === 3 && $value !== '') {
                if (strlen($value) < 5) {
                    $this->errors[] = "Row {$rowNumber}: '{$value}' is too short for a Member Name.";
                }
            }

            // 5. member role
            if ($colIndex === 7 && $value !== '') {
                if (!in_array($lowerValue, $this->validRoles)) {
                    $this->errors[] = "Row {$rowNumber}: '{$value}' is not a valid Member Role.";
                }
            }

            // 6. Gender
            if ($colIndex === 9 && $value !== '') {
                if (!in_array($lowerValue, $this->validGenders)) {
                    $this->errors[] = "Row {$rowNumber}: '{$value}' is not a valid Gender.";
                }
            }

            // 7. Disability
            if ($colIndex === 10 && $value !== '') {
                if (!in_array($lowerValue, $this->validDisabled)) {
                    $this->errors[] = "Row {$rowNumber}: '{$value}' is not a valid Disability Status.";
                }
            }

            // 8. Phone Number validation (Column I, index 8)
            if ($colIndex === 8 && $value !== '') {
                // Remove any non-digit characters (spaces, dashes, etc.)
                $this->validatePhoneNumber($value, $rowNumber);
            }

            // 9. ID Number
            if ($colIndex === 6 && $value !== '') {
                // Remove any non-digit characters (spaces, dashes, etc.)
                $this->validateIDNumber($value, $rowNumber, $row);
            }

            // 10. GFS
            if ($colIndex === 4 && $value !== '') {
                $this->validateGfs($value, $rowNumber, $row);
            }
        }

        $this->processMemberEntry($row, $rowNumber);
    }

    private function validatePhoneNumber($number, $rowNumber)
    {
        $digitsOnly = preg_replace('/[^0-9]/', '', $number);
        $digitCount = strlen($digitsOnly);

        // Check length
        if ($digitCount < 9 || $digitCount > 10) {
            $this->errors[] = "Row {$rowNumber}: Phone number '{$number}' must be 9 or 10 digits. Found {$digitCount} digit(s).";
        }
        // If 10 digits, first digit must be 0
        elseif ($digitCount === 10 && substr($digitsOnly, 0, 1) !== '0') {
            $this->errors[] = "Row {$rowNumber}: Phone number '{$number}' with 10 digits must start with 0.";
        }
        // If 9 digits, first digit must be 7 or 1
        elseif ($digitCount === 9 && !in_array(substr($digitsOnly, 0, 1), ['7', '1'])) {
            $this->errors[] = "Row {$rowNumber}: Phone number '{$number}' with 9 digits must start with 7 or 1.";
        }
    }

    private function validateIDNumber($ID, $rowNumber, $row)
    {
        $digitsOnly = preg_replace('/[^0-9]/', '', $ID);
        $digitCount = strlen($digitsOnly);

        if ($digitCount < 6 || $digitCount > 10) {
            $this->errors[] = "Row {$rowNumber}: ID number '{$ID}' must be 6 to 10 digits. Found {$digitCount} digit(s).";
            return;
        }

        $this->checkForDuplicateIDsInDoc($digitsOnly, $rowNumber, $row);
    }

    private function validateGfs($value, $rowNumber, $row)
    {
        $parsed = GfsCode::parse($value);
        if (!$parsed) {
            $this->errors[] = "Row {$rowNumber}: GFS Code '{$value}' is invalid. Sample 4-{$this->constituency->county_code}-{$this->constituency->constituency_code}-4110202-201-001";
            return;
        }

        $value = $parsed['full'];
        $providedInit = $parsed['init'];
        $providedCountyCode = $parsed['county'];
        $providedConstituencyCode = $parsed['constituency'];


        $expectedInit = (string) 4;
        $expectedCountyCode       = (string) $this->constituency->county_code;
        $expectedConstituencyCode = (string) $this->constituency->constituency_code;

        $expectedCountyCode       = str_pad($expectedCountyCode,       3, '0', STR_PAD_LEFT);
        $expectedConstituencyCode = str_pad($expectedConstituencyCode, 3, '0', STR_PAD_LEFT);
        if ($providedInit !== $expectedInit || $providedCountyCode !== $expectedCountyCode ||
            $providedConstituencyCode !== $expectedConstituencyCode) {

            $this->errors[] = "Row {$rowNumber}: GFS Code '{$value}' has wrong county/constituency codes. "
                . "Expected {$expectedInit}-{$expectedCountyCode}-{$expectedConstituencyCode}, "
                . "but found {$providedInit}-{$providedCountyCode}-{$providedConstituencyCode}";

            return;
        }

        $groupCode = $parsed['group_suffix'];

        $this->checkForDuplicateGroupCodeInDoc($groupCode, $rowNumber, $value, $row);
    }

    private function checkForDuplicateIDsInDoc(
        string $ID,
        int $rowNumber,
        $row
    ) : void
    {
        $rowData = $row->map(fn($item) => strtolower($item));
        $currentMemberName = trim($rowData[5] ?? '');

        if (isset($this->docMemberIDs[$ID])) {
            $existing = $this->docMemberIDs[$ID];
            $existingMemberName = $existing['member_name'];

            $isSamePerson = $currentMemberName === strtolower(trim($existingMemberName));
            if (!$isSamePerson) {
                // Different Members using same ID Number - this is a conflict
                $this->errors[] = "Row {$rowNumber}: ID Number '{$ID}' is already used by "
                    . "a DIFFERENT Member in row {$existing['rowNumber']}. "
                    . "\n  Current Member: '{$currentMemberName}'"
                    . "\n  Existing Memebr: '{$existingMemberName}'"
                    . "\n  Each Member must have a unique ID Number. Different Members cannot share the same ID Number.";
                return;
            }
        }

        $this->docMemberIDs[$ID] = [
            'row' => $rowData,
            'rowNumber' => $rowNumber,
            'member_name' => strtolower(trim($rowData[5] ?? '')),
        ];
    }

    private function extractGroupCode(string $gfs): ?string
    {
        return GfsCode::groupSuffix($gfs);
    }

    private function checkForDuplicateGroupCodeInDoc(
        string $groupCode,
        int $rowNumber,
        string $fullGfs,
        $row
    ): void {
        $rowData = $row->map(fn($item) => strtolower($item));
        // If we've already seen this group code in the Excel file
        $currentGroupName = trim($rowData[3] ?? 'Unknown');

        if (isset($this->docGroupCodes[$groupCode])) {
            $existing = $this->docGroupCodes[$groupCode];
            $existingGroupName = $existing['group_name'];

            // Check if they are the same group (by name)
            $isSameGroup = $currentGroupName === $existingGroupName;
            if (!$isSameGroup) {
                // Different groups using same GFS code - this is a conflict
                $this->errors[] = "Row {$rowNumber}: GFS code suffix '{$groupCode}' is already used by "
                    . "a DIFFERENT group in row {$existing['rowNumber']}. "
                    . "\n  Current Group: '{$currentGroupName}'"
                    . "\n  Existing Group: '{$existingGroupName}'"
                    . "\n  Each group must have a unique GFS code suffix. Different groups cannot share the same GFS code.";
                return;
            }
        }

        $this->docGroupCodes[$groupCode] = [
            'row' => $rowData,
            'rowNumber' => $rowNumber,
            'gfs' => $fullGfs,
            'group_name' => strtolower(trim($rowData[3] ?? '')),
        ];
    }

    private function processMemberEntry($rowData, $rowNumber)
    {
        // if row has prior errors just return
        if (!empty($this->errors)) return;

        $groupCode = $this->extractGroupCode(strtolower(trim($rowData[4] ?? '')));

        if (!$groupCode) {
            return;
        }

        $phase = strtolower(trim($rowData[1] ?? ''));
        $loanType = strtolower(trim($rowData[2] ?? ''));
        $groupName = trim($rowData[3] ?? '');
        $gfs = GfsCode::normalize(trim($rowData[4] ?? '')) ?? trim($rowData[4] ?? '');
        $memberId = strtolower(trim($rowData[6] ?? ''));
        $memberName = trim($rowData[5] ?? '');
        $memberRole = strtolower(trim($rowData[7] ?? ''));
        $phone = trim($rowData[8] ?? '');
        $gender = strtolower(trim($rowData[9] ?? ''));
        $disabled = strtolower(trim($rowData[10] ?? ''));

        $groupLoanKey = $this->buildGroupLoanKey($phase, $loanType, $groupCode);
        $groupLoan = $this->getOrCreateGroupLoanContainer($groupLoanKey, [
            'phase' => $phase,
            'loan_type' => $loanType,
            'group_code' => $groupCode,
            'group_name' => $groupName,
            'gfs_code' => $gfs,
        ]);

        // validate from exixting members
        if(isset($this->groupLoanMembers[$groupLoanKey])) {
            // check if member data exist, if it doesnt then add to members data
            if (isset($this->groupLoanMembers[$groupLoanKey]['members'][$memberId])) {
                $this->errors[] = "Row {$rowNumber}: Member ID '{$memberId}' already exists in this group loan "
                    . "(Phase: {$phase}, Loan Type: {$loanType}, Group: {$groupName}) at row "
                    . $this->groupLoanMembers[$groupLoanKey]['members'][$memberId]['row'];
                return;
            }

            // b4 savig member data check if primary role is defined
            $roleValidation = $this->validateMemberRole(
                $memberRole,
                $groupLoanKey,
                $memberId,
                $memberName,
                $rowNumber
            );

            if (!$roleValidation['valid']) {
                foreach ($roleValidation['errors'] as $error) {
                    $this->errors[] = $error;
                }
                return;
            }
        }

        // create member
        $memberData = [
            'member_id' => $memberId,
            'member_name' => $memberName,
            'role' => $memberRole,
            'phone' => $phone,
            'gender' => $gender,
            'disabled' => $disabled,
            'row' => $rowNumber
        ];

        $this->groupLoanMembers[$groupLoanKey]['members'][$memberId] = $memberData;
        $this->groupLoanMembers[$groupLoanKey]['rows'][] = $rowNumber;
        $this->groupLoanMembers[$groupLoanKey]['roles'][] = $memberRole;
        $this->groupLoanMembers[$groupLoanKey]['member_count'] = count($this->groupLoanMembers[$groupLoanKey]['members']);

        $this->docMemberIDs[$memberId] = [
            'row' => $rowData,
            'rowNumber' => $rowNumber,
            'member_name' => $memberName,
            'group_loan_key' => $groupLoanKey,
            'phase' => $phase,
            'loan_type' => $loanType,
            'group_code' => $groupCode,
            'role' => $memberRole
        ];

        //  dump($this->groupLoanMembers);
    }

    private function buildGroupLoanKey(string $phase, string $loanType, string $groupCode): string
    {
        return md5("{$phase}|{$loanType}|{$groupCode}"); // Use hash for consistent length
        // return "{$phase}|{$loanType}|{$groupCode}"; // Human readable
    }

    private function getOrCreateGroupLoanContainer(string $key, array $context): array
    {
        if (!isset($this->groupLoanMembers[$key])) {
            $this->groupLoanMembers[$key] = [
                'phase' => $context['phase'],
                'loan_type' => $context['loan_type'],
                'group_code' => $context['group_code'],
                'group_name' => $context['group_name'],
                'gfs_code' => $context['gfs_code'],
                'members' => [],
                'roles' => [], // Track roles: ['chairperson' => [memberId1], 'secretary' => [memberId2], ...]
                'rows' => [],
                'member_count' => 0,
            ];
        }

        return $this->groupLoanMembers[$key];
    }

    private function validateMemberRole(string $role, string $groupLoanKey, string $memberId, string $memberName, int $rowNumber): array
    {
        $result = [
            'valid' => true,
            'errors' => []
        ];

        // Define leadership roles that should be unique
        $leadershipRoles = ['chairperson', 'secretary', 'treasurer'];
        $normalizedRole = strtolower(trim($role));

        // Only validate if the container already exists (has previous members)
        if (isset($this->groupLoanMembers[$groupLoanKey])) {
            // Check if this is a leadership role
            if (in_array($normalizedRole, $leadershipRoles)) {
                // Check if this role already exists in this group loan
                if (isset($this->groupLoanMembers[$groupLoanKey]['roles'])) {
                    if (in_array($role, $this->groupLoanMembers[$groupLoanKey]['roles'])){
                        $groupName = $this->groupLoanMembers[$groupLoanKey]['group_name'] ?? '';
                        $phase = $this->groupLoanMembers[$groupLoanKey]['phase'] ?? '';
                        $loanType = $this->groupLoanMembers[$groupLoanKey]['loan_type'] ?? '';

                        $result['valid'] = false;
                        $result['errors'][] = "Row {$rowNumber}: Cannot add '{$memberName}' as {$role}. "
                            . "This group: '{$groupName} loan already has a {$role} in {$phase} - {$loanType}";
                    }
                }
            }
        }

        return $result;
    }

    private function validateAgainstDatabaseAfterDoc() : void
    {
        if (empty($this->groupLoanMembers)) {
            $this->errors[] = "You possibly have an empty document. Please check your document before re-uploading.";
            return;
        }

        if (!empty($this->errors)) return;

        $missingKeys = array_diff_key($this->groupLoanMembers, $this->validGroupLoans);

        if (!empty($missingKeys)) {
            $this->missingErrorMsg($missingKeys);
            return;
        }

        $matchedSystemLoans = array_intersect_key($this->validGroupLoans, $this->groupLoanMembers);

        // check the group names conflict
        if(!$this->matchGroupNames($this->groupLoanMembers, $matchedSystemLoans)) return;

        if(!$this->validateMatchMembers($matchedSystemLoans)) return;
    }

    private function missingErrorMsg($missingKeys) : void
    {
        $errorMessages = ["🔴 DATABASE VALIDATION FAILED\n"];
            $errorMessages[] = "The following entries could not be matched with existing records:\n";

            foreach ($missingKeys as $key => $data) {
                $phase = ucfirst($data['phase'] ?? '');
                $loanType = ucfirst($data['loan_type'] ?? '');
                $groupName = $data['group_name'] ?? '';
                $gfsCode = $data['gfs_code'] ?? '';
                $rows = is_array($data['rows']) ? implode(', ', $data['rows']) : $data['rows'];

                $errorMessages[] = sprintf(
                    "  📌 Group: %s\n" .
                    "     ├─ GFS Code: %s\n" .
                    "     ├─ Loan: %s in %s\n" .
                    "     └─ Rows affected: %s\n",
                    $groupName,
                    $gfsCode,
                    $loanType,
                    $phase,
                    $rows
                );
            }

            $errorMessages[] = "🔍 TROUBLESHOOTING TIPS:";
            $errorMessages[] = "  • Verify GFS Codes in the 'Groups Management' section";
            $errorMessages[] = "  • Check that group names are spelled exactly as in the system";
            $errorMessages[] = "  • Ensure the specified phases have been created";
            $errorMessages[] = "  • Confirm groups have the correct loan types in those phases";
            $errorMessages[] = "\n💡 Need help? Contact the system administrator if the issue persists.";

            $this->errors[] = implode("\n", $errorMessages);
    }

    private function matchGroupNames($groupLoanMembers, $matchedSystemLoans) : Bool
    {
        $match = true;

        $nameMismatchErrors = [];

        foreach ($matchedSystemLoans as $key => $matched) {
            $dbGroupName = strtolower(trim($matched['group_name'] ?? ''));
            $docData = $this->groupLoanMembers[$key];
            $docGroupName = strtolower(trim($docData['group_name'] ?? ''));

            if ($dbGroupName !== $docGroupName) {
                $rows = isset($docData['rows']) ? implode(', ', $docData['rows']) : 'N/A';
                $gfsCode = $docData['gfs_code'] ?? $matched['group_code'] ?? 'N/A';
                $phase = ucfirst($docData['phase'] ?? $matched['phase'] ?? '');
                $loanType = ucfirst($docData['loan_type'] ?? $matched['loan_type'] ?? '');

                $nameMismatchErrors[] = sprintf(
                    "  📍 %s - %s %s\n" .
                    "     System: '%s'\n" .
                    "     Document: '%s'\n" .
                    "     Rows: %s\n",
                    $gfsCode,
                    $phase,
                    $loanType,
                    $matched['group_name'],
                    $docData['group_name'],
                    $rows
                );
            }
        }

        if (!empty($nameMismatchErrors)) {
            $this->errors[] = "🔴 NAME MISMATCH ERRORS\n" .
                "The following groups have naming conflicts under the same GFS Code:\n\n" .
                implode("\n", $nameMismatchErrors) . "\n" .
                "Please update your document to use the exact system group names.";
            return false;
        }

        return $match;
    }

    private function validateMatchMembers($matchedSystemLoans) : Bool
    {
        $validationPassed = true;

        foreach ($this->groupLoanMembers as $key => $uploadData) {
            $groupLoanId = $matchedSystemLoans[$key]['group_loan_id'];
            $uploadMembers = $uploadData['members'] ?? [];
            $groupName = $uploadData['group_name'] ?? 'Unknown';
            $context = "{$groupName} ({$uploadData['phase']} | {$uploadData['loan_type']})";

            $existingMembers = $this->existingGroupMembers[$groupLoanId] ?? [];

            // dd($this->existingGroupMembers[$groupLoanId]);
            foreach ($uploadMembers as $memberId => $details) {
                if (isset($this->existingGroupMembers[$groupLoanId])) {
                    $isDuplicate = $this->existingGroupMembers[$groupLoanId]->contains('member_Id', (string)$memberId);
                    if ($isDuplicate) {
                        $this->errors[] = "Row {$details['row']}: Member '{$details['member_name']}' (ID: {$memberId}) " .
                            "is already registered for {$context}.";

                        $validationPassed = false;

                        // Remove this member from the array so they aren't processed later
                        // unset($this->groupLoanMembers[$key]['members'][$memberId]);
                    }

                    $role = strtolower(trim($details['role'] ?? ''));

                    if (in_array($role, ['chairperson', 'secretary', 'treasurer'])) {
                        foreach ($this->existingGroupMembers[$groupLoanId] as $existingMember) {
                            $existingRole = strtolower(trim($existingMember?->role ?? ''));
                            $existingMemberName = $existingMember?->member_name ?? '';
                            if ($existingRole === $role) {
                                $this->errors[] = "Row {$details['row']}: Cannot add {$role} '{$details['member_name']}' - " .
                                    "this role is already held by '{$existingMemberName}' in {$context}";
                                $validationPassed = false;
                            }
                        }
                    }
                }
            }
        }
        return $validationPassed;
    }

    private function storeInDB() : void
    {
        if (!empty($this->errors)) return;

        DB::beginTransaction();

        try {
            $this->importedCount = 0;
            $newMembersCreated = 0;

            // Step 1: Collect all members that need to be created
            $membersToCreate = $this->collectNewMembers();

            // Step 2: Batch insert new members
            if (!empty($membersToCreate)) {
                $newMembersCreated = $this->batchInsertMembers($membersToCreate);
            }

            // Step 3: Batch insert group member associations
            $this->importedCount = $this->batchInsertGroupMembers();

            DB::commit();

            $this->importResult = [
                'success' => true,
                'imported_count' => $this->importedCount,
                'new_members_created' => $newMembersCreated,
                'message' => sprintf(
                    "✅ Successfully imported %d member(s) into groups. %d new member(s) were added to the members table.",
                    $this->importedCount,
                    $newMembersCreated
                )
            ];

            DB::afterCommit(function () {
                Cache::tags([$this->cacheTag])->flush();
            });


        } catch (\Exception $e) {
            DB::rollBack();
            $this->handleImportError($e);
        }

    }

    private function collectNewMembers(): array
    {
        $membersToCreate = [];
        $candidateIds = [];

        foreach ($this->groupLoanMembers as $groupData) {
            foreach ($groupData['members'] as $memberId => $memberData) {
                $candidateIds[(string) $memberId] = true;
            }
        }

        if (empty($candidateIds)) {
            return [];
        }

        $existingIds = DB::table('members')
            ->whereIn('member_Id', array_keys($candidateIds))
            ->pluck('member_Id')
            ->map(fn ($id) => (string) $id)
            ->flip()
            ->all();

        foreach ($this->groupLoanMembers as $groupData) {
            foreach ($groupData['members'] as $memberId => $memberData) {
                $memberKey = (string) $memberId;

                if (!isset($existingIds[$memberKey])) {
                    $membersToCreate[$memberKey] = [
                        'member_Id' => $memberKey,
                        'name' => $memberData['member_name'],
                        'number' => $memberData['phone'],
                        'gender' => ucfirst($memberData['gender']),
                        'disabled' => $memberData['disabled'] === 'yes' ? 1 : 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        return $membersToCreate;
    }

    /**
     * Batch insert members
     */
    private function batchInsertMembers(array $membersToCreate): int
    {
        $created = 0;
        $batches = array_chunk($membersToCreate, self::MEMBER_BATCH_SIZE, true);

        foreach ($batches as $batch) {
            DB::table('members')->insert(array_values($batch));
            $created += count($batch);
            \Log::debug("Created batch of " . count($batch) . " new members");
        }

        return $created;
    }

    /**
     * Batch insert group member associations
     */
    private function batchInsertGroupMembers(): int
    {
        $imported = 0;
        $batch = [];

        foreach ($this->groupLoanMembers as $groupLoanKey => $groupData) {
            if (!isset($this->validGroupLoans[$groupLoanKey])) {
                continue;
            }

            $groupLoanId = $this->validGroupLoans[$groupLoanKey]['group_loan_id'];

            foreach ($groupData['members'] as $memberId => $memberData) {
                $batch[] = [
                    'group_loans_id' => $groupLoanId,
                    'member_Id' => $memberId,
                    'role' => $memberData['role'],
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                $imported++;

                if (count($batch) >= self::GROUP_MEMBER_BATCH_SIZE) {
                    DB::table('group_members')->insert($batch);
                    \Log::debug("Inserted batch of " . count($batch) . " group member associations");
                    $batch = [];
                }
            }
        }

        // Insert remaining
        if (!empty($batch)) {
            DB::table('group_members')->insert($batch);
            \Log::debug("Inserted final batch of " . count($batch) . " group member associations");
        }

        return $imported;
    }

    /**
     * Handle import error
     */
    private function handleImportError(\Exception $e): void
    {
        // \Log::error("Member import failed: " . $e->getMessage(), [
        //     'trace' => $e->getTraceAsString()
        // ]);

        $this->errors[] = "Database import failed: " . $e->getMessage();
        $this->importResult = [
            'success' => false,
            'imported_count' => 0,
            'new_members_created' => 0,
            'message' => '❌ Import failed: ' . $e->getMessage()
        ];

        throw $e;
    }

    public function getImportResult(): array
    {
        return $this->importResult ?? [
            'success' => false,
            'imported_count' => 0,
            'new_members_created' => 0,
            'message' => 'No import result available'
        ];
    }

    public function getErrors(): array
    {
        return $this->errors;
    }


}
