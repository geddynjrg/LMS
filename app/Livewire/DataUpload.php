<?php

namespace App\Livewire;

use App\Models\Constituency;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Attributes\On;

class DataUpload extends Component
{
    use WithFileUploads;

    public Constituency $constituency;
    public $loanPhasesData;
    public $groupsData;
    public $groupsFileName = '';
    public $groupLoansData;
    public $groupMembersData;

    public $importing = false;
    public $importingPhase = false;
    public $importProgress = 0;
    public $importResult = null;
    public $groupsImportResult = null;
    public $groupLoansImportResult = null;
    public $groupMembersImportResult = null;


    public $importMessage = '';
    public $importProgressGroups = 0;
    public $importMessageGroups = '';
    public $importProgressGroupLoans = 0;
    public $importMessageGroupLoans = '';
    public $importProgressGroupMembers = 0;
    public $importMessageGroupMembers = '';


    public function mount(Constituency $constituency)
    {
        $this->constituency = $constituency;
    }

    public function downloadTemplate($type)
    {
        $templates = [
            'loan-phases' => 'phases.xlsx',
            'groups' => 'groups.xlsx',
            'group-loans' => 'group_loans.xlsx',
            'members' => 'members.xlsx'
        ];

        if (!array_key_exists($type, $templates)) {
            return;
        }

        $filename = $templates[$type];
        $filePath = public_path('templates/' . $filename);

        if (file_exists($filePath)) {
            return response()->download($filePath, $filename);
        } else {
            session()->flash('error', 'Template file not found: ' . $filename);
        }
    }


    public function updatedGroupsData()
    {
        if ($this->groupsData) {
            $this->groupsFileName = $this->groupsData->getClientOriginalName();
        } else {
            $this->groupsFileName = '';
        }
    }

    public function pollProgress()
    {
        $progressKey = "import_progress_{$this->constituency->constituency_code}";
        $progress = \Illuminate\Support\Facades\Cache::get($progressKey);

        if ($progress) {
            $this->importProgress = $progress['progress'];
            $this->importMessage = $progress['message'];
        }
    }

    // public function pollGroupsProgress()
    // {
    //     $progressKey = "import_progress_{$this->constituency->constituency_code}_groups";
    //     $progress = \Illuminate\Support\Facades\Cache::get($progressKey);

    //     if ($progress) {
    //         $this->importProgressGroups = $progress['progress'];
    //         $this->importMessageGroups = $progress['message'];
    //     }
    // }


    // Add file removal methods
    public function removeFile($field)
    {
        $this->$field = null;
    }


    public function loanPhasesForm()
    {
        $this->validate([
            'loanPhasesData' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $this->importResult = null;
        $this->importProgress = 0;
        $this->importMessage = 'Starting import...';

        try {
            $import = new \App\Imports\LoanPhasesImport($this->constituency->constituency_code);
            Excel::import($import, $this->loanPhasesData);

            // Final update
            $this->importProgress = 100;
            $this->importMessage = 'Import completed successfully!';

            $stats = $import->getImportStats();
            $this->dispatch('import-completed', form: 'loanPhasesForm');
            $this->importResult = [
                'success' => true,
                'message' => "Successfully imported {$stats['imported']} new phase(s). {$stats['skipped']} row(s) skipped.",
                'stats' => $stats
            ];

            $this->loanPhasesData = null;

        } catch (\Exception $e) {
            $this->importResult = [
                'success' => false,
                'message' => 'Import Failed: ' . $e->getMessage(),
            ];

            // Clear progress on error too
            $progressKey = "import_progress_{$this->constituency->constituency_code}";
            \Illuminate\Support\Facades\Cache::forget($progressKey);
        }
    }

    // Add this method to reset file after import
    public function resetFile()
    {
        $this->loanPhasesData = null;
    }

    public function groupsForm()
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300); // 5 minutes

        $this->groupsImportResult = null;
        $this->validate([
            'groupsData' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);


        $this->importProgressGroups = 0;
        $this->importMessageGroups = 'Starting import...';

        // EMIT the import-started event
        $this->dispatch('import-started');
        // 👇 Start polling
        $this->dispatch('start-groups-polling');

        try {
            $import = new \App\Imports\GroupsImport($this->constituency->constituency_code);
            Excel::import($import, $this->groupsData);

            // Final update
            $this->importProgressGroups = 100;
            $this->importMessageGroups = 'Import completed successfully!';
            $this->dispatch('import-completed', form: 'groupsForm');

            $stats = $import->getImportStats();
            $this->groupsImportResult = [
                'success' => true,
                'message' => "Successfully imported {$stats['imported']} new group(s). {$stats['skipped']} group(s) skipped (already exist).",
                'stats' => $stats
            ];

            $this->groupsData = null;
            $this->dispatch('stop-groups-polling');

        } catch (\Exception $e) {
            // $this->groupsImportResult = [
            //     'success' => false,
            //     'message' => $e->getMessage(),
            // ];

            // // Clear progress on error
            // $progressKey = "import_progress_{$this->constituency->constituency_code}_groups";
            // \Illuminate\Support\Facades\Cache::forget($progressKey);

            $errorMessage = $e->getMessage();
            $parsedErrors = $this->parseValidationErrors($errorMessage);
            $this->groupsImportResult = [
                'success' => false,
                'message' => $errorMessage,
                'errors' => $parsedErrors['errors'],
                'total_errors' => $parsedErrors['total'],
                'display_errors' => $parsedErrors['display']
            ];

            // Stop polling on error too
            $this->dispatch('stop-groups-polling');

            // Clear progress on error
            $this->clearGroupsProgress();
        }
    }


    private function parseValidationErrors($errorMessage)
    {
        $errors = [];

        // Remove the first line "Validation errors found:" if it exists
        $errorMessage = preg_replace('/^Validation errors found:\s*\n?/', '', $errorMessage);

        // Split by lines and process each one
        $lines = explode("\n", $errorMessage);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines and the "and X more errors" summary line
            if (empty($line) || str_contains($line, '... and') || str_contains($line, 'more errors')) {
                continue;
            }

            // Match "Row X: Error description" pattern
            if (preg_match('/^Row (\d+): (.+)$/', $line, $match)) {
                $errors[] = [
                    'row' => $match[1],
                    'message' => trim($match[2])
                ];
            }
            // If it doesn't match the Row pattern but has content, include it as unknown row
            elseif (!empty($line)) {
                $errors[] = [
                    'row' => 'Unknown',
                    'message' => $line
                ];
            }
        }

        $totalErrors = count($errors);
        $displayErrors = array_slice($errors, 0, 20);

        return [
            'errors' => $errors,
            'total' => $totalErrors,
            'display' => $displayErrors
        ];
    }

