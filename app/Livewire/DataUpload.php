<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LoanTemplateExport;
use App\Exports\MembersTemplateExport;
use App\Services\Loan\LoanPhaseService;
use App\Models\Constituency;
use App\Models\Group;
use App\Models\GroupLoan;

class DataUpload extends Component
{
    use WithFileUploads;

    protected LoanPhaseService $loanPhaseService;

    private $cacheTag = null;
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

    public function boot(LoanPhaseService $loanPhaseService)
    {
        $this->loanPhaseService = $loanPhaseService;
    }

    public function mount(Constituency $constituency)
    {
        $this->constituency = $constituency->load(['wards']);
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

        $phases = $this->getPhases->pluck('phase')->toArray();

        if ($type === 'groups') {
            $wards = $this->constituency->wards->pluck('ward_name')->toArray();

            if (empty($phases)){
                $this->dispatch('swal:toast',
                    title: 'Download failed 😕',
                    message: 'There are no loans phase records. Ensure you completed step 1',
                    type: 'error'
                );
                return;
            }

            return Excel::download(
                new LoanTemplateExport(
                    $wards,
                    $phases,
                    (string) $this->constituency->county_code,
                    (string) $this->constituency->constituency_code
                ),
                'Group_Loan_Template.xlsx'
            );
        }

        if ($type === 'members') {
            if (empty($phases)) {
                $this->dispatch('swal:toast',
                    title: 'Download failed 😕',
                    message: 'There are no loan phase records. Ensure you completed step 1',
                    type: 'error'
                );
                return;
            }

            $groups = $this->getGroupsForMembersTemplate();

            if (empty($groups)) {
                $this->dispatch('swal:toast',
                    title: 'Download failed 😕',
                    message: 'There are no groups with GFS codes recorded. Ensure you completed step 2',
                    type: 'error'
                );
                return;
            }

            return Excel::download(
                new MembersTemplateExport(
                    $phases,
                    $groups
                ),
                'Member_Template.xlsx'
            );
        }

        $filename = $templates[$type];
        $filePath = public_path('templates/' . $filename);

        if (file_exists($filePath)) {
            return response()->download($filePath, $filename);
        } else {
            session()->flash('error', 'Template file not found: ' . $filename);
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
        switch ($field) {
            case 'loanPhasesData':
                $this->resetValidation('loanPhasesData');

                // If you want to hide the previous Import Result when a new file is picked:
                $this->importResult = null;
                break;

            default:
                # code...
                break;
        }
        $this->$field = null;
    }

    public function loanPhasesForm()
    {
        $this->validate([
            'loanPhasesData' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $import = new \App\Imports\LoanPhasesImport($this->constituency->constituency_code);
            Excel::import($import, $this->loanPhasesData);

            // Final update
            $stats = $import->getImportStats();

            $this->importResult = [
                'success' => true,
                'message' => "Successfully imported {$stats['imported']} new phase(s). {$stats['skipped']} row(s) skipped.",
                'stats' => $stats
            ];

            $this->loanPhasesData = null;

            $this->dispatch('swal:toast',
                title: 'Data Upload 😊',
                message: 'Phase Data Successfully Uploaded.',
                type: 'success'
            );

        } catch (\Exception $e) {
            $this->importResult = [
                'success' => false,
                'message' => 'Import Failed: ' . $e->getMessage(),
            ];

            // $this->addError('loanPhasesData', 'The file could not be processed: ' . $e->getMessage());

            // Clear progress on error too
            $progressKey = "import_progress_{$this->constituency->constituency_code}";
            \Illuminate\Support\Facades\Cache::forget($progressKey);
        }
    }

    public function updatedLoanPhasesData()
    {
        $this->resetValidation('loanPhasesData');

        $this->importResult = null;
    }

    public function updatedGroupsData()
    {
        $this->resetValidation('groupsData');
        $this->groupsImportResult = null;
    }

    public function updatedGroupMembersData()
    {
        $this->resetValidation('groupMembersData');
        $this->groupMembersImportResult = null;
    }

    public function resetFile()
    {
        $this->loanPhasesData = null;
    }

    #[Computed]
    public function getPhases()
    {
        return $this->loanPhaseService->getPhasesForConstituencyByStatus($this->constituency->constituency_code, 'disbursed');
    }

    #[Computed]
    public function hasUploadedPhases(): bool
    {
        return $this->getPhases->isNotEmpty();
    }

    #[Computed]
    public function hasUploadedGroups(): bool
    {
        return GroupLoan::query()
            ->whereHas('loanPhase', function ($query) {
                $query->where('constituency_code', $this->constituency->constituency_code);
            })
            ->whereNotNull('gfs_code')
            ->exists();
    }

    private function getGroupsForMembersTemplate(): array
    {
        return GroupLoan::query()
            ->whereHas('loanPhase', function ($query) {
                $query->where('constituency_code', $this->constituency->constituency_code);
            })
            ->whereHas('group.ward', function ($query) {
                $query->where('constituency_code', $this->constituency->constituency_code);
            })
            ->whereNotNull('gfs_code')
            ->with([
                'group:id,name',
                'loanPhase:id,phase',
            ])
            ->get()
            ->map(function (GroupLoan $groupLoan) {
                $loanType = ucfirst(strtolower((string) ($groupLoan->loan_type ?: 'new')));

                return [
                    'phase' => $groupLoan->loanPhase->phase ?? '',
                    'loan_type' => $loanType,
                    'group_name' => $groupLoan->group->name ?? '',
                    'gfs_code' => $groupLoan->gfs_code,
                ];
            })
            ->filter(fn (array $group) => $group['phase'] !== '' && $group['group_name'] !== '' && $group['gfs_code'] !== '')
            ->values()
            ->all();
    }

    public function groupsForm()
    {
        $this->groupsImportResult = null;

        $this->validate([
            'groupsData' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            $phases = $this->getPhases->pluck('phase')->map(fn($item) => strtolower($item))->toArray();

            $import = new \App\Imports\LoanTemplateImport($this->constituency, $phases);
            Excel::import($import, $this->groupsData);

            // Final update
            $result = $import->getImportResult();

            if ($result['success']) {
                $this->groupsImportResult = [
                    'success' => true,
                    'message' => $result['message'],
                    'stats' => [
                        'imported' => $result['imported_count'],
                        'skipped' => 0,
                        'new' => $result['imported_new_groups_count'],
                    ]
                ];

                $this->groupsData = null;
                $this->dispatch('swal:toast',
                    title: 'Data Upload 😊',
                    message: 'Groups Data Successfully Uploaded.',
                    type: 'success'
                );
            } else {
                $this->groupsImportResult = [
                    'success' => false,
                    'message' => $result['message'],
                    'errors' => $result['errors'],
                    'stats' => [
                        'imported' => 0,
                        'skipped' => 0,
                        'new' => 0
                    ]
                ];

                $this->dispatch('swal:toast',
                    title: 'Data failed 😕',
                    message: 'Groups upload data failed.' . $this->groupsImportResult['message'],
                    type: 'error'
                );
            }

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $parsedErrors = $this->parseValidationErrors($errorMessage);

            $this->groupsImportResult = [
                'success' => false,
                'message' => $errorMessage,
                'errors' => $parsedErrors['errors'],
                'total_errors' => $parsedErrors['total'],
                'display_errors' => $parsedErrors['display'],
                'stats' => [
                    'imported' => 0,
                    'skipped' => 0,
                    'new' => 0,
                ]
            ];
        }
    }

    private function parseValidationErrors($errorMessage)
    {
        $errors = [];

        // 1. Remove the header prefix if it exists
        $errorMessage = preg_replace('/^Validation errors found:\s*\n?/', '', $errorMessage);

        // 2. SPLIT FIX: Split only when a newline is followed by "Row "
        // This keeps multi-line details (Current/Existing Group) within the same array item.
        $blocks = preg_split('/\n(?=Row \d+:)/', $errorMessage);

        foreach ($blocks as $block) {
            $block = trim($block);
            if (empty($block)) continue;

            // Check for the summary line (usually at the very end)
            if (str_contains($block, '... and') || str_contains($block, 'more errors')) {
                $errors[] = [
                    'row' => 'Info',
                    'message' => $block
                ];
                continue;
            }

            // Match "Row X: Error description" - and grab EVERYTHING after the colon
            // using 's' modifier to allow dot (.) to match newlines
            if (preg_match('/^Row (\d+): (.+)$/s', $block, $match)) {
                $errors[] = [
                    'row' => $match[1],
                    'message' => trim($match[2])
                ];
            } else {
                $errors[] = [
                    'row' => '?',
                    'message' => $block
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
        $this->validate([
            'groupMembersData' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        try {
            if (is_array($this->groupMembersData)) {
                throw new \RuntimeException('Invalid upload state. Please remove the file, re-select it, and try again.');
            }

            $uploadPath = $this->groupMembersData->getRealPath();
            if (! $uploadPath || ! is_readable($uploadPath)) {
                throw new \RuntimeException('Upload file could not be read. Please re-select the file and try again.');
            }

            $phases = $this->getPhases->pluck('phase')->map(fn($item) => strtolower($item))->toArray();

            $import = new \App\Imports\MembersTemplateImport($this->constituency, $phases);
            Excel::import($import, $uploadPath);

            $result = $import->getImportResult();

            if ($result['success']) {
                $this->groupMembersImportResult = [
                    'success' => true,
                    'message' => $result['message'],
                    'stats' => [
                        'imported' => $result['imported_count'],
                        'skipped' => 0,
                        'new' => $result['new_members_created']
                    ]
                ];

                $this->groupMembersData = null;

                $this->dispatch('swal:toast',
                    title: 'Members Data Upload 😊',
                    message: 'Member Data Uploaded Successfully.',
                    type: 'success'
                );

            } else {
                $this->groupMembersImportResult = [
                    'success' => false,
                    'message' => $result['message'],
                    'errors' => $import->getErrors() ?? [],
                    'stats' => [
                        'imported' => 0,
                        'skipped' => 0,
                        'new' => 0
                    ]
                ];
            }

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
        }
    }

    private function getExistingGroups()
    {
        $this->cacheTag = config('cache.tags.groups.constituency') . $this->constituency->constituency_code;

        $cacheKey = "groups_data";

        $existingGroups = Cache::tags([$this->cacheTag])->get($cacheKey);

        if (!$existingGroups) {
            $groups = Group::whereHas('ward', function($q) {
                $q->where('constituency_code', $this->constituency->constituency_code);
            })
            ->with('ward')
            ->get()
            ->map(function($group) {
                return [
                    'code' => $group->code,
                    'name' => $group->name,
                    'ward' => $group->ward->ward_name ?? '',
                    'reg_number' => $group->reg_number ?? ''
                ];
            })
            ->toArray();

            if (empty($groups)){
                return null;
            }

            Cache::tags([$this->cacheTag])->put($cacheKey, $groups, now()->addMinutes(2));

            $existingGroups = $groups;
        }
        return $existingGroups;
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
