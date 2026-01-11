<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TempApplication;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use App\Services\Loan\LoanPhaseService;
use App\Services\Loan\TempApplicationService;
use App\Services\Loan\LoanAnalysisService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use App\Services\Loan\DefaultAnalysisService;
use App\Models\Member;
use App\Models\Group;
use App\Models\TempGroupMember;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\TempApplicationApproval;
use App\Models\PhaseApplication;

class ApplicationView extends Component
{
    protected LoanPhaseService $phaseService;
    protected TempApplicationService $appService;
    protected DefaultAnalysisService $defaultService;
    protected LoanAnalysisService $analysisService;

    public $isDataLoaded = false;

    public $applicationId;
    public $county;
    public $constituency;
    public $application;
    public $defaultMembers;
    public $groupMembers;

    // links
    public $suggestedMatches = [];
    public $isSearching = false;
    public ?Group $linkedGroup;
    public ?PhaseApplication $phaseApplication;

    public $selectedPhaseForQuickAssign = '';
    public $selectedPhaseId;
    public $isManualSearch = false;
    public $searchTerm = '';

    public $activeTab = 'group-loan';

    // Sidebar fields
    public $remarks = '';
    public $actionTaken = false;

    // member
    public $selectedMember;

    // gfs
    public $groupCode;
    public $gfsCode;

    // approval
    public $approvalAmount;
    public $approvalComments;
    public $groupIdentifier;
    public $eligibility = [
        'min' => 50000,
        'max' => 100000,
        'balance' => 0,
        'can_borrow' => true
    ];
    public $assessmentData;

    // ward
    public $selectedWardId;

    public function boot(LoanPhaseService $phaseService, LoanAnalysisService $analysisService, TempApplicationService $appService, DefaultAnalysisService $defaultService)
    {
        $this->phaseService = $phaseService;
        $this->appService = $appService;
        $this->defaultService = $defaultService;
        $this->analysisService = $analysisService;
    }

    public function mount($constituency, $applicationId)
    {
        $this->constituency = $constituency->load(['county', 'wards']);
        $this->county = $this->constituency->county;
        $this->applicationId = $applicationId;

        $this->linkedGroup = null;

        $this->defaultMembers = [];

        $this->groupMembers = null;

        $this->loadApplication();
        $this->isDataLoaded = true;
        $this->approvalAmount =  $this->application->loan_amount;

        $this->generateGfs();
    }


    public function refreshData()
    {
        Cache::forget("app_enrichment_{$this->applicationId}");
        unset($this->riskFlags);
        unset($this->riskFlagsCount);
        unset($this->riskCategory);
        unset($this->riskCategory);
        unset($this->activePhases);
        unset($this->gfsCode);
        reset($this->eligibility);

        $this->appService->invalidateApplicationCache($this->application);
        $this->loadApplication();
        $this->generateGfs();
    }

    public function updating()
    {
        $this->loadApplication();
    }


    public function updatedSelectedPhaseId($value)
    {
        // $this->loadApplication();
    }

    public function updatedGroupCode($value)
    {
        $cleanValue = trim($value);

        if (is_numeric($cleanValue)) {
            $targetLength = max(3, strlen($cleanValue));
            $this->groupCode = str_pad($cleanValue, $targetLength, '0', STR_PAD_LEFT);
        }

        $this->validate([
            'groupCode' => [
                'required', 'numeric', 'min:1', 'regex:/^\d{3,}$/',
                function ($attribute, $value, $fail) {
                    // Check Master Groups in this Constituency
                    $existingInMaster = Group::whereHas('ward', function ($query) {
                        $query->where('constituency_code', $this->constituency->constituency_code);
                    })
                    ->where('code', $this->groupCode)
                    ->exists();

                    if ($existingInMaster) {
                        $fail("The Group Code {$this->groupCode} is already registered to a group.");
                        return;
                    }

                    // Check Other Pending Applications in this Constituency
                    $existingInPhase = PhaseApplication::where('group_code', $value)
                        ->where('temp_application_id', '!=', $this->application->id) // Exclude current app
                        ->whereHas('reviewPhase', function ($query) {
                            $query->where('constituency_code', $this->constituency->constituency_code);
                        })
                        ->exists();

                    if ($existingInPhase) {
                        $gd=$this->groupCode+1;
                        $fail("The Group Code {$this->groupCode} is currently being used by another pending application. Try {$gd}");
                    }
                },
            ],
        ]);

    }

