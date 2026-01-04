<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TempApplication;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use App\Services\Loan\LoanPhaseService;
use App\Services\Loan\TempApplicationService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use App\Services\Loan\DefaultAnalysisService;
use App\Models\Member;
use App\Models\Group;

class ApplicationView extends Component
{
    protected LoanPhaseService $phaseService;
    protected TempApplicationService $appService;
    protected DefaultAnalysisService $defaultService;

    public $isDataLoaded = false;

    public $applicationId;
    public $county;
    public $constituency;
    public $application;
    public $defaultMembers;

    // links
    public $suggestedMatches = [];
    public $isSearching = false;
    public ?Group $linkedGroup;

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

    public function boot(LoanPhaseService $phaseService, TempApplicationService $appService, DefaultAnalysisService $defaultService)
    {
        $this->phaseService = $phaseService;
        $this->appService = $appService;
        $this->defaultService = $defaultService;
    }

    public function mount($constituency, $applicationId)
    {
        $this->constituency = $constituency->load('county');
        $this->county = $this->constituency->county;
        $this->applicationId = $applicationId;

        $this->linkedGroup = null;

        $this->defaultMembers = [];

        $this->loadApplication();
        $this->isDataLoaded = true;
    }


    public function refreshData()
    {
        unset($this->riskFlags);
        unset($this->riskFlagsCount);
        unset($this->activePhases);

        $this->appService->invalidateApplicationCache($this->application);
        $this->loadApplication();

    }

    public function updatedActiveTab($value)
    {
        $this->loadApplication();
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
                }
                $this->dispatch('flash-error', message: 'Please correct the errors in the form.');
            }
        } catch (\Exception $e) {
            $this->dispatch('flash-error', message: $e->getMessage());
        }

    }

    public function removeFromPhase()
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

    public function removeLink()
    {
        if (is_null($this->application->group_id)) return;

        try {
            $this->appService->unLinkFromMasterGroup($this->application);
            $this->linkedGroup = null;
            $this->dispatch('flash-success', message: 'Application Successfully unlinked from master group');
            $this->refreshData();
        } catch (ValidationException $e) {
            $this->dispatch('flash-error', message: collect($e->errors())->flatten()->first());
        } catch (\Exception $e) {
            $this->dispatch('flash-error', message: $e->getMessage());
        }
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
        $this->application = $this->appService->getEnrichedApplication(
            $this->applicationId,
            $this->constituency->constituency_code
        );

        $this->linkedGroup = null;

        // if a group is linked the load also its relations
        if ($this->application->link_status === 'linked')
            $this->linkedGroup = $this->defaultService->getGroupWithMetrics($this->application->linkedGroup);

        //if (isset(($this->application->groupMembers->first()->is_defaulter))) dd('set', $this->application->groupMembers->first()->is_defaulter);
        if ($this->application->groupMembers->first() && !isset($this->application->groupMembers->first()->is_defaulter)) {
            $this->loadDefaultMembers(); // Manually re-run the hydration logic
        }
    }


    /**
     *  Attempt Link
     */
    public function findMatches()
    {
        $this->isSearching = true;

        $this->suggestedMatches = $this->appService->findMatchingGroups($this->application);

        $this->isSearching = false;

        $this->dispatch('open-modal', name: 'modal-group-linker');
    }

    public function confirmLink($groupId)
    {
        try {
            unset($this->riskFlags);
            unset($this->riskFlagsCount);

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

    /**
     * Perform manual search as the user types
     */
    public function updatedSearchTerm()
    {
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

    public function approve()
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
    public function riskFlags()
    {
        // Only show if group is linked
        if (!$this->application->group_id || $this->linkedGroup === null) {
            return ['Group not linked - cannot assess risk.'];
        }

        $flags = [];
        $group = $this->linkedGroup;
        //dd($group);
        if ($group->currentLoan->is_defaulted) {
            $flags[] = "Group is a defaulter: {$group->defaulted_count} defaulted loans, KES " .
                       number_format($group->total_arrears) . " in arrears.";
        }

        $serviceRisks = $this->defaultService->getRiskFlags($group);


        // // 3. Cross-default members (members in other defaulted groups)
        // // Cross-default members (optimized: only check application members)
        // $appMemberIds = $this->application->groupMembers->pluck('id_number')->toArray();
        // $crossDefaults = 0;// $this->defaultService->getCrossDefaultMembers($appMemberIds, $group->id);

        // $crossCount = 0;//$crossDefaults->count();
        // if ($crossCount > 0) {
        //     $flags[] = "$crossCount member(s) have defaulted loans in other groups.";
        // }

        // return $flags;

        // // Optional: Add more flags (e.g. overdue loans not defaulted yet)
        // $overdueLoans = $group->currentLoan()->where('total_arrears', '>', 0)->where('is_defaulted', false)->count();
        // if ($overdueLoans > 0) {
        //     $flags[] = "$overdueLoans overdue loan(s) (not yet defaulted).";
        // }

        return array_merge($flags, $serviceRisks);
    }

    #[Computed]
    public function riskFlagsCount()
    {
        return count($this->riskFlags);
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
        // Find the member within the already loaded collection
        $this->selectedMember = $this->application->groupMembers
            ->where('id', $memberId)
            ->first();

        // 2. Dispatch the event to open the modal
        $this->dispatchBrowserEvent('open-modal', ['id' => '#memberModal']);
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
