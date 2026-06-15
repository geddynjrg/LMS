<?php
// app/Imports/LoanPhasesImport.php

namespace App\Imports;

use App\Models\LoanPhase;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class LoanPhasesImport implements ToCollection
{
    private $constituencyCode;
    private $importedCount = 0;
    private $skippedCount = 0;
    private $skippedRows = [];
    private $totalRows = 0;
    private $processedRows = 0;

    public function __construct($constituencyCode)
    {
        $this->constituencyCode = $constituencyCode;
    }

    public function collection(Collection $rows)
    {
        $this->totalRows = $rows->count() - 1; // Exclude header
        $this->processedRows = 0;

        // Validate column headers first
        if (!$this->validateHeaders($rows->first())) {
            throw new \Exception('Invalid Excel template. Please download the correct template format.');
        }

        // Dispatch initial progress
        $this->updateProgress(5, 'Starting import process...');

        foreach ($rows as $index => $row) {
            // Skip header row (index 0)
            if ($index === 0) {
                continue;
            }

            $this->processedRows++;

            // Update progress based on actual progress
            $progress = min(90, 5 + (($this->processedRows / $this->totalRows) * 85));

            // Update progress every row for smoother updates, or use modulus for fewer updates
            if ($this->processedRows % 2 === 0 || $this->processedRows === 1 || $this->processedRows === $this->totalRows) {
                $this->updateProgress(
                    (int) $progress,
                    "Processing row {$this->processedRows} of {$this->totalRows}..."
                );
            }

            $sno = $row[0] ?? null;
            $phaseName = $row[1] ?? null;

            // Skip empty rows
            if (empty($sno) && empty($phaseName)) {
                continue;
            }

            // Validate row data
            if (!$this->validateRowData($sno, $phaseName, $index + 1)) {
                continue;
            }

            $phaseName = $this->normalizePhaseName($phaseName);

            // Check if phase already exists
            $existingPhase = LoanPhase::where('constituency_code', $this->constituencyCode)
                ->whereRaw('LOWER(phase) = ?', [strtolower($phaseName)])
                ->first();

            if ($existingPhase) {
                $this->skippedRows[] = [
                    'row' => $index + 1,
                    'phase' => $phaseName,
                    'reason' => 'Phase already exists: ' . $existingPhase->phase
                ];
                $this->skippedCount++;
                continue;
            }

            // Validate phase name format
            if (!$this->isValidPhaseName($phaseName)) {
                $this->skippedRows[] = [
                    'row' => $index + 1,
                    'phase' => $phaseName,
                    'reason' => 'Invalid phase name format'
                ];
                $this->skippedCount++;
                continue;
            }

            // Create new phase
            LoanPhase::create([
                'constituency_code' => $this->constituencyCode,
                'phase' => $phaseName,
                'status' => 'disbursed',
                'amount_disbursed' => 0,
                'amount_repaid' => 0,
            ]);

            $this->importedCount++;
        }

        // Final progress update
        $this->updateProgress(95, 'Finalizing import...');

        // Clear the progress cache when done
        $this->clearProgress();

        if ($this->importedCount === 0 && $this->skippedCount === 0) {
            throw new \Exception('No valid phase data found in the file.');
        }
    }

    /**
     * Update progress in cache
     */
    private function updateProgress($progress, $message)
    {
        // Store progress in cache with a unique key
        $progressKey = "import_progress_{$this->constituencyCode}";
        Cache::put($progressKey, [
            'progress' => $progress,
            'message' => $message,
            'updated_at' => now()
        ], 60); // Store for 60 seconds

        // Small delay to make progress visible (optional)
        usleep(100000); // 0.1 second
    }

    /**
     * Clear progress cache
     */
    private function clearProgress()
    {
        $progressKey = "import_progress_{$this->constituencyCode}";
        Cache::forget($progressKey);
    }

    /**
     * Validate column headers
     */
    private function validateHeaders($headerRow)
    {
        if (empty($headerRow) || count($headerRow) < 2) {
            return false;
        }

        $expectedHeaders = ['s/no.', 'phase name*'];
        $actualHeaders = [
            strtolower(trim($headerRow[0] ?? '')),
            strtolower(trim($headerRow[1] ?? ''))
        ];

        return $actualHeaders === $expectedHeaders;
    }

    /**
     * Validate individual row data
     */
    private function validateRowData($sno, $phaseName, $rowNumber)
    {
        // Check if serial number is numeric
        if (!is_numeric($sno)) {
            $this->skippedRows[] = [
                'row' => $rowNumber,
                'phase' => $phaseName ?? 'N/A',
                'reason' => 'Invalid serial number: ' . $sno
            ];
            $this->skippedCount++;
            return false;
        }

        // Check if phase name is provided
        if (empty($phaseName)) {
            $this->skippedRows[] = [
                'row' => $rowNumber,
                'phase' => 'N/A',
                'reason' => 'Phase name is required'
            ];
            $this->skippedCount++;
            return false;
        }

        return true;
    }

    /**
     * Validate phase name format
     */
    private function isValidPhaseName($phaseName)
    {
        // Must start with "Phase" (case insensitive)
        if (!preg_match('/^phase\s+/i', $phaseName)) {
            return false;
        }

        // Must be followed by a number or letter (Phase 1, Phase 2A, Phase III, etc.)
        $phasePart = trim(substr($phaseName, 5)); // Remove "Phase"
        return !empty($phasePart);
    }

    /**
     * Normalize phase name
     */
    private function normalizePhaseName($phaseName)
    {
        if (empty($phaseName)) {
            return null;
        }

        // Trim whitespace
        $phaseName = trim($phaseName);

        // Convert to proper case (first letter of each word uppercase)
        $phaseName = Str::title($phaseName);

        // Remove extra spaces
        $phaseName = preg_replace('/\s+/', ' ', $phaseName);

        return $phaseName;
    }

    public function getImportStats()
    {
        return [
            'imported' => $this->importedCount,
            'skipped' => $this->skippedCount,
            'skipped_rows' => $this->skippedRows,
            'total' => $this->importedCount + $this->skippedCount
        ];
    }
}