    public function assignToPhase()
    {
        // Access the property directly
        if (!$this->selectedPhaseId) return;

        $this->validate([
            'selectedPhaseId' => 'required|exists:loan_phases,id',
        ]);

        $this->selectedPhaseId = (int)$this->selectedPhaseId;

        try {
            $phase = $this->phaseService->getPhase($this->selectedPhaseId);

            $unassignedIds = [$this->application->id];
            $this->phaseService->bulkAssignApplications($phase, $unassignedIds);


            $this->appService->invalidateStatsCache($this->constituency->constituency_code);
            $this->reset('selectedPhaseId');
            $this->dispatch('flash-success', message: 'Applications assigned to phase successfully');

            $this->refreshData();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                    $this->dispatch('flash-error', message: $message);
                }

            }
        } catch (\Exception $e) {
            $this->dispatch('flash-error', message: $e->getMessage());
        }

    }

    public function confirmRemoveFromPhase()
    {
        try {
            //phaseApplication.reviewPhase
            if (is_null($this->application->phaseApplication)) return;
            $phase = $this->application->phaseApplication->reviewPhase;
            $applicationId = $this->application->phaseApplication->temp_application_id;

            $this->phaseService->removeApplication($phase, $applicationId);
            $this->appService->invalidateStatsCache($this->constituency->constituency_code);
            $this->dispatch('flash-success', message: 'Application removed from phase');
            $this->refreshData();

        } catch (ValidationException $e) {
            $this->dispatch('flash-error', message: collect($e->errors())->flatten()->first());
        } catch (\Exception $e) {
            $this->dispatch('flash-error', message: $e->getMessage());
        }
    }

    public function removeFromPhase()
    {
        $this->dispatch('confirm-action',
            title: 'Remove from Phase?',
            text: 'This will decouple the application from the current batch.',
            icon: 'warning',
            confirmText: 'Yes, Remove it!',
            componentId: $this->getId(),
            action: 'confirmRemoveFromPhase',
            // params: $this->application->id // Any data the method needs
        );
    }

    public function removeLink()
    {
        $this->dispatch('confirm-action',
            title: 'Remove link?',
            text: 'This will decouple the application from the current liked group.',
            icon: 'warning',
            confirmText: 'Yes, Remove it!',
            componentId: $this->getId(),
            action: 'confirmRemoveLink',
            // params: $this->application->id // Any data the method needs
        );
    }

    public function confirmRemoveLink()
    {
        $this->loadApplication();

        if (is_null($this->application->phaseApplication->group_id)) return;

        try {
            $this->appService->unLinkFromMasterGroup($this->application);
            $this->linkedGroup = null;

            $this->eligibility = [
                'min' => 50000,
                'max' => 100000,
                'balance' => 0,
                'can_borrow' => true
            ];
            $this->dispatch('flash-success', message: 'Application Successfully unlinked from master group');
            $this->refreshData();
        } catch (ValidationException $e) {
            $this->dispatch('flash-error', message: collect($e->errors())->flatten()->first());
        } catch (\Exception $e) {
            $this->dispatch('flash-error', message: $e->getMessage());
        }
        // $this->loadApplication();
    }

    #[Computed]
    public function activePhases()
    {
        if (!is_null($this->application->phaseApplication)) return;
        return $this->phaseService->getActivePhasesForConstituency(
            $this->constituency->constituency_code
        );
    }

    public function loadApplication()
    {
        $cacheKey = "app_enrichment_{$this->applicationId}";

        $this->application = Cache::remember($cacheKey, now()->addSeconds(10), function () {
            return $this->appService->getEnrichedApplication(
                $this->applicationId,
                $this->constituency->constituency_code
            );
        });

        //  dd($this->application->phaseApplication);
        $this->groupMembers = $this->application->groupMembers;

        //dd($this->application);
        $this->linkedGroup = null;

        // if a group is linked the load also its relations
        if (!is_null($this->application->phaseApplication)){
            if ($this->application->link_status === 'linked' && !is_null($this->application->phaseApplication->group_id)){

                $this->linkedGroup = $this->defaultService->getGroupWithMetrics($this->application->phaseApplication->linkedGroup);

                // dd($this->application->phaseApplication);
                $this->eligibility = $this->analysisService->getEligibilityData($this->linkedGroup);
                $this->groupCode = $this->linkedGroup->code;
            }
        }


    }

    public function generateGfs()
    {
        $gfs = $this->appService->generateGfsCode($this->application, $this->constituency);
        $this->groupCode = $gfs['groupCode'];
        $this->gfsCode = $gfs['gfscode'];

        if ($this->application->link_status === 'linked' && !is_null($this->application->phaseApplication?->group_id)){
            $this->groupCode = $this->linkedGroup->code;
        }
    }


    /**
     *  Attempt Link
     */
    public function findMatches()
    {
        $this->loadApplication();

        if ($this->application->phaseApplication === null){
            $this->dispatch('flash-error', message: "Assign application to a phase first");
            return;
        }
        $this->isSearching = true;

        $this->suggestedMatches = $this->appService->findMatchingGroups($this->application);

        $this->isSearching = false;

        $this->dispatch('open-modal', name: 'modal-group-linker');
    }


    public function confirmLink($groupId)
    {
        try {
            $this->loadApplication();

            $this->appService->linkToMasterGroup($this->application, $groupId);

            $this->refreshData();

            $this->dispatch('close-modal', name: 'modal-group-linker');
            $this->dispatch('flash-success', message: 'Group linked successfully!');

            $this->reset('searchTerm');
        } catch (ValidationException $e) {
            $this->dispatch('flash-error', message: collect($e->errors())->flatten()->first());
        } catch (\Exception $e) {
            $this->dispatch('flash-error', message: $e->getMessage());
        }
    }

    public function markAsNoMatch()
    {
        $this->application->update(['link_status' => 'attempted_no_match']);
        $this->refreshData();
        $this->dispatch('close-modal', name: 'modal-group-linker');
    }

    /**
     * Toggle between modes
     */
    public function toggleSearchMode()
    {
        $this->isManualSearch = !$this->isManualSearch;
        $this->searchTerm = ''; // Reset search
        if (!$this->isManualSearch) {
            $this->findMatches(); // Refresh automatic suggestions
        } else {
            $this->suggestedMatches = []; // Clear suggestions for manual start
        }
    }

    // public function updated($property, $value)
    // {
    //     dd($property, $value);
    //     // Log::info($property . ' was changed to ' . $value);
    // }

    // public function updating()
    // {
    //     dd('updating');
    // }


    /**
     * Perform manual search as the user types
     */
    public function updatedSearchTerm()
    {
        // $this->loadApplication();

        if (strlen($this->searchTerm) < 3) {
            $this->suggestedMatches = [];
            return;
        }

        // Call service but passing a manual search string
        $this->suggestedMatches = $this->appService->manualSearchGroups(
            $this->application,
            $this->searchTerm
        );
    }

    public function getProcessedExpenses()
    {
        $expenses = $this->application->expenses;

        // Handle JSON string
        if (is_string($expenses)) {
            $expenses = json_decode($expenses, true);
        }

        $processedExpenses = [];

        if (is_array($expenses)) {
            // Handle your specific structure: [{"cost_of_goods": 0, "rent": 1000}]
            if (isset($expenses[0]) && is_array($expenses[0])) {
                foreach ($expenses[0] as $name => $amount) {
                    if ($amount > 0) {
                        $processedExpenses[] = [
                            'name' => ucfirst(str_replace('_', ' ', $name)),
                            'amount' => $amount
                        ];
                    }
                }
            }
            // Handle standard structure: [{"name": "Rent", "amount": 1000}]
            else {
                foreach ($expenses as $expense) {
                    if (is_array($expense)) {
                        $processedExpenses[] = [
                            'name' => $expense['name'] ?? 'Expense',
                            'amount' => $expense['amount'] ?? 0
                        ];
                    }
                }
            }
        }

        return $processedExpenses;
    }

    public function syncApplication()
    {
        try {
            $this->application->update([
                'sync_status' => 'processing',
                'last_sync_at' => now(),
                'sync_attempts' => $this->application->sync_attempts + 1
            ]);

            // Your sync logic here
            // ...

            $this->application->update(['sync_status' => 'completed']);

            $this->dispatch('flash-success', message: 'Application synced successfully!');
            $this->loadApplication(); // Reload the application data
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to sync application: ' . $e->getMessage());
        }
    }

    public function approve ()
    {
        $this->loadApplication();
        $this->dispatch('open-modal', name: 'modal-approve');
    }

    public function confirmApproval()
    {
        // Preliminary check for balance
        if (!$this->eligibility['can_borrow']) {
            $this->addError('approvalAmount', "This group has an outstanding balance of Kes " . number_format($this->eligibility['balance']));
            return;
        }

        $rules = [
            'approvalAmount'   => "required|numeric|min:{$this->eligibility['min']}|max:{$this->eligibility['max']}",
            'approvalComments' => 'nullable|string|max:500',
            'selectedWardId'   => !$this->application->ward_id ? 'required' : 'nullable',
        ];

        if (!$this->application->phaseApplication->group_id) {
            $rules['groupCode'] = [
                'required',
                'numeric',
                'min:1',
                'regex:/^\d{3,}$/',
                function ($attribute, $value, $fail) {
                    // A. Check Master Groups in this Constituency
                    $existingInMaster = Group::whereHas('ward', function ($query) {
                        $query->where('constituency_code', $this->constituency->constituency_code);
                    })
                    ->where('code', $value) // Use $value from the closure
                    ->exists();

                    if ($existingInMaster) {
                        $fail("The Group Code {$value} is already registered to another group in this constituency.");
                        return;
                    }

                    // B. Check Other Pending Applications in this Constituency

                    $existingInPhase = PhaseApplication::where('group_code', $value)
                        ->where('temp_application_id', '!=', $this->application->id) // Exclude current app
                        ->whereHas('reviewPhase', function ($query) {
                            $query->where('constituency_code', $this->constituency->constituency_code);
                        })
                        ->exists();

                    if ($existingInPhase) {
                        $gd=$this->groupCode+1;
                        $fail("The Group Code {$value} is reserved by another pending application.Try {$gd}");
                    }
                },
            ];
        }

        $this->validate($rules, [
            'selectedWardId.required' => "You need to select a ward",
            'approvalAmount.max' => "The maximum allowed loan is KES " . number_format($this->eligibility['max']),
            'approvalAmount.min' => "Approved amount cannot be less than kes 50,000.00",
        ]);

        if (!$this->application->phaseApplication) {
            $this->addError('approvalAmount', "This application must be assigned to a Disbursement Phase before approval.");
            return;
        }

        if (!in_array($this->application->link_status, ['attempted_no_match', 'linked'])) {
            $this->addError('approvalAmount', "You must attempt to link this application to a group (or confirm it is a new group) before approval.");
            return;
        }

        // dd('approve');
        // $this->dispatch('close-modal', name: 'modal-approve');
        $groupName = strtoupper($this->application->group_name);
        $formattedAmount = number_format($this->approvalAmount, 2);
        $fullGfsCode = $this->gfsCode . $this->groupCode; // The generated code


        $this->dispatch('confirm-approve-action',
            title       : 'Confirm Approval',
            html        :  "<div class='text-left px-3'>
                                <p class='mb-2'>You are approving an application for:</p>
                                <h6 class='text-primary mb-3'><strong>{$groupName}</strong></h6>
                                <table class='table table-sm table-bordered'>
                                    <tr class='bg-light'>
                                        <td><strong>Approved Amount:</strong></td>
                                        <td class='text-success'><strong>KES {$formattedAmount}</strong></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Group Code:</strong></td>
                                        <td>{$this->groupCode}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>GFS Code:</strong></td>
                                        <td><small>{$fullGfsCode}</small></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Phase:</strong></td>
                                        <td>{$this->application->phaseApplication->reviewPhase->phase}</td>
                                    </tr>
                                </table>
                            </div>",
            icon        : 'question',
            confirmText : 'Yes, Approve Now',
            componentId : $this->getId(),
            action      : 'submitApproval',
            params      : null
        );
    }

    public function submitApproval()
    {

        try {
            DB::transaction(function () {
                $this->application->update([
                    'status'           => 'under_hq_review',
                    'ward_id'          => $this->selectedWardId ?? $this->application->ward_id,
                    'risk_level'       => $this->assessmentData['overall_category'],
                    'assessment_data'  => $this->assessmentData,
                ]);


                $this->application->phaseApplication->update([
                    'temp_application_id' => $this->application->id,
                    'status'              => 'pending_hq_approval',
                    'user_id'             => auth()->id(),
                    'level'               => 'constituency',
                    'action'              => 'recommended_for_approval',
                    'amount_approved'     => $this->approvalAmount,
                    'comments'             => $this->approvalComments,
                    'group_code'       => $this->groupCode ?? $this->application->phaseApplication->group_code,
                    'gfs_code'         => $this->gfsCode.$this->groupCode ?? $this->application->phaseApplication->group_code,
                ]);

                activity()
                    ->performedOn($this->application)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'amount'     => $this->approvalAmount,
                        'risk_level' => $this->assessmentData['overall_category'],
                        'ip_address' => request()->ip()
                    ])
                    ->log("Recommended application for HQ review.");

            });

            $nextApp = PhaseApplication::select(['id', 'loan_phase_id', 'temp_application_id', 'status', 'amount_applied'])
                ->with(['tempApplication' => function ($query) {
                    $query->select(['id', 'group_name', 'application_id']); // Include PK/FK for the link to work
                }])
                ->where('loan_phase_id', $this->application->phaseApplication->loan_phase_id)
                ->where('status', 'pending')
                ->where('id', '!=', $this->application->phaseApplication->id)
                ->first();

            // dd($nextApp);

            $this->reset(['approvalAmount', 'approvalComments', 'groupCode', 'selectedWardId']);

            $this->dispatch('flash-success', message: 'Applications approved successfully');

            $this->dispatch('approval-completed',
                message: 'Application approved successfully!',
                nextId: $nextApp ? $nextApp->temp_application_id : null,
                isPhaseComplete: is_null($nextApp),
                // Pass the summary details for the next application
                nextSummary: $nextApp ? [
                    'group_name' => $nextApp->tempApplication->group_name,
                    'app_id' =>   $nextApp->tempApplication->application_id,
                    'amount' =>   number_format($nextApp->amount_applied, 2)
                ] : null
            );

            $this->refreshData();

        } catch (\Exception $e) {
            Log::error("Constituency Approval Error: " . $e->getMessage());
            $this->addError('approvalAmount', "Internal error. Please check logs.");
        }
    }

    public function confirmApprove()
    {
        // Your approval logic here
        $this->application->update(['status' => 'approved']);
        $this->dispatch('flash-success', message: 'Application approved successfully!');
        $this->actionTaken = true;
    }

    public function reject()
    {
        // Your rejection logic here
        $this->application->update(['status' => 'rejected', 'reviewer_remarks' => $this->remarks]);
        $this->dispatch('flash-error', message: 'Application rejected.');
        $this->actionTaken = true;
    }

    public function linkGroup()
    {
        // Trigger modal or logic to link group (as per your earlier plan)
        $this->dispatch('openLinker', id: $this->application->id);
    }

    #[Computed]
    public function riskAssessment(): array
    {
        $group = $this->linkedGroup ?? null;

        $this->assessmentData = (array) $this->defaultService->calculateGroupRiskLevel(
            $group,
            $this->application->groupMembers
        );
        return $this->assessmentData;
    }

    #[Computed]
    public function riskFlags()
    {
        return $this->riskAssessment['reasons'] ?? [];
    }

    #[Computed]
    public function riskFlagsCount()
    {
        return count($this->riskFlags);
    }

    #[Computed]
    public function riskCategory(): string
    {
        return $this->riskAssessment['overall_category'] ?? 'Low Risk';
    }

    public function getMemberConflictCountProperty()
    {
        if (!$this->application || !$this->application->groupMembers) {
            return 0;
        }
        return $this->application->groupMembers->where('is_defaulter', true)->count();
    }


    public function viewMember($memberId)
    {
        $this->loadApplication();
        // Find the member within the already loaded collection
        $this->selectedMember = $this->application->groupMembers
            ->where('id', $memberId)
            ->first();

        // 2. Dispatch the event to open the modal
        $this->dispatch('open-modal', name: 'memberModal');
    }

    public function rendered ()
    {
        //dd('rendered');
    }

    public function render()
    {
        return view('livewire.loan.app.application-view', [
            'processedExpenses' => $this->appService->getProcessedExpenses($this->application),
            'processedFiles'    => $this->appService->getProcessedFiles(
                $this->application,
                $this->county->slug ?? $this->constituency->county->slug,
                $this->constituency->slug
            ),
            'groupOfficials' => $this->appService->getGroupOfficials($this->application),
            'activePhases' => $this->activePhases(),
        ]);
    }
}
