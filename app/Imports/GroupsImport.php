<?php
// app/Imports/GroupsImport.php

namespace App\Imports;

use App\Models\Group;
use App\Models\Wards;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GroupsImport implements ToCollection, WithChunkReading, WithBatchInserts
{
    private $constituencyCode;
    private $importedCount = 0;
    private $skippedCount = 0;
    private $errors = [];
    private $totalRows = 0;
    private $processedRows = 0;
    private $wardsCache = [];

    // Cache existing codes to avoid repeated queries
    private $existingGroupCodes = [];
    private $existingIdentifiers = [];
    private $existingRegNumbers = [];
    private $existingGfsCodes = [];

    public function __construct($constituencyCode)
    {
        $this->constituencyCode = $constituencyCode;
        $this->loadWardsCache();
        $this->loadExistingDataCache(); // Pre-load existing data
    }

    /**
     * Pre-load all existing data for this constituency to avoid N+1 queries
     */
    private function loadExistingDataCache()
    {
        $existingGroups = Group::whereHas('ward.constituency', function($query) {
            $query->where('constituency_code', $this->constituencyCode);
        })->get(['code', 'identifier', 'reg_number', 'gfs_code']);

        foreach ($existingGroups as $group) {
            // Normalize all values to lowercase for case-insensitive comparison
            if (!empty($group->code)) {
                $this->existingGroupCodes[strtolower(trim($group->code))] = true;
            }
            if (!empty($group->identifier)) {
                $this->existingIdentifiers[strtolower(trim($group->identifier))] = true;
            }
            if (!empty($group->reg_number)) {
                $this->existingRegNumbers[strtolower(trim($group->reg_number))] = true;
            }
            if (!empty($group->gfs_code)) {
                $this->existingGfsCodes[strtolower(trim($group->gfs_code))] = true;
            }
        }
    }

    /**
     * Process in chunks to reduce memory usage
     */
    public function chunkSize(): int
    {
        return 500; // Process 500 rows at a time
    }

    /**
     * Insert in batches for better performance
     */
    public function batchSize(): int
    {
        return 100; // Insert 100 records at a time
    }

    public function collection(Collection $rows)
    {
        $this->totalRows = $rows->count() - 1; // Exclude header
        $this->processedRows = 0;

        // Validate column headers first
        if (!$this->validateHeaders($rows->first())) {
            throw new \Exception('Invalid Excel template. Please download the correct template format.');
        }

        $this->updateProgress(5, 'Starting groups import...');

        // First pass: Validate all rows (optimized)
        $validationErrors = $this->validateAllRowsOptimized($rows);
        //dd($validationErrors);
        if (!empty($validationErrors)) {
            $errorMessages = implode("\n", array_slice($validationErrors, 0, 20)); // Show first 20 errors
            if (count($validationErrors) > 20) {
                $errorMessages .= "\n... and " . (count($validationErrors) - 20) . " more errors";
            }
            throw new \Exception("Validation errors found:\n" . $errorMessages);
        }

        $this->updateProgress(20, 'All data validated. Creating groups...');

        // Batch insert collection
        $groupsToInsert = [];

        foreach ($rows as $index => $row) {
            if ($index === 0) continue;

            $this->processedRows++;

            // Update progress less frequently for better performance
            if ($this->processedRows % 50 === 0 || $this->processedRows === 1 || $this->processedRows === $this->totalRows) {
                $progress = min(90, 20 + (($this->processedRows / $this->totalRows) * 70));
                $this->updateProgress(
                    (int) $progress,
                    "Processing now {$this->processedRows} of {$this->totalRows} rows..."
                );
            }

            // Extract row data
            $sno = $row[0] ?? null;
            $wardName = $row[1] ?? null;
            $groupName = $row[2] ?? null;
            $groupCode = $row[3] ?? null;
            $gfsCode = $row[4] ?? null;
            $identifier = $row[5] ?? null;
            $regNumber = $row[6] ?? null;
            $category = $row[7] ?? null;
            $activity = $row[8] ?? null;

            // Get ward ID
            $wardId = $this->getWardId($wardName);

            if ($wardId === null) {
                $this->skippedCount++;
                continue;
            }


            // Normalize values for duplicate checking
            $normalizedGroupCode = !empty($groupCode) ? strtolower(trim($groupCode)) : null;
            $normalizedIdentifier = !empty($identifier) ? strtolower(trim($identifier)) : null;
            $normalizedRegNumber = !empty($regNumber) ? strtolower(trim($regNumber)) : null;
            $normalizedGfsCode = !empty($gfsCode) ? strtolower(trim($gfsCode)) : null;

            $hasDuplicate = false;
            $duplicateReason = '';

            if ($normalizedGroupCode && isset($this->existingGroupCodes[$normalizedGroupCode])) {
                $hasDuplicate = true;
                $duplicateReason = "Group code '{$groupCode}' already exists";
            } elseif ($normalizedIdentifier && isset($this->existingIdentifiers[$normalizedIdentifier])) {
                $hasDuplicate = true;
                $duplicateReason = "Group Identifier '{$identifier}' already exists";
            } elseif ($normalizedRegNumber && isset($this->existingRegNumbers[$normalizedRegNumber])) {
                $hasDuplicate = true;
                $duplicateReason = "Registration number '{$regNumber}' already exists";
            } elseif ($normalizedGfsCode && isset($this->existingGfsCodes[$normalizedGfsCode])) {
                $hasDuplicate = true;
                $duplicateReason = "GFS code '{$gfsCode}' already exists";
            }

            if ($hasDuplicate) {
                $this->skippedCount++;
                // Log the duplicate for debugging
                Log::info("Skipped duplicate group: {$duplicateReason}", [
                    'group_code' => $groupCode,
                    'identifier' => $identifier,
                    'reg_number' => $regNumber,
                    'gfs_code' => $gfsCode
                ]);
                continue;
            }

            // Add to batch insert
            $groupsToInsert[] = [
                'ward_id' => $wardId,
                'name' => $groupName,
                'slug' => Str::slug($groupName . '-' . uniqid()),
                'identifier' => $identifier,
                'code' => $groupCode,
                'gfs_code' => $gfsCode,
                'reg_number' => $regNumber,
                'date_formed' => $this->parseDate($row[9] ?? null),
                'date_registered' => $this->parseDate($row[10] ?? null),
                'bank' => $row[11] ?? null,
                'account' => $row[12] ?? null,
                'category' => $category,
                'activity' => $activity,
                'activity_desc' => $row[13] ?? null,
                'location' => $row[14] ?? null,
                'box' => null,
                'status' => 'Active',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Add to cache to prevent duplicates in same import
            if ($normalizedGroupCode) {
                $this->existingGroupCodes[$normalizedGroupCode] = true;
            }
            if ($normalizedIdentifier) {
                $this->existingIdentifiers[$normalizedIdentifier] = true;
            }
            if ($normalizedRegNumber) {
                $this->existingRegNumbers[$normalizedRegNumber] = true;
            }
            if ($normalizedGfsCode) {
                $this->existingGfsCodes[$normalizedGfsCode] = true;
            }

            $this->importedCount++;

            // Batch insert when reach batch size
            if (count($groupsToInsert) >= $this->batchSize()) {
                Group::insert($groupsToInsert);
                $groupsToInsert = []; // Reset
            }
        }

        // Insert any remaining records
        if (!empty($groupsToInsert)) {
            Group::insert($groupsToInsert);
        }

        $this->updateProgress(95, 'Finalizing import...');
        $this->clearProgress();

        if ($this->importedCount === 0) {
            throw new \Exception('No new groups were imported. All groups already exist or data was invalid.');
        }
    }

    /**
     * Optimized validation without duplicate database queries
     */
    private function validateAllRowsOptimized(Collection $rows)
    {
        $errors = [];
        $seenInFile = []; // Track duplicates within the file itself

        foreach ($rows as $index => $row) {
            if ($index === 0) continue;

            $rowNumber = $index + 1;
            $sno = $row[0] ?? null;
            $wardName = $row[1] ?? null;
            $groupName = $row[2] ?? null;
            $groupCode = $row[3] ?? null;
            $gfsCode = $row[4] ?? null;
            $identifier = $row[5] ?? null;
            $regNumber = $row[6] ?? null;
            $category = $row[7] ?? null;
            $activity = $row[8] ?? null;
            $dateFormed = $row[9] ?? null;
            $dateRegistered = $row[10] ?? null;

            // Validate mandatory fields
            if (empty($sno) || !is_numeric($sno)) {
                $errors[] = "Row {$rowNumber}: S/No. is required and must be a number";
            }

            if (empty($wardName)) {
                $errors[] = "Row {$rowNumber}: Ward name is required";
            } elseif (!$this->isValidWard($wardName)) {
                $errors[] = "Row {$rowNumber}: Ward '{$wardName}' does not exist in this constituency";
            }

            if (empty($groupName)) {
                $errors[] = "Row {$rowNumber}: Group name is required";
            }

            if (empty($gfsCode)) {
                $errors[] = "Row {$rowNumber}: GFS code is required";
            }
            if (empty($groupCode)) {
                $errors[] = "Row {$rowNumber}: Group code is required";
            }
            if (empty($regNumber)) {
                $errors[] = "Row {$rowNumber}: Registration number is required";
            }

            // Normalize values for duplicate checking
            $normalizedGroupCode = !empty($groupCode) ? strtolower(trim($groupCode)) : null;
            $normalizedIdentifier = !empty($identifier) ? strtolower(trim($identifier)) : null;
            $normalizedRegNumber = !empty($regNumber) ? strtolower(trim($regNumber)) : null;
            $normalizedGfsCode = !empty($gfsCode) ? strtolower(trim($gfsCode)) : null;

            // Check duplicates within the file
            if ($normalizedGroupCode && isset($seenInFile['codes'][$normalizedGroupCode])) {
                $errors[] = "Row {$rowNumber}: Group code '{$groupCode}' is duplicated in the file";
            }
            if ($normalizedIdentifier && isset($seenInFile['identifiers'][$normalizedIdentifier])) {
                $errors[] = "Row {$rowNumber}: Group Identifier '{$identifier}' is duplicated in the file";
            }
            if ($normalizedRegNumber && isset($seenInFile['reg'][$normalizedRegNumber])) {
                $errors[] = "Row {$rowNumber}: Registration number '{$regNumber}' is duplicated in the file";
            }
            if ($normalizedGfsCode && isset($seenInFile['gfs'][$normalizedGfsCode])) {
                $errors[] = "Row {$rowNumber}: GFS code '{$gfsCode}' is duplicated in the file";
            }

            // Store normalized values for file-level duplicate checking
            if ($normalizedGroupCode) $seenInFile['codes'][$normalizedGroupCode] = true;
            if ($normalizedIdentifier) $seenInFile['identifiers'][$normalizedIdentifier] = true;
            if ($normalizedRegNumber) $seenInFile['reg'][$normalizedRegNumber] = true;
            if ($normalizedGfsCode) $seenInFile['gfs'][$normalizedGfsCode] = true;


            // Check against existing data (using cache, no DB query)
            if ($normalizedGroupCode && isset($this->existingGroupCodes[$normalizedGroupCode])) {
                $errors[] = "Row {$rowNumber}: Group code '{$groupCode}' already exists in this constituency";
            }
            if ($normalizedIdentifier && isset($this->existingIdentifiers[$normalizedIdentifier])) {
                $errors[] = "Row {$rowNumber}: Group Identifier '{$identifier}' already exists in this constituency";
            }
            if ($normalizedRegNumber && isset($this->existingRegNumbers[$normalizedRegNumber])) {
                $errors[] = "Row {$rowNumber}: Registration number '{$regNumber}' already exists in this constituency";
            }
            if ($normalizedGfsCode && isset($this->existingGfsCodes[$normalizedGfsCode])) {
                $errors[] = "Row {$rowNumber}: GFS code '{$gfsCode}' already exists in this constituency";
            }



            if (empty($category)) {
                $errors[] = "Row {$rowNumber}: Category is required";
            } elseif (!in_array(strtolower($category), ['youth', 'women', 'pwd'])) {
                $errors[] = "Row {$rowNumber}: Category must be one of: Youth, Women, PWD";
            }

            if (empty($activity)) {
                $errors[] = "Row {$rowNumber}: Activity is required";
            } elseif (!in_array(strtolower($activity), ['trade', 'service', 'manufacturing', 'agriculture', 'other'])) {
                $errors[] = "Row {$rowNumber}: Activity must be one of: Trade, Service, Manufacturing, Agriculture, Other";
            }

            // Validate dates only if provided (optional fields)
            if (!empty($dateFormed) && !$this->isValidDate($dateFormed)) {
                if (is_numeric($dateFormed)) {
                    try {
                        // Convert Excel serial (e.g. 42402) to Y-m-d.
                        // Excel epoch for Windows spreadsheets: 1899-12-30
                        $base = Carbon::createFromFormat('Y-m-d', '1899-12-30');
                        $converted = $base->copy()->addDays((int) floor($dateFormed));
                        $normalized = $converted->format('Y-m-d');

                        // Update the rows collection so the converted date is used later during import
                        $rows[$index][9] = $normalized;
                        $dateFormed = $normalized;

                        // Date is now valid; skip adding an error for this field
                        continue;
                    } catch (\Exception $e) {
                        // If conversion fails, fall through to add the validation error below
                    }
                }
                $errors[] = "Row {$rowNumber}: Date formed must be in YYYY-MM-DD format or empty";
            }

            if (!empty($dateRegistered) && !$this->isValidDate($dateRegistered)) {
                if (is_numeric($dateRegistered)) {
                    try {
                        // Convert Excel serial (e.g. 42402) to Y-m-d.
                        // Excel epoch for Windows spreadsheets: 1899-12-30
                        $base = Carbon::createFromFormat('Y-m-d', '1899-12-30');
                        $converted = $base->copy()->addDays((int) floor($dateRegistered));
                        $normalized = $converted->format('Y-m-d');

                        // Update the rows collection so the converted date is used later during import
                        $rows[$index][10] = $normalized;
                        $dateRegistered = $normalized;

                        // Date is now valid; skip adding an error for this field
                        continue;
                    } catch (\Exception $e) {
                        // If conversion fails, fall through to add the validation error below
                    }
                }
                $errors[] = "Row {$rowNumber}: Date registered must be in YYYY-MM-DD format or empty";
            }
        }

        return $errors;
    }

    /**
     * Validate column headers
     */
    private function validateHeaders($headerRow)
    {
        if (empty($headerRow) || count($headerRow) < 9) {
            return false;
        }

        $expectedHeaders = [
            's/no*', 'ward*', 'name*', 'code*', 'gfs_code*', 'identifier',
            'reg_number*', 'category*', 'activity*', 'date_formed', 'date_registered',
            'bank', 'account', 'activity_desc', 'location'
        ];

        $actualHeaders = array_map(function($header) {
            return strtolower(trim($header ?? ''));
        }, $headerRow->toArray());

        // Check if we have at least the mandatory headers
        for ($i = 0; $i < 9; $i++) {
            if (!isset($actualHeaders[$i]) || $actualHeaders[$i] !== $expectedHeaders[$i]) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if ward exists in constituency (case-insensitive)
     */
    private function isValidWard($wardName)
    {
        $normalizedWardName = strtolower(trim($wardName));

        foreach ($this->wardsCache as $cachedWardName => $wardId) {
            if (strtolower($cachedWardName) === $normalizedWardName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get ward ID from name (case-insensitive)
     */
    private function getWardId($wardName)
    {
        $normalizedWardName = strtolower(trim($wardName));

        foreach ($this->wardsCache as $cachedWardName => $wardId) {
            if (strtolower($cachedWardName) === $normalizedWardName) {
                return $wardId;
            }
        }

        return null;
    }

    /**
     * Load wards cache for the constituency
     */
    private function loadWardsCache()
    {
        $wards = Wards::where('constituency_code', $this->constituencyCode)->get();

        foreach ($wards as $ward) {
            $this->wardsCache[$ward->ward_name] = $ward->id;
        }
    }

    /**
     * Validate date format (optional field)
     */
    private function isValidDate($date)
    {
        if (empty($date)) {
            return true; // Empty is valid since it's optional
        }

        try {
            // Try to parse the date
            $parsedDate = Carbon::createFromFormat('Y-m-d', $date);

            // Check if the parsed date matches the original (to catch invalid dates like 2023-02-30)
            return $parsedDate && $parsedDate->format('Y-m-d') === $date;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Parse date string to Carbon instance (handles empty values)
     */
    private function parseDate($dateString)
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $dateString);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Update progress in cache
     */
    private function updateProgress($progress, $message)
    {
        // Only update if progress changed significantly (reduce cache writes)
        static $lastProgress = 0;

        if (abs($progress - $lastProgress) >= 5 || $progress === 100) {
            $progressKey = "import_progress_{$this->constituencyCode}_groups";
            Cache::put($progressKey, [
                'progress' => $progress,
                'message' => $message,
                'updated_at' => now()
            ], 60);
            $lastProgress = $progress;
        }
    }

    /**
     * Clear progress cache
     */
    private function clearProgress()
    {
        $progressKey = "import_progress_{$this->constituencyCode}_groups";
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