    public function groupLoansForm()
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);
        $this->groupLoansImportResult = null;
        $this->validate([
            'groupLoansData' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $this->importProgressGroupLoans = 0;
        $this->importMessageGroupLoans = 'Starting group loans import...';

        $this->dispatch('import-started');
        $this->dispatch('start-groups-polling');

        try {
            $import = new \App\Imports\GroupLoansImport($this->constituency->constituency_code);
            Excel::import($import, $this->groupLoansData);

            $this->importProgressGroupLoans = 100;
            $this->importMessageGroupLoans = 'Import completed successfully!';
            $this->dispatch('import-completed', form: 'groupLoansForm');

            $stats = $import->getImportStats();
            $this->groupLoansImportResult = [
                'success' => true,
                'message' => "Successfully imported {$stats['imported']} new group loan(s). {$stats['skipped']} loan(s) skipped (already exist).",
                'stats' => $stats
            ];

            $this->groupLoansData = null;
            $this->dispatch('stop-groups-polling');

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $parsedErrors = $this->parseValidationErrors($errorMessage);
            $this->groupLoansImportResult = [
                'success' => false,
                'message' => $errorMessage,
                'errors' => $parsedErrors['errors'],
                'total_errors' => $parsedErrors['total'],
                'display_errors' => $parsedErrors['display']
            ];

            $this->dispatch('stop-groups-polling');
            $this->clearGroupsProgress();
        }
        $this->importProgressGroupLoans = 0;
    }

    public function groupMembersForm()
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);

        $this->validate([
            'groupMembersData' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $this->importProgressGroupMembers = 0;
        $this->importMessageGroupMembers = 'Starting members import...';

        $this->dispatch('import-started');
        $this->dispatch('start-groups-polling');

        try {
            $import = new \App\Imports\MembersImport($this->constituency->constituency_code);
            Excel::import($import, $this->groupMembersData);

            $this->importProgressGroupMembers = 100;
            $this->importMessageGroupMembers = 'Import completed successfully!';
            $this->dispatch('import-completed', form: 'groupMembersForm');

            $stats = $import->getImportStats();
            $this->groupMembersImportResult = [
                'success' => true,
                'message' => "Successfully imported {$stats['imported_members']} new member(s) and {$stats['imported_group_members']} group association(s).",
                'stats' => $stats
            ];

            $this->groupMembersData = null;
            $this->dispatch('stop-groups-polling');

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $parsedErrors = $this->parseValidationErrors($errorMessage);
            $this->groupMembersImportResult = [
                'success' => false,
                'message' => $errorMessage,
                'errors' => $parsedErrors['errors'],
                'total_errors' => $parsedErrors['total'],
                'display_errors' => $parsedErrors['display']
            ];

            $this->dispatch('stop-groups-polling');
            $this->clearGroupsProgress();
        }
    }

    /**
     * Poll for groups progress updates
    */
    #[On('poll-groups-progress')]
    public function pollGroupsProgress()
    {
        // Only update if import is in progress
        if ($this->importProgressGroups < 100) {
            $progressKey = "import_progress_{$this->constituency->constituency_code}_groups";
            $progress = \Illuminate\Support\Facades\Cache::get($progressKey);

            if ($progress) {
                $this->importProgressGroups = $progress['progress'];
                $this->importMessageGroups = $progress['message'];

                // Auto-stop when complete (safety net)
                if ($progress['progress'] >= 100) {
                    $this->clearGroupsProgress();
                    $this->dispatch('stop-groups-polling');
                }
            }
        }
    }

    #[On('update-filename')]
    public function setFileName($fileName)
    {
        $this->groupsFileName = $fileName['fileName'] ?? '';
    }

    /**
     * Clear groups progress cache
    */
    private function clearGroupsProgress()
    {
        $progressKey = "import_progress_{$this->constituency->constituency_code}_groups";
        \Illuminate\Support\Facades\Cache::forget($progressKey);
    }

    /**
     * Manually clear progress (optional - for UI button)
     */
    public function clearProgress()
    {
        $this->clearGroupsProgress();
        $this->importProgressGroups = 0;
        $this->importProgressGroupLoans = 0;
        $this->importMessageGroups = '';
    }


    public function render()
    {
        return view('livewire.reports.data-upload');
    }
}
