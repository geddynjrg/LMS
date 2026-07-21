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
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Carbon\Carbon;
use App\Jobs\UpdateLoanDefaultStatusForConstituency;
use App\Models\Constituency;
use App\Models\Group;
use App\Models\GroupLoan;
use App\Models\LoanPhase;
use App\Models\Wards;
use App\Services\Loan\LoanPhaseService;
use App\Support\GfsCode;
use App\Support\LoanPhaseFinancialTotals;

class LoanTemplateImport implements
    ToCollection,
    WithChunkReading,
    WithEvents
{
    private $constituency;

    private $cacheTag = null;

    private $headersValidated = false;
    private $firstChunkProcessed = false;
    private $hasProcessedHeaderAndSummary = false;
    private $headerRow = null;
    private $nextRowNumber = 3;

    private $totalRows = 0;
    private $processedRows = 0;
    private $validWards = [];
    private $validPhases = [];
    protected $loan_types = ['new', 'resubmission', 'refinancing', 'reallocation'];
    protected $categories = ['youth', 'women', 'pwd'];
    protected $sectors    = ['trade', 'service', 'manufacturing', 'agriculture', 'other'];

    private $existingGroupCodes = [];
    private $existingIdentifiers = [];
    private $existingRegNumbers = [];
    private $existingGfsCodes = [];

    private $docGroupCodes = [];
    private $docIdentifiers = [];
    private $docEntries = [];

    private $existingDBGroups = [];

    private $importSuccess = false;
    private $importedNewGroupsCount = 0;
    private $importedCount = 0;
    private $skippedCount = 0;
    private $errors = [];
    private array $affectedPhaseIds = [];

    public function __construct(Constituency $constituency, $phases)
    {
        $this->constituency = $constituency;
        $this->validPhases = is_array($phases) ? $phases : $phases->toArray();
        $this->validWards = $constituency->wards->pluck('ward_name')->map('strtolower')->toArray();

        $this->loadExistingData();
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

            $dataRows = $collection->slice(2);

            $this->nextRowNumber = 1;
        } else {
            $this->nextRowNumber = 3;
            $dataRows = $collection;
        }


        if (!$this->firstChunkProcessed) {
            $this->firstChunkProcessed = true;
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
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function () {
                if (!empty($this->errors)) {
                    throw new \Exception("Validation errors found:\n" . implode("\n", $this->errors));
                }
                // database validation on all collected data
                $this->validateConsistentGfsCodeForGroupName();
                $this->validateAgainstDatabaseAfterDoc();

                if (!empty($this->errors)) {
                    throw new \Exception("Validation errors found:\n" . implode("\n", $this->errors));
                }

                $this->storeInDb();

                if (!empty($this->errors)) {
                    throw new \Exception("Storage errors found:\n" . implode("\n", $this->errors));
                }

                $this->importSuccess = true;

                // clear cache (persist)
                Cache::tags([$this->cacheTag])->flush();
            },
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
        foreach ($mapping as $colIndex => $setup) {
            if (count($this->errors) >= 20) return;

            $value = trim($row[$colIndex] ?? '');
            $lowerValue = strtolower($value);

            // 1. Mandatory Check
            if ($setup['required'] && $value === '') {
                $this->errors[] = "Row {$rowNumber}: Mandatory field '{$setup['name']}' is missing.";
                continue;
            }

            // 2. Date
            if ($colIndex === 1 && $value !== '') {
                if (!$this->isValidDate($value)) {
                    $this->errors[] = "Row {$rowNumber}: '{$value}' is not a recognized date format. Please use YYYY-MM-DD.";
                }
            }

            // 4. ward
            if ($colIndex === 3 && $value !== '') {
                if (!in_array($lowerValue, $this->validWards)) {
                    $this->errors[] = "Row {$rowNumber}: '{$value}' is not a valid Ward for this constituency.";
                }
            }

            // 5. Phase
            if ($colIndex === 4 && $value !== '') {
                if (!in_array($lowerValue, $this->validPhases)) {
                    $this->errors[] = "Row {$rowNumber}: '{$value}' is not a valid Phase for this constituency.";
                }
            }

            //
            if ($colIndex === 5 && $value !== '') {
                if (!in_array($lowerValue, $this->loan_types)) {
                    $this->errors[] = "Row {$rowNumber}: '{$value}' is not a valid loan type.";
                }
            }

            if ($colIndex === 7 && $value !== '') {
                $groupName = strtolower(trim($row[2] ?? ''));
                $gfs = strtolower(trim($row[6] ?? ''));

                $this->validateIdentifier($lowerValue, $rowNumber, $groupName, $gfs);
            }

            if ($colIndex === 8 && $value !== '') {
                if (!in_array($lowerValue, $this->categories)) {
                    $this->errors[] = "Row {$rowNumber}: '{$value}' is not a valid Group Category.";
                }
            }

            if ($colIndex === 9 && $value !== '') {
                if (!in_array($lowerValue, $this->sectors)) {
                    $this->errors[] = "Row {$rowNumber}: '{$value}' is not a valid Group Sector.";
                }
            }

            // 4. Numeric Checks (Approved, Issued, Repaid: 10, 11, 12)
            if (in_array($colIndex, [10, 11, 12]) && $value !== '') {
                if (!is_numeric(str_replace(',', '', $value))) {
                    $this->errors[] = "Row {$rowNumber}: '{$setup['name']}' must be a number.";
                }
            }

            // 6. gfs
            if ($colIndex === 6 && $value !== '') {
                $this->validateGfs($value, $rowNumber, $row, $mapping);
            }
        }
    }

    private function isValidDate($date): bool
    {
        if (empty($date) || strtolower($date) === 'null') {
            return true;
        }

        try {
            // Case 1: Check if it's an Excel serial number (numeric)
            if (is_numeric($date) && $date > 0) {
                // Excel serial numbers 1-59 have leap year bug, but we'll still accept them
                $unixTimestamp = ($date - 25569) * 86400;
                $parsedDate = Carbon::createFromTimestamp($unixTimestamp);

                // Check if it's a real calendar date
                return checkdate($parsedDate->month, $parsedDate->day, $parsedDate->year);
            }

            // Case 2: Try Carbon's parser for string formats
            $parsedDate = Carbon::parse($date);

            // Additional validation for common formats to catch things like "45686" as string
            if (is_string($date) && preg_match('/^\d{5,6}$/', $date)) {
                // Looks like an Excel serial number stored as string
                $numericDate = (int)$date;
                $unixTimestamp = ($numericDate - 25569) * 86400;
                $parsedDate = Carbon::createFromTimestamp($unixTimestamp);
            }

            return $parsedDate->isValid() &&
                checkdate($parsedDate->month, $parsedDate->day, $parsedDate->year);

        } catch (\Exception $e) {
            return false;
        }
    }

    private function validateGfs($value, $rowNumber, $row, $mapping)
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

        // dd($providedInit);
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

        $this->checkForDuplicateGroupCodeInDoc($groupCode, $rowNumber, $value, $row, $mapping);
    }

    public function chunkSize(): int
    {
        return 500;
    }

    private function getColumnMapping(): array
    {
        return [
            0  => ['name' => 'S/No',                 'required' => true],
            1  => ['name' => 'Date of Disbursement', 'required' => true],
            2  => ['name' => 'Name of Group',        'required' => true],
            3  => ['name' => 'Ward',                 'required' => true],
            4  => ['name' => 'Loan Phase',           'required' => true],
            5  => ['name' => 'Loan Type',            'required' => true],
            6  => ['name' => 'GFS Code',             'required' => true],
            7  => ['name' => 'Identifier',           'required' => false],
            8  => ['name' => 'Category',             'required' => true],
            9  => ['name' => 'Sector',               'required' => true],
            10 => ['name' => 'Amount Approved',      'required' => true],
            11 => ['name' => 'Amount Issued',        'required' => true],
            12 => ['name' => 'Amount Repaid',        'required' => true],
        ];
    }

    private function loadExistingData(): void
    {
        $this->cacheTag = config('cache.tags.groups.constituency') . $this->constituency->constituency_code;
        $cacheKey = "group_history";

        $this->existingDBGroups = Cache::tags([$this->cacheTag])->get($cacheKey);

        if (!$this->existingDBGroups) {
            $history = Group::whereHas('ward', function($query) {
                $query->where('constituency_code', $this->constituency->constituency_code);
            })
            ->with([
                'ward:id,ward_name,constituency_code',
                'groupLoans' => function($query) {
                    $query->select('id', 'group_id', 'loan_phase_id', 'loan_type', 'loan_status')
                        ->whereIn('loan_status', ['financed', 'cancelled']);
                },
                'groupLoans.loanPhase:id,phase'
            ])
            ->get(['id', 'ward_id', 'name', 'code', 'identifier'])
            ->keyBy('code');

            if ($history->isNotEmpty()) {
                Cache::tags([$this->cacheTag])->put($cacheKey, $history, now()->addMinutes(2));
            }

            $this->existingDBGroups = $history;
        }
    }

    /**
     * Find group in cached data by code, name, identifier, or GFS suffix
     */
    private function findGroupInCache(string $groupCode = '', string $groupName = '', string $identifier = ''): ?object
    {
        $groupNameLower = strtolower(trim($groupName));
        $identifierLower = strtolower(trim($identifier));

        // Try to find by code first (most reliable)
        if (!empty($groupCode) && $this->existingDBGroups && isset($this->existingDBGroups[$groupCode])) {
            return $this->existingDBGroups[$groupCode];
        }

        // If not found by code, try by identifier (second most reliable)
        if (!empty($identifierLower) && $this->existingDBGroups) {
            foreach ($this->existingDBGroups as $group) {
                if (!empty($group->identifier) && strtolower(trim($group->identifier)) === $identifierLower) {
                    return $group;
                }
            }
        }

        // If not found by code or identifier, try by name (case-insensitive)
        if (!empty($identifierLower) && $this->existingDBGroups) {
            foreach ($this->existingDBGroups as $group) {
                if (!empty($group->identifier) && strtolower(trim($group->identifier)) === $identifierLower) {
                    return $group;
                }
            }
        }

        return null;
    }

    private function identifierExistsInDatabase(string $identifier, ?int $excludeGroupId = null): bool
    {
        if (empty($identifier) || !$this->existingDBGroups) {
            return false;
        }

        $identifierLower = strtolower(trim($identifier));

        foreach ($this->existingDBGroups as $group) {
            if ($excludeGroupId !== null && $group->id === $excludeGroupId) {
                continue;
            }

            if (!empty($group->identifier) && strtolower(trim($group->identifier)) === $identifierLower) {
                return true;
            }
        }

        return false;
    }

    private function groupCodeExistsInDatabase(string $groupCode, ?int $excludeGroupId = null): bool
    {
        if (empty($groupCode) || !$this->existingDBGroups) {
            return false;
        }

        // Direct lookup by code (faster)
        if (isset($this->existingDBGroups[$groupCode])) {
            $group = $this->existingDBGroups[$groupCode];

            // If we're excluding a specific group, check if this is that group
            if ($excludeGroupId !== null && $group->id === $excludeGroupId) {
                return false;
            }

            return true;
        }

        return false;
    }

    private function extractGroupCode(string $gfs): ?string
    {
        return GfsCode::groupSuffix($gfs);
    }

    private function checkForDuplicateGroupCodeInDoc(
        string $groupCode,
        int $rowNumber,
        string $fullGfs,
        $row,
        array $mapping
    ): void {
        $rowData = $row->map(fn($item) => strtolower($item));
        // If we've already seen this group code in the Excel file
        $currentGroupName = trim($rowData[2] ?? 'Unknown');
        $currentPhase = trim($rowData[4] ?? 'Unknown');
        $currentLoanType = trim($rowData[5] ?? 'Unknown');

        if (isset($this->docGroupCodes[$groupCode])) {
            $existing = $this->docGroupCodes[$groupCode];

            // Get current row data for comparison
            $existingGroupName = $existing['group_name'];
            $existingPhase = $existing['phase'];
            $existingLoanType = $existing['loan_type'];

            // Check if they are the same group (by name)
            $isSameGroup = $currentGroupName === $existingGroupName;
            if (!$isSameGroup) {
                // Different groups using same GFS code - this is a conflict
                $this->errors[] = "Row {$rowNumber}: GFS code suffix '{$groupCode}' is already used by "
                    . "a DIFFERENT group in row {$existing['rowNumber']}. "
                    . "\n  Current Group: '{$currentGroupName}' (Phase: {$currentPhase}, Loan Type: {$currentLoanType})"
                    . "\n  Existing Group: '{$existingGroupName}' (Phase: {$existingPhase}, Loan Type: {$existingLoanType})"
                    . "\n  Each group must have a unique GFS code suffix. Different groups cannot share the same GFS code.";
                return;
            }

            // Same group - now check phase rules
            $isSamePhase = $currentPhase === $existingPhase;
            if ($isSamePhase) {
                // Same group, same phase - cannot have multiple entries
                $this->errors[] = "Row {$rowNumber}: Group '{$currentGroupName}' already has an entry "
                    . "in the same '{$currentPhase}' at row {$existing['rowNumber']}. "
                    . "A group can only have ONE entry per phase.";
                return;
            }

            $primaryLoanTypes = collect($this->loan_types)->reject(fn($name) => $name === 'refinancing')->toArray();
            // dd(in_array($currentLoanType, $primaryLoanTypes), $currentLoanType, $primaryLoanTypes);
            if (in_array($currentLoanType, $primaryLoanTypes)) {
                // dd(in_array($currentLoanType, $primaryLoanTypes), $currentLoanType, $currentPhase, $primaryLoanTypes);
                foreach ($this->docGroupCodes as $code => $data) {
                    if (in_array($existingLoanType, $primaryLoanTypes)) {
                        $this->errors[] = "Row {$rowNumber}: Group '{$currentGroupName}' already has a "
                            . "'{$existingLoanType}' loan in '{$existingPhase}' (row {$existing['rowNumber']}). "
                            . "A group can only have ONE loan application of type New, Resubmission, or Reallocation. "
                            . "Multiple entries are only allowed for Refinancing.";
                        return;
                    }
                }
            }

            // if ($currentLoanType === 'refinancing') {
            //     $hasPrimaryLoan = false;

            //     // Check if there's a primary loan type (New/Resubmission/Reallocation) in an earlier phase
            //     foreach ($this->docGroupCodes as $code => $data) {
            //         if (in_array($existingLoanType, $primaryLoanTypes)) {
            //             $hasPrimaryLoan = true;
            //             break;
            //         }
            //     }

            //     if (!$hasPrimaryLoan) {
            //         $this->errors[] = "Row {$rowNumber}: Group '{$currentGroupName}' has a 'Refinancing' loan type, "
            //             . "but no primary loan (New/Resubmission/Reallocation) found in earlier phases. "
            //             . "Refinancing must be preceded by a New, Resubmission, or Reallocation loan.";
            //         return;
            //     }
            // }
        }

        // Store the group code for future duplicate checking
        $this->docGroupCodes[$groupCode] = [
            'row' => $rowData,
            'rowNumber' => $rowNumber,
            'gfs' => $fullGfs,
            'group_name' => trim($rowData[2] ?? ''),
            'phase' => trim($rowData[4] ?? ''),
            'loan_type' => trim($rowData[5] ?? '')
        ];

        $this->docEntries[] = [
            'rowNumber' => $rowNumber,
            'gfs_full' => $fullGfs,
            'group_code' => $groupCode,
            'group_name' => $currentGroupName,
            'phase' => $currentPhase,
            'loan_type' => $currentLoanType,
            'identifier' => trim($row[7] ?? ''),
            'ward' => trim($rowData[3] ?? ''),
            'date' => trim($row[1] ?? ''),
            'category' => trim($rowData[8] ?? ''),
            'sector' => trim($rowData[9] ?? ''),
            'amount_approved' => trim($row[10] ?? ''),
            'amount_issued' => trim($row[11] ?? ''),
            'amount_repaid' => trim($row[12] ?? '')
        ];
    }

    private function validateIdentifier(
        string $identifier,
        int $rowNumber,
        string $groupName,
        string $gfs
    ) : void
    {
        $groupCode = $this->extractGroupCode($gfs);

        if (isset($this->docIdentifiers[$identifier])){
            $existing = $this->docIdentifiers[$identifier];
            $existingGroupName = $existing['group_name'];
            $existingGroupCode = $existing['group_code'];

            $isSameGroupCode = $groupCode === $existingGroupCode;
            if (!$isSameGroupCode) {
                $this->errors[] = "Row {$rowNumber}: Idetifier '{$identifier}' is already used by "
                    . "a DIFFERENT group in row {$existing['rowNumber']}. "
                    . "\n  Current Group: '{$groupName}' (Identifier: {$identifier})"
                    . "\n  Existing Group: '{$existingGroupName}' (Identifier: {$identifier})"
                    . "\n  Each group must have a unique Idetifier. Different groups cannot share the same Idetifier code.";
                return;
            }
        }

        $this->docIdentifiers[$identifier] = [
            'group_name' => $groupName ?? '',
            'rowNumber' => $rowNumber,
            'group_code' => $groupCode,
        ];
    }

    private function validateConsistentGfsCodeForGroupName(): void
    {
        if (empty($this->docEntries)) {
            return;
        }

        $namesToCodes = [];

        foreach ($this->docEntries as $entry) {
            $nameKey = strtolower(trim($entry['group_name'] ?? ''));
            $groupCode = trim($entry['group_code'] ?? '');

            if ($nameKey === '' || $groupCode === '') {
                continue;
            }

            if (!isset($namesToCodes[$nameKey])) {
                $namesToCodes[$nameKey] = [
                    'group_name' => $entry['group_name'],
                    'codes' => [],
                ];
            }

            $namesToCodes[$nameKey]['codes'][$groupCode][] = $entry['rowNumber'];
        }

        foreach ($namesToCodes as $data) {
            if (count($data['codes']) <= 1) {
                continue;
            }

            $details = [];
            foreach ($data['codes'] as $code => $rows) {
                $details[] = "GFS suffix '{$code}' (rows: " . implode(', ', $rows) . ')';
            }

            $this->errors[] = "Group '{$data['group_name']}' appears with multiple GFS code suffixes in this document. "
                . 'All rows for the same group must use the same GFS code. Found: '
                . implode('; ', $details)
                . '. Example: Phase 5 refinancing must keep the same GFS suffix as Phase 1.';
        }
    }

    private function validateAgainstDatabaseAfterDoc(): void
    {
        if (empty($this->docEntries)) {
            $this->errors[] = "Your Document is Empty";
            return;
        }

        $groupedByGroup = [];

        foreach ($this->docEntries as $entry) {
            $groupKey = strtolower(trim($entry['group_code']));
            if (!isset($groupedByGroup[$groupKey])) {
               $groupedByGroup[$groupKey] = [
                    'group_name' => $entry['group_name'],
                    'identifier' => $entry['identifier'] ?? '',
                    'entries' => []
                ];
            }
            $groupedByGroup[$groupKey]['entries'][] = $entry;
        }



        foreach ($groupedByGroup as $groupCode => $groupData) {
            if (count($this->errors) >= 20) return;

            $entries = $groupData['entries'];
            $groupName = $groupData['group_name'];
            $identifier = $groupData['identifier'];

            // verify group names match
            $this->isSameGroupNameInEntry($entries, $groupCode);

            // verify identifiers match
            $this->isSameIdentifierInEntry($entries, $groupCode);

            $isNewGroup = !$this->groupCodeExistsInDatabase($groupCode);

            if ($isNewGroup) {
                // if new then first check for valid identifier

                if (!empty($identifier) && $this->identifierExistsInDatabase($identifier)) {
                    $rows = implode(', ', array_column($entries, 'rowNumber'));
                    $this->errors[] = "Group '{$groupName}' (Code: {$groupCode}) has identifier '{$identifier}' "
                        . "that already exists in database. Identifiers must be unique within a constituency.\n"
                        . "Affected rows: {$rows}";
                    continue;
                }

                // then this means if it has a loan status of refinancing
                $this->validateNewGroupEntries($entries, $groupName, $groupCode);

            } else {
                $existingGroup = $this->findGroupInCache($groupCode, $groupName, $identifier);

                if ($existingGroup) {
                    $this->validateExistingGroupEntries(
                        $entries,
                        $groupName,
                        $groupCode,
                        $existingGroup
                    );
                } else {
                    dump("WARNING: Group code {$groupCode} exists but findGroupInCache returned null");
                    continue;
                }
            }

        }
    }

    private function isSameGroupNameInEntry(array $entries, string $groupCode): void
    {
        if (empty($entries)) return;

        $expectedName = strtolower(trim($entries[0]['group_name'] ?? ''));
        $mismatchedRows = [];

        foreach ($entries as $entry) {
            $entryGroupName = strtolower(trim($entry['group_name'] ?? ''));
            $rowNumber = $entry['rowNumber'];

            if ($entryGroupName !== $expectedName) {
                $mismatchedRows[] = "Row {$rowNumber}: '{$entry['group_name']}'";
            }
        }

        if (!empty($mismatchedRows)) {
            $this->errors[] = "Group Code '{$groupCode}' has inconsistent group names. "
                . "Expected: '{$entries[0]['group_name']}'. "
                . "Found mismatches: " . implode(', ', $mismatchedRows)
                . "All entries with the same group code must have the same group name. ";

            return;
        }
    }

    private function isSameIdentifierInEntry(array $entries, string $groupCode): void
    {
        $identifiers = [];
        $identifierToRows = [];
        $rowsWithIdentifier = [];
        $rowsWithoutIdentifier = [];

        foreach ($entries as $entry) {
            $identifier = strtolower(trim($entry['identifier'] ?? ''));
            $rowNumber = $entry['rowNumber'];
            $originalIdentifier = $entry['identifier'] ?? '';

            if (!empty($identifier)) {
                $identifiers[$identifier] = ($identifiers[$identifier] ?? 0) + 1;

                // Store row numbers for each identifier
                if (!isset($identifierToRows[$identifier])) {
                    $identifierToRows[$identifier] = [
                        'rows' => [],
                        'original' => $originalIdentifier
                    ];
                }
                $identifierToRows[$identifier]['rows'][] = $rowNumber;

                $rowsWithIdentifier[] = $rowNumber;
            } else {
                $rowsWithoutIdentifier[] = $rowNumber;
            }
        }

        // Case 1: Mixed presence - some rows have identifier, some don't
        if (!empty($rowsWithIdentifier) && !empty($rowsWithoutIdentifier)) {
            $this->errors[] = "Group Code '{$groupCode}' has inconsistent identifier presence.\n"
                . "  Rows WITH identifier: " . implode(', ', $rowsWithIdentifier) . "\n"
                . "  Rows WITHOUT identifier: " . implode(', ', $rowsWithoutIdentifier) . "\n"
                . "Either all entries for a group must have an identifier, or none should.";
            return;
        }

        // Case 2: Multiple different identifiers found
        if (count($identifiers) > 1) {
            $identifierDetails = [];
            foreach ($identifierToRows as $identifier => $data) {
                $rows = implode(', ', $data['rows']);
                $identifierDetails[] = "'{$data['original']}' (rows: {$rows})";
            }

            // Determine the expected identifier (most frequent or first occurrence)
            $expectedIdentifier = array_key_first($identifiers);
            $expectedOriginal = $identifierToRows[$expectedIdentifier]['original'];
            $expectedRows = implode(', ', $identifierToRows[$expectedIdentifier]['rows']);

            $errorMessage = "Group Code '{$groupCode}' has inconsistent identifiers across rows.\n";
            $errorMessage .= "Expected identifier: '{$expectedOriginal}' (rows: {$expectedRows})\n";
            $errorMessage .= "Found variations:\n";

            foreach ($identifierDetails as $index => $detail) {
                if ($index > 0) {
                    $errorMessage .= "  - {$detail}\n";
                }
            }

            $this->errors[] = $errorMessage;
            return;
        }
    }

    private function validateExistingGroupEntries(
        array $entries,
        string $groupName,
        string $groupCode,
        object $existingGroup
    ): void {

        $primaryLoanTypes = collect($this->loan_types)->reject(fn($name) => $name === 'refinancing')->toArray();
        $refinancingType = 'refinancing';

        $primaryEntries = [];
        $refinancingEntries = [];

        // Get ALL existing loans from database for this group
        $dbPrimaryLoans = [];
        $dbRefinancingLoans = [];
        $allDbPhases = [];

        foreach ($existingGroup->groupLoans as $loan) {
            $loanTypeLower = strtolower(trim($loan->loan_type)) ;
            $loanPhase = strtolower(trim($loan->loanPhase->phase) ?? 'Unknown');

            $loanData = [
                'phase' => $loanPhase,
                'loan_type' => $loan->loan_type,
                'gfs_code' => $loan->gfs_code,
                'id' => $loan->id
            ];

            $allDbPhases[] = $loanPhase;
            if ($loan->loan_status !== 'cancelled') {
                if (in_array($loanTypeLower, $primaryLoanTypes)) {
                    $dbPrimaryLoans[] = $loanData;
                } elseif ($loanTypeLower === $refinancingType) {
                    $dbRefinancingLoans[] = $loanData;
                }
            }

        }

        foreach ($entries as $entry) {
            $loanType = strtolower(trim($entry['loan_type']));

            if (in_array($loanType, $primaryLoanTypes)) {
                $primaryEntries[] = $entry;
            } elseif ($loanType === $refinancingType) {
                $refinancingEntries[] = $entry;
            }
        }

        // RULE 1: Existing group CANNOT have any primary loan types in the document
        if (!empty($primaryEntries) && !empty($dbPrimaryLoans)) {
            foreach ($primaryEntries as $entry) {
                $this->errors[] = "Row {$entry['rowNumber']}: Cannot add primary loan type '{$entry['loan_type']}' for existing group";
            }

            $this->errors[] = "Group '{$groupName}' (Code: {$groupCode}) already exists in database. "
                . "An existing group cannot have a loan of type New, Resubmission, or Reallocation. "
                . "Only Refinancing loans can be added for existing groups.";

            return;
        }

        // RULE 2: Check if database has any primary loans (it should, for refinancing to be valid)
        if (empty($dbPrimaryLoans) && !empty($refinancingEntries)) {
            foreach ($refinancingEntries as $entry) {
                $this->errors[] = "Row {$entry['rowNumber']}: Refinancing loan requires a primary loan in database";
            }
            $refinancingRows = implode(', ', array_column($refinancingEntries, 'rowNumber'));
            $this->errors[] = "Group '{$groupName}' (Code: {$groupCode}) has refinancing entries in document "
                . "(rows: {$refinancingRows}) but no primary loan found in database. "
                . "Refinancing must be preceded by a financed primary loan (New/Resubmission/Reallocation).";
            return;
        }

        // RULE 3: Check phase uniqueness across database AND document for refinancing entries
        $usedPhases = $allDbPhases; // Start with all phases from database
        // dd($primaryEntries,$refinancingEntries,$allDbPhases);

        foreach ($primaryEntries as $newEntry){
            $newPhase = $newEntry['phase'];
            $newRow = $newEntry['rowNumber'];

            if (in_array($newPhase, $allDbPhases)) {
                $this->errors[] = "Row {$newRow}: Group '{$groupName}' already has a loan "
                    . "in '{$newPhase}' in the database. "
                    . "A group can only have ONE entry per phase.";
                continue;
            }
        }
        foreach ($refinancingEntries as $refinancingEntry) {

            $refinancingPhase = $refinancingEntry['phase'];
            $refinancingRow = $refinancingEntry['rowNumber'];

            // Check if phase already exists in database
            if (in_array($refinancingPhase, $allDbPhases)) {
                $this->errors[] = "Row {$refinancingRow}: Group '{$groupName}' already has a loan "
                    . "in '{$refinancingPhase}' in the database. "
                    . "A group can only have ONE entry per phase.";
                continue;
            }

            // Check for duplicate phases within document
            if (in_array($refinancingPhase, $usedPhases)) {
                $this->errors[] = "Row {$refinancingRow}: Group '{$groupName}' already has an entry "
                    . "in Phase '{$refinancingPhase}' in this document. "
                    . "A group can only have ONE entry per phase.";
                continue;
            }

            $usedPhases[] = $refinancingPhase;
        }

        // RULE 4: Check that refinancing phases are AFTER the latest primary loan in database
        // if (!empty($refinancingEntries) && !empty($dbPrimaryLoans)) {
        //     $latestPrimaryPhase = $this->getLatestPhase($dbPrimaryLoans);
        //     $latestPrimaryIndex = $this->orderedPhases[strtolower(trim($latestPrimaryPhase))] ?? -1;

        //     foreach ($refinancingEntries as $refinancingEntry) {
        //         $refinancingPhase = $refinancingEntry['phase'];
        //         $refinancingRow = $refinancingEntry['rowNumber'];
        //         $refinancingIndex = $this->orderedPhases[strtolower(trim($refinancingPhase))] ?? -1;

        //         if ($refinancingIndex <= $latestPrimaryIndex) {
        //             $this->errors[] = "Row {$refinancingRow}: Refinancing for Group '{$groupName}' "
        //                 . "is in Phase '{$refinancingPhase}', but the latest primary loan in database "
        //                 . "is in Phase '{$latestPrimaryPhase}'. Refinancing must be in a later phase.";
        //         }
        //     }
        // }

        // RULE 5: Check that refinancing entries are in chronological order
        // if (count($refinancingEntries) > 1) {
        //     $previousPhase = null;
        //     $previousIndex = -1;

        //     // Sort refinancing entries by phase order
        //     usort($refinancingEntries, function($a, $b) {
        //         $indexA = $this->orderedPhases[strtolower(trim($a['phase']))] ?? 0;
        //         $indexB = $this->orderedPhases[strtolower(trim($b['phase']))] ?? 0;
        //         return $indexA <=> $indexB;
        //     });

        //     foreach ($refinancingEntries as $refinancingEntry) {
        //         $currentPhase = $refinancingEntry['phase'];
        //         $currentRow = $refinancingEntry['rowNumber'];
        //         $currentIndex = $this->orderedPhases[strtolower(trim($currentPhase))] ?? 0;

        //         if ($previousPhase !== null && $currentIndex <= $previousIndex) {
        //             $this->errors[] = "Row {$currentRow}: Refinancing entries must be in chronological order. "
        //                 . "Phase '{$currentPhase}' appears after or same as Phase '{$previousPhase}'.";
        //         }

        //         $previousPhase = $currentPhase;
        //         $previousIndex = $currentIndex;
        //     }
        // }

    }

    private function validateNewGroupEntries(array $entries, string $groupName, string $groupCode): void
    {
        $primaryLoanTypes = collect($this->loan_types)->reject(fn($name) => $name === 'refinancing')->toArray();
        $refinancingType = 'refinancing';

        $primaryEntries = [];
        $refinancingEntries = [];


        foreach ($entries as $entry) {
            $loanType = strtolower(trim($entry['loan_type']));

            if (in_array($loanType, $primaryLoanTypes)) {
                $primaryEntries[] = $entry;
            } elseif ($loanType === $refinancingType) {
                $refinancingEntries[] = $entry;
            }
        }

        if (empty($primaryEntries)) {
            foreach ($entries as $entry) {
                $this->errors[] = "Row {$entry['rowNumber']}: Cannot add Refinancing without a prior existing group.";
            }
            $this->errors[] = "Group '{$groupName}' (Code: {$groupCode}) is a seen as a new group but has no primary loan type in the system. "
                . "A new group must have exactly ONE prior loan of type New, Resubmission, or Reallocation. ";
            return;
        }

        if (count($primaryEntries) > 1 ) {
            foreach ($primaryEntries as $entry) {
                $this->errors[] = "Row {$entry['rowNumber']}: Cannot have Multiple entries for primary loan type.";
            }
            $primaryRows = implode(', ', array_column($primaryEntries, 'rowNumber'));
            $this->errors[] = "Group '{$groupName}' (Code: {$groupCode}) has multiple primary loan types. "
                . "A new group can only have ONE loan of type New, Resubmission, or Reallocation. "
                . "Found primary loans in row(s): {$primaryRows}";
            return;
        }

        $primaryEntry = $primaryEntries[0];
        $primaryPhase = $primaryEntry['phase'];
        $primaryLoanType = $primaryEntry['loan_type'];

        $usedPhases = [$primaryPhase];
        $refinancingErrors = [];

        foreach ($refinancingEntries as $refinancingEntry) {
            $refinancingPhase = $refinancingEntry['phase'];
            $refinancingRow = $refinancingEntry['rowNumber'];

            // Check if phase is after primary loan
            // if ($this->isPhaseEarlier($primaryPhase, $refinancingPhase)) {
            //     $refinancingErrors[] = "Row {$refinancingRow}: Refinancing in phase '{$refinancingPhase}' "
            //         . "must be AFTER the primary loan phase '{$primaryPhase}'";
            // }

            // Check for duplicate phases
            if (in_array($refinancingPhase, $usedPhases)) {
                $refinancingErrors[] = "Row {$refinancingRow}: Group already has an entry in phase '{$refinancingPhase}'";
            }

            $usedPhases[] = $refinancingPhase;
        }

        if (count($refinancingErrors) > 0){
            foreach ($refinancingErrors as $error) {
                $this->errors[] = $error;
            }
            return;
        }
    }

    private function findOrCreateGroup(string $groupName, string $groupCode, ?string $identifier, array $sampleEntry): ?Group
    {
        // First try to find by group code
        $group = Group::where('code', $groupCode)
            ->whereHas('ward', function($query) {
                $query->where('constituency_code', $this->constituency->constituency_code);
            })
            ->first();

        if ($group) {
            return $group;
        }

        // Create new group
        try {
            // Find ward ID from the entry
            $wardName = $sampleEntry['ward'] ?? null;
            $ward = Wards::where('constituency_code', $this->constituency->constituency_code)
                ->whereRaw('LOWER(ward_name) = ?', [strtolower($wardName)])
                ->first();

            if (!$ward) {
                $this->errors[] = "Row {$sampleEntry['rowNumber']}: Ward '{$wardName}' not found for constituency";
                return null;
            }



            $group = Group::create([
                'name' => strtoupper($groupName),
                'code' => $groupCode,
                'identifier' => strtoupper($identifier),
                'ward_id' => $ward->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            if ($group) {
                $this->importedNewGroupsCount++;
            }

            return $group;
        } catch (\Exception $e) {
            $this->errors[] = "Row {$sampleEntry['rowNumber']}: Failed to create group '{$groupName}': " . $e->getMessage();
            return null;
        }
    }

    /**
     * Parse and validate date from entry
     *
     * @param array $entry The entry containing date
     * @return string|null Formatted date string or null on failure
     */
    private function parseAndValidateDate(array $entry): ?string
    {
        $rowNumber = $entry['rowNumber'] ?? '?';

        if (empty($entry['date'])) {
            $this->errors[] = "Row {$rowNumber}: Disbursement date is required";
            return null;
        }

        $dateValue = $entry['date'];

        try {
            $carbonDate = null;

            // Case 1: Check if it's an Excel serial number (numeric)
            if (is_numeric($dateValue) && $dateValue > 0) {
                // Excel dates start from January 1, 1900
                // Unix timestamp starts from January 1, 1970
                // Excel serial number 1 = 1900-01-01
                // Excel has a bug where it thinks 1900 is a leap year, so we adjust

                $unixTimestamp = ($dateValue - 25569) * 86400; // 25569 = days between 1900-01-01 and 1970-01-01
                $carbonDate = Carbon::createFromTimestamp($unixTimestamp);

                // Validate it's a real calendar date
                if (!checkdate($carbonDate->month, $carbonDate->day, $carbonDate->year)) {
                    $this->errors[] = "Row {$rowNumber}: Invalid Excel date serial '{$dateValue}' - not a real calendar date";
                    return null;
                }

                // Check if date is within valid range (2013-01-01 to today)
                $minDate = Carbon::createFromDate(2013, 1, 1);
                $maxDate = Carbon::today();

                if ($carbonDate->lt($minDate) || $carbonDate->gt($maxDate)) {
                    $this->errors[] = "Row {$rowNumber}: Date '{$carbonDate->format('Y-m-d')}' must be between 2013-01-01 and today";
                    return null;
                }

                // Format to YYYY-MM-DD for database storage
                return $carbonDate->format('Y-m-d');
            }

            // Case 2: Try common string formats
            $formats = ['Y-m-d', 'd-m-Y', 'd/m/Y', 'Y/m/d', 'd.m.Y', 'Y.m.d', 'm/d/Y', 'Ymd'];
            $parsed = false;

            foreach ($formats as $format) {
                try {
                    $carbonDate = Carbon::createFromFormat($format, $dateValue);
                    $parsed = true;
                    break;
                } catch (\Exception $e) {
                    continue;
                }
            }

            if (!$parsed) {
                // Try Carbon's flexible parser as fallback
                $carbonDate = Carbon::parse($dateValue);
            }

            // Validate it's a real calendar date
            if (!checkdate($carbonDate->month, $carbonDate->day, $carbonDate->year)) {
                $this->errors[] = "Row {$rowNumber}: Invalid date '{$dateValue}' - not a real calendar date";
                return null;
            }

            // Check if date is within valid range (2013-01-01 to today)
            $minDate = Carbon::createFromDate(2013, 1, 1);
            $maxDate = Carbon::today();

            if ($carbonDate->lt($minDate) || $carbonDate->gt($maxDate)) {
                $this->errors[] = "Row {$rowNumber}: Date '{$carbonDate->format('Y-m-d')}' must be between 2013-01-01 and today";
                return null;
            }

            // Format to YYYY-MM-DD for database storage
            return $carbonDate->format('Y-m-d');

        } catch (\Exception $e) {
            $this->errors[] = "Row {$rowNumber}: Invalid date format '{$dateValue}'. " .
                "Expected formats: YYYY-MM-DD, DD-MM-YYYY, or Excel serial number (e.g., 45686 = 2025-01-29)";
            return null;
        }
    }

    /**
     * Create a group loan entry
     */
    private function createGroupLoan(Group $group, array $entry): bool
    {
        try {
            // Find or create loan phase
            $phaseName = $entry['phase'];


            $loanPhase = LoanPhase::where('constituency_code', $this->constituency->constituency_code)
                ->whereRaw('LOWER(phase) = ?', [strtolower($phaseName)])
                ->first();

            if (!$loanPhase) {
                // Phase doesn't exist - this is an error
                $this->errors[] = "Row {$entry['rowNumber']}: Phase '{$phaseName}' not found for constituency. "
                    . "Please ensure the phase is created before importing.";
                return false;
            }

            // Parse date
            $disbursementDate = $this->parseAndValidateDate($entry);
            if ($disbursementDate === null) {
                $this->errors[] = "Row {$entry['rowNumber']}: Date of disbursement '{$entry['date']}' is invalid. "
                    . "Please ensure the date of disbursement in is the correct format (YYYY/MM/DD) before importing.";
                return false;
            }

            $amountIssued = max((int) str_replace(',', '', $entry['amount_issued'] ?? 0), 0);

            if ($amountIssued === 0) $disbursementDate = null;

            // Create the loan
            $groupLoan = GroupLoan::create([
                'loan_phase_id' => $loanPhase->id,
                'group_id' => $group->id,
                'gfs_code' => $entry['gfs_full'],
                'category' => ucfirst($entry['category'] ?? ''),
                'sector' => ucfirst($entry['sector'] ?? ''),
                'loan_type' => ucfirst($entry['loan_type']),
                'status' => $amountIssued > 0? 'active' : 'cancelled',
                'loan_status' => $amountIssued > 0 ? 'financed' : 'cancelled',
                'disbursement_date' => $disbursementDate,
                'amount_approved' => (int) str_replace(',', '', $entry['amount_approved'] ?? 0),
                'amount_issued' => $amountIssued,
                'amount_repaid' => $amountIssued > 0 ? (int) str_replace(',', '', $entry['amount_repaid'] ?? 0) : 0,
                'loan_level' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $this->affectedPhaseIds[$loanPhase->id] = true;

            return true;

        } catch (\Exception $e) {
            $this->errors[] = "Row {$entry['rowNumber']}: Failed to create loan for group '{$group->name}': " . $e->getMessage();
            return false;
        }
    }

    private function storeInDb()
    {
        if (empty($this->docEntries)) {
            $this->importResult = [
                'success' => true,
                'imported_count' => 0,
                'message' => 'No valid entries to import',
                'successful' => [],
                'failed' => []
            ];
            return;
        }

        DB::beginTransaction();

        try {
            $this->importedCount = 0;
            $successfulImports = [];
            $failedImports = [];
            $groupedByCode = [];

            foreach ($this->docEntries as $entry) {
                $groupCode = strtolower(trim($entry['group_code']));
                if (!isset($groupedByCode[$groupCode])) {
                    $groupedByCode[$groupCode] = [
                        'group_name' => $entry['group_name'],
                        'identifier' => $entry['identifier'] ?? null,
                        'entries' => []
                    ];
                }
                $groupedByCode[$groupCode]['entries'][] = $entry;
            }

            foreach ($groupedByCode as $groupCode => $groupData) {
                $groupName = $groupData['group_name'];
                $identifier = $groupData['identifier'];
                $entries = $groupData['entries'];

                // Find or create the group
                $group = $this->findOrCreateGroup($groupName, $groupCode, $identifier, $entries[0]);

                if (!$group) {
                    // Error already added in findOrCreateGroup
                    DB::rollBack();
                    return;
                }

                // Process each loan entry for this group
                foreach ($entries as $entry) {
                    $success = $this->createGroupLoan($group, $entry);

                    if ($success) {
                        $this->importedCount++;
                        $successfulImports[] = "Row {$entry['rowNumber']}: Imported {$entry['loan_type']} loan for {$groupName}";
                    } else {
                        $this->errors[] = "Row {$entry['rowNumber']}: Failed to import {$entry['loan_type']} loan for {$groupName}";
                        DB::rollBack();
                        return;
                    }
                }

            }
            DB::commit();

             DB::afterCommit(function () {
                Cache::tags([$this->cacheTag])->flush();

                foreach (array_keys($this->affectedPhaseIds) as $phaseId) {
                    LoanPhaseFinancialTotals::refresh($phaseId);
                }

                app(LoanPhaseService::class)->invalidatePhasesCache($this->constituency->constituency_code);

                UpdateLoanDefaultStatusForConstituency::dispatch(
                    $this->constituency->constituency_code,
                    true
                )->onQueue('financial-updates');
            });


        } catch (\Exception $e) {
            DB::rollBack();

            // Add error message
            $this->errors[] = "Database import failed: " . $e->getMessage();

            // Re-throw to be caught in registerEvents
            throw $e;
        }
    }

    public function getImportResult(): array
    {
        $groupWord = Str::plural('group', $this->importedNewGroupsCount);
        $loanWord = Str::plural('entry', $this->importedCount);

        return [
            'success' => $this->importSuccess && empty($this->errors),
            'imported_count' => $this->importedCount,
            'imported_new_groups_count' =>$this->importedNewGroupsCount,
            'errors' => $this->errors,
            'message' => $this->importSuccess
                ? "Successfully imported {$this->importedCount} {$loanWord} with {$this->importedNewGroupsCount} new {$groupWord}."
                : "Import failed with " . count($this->errors) . " error(s)"
        ];
    }
    // public function onFailure(Failure ...$failures)
    // {
    //     foreach ($failures as $failure) {
    //     $rowNum = $failure->row();
    //         foreach ($failure->errors() as $error) {
    //             $this->errors[] = "Row {$rowNum}: {$error} (column: {$failure->attribute()})";
    //         }
    //     }
    // }
}
