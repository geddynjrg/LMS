<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\Loan\LoanPhaseService;
use App\Services\Loan\TempApplicationService;
use App\Services\Loan\LoanAnalysisService;
use App\Services\Loan\LoanRecommendationService;
use App\Services\PathwayPrescriptionService;
use App\Services\Loan\WardAllocationService;
use App\Services\Loan\DefaultAnalysisService;
use App\Services\Ml\RepaymentScoreClient;
use App\Services\IdempotencyService;
use App\Exports\ApplicationInstitutionalDataExport;
use App\Jobs\SyncApplicationStatusToDrupal;
use App\Models\GroupLoan;
use App\Models\Member;
use App\Models\Group;
use App\Models\TempApplication;
use App\Models\TempGroupMember;
use App\Models\PhaseApplication;
use App\Services\Loan\TrainingCertificateService;
use App\Services\SmsService;
use App\Livewire\Concerns\ManagesApplicationReviewWorkflow;
use App\Livewire\Concerns\ManagesPhaseDisbursement;
use App\Livewire\Concerns\ManagesApplicationDetailEditing;
use Exception;
use Mpdf\Tag\Th;
use Throwable;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;


class ApplicationView extends Component
{
    use ManagesPhaseDisbursement;
    use ManagesApplicationReviewWorkflow;
    use ManagesApplicationDetailEditing;

    public $selectedAppId;
    public $selectedGroupId;
    public $app_amount_issued;
    public $app_approved_amount;
    public $app_cheque_number;
    public $app_group_identifier;
    public $app_disbursement_comment;
    public $hasExistingIdentifier = false;

    protected LoanPhaseService $phaseService;
    protected TempApplicationService $appService;
    protected DefaultAnalysisService $defaultService;
    protected LoanAnalysisService $analysisService;
    protected LoanRecommendationService $recommendationService;
    protected PathwayPrescriptionService $pathwayPrescription;
    protected WardAllocationService $wardAllocationService;
    protected IdempotencyService $idempotencyService;

    public $isDataLoaded = false;

    public $applicationId;
    public $county;
    public $constituency;
    public $defaultMembers;
    public $groupMembers;

    // links
    public $suggestedMatches = [];
    public $isSearching = false;
    public ?PhaseApplication $phaseApplication;

    public $selectedPhaseForQuickAssign = '';
    public $selectedPhaseId;
    public $isManualSearch = false;
    public $searchTerm = '';

    #[Url(except: 'all-details')]
    public $activeTab = 'all-details';

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

    // ward
    public $selectedWardId;

    // next application summary
    public $nextSummary = null;
    public $phaseCompleted = false;
    public $submissionUrl;

    public $reviewType; // 'approval' or 'rejection'
    public $rejectionReason;
    public $reviewingApplicationId;

    public $selectedRejectionKey;
    public $rejectionComment;

    /** Officer comments when returning an application to the group for correction. */
    public $returnToGroupComment;

    public $rejectionReasons = [
        'incomplete_information' => 'Incomplete or incorrect information',
        'missing_documents'      => 'Required documents missing or invalid',
        'eligibility_criteria'   => 'Does not meet eligibility criteria',
        'duplicate_application'  => 'Duplicate application',
        'insufficient_collateral'=> 'Insufficient collateral',
        'poor_credit_history'    => 'Poor credit history',
        'member_declined'        => 'Group member(s) declined participation',
        'other'                  => 'Other reason',
    ];

    public $idempotencyKey;

    public $payload = [];

    public function boot(
        LoanPhaseService $phaseService,
        LoanAnalysisService $analysisService,
        LoanRecommendationService $recommendationService,
        PathwayPrescriptionService $pathwayPrescription,
        WardAllocationService $wardAllocationService,
        TempApplicationService $appService,
        DefaultAnalysisService $defaultService,
        IdempotencyService $idempotencyService
        )
    {
        $this->phaseService = $phaseService;
        $this->appService = $appService;
        $this->defaultService = $defaultService;
        $this->analysisService = $analysisService;
        $this->recommendationService = $recommendationService;
        $this->pathwayPrescription = $pathwayPrescription;
        $this->wardAllocationService = $wardAllocationService;
        $this->idempotencyService = $idempotencyService;
    }

    public function mount($constituency, $applicationId)
    {
        $this->constituency = $constituency->load(['county', 'wards']);

        $this->county = $this->constituency->county;

        $this->applicationId = $applicationId;

        $this->linkedGroup = null;

        $this->idempotencyKey = "application_action_{$applicationId}";

        $this->defaultMembers = [];

        $this->groupMembers = null;
    }

    #[Computed]
    public function activePhases()
    {
        if (!is_null($this->application->phaseApplication)) return;
        return $this->phaseService->getActivePhasesForConstituency(
            $this->constituency->constituency_code
        );
    }

    #[Computed]
    public function application()
    {
        try {
            return $this->appService->getEnrichedApplication(
                $this->applicationId,
                $this->constituency->constituency_code
            );
        } catch (\Throwable $th) {
            abort(404, 'Application not found.');
        }
    }

    public function canDownloadTrainingCertificate(): bool
    {
        $phaseApplication = $this->application->phaseApplication;

        if (!$phaseApplication) {
            return false;
        }

        return app(TrainingCertificateService::class)
            ->isOfficerCertificateAvailable($phaseApplication);
    }

    public function trainingCertificateNotice(): ?string
    {
        $phaseApplication = $this->application->phaseApplication;

        if (!$phaseApplication?->is_trained || $this->canDownloadTrainingCertificate()) {
            return null;
        }

        $phaseApplication->loadMissing('loanPhase');

        return app(TrainingCertificateService::class)
            ->certificateAvailabilityMessage($phaseApplication->loanPhase, $phaseApplication);
    }

    public function canShowDisbursementActions(): bool
    {
        $phaseApplication = $this->application->phaseApplication;
        $phase = $phaseApplication?->loanPhase;

        return $phaseApplication && $phase && $this->canMarkDisbursed($phaseApplication, $phase);
    }

    public function openMarkDisbursedModal(): void
    {
        $phaseApplication = $this->application->phaseApplication;
        if (!$phaseApplication || !$phaseApplication->loanPhase?->canManageDisbursementBy(auth()->user())) {
            return;
        }

        $this->resetErrorBag(['app_amount_issued', 'app_cheque_number', 'app_group_identifier', 'app_disbursement_comment']);
        $phaseApplication->loadMissing(['linkedGroup:id,name,code,identifier', 'loanPhase']);
        $this->selectedAppId = $phaseApplication->id;
        $group = $phaseApplication->linkedGroup;
        $this->selectedGroupId = $group?->id;
        $this->app_approved_amount = $phaseApplication->amount_approved;
        $this->app_amount_issued = $phaseApplication->amount_approved;
        if ($group?->identifier) {
            $this->app_group_identifier = $group->identifier;
            $this->hasExistingIdentifier = true;
        } elseif ($group) {
            $this->app_group_identifier = $this->appService->generateIdentifier($group->name, $group->code);
            $this->hasExistingIdentifier = false;
        }
        $this->dispatch('open-modal', name: 'app-view-disburse-modal');
    }

    public function openNotDisbursedModal(): void
    {
        $phaseApplication = $this->application->phaseApplication;
        if (!$phaseApplication) {
            return;
        }
        $this->selectedAppId = $phaseApplication->id;
        $this->reset('disbursementFailureReason', 'disbursementFailureNotes');
        $this->dispatch('open-modal', name: 'app-view-not-disbursed-modal');
    }

    public function processDisbursementFromView()
    {
        $phaseApplication = $this->application->phaseApplication;
        if (!$phaseApplication?->loanPhase?->canManageDisbursementBy(auth()->user())) {
            return;
        }

        $this->validate([
            'app_amount_issued' => ['required', 'numeric', 'min:50000', "max:{$this->app_approved_amount}"],
            'app_cheque_number' => 'required|string|max:50',
            'app_group_identifier' => 'required|string|min:6',
            'app_disbursement_comment' => [
                Rule::requiredIf($this->app_approved_amount != $this->app_amount_issued),
                'nullable', 'string', 'min:20',
            ],
        ]);

        $idempotencyKey = "application_action_{$this->selectedAppId}";
        if (!$this->idempotencyService->acquire($idempotencyKey, ['action' => 'disburse'])) {
            return;
        }

        try {
            $this->appService->approveApplicationDisbursement($this->selectedAppId, $idempotencyKey, [
                'app_amount_issued' => $this->app_amount_issued,
                'app_cheque_number' => $this->app_cheque_number,
                'app_group_identifier' => $this->app_group_identifier,
                'app_disbursement_comment' => $this->app_disbursement_comment,
            ]);
            unset($this->application);
            $this->dispatch('close-modal', name: 'app-view-disburse-modal');
            $this->dispatch('swal:toast', title: 'Disbursed', message: 'Recorded as disbursed. Loans portal will update on next sync.', type: 'success');
        } catch (\Exception $e) {
            $this->idempotencyService->release($idempotencyKey);
            $this->dispatch('swal:toast', title: 'Error', message: $e->getMessage(), type: 'error');
        }
    }

    public function processNotDisbursedFromView()
    {
        $phaseApplication = $this->application->phaseApplication;
        if (!$phaseApplication?->loanPhase?->canManageDisbursementBy(auth()->user())) {
            return;
        }

        $this->validate([
            'disbursementFailureReason' => ['required', Rule::in(array_column($this->disbursementFailureReasonOptions(), 'id'))],
            'disbursementFailureNotes' => ['required', 'string', 'min:20', 'max:500'],
        ]);

        $idempotencyKey = "application_action_{$this->selectedAppId}";
        if (!$this->idempotencyService->acquire($idempotencyKey, ['action' => 'not_disbursed'])) {
            return;
        }

        try {
            $this->appService->recordDisbursementFailure($this->selectedAppId, $idempotencyKey, [
                'failure_reason' => $this->disbursementFailureReason,
                'failure_notes' => $this->disbursementFailureNotes,
            ]);
            unset($this->application);
            $this->dispatch('close-modal', name: 'app-view-not-disbursed-modal');
            $this->dispatch('swal:toast', title: 'Recorded', message: 'Marked as not disbursed.', type: 'success');
        } catch (\Exception $e) {
            $this->idempotencyService->release($idempotencyKey);
            $this->dispatch('swal:toast', title: 'Error', message: $e->getMessage(), type: 'error');
        }
    }


    #[Computed]
    public function eligibility()
    {
        return $this->analysisService->getEligibilityData(
            $this->application->phaseApplication?->linkedGroup,
            (int) round((float) ($this->application->loan_amount ?? 0))
        );
    }

    #[Computed]
    public function loanRecommendation()
    {
        return $this->pathwayPrescription->prescribe(
            $this->application,
            $this->eligibility
        );
    }

    #[Computed]
    public function pathwayPrescription()
    {
        return $this->loanRecommendation;
    }

    #[Computed]
    public function wardAllocationSummary()
    {
        $phase = $this->application->phaseApplication?->reviewPhase;

        if (! $phase) {
            return null;
        }

        return $this->wardAllocationService->summarisePhase($phase);
    }

    #[Computed]
    public function groupMembers()
    {
        return $this->application->groupMembers;
    }

    #[Computed]
    public function linkedGroup()
    {
        return $this->application->phaseApplication?->linkedGroup;
    }

    #[Computed]
    public function processedExpenses()
    {
        return $this->appService->getProcessedExpenses($this->application);
    }

    #[Computed]
    public function processedFiles()
    {
        return $this->appService->getProcessedFiles(
            $this->application,
            $this->county->slug ?? $this->constituency->county->slug,
            $this->constituency->slug
        );
    }

    private function isAppActionProcessing($payload) : bool
    {
        return ($this->idempotencyService->acquire($this->idempotencyKey, $payload));
    }


    public function refreshData()
    {
        $this->appService->forgetApplication($this->applicationId);
        unset($this->application);
    }

    // fns
    public function confirmRemoveFromPhase()
    {

        try {
            //phaseApplication.reviewPhase
            if (is_null($this->application->phaseApplication)) return;

            $phase = $this->application->phaseApplication->reviewPhase;

            $applicationId = $this->application->phaseApplication->temp_application_id;

            $this->phaseService->removeApplication($phase, $applicationId);

            $this->appService->invalidateStatsCache($this->constituency->constituency_code);

            $this->dispatch('close-modal', name: 'modal-remove-from-phase');

            $this->dispatch('swal:toast',
                title: 'Phase Unassigned 😊',
                message: 'Application removed from phase.',
                type: 'success'
            );

            $this->refreshData();

        } catch (ValidationException $e) {
            $this->dispatch('swal:toast',
                title: 'Phase Assigned',
                message: collect($e->errors())->flatten()->first(),
                type: 'error'
            );
        } catch (\Exception $e) {
            $this->dispatch('swal:toast',
                title: 'Phase Assigned',
                message: $e->getMessage(),
                type: 'error'
            );
        }
    }

        /**
     *  Attempt Link
     */
    public function findMatches()
    {
        if ($this->application->phaseApplication === null){
            $this->dispatch('swal:toast',
                title: 'Find Match',
                message: 'Assign application to a phase first.',
                type: 'error'
            );
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

            $this->appService->linkToMasterGroup($this->application, $groupId);

            $this->refreshData();

            $this->reset('searchTerm');

            $this->dispatch('close-modal', name: 'modal-group-linker');

            $this->dispatch('swal:toast',
                title: 'Group Link 🤩',
                message: 'Group linked successfully!',
                type: 'success'
            );

            $this->dispatch('app-updated')->to(SideNavBadge::class);

        } catch (ValidationException $e) {
            $this->dispatch('swal:toast',
                title: 'Group Link Error 😕',
                message: collect($e->errors())->flatten()->first(),
                type: 'error'
            );
        } catch (\Exception $e) {
            Log::error("Group Link Failed: ", [
                $e->getMessage(),
            ]);
            $this->dispatch('swal:toast',
                title: 'Group Link Error 😕',
                message: 'Oops! Linking failed.',
                type: 'error'
            );
        }
    }

    public function markAsNoMatch()
    {
        $this->appService->markAsNoMatch($this->application);

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

    public function getStatusOptionsProperty()
    {
        return $this->appService->getConsStatusOptionsProperty();
    }

    public function getStatusLabel($value)
    {
        return collect($this->statusOptions)->firstWhere('value', $value)['name'] ?? $value;
    }

    public function getStatusClasses($status)
    {
        return $this->appService->getStatusClasses($status);
    }

    public function getFormattedRejectionReasonsProperty()
    {
        return collect($this->rejectionReasons)->map(function ($label, $key) {
            return [
                'id' => $key,
                'name' => $label,
                'color' => '#dc3545'
            ];
        })->values()->toArray();
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
            $this->dispatch('swal:toast',
                title: 'Phase Assigned',
                message: 'Applications assigned to phase successfully.',
                type: 'success'
            );
            $this->refreshData();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                    $this->dispatch('swal:toast',
                        title: 'Phase Assigned',
                        message: $message,
                        type: 'error'
                    );
                }

            }
        } catch (\Exception $e) {
            $this->dispatch('swal:toast',
                title: 'Phase Assigned',
                message: $e->getMessage(),
                type: 'error'
            );
        }

    }

    public function removeFromPhase()
    {
        $this->dispatch('open-modal', name: 'modal-remove-from-phase');
    }

    public function removeLink()
    {
        $this->dispatch('open-modal', name: 'modal-unlink-confirm');
    }

    public function confirmRemoveLink()
    {
        if (is_null($this->application->phaseApplication->group_id)) return;

        try {
            $this->appService->unLinkFromMasterGroup($this->application);
            $this->linkedGroup = null;

            // $this->eligibility = [
            //     'min' => 50000,
            //     'max' => 100000,
            //     'balance' => 0,
            //     'can_borrow' => true
            // ];

            $this->dispatch('app-updated')->to(SideNavBadge::class);

            $this->dispatch('close-modal', name: 'modal-unlink-confirm');

            $this->dispatch('swal:toast',
                title: 'Group Unlink 😊',
                message: 'Application Successfully unlinked from master group.',
                type: 'success'
            );

            $this->reset(['groupCode', 'gfsCode']);

            $this->refreshData();
        } catch (ValidationException $e) {
            $this->dispatch('swal:toast',
                title: 'Group Unlink Error 😟',
                message: collect($e->errors())->flatten()->first(),
                type: 'Error'
            );
        } catch (\Exception $e) {
            Log::error("Group Unlink Error: ", [
                $e->getMessage()
            ]);
            $this->dispatch('swal:toast',
                title: 'Group Unlink Error 😟',
                message: $e->getMessage(),
                type: 'Error'
            );
        }
    }

    public function generateGfs()
    {
        $gfs = $this->appService->generateGfsCode($this->application, $this->constituency);
        $this->groupCode = $gfs['groupCode'];
        $this->gfsCode = $gfs['gfscode'];

        if ($this->application->link_status === 'linked' && !is_null($this->application->phaseApplication?->group_id)) {
            $this->groupCode = $this->linkedGroup?->code;
        }
    }

    #[Computed]
    public function appGeneratedGfs()
    {
        $gfs = $this->appService->generateGfsCode($this->application, $this->constituency);

        if ($this->application->link_status === 'linked' && !is_null($this->application->phaseApplication?->group_id)) {
            $gfs['groupCode'] = $this->linkedGroup?->code;
        }
        return $gfs;
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
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to sync application: ' . $e->getMessage());
        }
    }

    public function approve()
    {
        $this->resetErrorBag();

        $this->generateGfs();

        $recommendation = $this->loanRecommendation;
        $amount = (int) round((float) ($recommendation['recommended_amount'] > 0
            ? $recommendation['recommended_amount']
            : ($this->application->loan_amount ?? 0)));
        $this->approvalAmount = $amount;

        // Open after Livewire morph completes; wire:ignore.self on the modal shell
        // prevents morph from updating Bootstrap's show state, so we sync via JS.
        $this->js("
            setTimeout(function () {
                var modal = document.getElementById('modal-approve');
                if (!modal) return;
                var input = modal.querySelector('[data-approval-amount]');
                if (input) {
                    input.value = {$amount};
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                }
                $(modal).modal('show');
            }, 100);
        ");
    }

    public function confirmApproval()
    {
        $recommendation = $this->loanRecommendation;

        if ($recommendation['should_decline']) {
            $this->addError('approvalAmount', $recommendation['explanation']);

            return;
        }

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

        $this->submitApproval();

    }

    public function submitApproval()
    {
        $wardName = '';
        if (empty($this->application->ward_id)) {
            $wardName = $this->constituency->wards->where('id', $this->selectedWardId)->first()->ward_name ?? 'N/A';
        }

        $result = false;
        try {
            $result = DB::transaction(function (){
                $this->application->update([
                    'ward_id'          => $this->selectedWardId ?? $this->application->ward_id,
                    'ward_name'        => empty($this->application->ward_id) ? $this->constituency->wards->where('id', $this->selectedWardId)->first()->ward_name ?? 'N/A' : $this->application->ward_name,
                    'risk_level'       => $this->riskAssessmentData['overall_category'],
                    'assessment_data'  => $this->riskAssessmentData,
                    'status'           => 'under_constituency_review',
                    'lms_to_loan_app_sync_status' => 'pending',
                ]);

                $this->application->phaseApplication->update([
                    'temp_application_id' => $this->application->id,
                    'status'              => 'pending_submission_to_hq',
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
                        'risk_level' => $this->riskAssessmentData['overall_category'],
                        'ip_address' => request()->ip()
                    ])
                    ->log("Recommended application for HQ review.");

                SyncApplicationStatusToDrupal::pushAfterCommit($this->application->id);

                return true;
            });
        } catch (\Exception $e) {
            Log::error("Constituency Approval Error: " . $e->getMessage());
            dd($e->getMessage());
            $this->dispatch('swal:toast',
                title: 'Error Approving Application 😕',
                message: 'Internal Server Error',
                type: 'error'
            );
        }

        if ($result) {
            $this->reset(['approvalAmount', 'approvalComments', 'groupCode', 'selectedWardId']);

            $this->dispatch('close-modal', name: 'modal-approve');

            $this->dispatch('swal:toast',
                title: 'Approval 🤩',
                message: 'Applications approved successfully',
                type: 'success'
            );

            $this->refreshData();

            $this->navigateToNextAfterReview();
        }
    }

    public function notify($type, $title, $message)
    {
        $this->dispatch('swal:toast', [
            'type'    => $type, // 'success', 'error', 'warning', 'info'
            'title'   => $title,
            'message' => $message,
        ]);
    }

    public function rejectApplication()
    {
        $this->reset(['selectedRejectionKey', 'rejectionComment']);

        $this->resetErrorBag();

        $this->dispatch('open-modal', name: 'modal-reject-application');
    }

    public function returnToGroup()
    {
        $this->reset(['returnToGroupComment']);
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'modal-return-to-group');
    }

    public function confirmReturnToGroup(SmsService $smsService)
    {
        if (($this->application->phaseApplication->status ?? null) !== 'pending') {
            $this->notify('error', 'Invalid Action', 'Only applications awaiting constituency decision can be returned to the group.');
            return;
        }

        $this->validate([
            'returnToGroupComment' => [
                'required',
                'string',
                'min:20',
                'max:500',
                function ($attribute, $value, $fail) {
                    $cleanValue = strtolower(str_replace(' ', '', $value));
                    if (preg_match('/(.)\1{3,}/', $cleanValue)) {
                        $fail('The explanation contains too many repetitive characters. Please provide a real reason.');
                    }

                    $uniqueChars = count(count_chars($cleanValue, 1));
                    if ($uniqueChars < 6) {
                        $fail('This reason looks too generic. Please provide more specific details.');
                    }
                    $words = explode(' ', strtolower(trim($value)));
                    $uniqueWords = array_unique($words);
                    if (count($words) > 4 && (count($uniqueWords) / count($words)) < 0.5) {
                        $fail('Please provide a constructive sentence rather than repeating words.');
                    }
                },
            ],
        ], [
            'returnToGroupComment.required' => 'You must tell the group what to correct.',
            'returnToGroupComment.min' => 'Comments must be at least 20 characters.',
        ]);

        try {
            $result = DB::transaction(function () {
                $phaseApplication = PhaseApplication::where('temp_application_id', $this->application->id)->first();

                if (!$phaseApplication || $phaseApplication->status !== 'pending') {
                    return false;
                }

                $phaseApplication->update([
                    'status' => 'deferred',
                    'user_id' => auth()->id(),
                    'level' => 'constituency',
                    'action' => 'returned_to_applicant',
                    'rejection_reason' => 'incomplete_information',
                    'rejection_message' => $this->returnToGroupComment,
                    'comments' => $this->returnToGroupComment,
                ]);

                $this->application->update([
                    'status' => 'draft',
                    'lms_to_loan_app_sync_status' => 'pending',
                ]);

                activity()
                    ->performedOn($phaseApplication)
                    ->causedBy(auth()->user())
                    ->createdAt(now())
                    ->withProperties([
                        'type' => 'application_returned_to_group',
                        'attributes' => [
                            'status' => 'draft',
                            'comment' => $this->returnToGroupComment,
                            'application_number' => $this->application->application_id,
                        ],
                        'context' => [
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                        ],
                    ])
                    ->log('Application returned to group for correction.');

                SyncApplicationStatusToDrupal::pushAfterCommit($this->application->id);

                return true;
            });
        } catch (Exception $e) {
            Log::error('Return to group failed', [
                'application_id' => $this->application->application_id ?? null,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('swal:toast',
                title: 'Invalid Action 😕',
                message: 'Internal Server Error.',
                type: 'error'
            );
            return;
        }

        if (!$result) {
            $this->notify('error', 'Invalid Application', 'ERROR: You have no access to this application.');
            return;
        }

        $smsSent = $this->notifyChairpersonOfReturn($smsService, $this->returnToGroupComment);

        $this->reset(['returnToGroupComment']);
        $this->resetErrorBag();
        $this->dispatch('close-modal', name: 'modal-return-to-group');

        $this->notify(
            'success',
            'Returned to group',
            $smsSent
                ? 'Application marked as draft. An SMS with your comments was sent to the group contact.'
                : 'Application marked as draft. SMS could not be sent — share your comments with the group directly.'
        );

        $this->navigateToNextAfterReview();
        $this->refreshData();
    }

    protected function notifyChairpersonOfReturn(SmsService $smsService, string $comments): bool
    {
        $this->application->loadMissing('groupMembers');
        $members = $this->application->groupMembers ?? collect();
        $officials = $this->appService->getGroupOfficials($this->application);

        $candidates = [
            $officials['chairperson'] ?? null,
            $members->first(fn ($m) => str_contains(strtolower((string) $m->position), 'chair')),
            $officials['secretary'] ?? null,
            $officials['treasurer'] ?? null,
            $members->first(fn ($m) => filled($m->phone_number)),
        ];

        $phone = null;
        $recipientRole = null;
        foreach ($candidates as $candidate) {
            if ($candidate && filled($candidate->phone_number ?? null)) {
                $phone = $candidate->phone_number;
                $recipientRole = $candidate->position ?: 'member';
                break;
            }
        }

        if (!$phone && filled($this->application->phone_number)) {
            $phone = $this->application->phone_number;
            $recipientRole = 'application_contact';
        }

        if (!$phone) {
            Log::warning('Return-to-group SMS skipped: no group contact phone', [
                'application_id' => $this->application->application_id,
                'member_positions' => $members->pluck('position')->filter()->values()->all(),
            ]);
            return false;
        }

        $portal = str_contains((string) config('drupal.base_url'), 'prod')
            || str_contains((string) config('app.url'), 'lms.uwezo.go.ke')
            ? 'loans.uwezo.go.ke'
            : 'loans-staging.uwezo.go.ke';

        $groupName = $this->application->group_name;
        $appId = $this->application->application_id;
        $shortComment = mb_strimwidth(trim($comments), 0, 240, '…');

        $message = "Uwezo Fund: Application {$appId} ({$groupName}) was returned by the Constituency Office for correction: {$shortComment}. Log in at {$portal} to update and resubmit.";

        $sent = $smsService->send($phone, $message);
        if ($sent) {
            Log::info('Return-to-group SMS sent', [
                'application_id' => $appId,
                'role' => $recipientRole,
                'phone' => $phone,
            ]);
        }

        return $sent;
    }

public function getMemberFileData($data, $key = 'url')
{
    if (!$data) return null;

    $decoded = is_array($data) ? $data : json_decode($data, true);

    if (!isset($decoded[$key])) return null;

    // If fetching the URL, ensure it is properly prefixed
    if ($key === 'url') {
        return str_starts_with($decoded[$key], 'http')
            ? $decoded[$key]
            : asset('storage/' . $decoded[$key]);
    }

    return $decoded[$key];
}

public function downloadMemberFile($memberId, $field)
{
    $member = \App\Models\TempGroupMember::findOrFail($memberId);

    // Get the JSON data from the field
    $data = $member->$field;

    if (empty($data)) {
        return $this->dispatch('notify', ['type' => 'error', 'message' => 'File data is missing.']);
    }

    // Handle both string JSON and already-cast arrays
    $fileInfo = is_array($data) ? $data : json_decode($data, true);

    if (!isset($fileInfo['path'])) {
        return $this->dispatch('notify', ['type' => 'error', 'message' => 'File path not found.']);
    }

    // Check if file exists on the disk
    if (!Storage::disk('public')->exists($fileInfo['path'])) {
        return $this->dispatch('notify', ['type' => 'error', 'message' => 'The file does not exist on the server.']);
    }

    return Storage::disk('public')->download(
        $fileInfo['path'],
        $fileInfo['original_filename'] ?? 'document.pdf'
    );
}

    public function confirmRejectApplication()
    {
        $this->validate([
            'selectedRejectionKey' => [
                'required',
                Rule::in(array_keys($this->rejectionReasons)),
            ],
            'rejectionComment' => [
                'required',
                'string',
                'min:20',
                'max:500',
                function ($attribute, $value, $fail) {
                    $cleanValue = strtolower(str_replace(' ', '', $value));
                    if (preg_match('/(.)\1{3,}/', $cleanValue)) {
                        $fail('The explanation contains too many repetitive characters. Please provide a real reason.');
                    }

                    $uniqueChars = count(count_chars($cleanValue, 1));
                    if ($uniqueChars < 6) {
                        $fail('This reason looks too generic. Please provide more specific details.');
                    }
                    $words = explode(' ', strtolower(trim($value)));
                    $uniqueWords = array_unique($words);
                    if (count($words) > 4 && (count($uniqueWords) / count($words)) < 0.5) {
                        $fail('Please provide a constructive sentence rather than repeating words.');
                    }
                },
            ],
        ], [
            'selectedRejectionKey.in' => 'Please select a valid rejection category from the list.',
            'selectedRejectionKey.required' => 'Please select a primary category for this rejection.',
            'rejectionComment.required' => 'You must provide a detailed explanation for the applicant.',
            'rejectionComment.min' => 'The explanation is too short. Please provide at least 20 characters of detail.',
        ]);

        try {
            $result = DB::transaction(function () {

                $phaseApplication = PhaseApplication::where('temp_application_id', $this->application->id)->first();

                if ($phaseApplication) {
                    $phaseApplication->update([
                        'status' => 'rejected',
                        'user_id' => auth()->id(),
                        'level' => 'constituency',
                        'action' => 'declined_at_constituency',
                        'rejection_reason' => $this->selectedRejectionKey,
                        'rejection_message' => $this->rejectionComment,
                    ]);

                    $this->application->update([
                        'status' => 'rejected',
                        'lms_to_loan_app_sync_status'  => 'pending',
                    ]);

                    activity()
                        ->performedOn($this->application->phaseApplication) // The specific phase record
                        ->causedBy(auth()->user())
                        ->createdAt(now())
                        ->withProperties([
                            'type' => 'application_rejection',
                            'attributes' => [
                                'status' => 'rejected',
                                'rejection_category' => $this->selectedRejectionKey, // e.g., 'missing_documents'
                                'rejection_label' => $this->rejectionReasons[$this->selectedRejectionKey], // e.g., 'Required documents missing'
                                'comment' => $this->rejectionComment,
                                'application_number' => $this->application->application_number,
                            ],
                            'context' => [
                                'ip_address' => request()->ip(),
                                'user_agent' => request()->userAgent(),
                            ]
                        ])
                        ->log("Application rejected due to: " . $this->rejectionReasons[$this->selectedRejectionKey]);

                    SyncApplicationStatusToDrupal::pushAfterCommit($this->application->id);

                    return true;
                }

                return false;
            });
        } catch (Exception $e) {
            $this->dispatch('swal:toast',
                title: 'Invalid Action 😕',
                message: 'Internal Server Error.',
                type: 'error'
            );
            return false;
        }

        if ($result){
            $this->reset(['selectedRejectionKey', 'rejectionComment']);

            $this->resetErrorBag();

            $this->dispatch('close-modal', name: 'modal-reject-application');

            $this->notify('success', 'Rejection', 'Application has been declined.');

            $this->navigateToNextAfterReview();

            $this->refreshData();
        } else {

            $this->notify('error', 'Invalid Application', 'ERROR: You have no access to this application.');
            return;
        }
    }

    private function getNext()
    {
        return PhaseApplication::select(['id', 'loan_phase_id', 'temp_application_id', 'status', 'amount_applied'])
            ->with(['tempApplication' => function ($query) {
                $query->select(['id', 'group_name', 'application_id', 'loan_amount']);
            }])
            ->where('loan_phase_id', $this->application->phaseApplication->loan_phase_id)
            ->where('status', 'pending')
            ->where('id', '!=', $this->application->phaseApplication->id)
            ->first();
    }

    private function navigateToNextAfterReview()
    {
        $nextApp = $this->getNext();

        if ($nextApp) {
            $this->phaseCompleted = false;

            $this->nextSummary = [
                'group_name' => $nextApp->tempApplication->group_name ?? 'N/A',
                'app_id' => $nextApp->tempApplication->application_id ?? 'N/A',
                'amount' => number_format($nextApp->amount_applied ?? $nextApp->tempApplication->loan_amount ?? 0),
                'url' => route('const.app.application-view', [
                    'county' => $this->county->slug,
                    'constituency' => $this->constituency->slug,
                    'applicationId' => $nextApp->tempApplication->application_id
                ])
            ];
        } else {
            $this->phaseCompleted = true;
            $this->nextSummary = null;
            $this->submissionUrl = route('const.app.app_phase_details', [
                'county' => $this->county->slug,
                'constituency' => $this->constituency->slug,
                'phase' => $this->application->phaseApplication->reviewPhase->slug,
            ]);
        }

        $this->dispatch('open-modal', name: 'modal-next-step');
    }

    public function reviewDecision()
    {
        if (!$this->application || !$this->application->phaseApplication) {
            $this->dispatch('swal:toast',
                title: 'Invalid Action 😕',
                message: 'No decision found to review.',
                type: 'error'
            );
            return;
        }

        $status = $this->application->phaseApplication->status;

        if ($status === 'rejected') {
            $this->reviewType = 'rejection';
            $this->rejectionReason = $this->application->phaseApplication->rejection_message ?? $this->application->phaseApplication->rejection_reason ?? 'No reason provided';
        } elseif ($status === 'pending_submission_to_hq') {
            $this->reviewType = 'approval';
            $this->rejectionReason = null;
        } else {
            $this->dispatch('swal:toast',
                title: 'Invalid Action 😕',
                message: 'Current status does not allow for review.',
                type: 'info'
            );
            return;
        }

        $this->reviewingApplicationId = $this->application->id;

        $this->dispatch('open-modal', name: 'modal-review-decision');
    }

    public function confirmReviewDecision()
    {
        if (!$this->application || !$this->application->phaseApplication) {
            $this->dispatch('swal:toast',
                title: 'Invalid Action 😕',
                message: 'No decision found to review.',
                type: 'info'
            );
            return;
        }

        $currentStatus = $this->application->phaseApplication->status;


        try {
            $result  = DB::transaction(function () use ($currentStatus) {
                $this->application->update([
                    'lms_to_loan_app_sync_status' => 'pending',
                    'status'           => 'under_constituency_review',
                    'risk_level'       => null,
                    'assessment_data'  => null,
                ]);


                $this->application->phaseApplication->update([
                    'status'              => 'pending',
                    'user_id'             => auth()->id(),
                    'level'               => 'constituency',
                    'action'              => 'review',
                    'amount_approved'     => 0,
                    'comments'             => null,
                    'group_code'       => null,
                    'gfs_code'         => null,
                    'rejection_reason' => null,
                    'rejection_message' => null,
                ]);

                activity()
                    ->performedOn($this->application)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'ip_address' => request()->ip()
                    ])
                    ->log("Reviewed decision at constituency level.");

                if ($currentStatus === 'rejected') {
                    $this->application->update([
                        'lms_to_loan_app_sync_status'  => 'pending',
                    ]);

                    SyncApplicationStatusToDrupal::pushAfterCommit($this->application->id);
                }

                return true;
            });
        } catch (Exception $e) {
            $this->dispatch('swal:toast',
                title: 'Invalid Action 😕',
                message: 'Internal Server Error.',
                type: 'error'
            );
            return false;
        }

        if ($result){
            $this->dispatch('close-modal', name: 'modal-review-decision');

            $this->dispatch('swal:toast',
                title: 'Reviewed 🤩',
                message: 'Decision reviewed successfully.',
                type: 'success'
            );

            $this->refreshData();
        } else {
            $this->notify('error', 'Invalid Application', 'ERROR: You have no access to this application.');
        }
    }

    public function linkGroup()
    {
        $this->dispatch('openLinker', id: $this->application->id);
    }

    #[Computed]
    public function riskAssessmentData(): array
    {
        return (array) $this->defaultService->calculateGroupRiskLevel(
            $this->application->phaseApplication?->linkedGroup,
            $this->application->groupMembers
        );
    }

    #[Computed]
    public function riskFlags()
    {
        return $this->riskAssessmentData['reasons'] ?? [];
    }

    #[Computed]
    public function riskFlagsCount()
    {
        return count($this->riskFlags);
    }

    #[Computed]
    public function riskCategory(): string
    {
        return $this->riskAssessmentData['overall_category'] ?? 'Low Risk';
    }


    #[Computed]
    public function mlScoreData(): ?array
    {
        $applicationId = $this->application->application_id ?? null;
        if (!$applicationId) {
            return null;
        }

        /** @var RepaymentScoreClient $client */
        $client = app(RepaymentScoreClient::class);
        $payload = $client->scoreIntake((string) $applicationId);
        if (!$payload) {
            return null;
        }

        // Best-effort persist under assessment_data (non-blocking)
        try {
            $merged = $client->mergeIntoAssessmentData(
                is_array($this->application->assessment_data) ? $this->application->assessment_data : null,
                $payload
            );
            if ($merged !== ($this->application->assessment_data ?? null)) {
                $this->application->forceFill(['assessment_data' => $merged])->saveQuietly();
            }
        } catch (\Throwable $e) {
            Log::debug('ML assessment_data persist skipped', ['error' => $e->getMessage()]);
        }

        return $payload;
    }


    public function getMemberConflictCountProperty()
    {
        if (!$this->application || !$this->application->groupMembers) {
            return 0;
        }
        return $this->application->groupMembers->filter(
            fn ($member) => $member->is_defaulter || ($member->has_other_group_loan ?? false)
        )->count();
    }


    public function viewMember($memberId)
    {
        // Find the member within the already loaded collection
        $this->selectedMember = $this->application->groupMembers
            ->where('id', $memberId)
            ->first();

        // 2. Dispatch the event to open the modal
        $this->dispatch('open-modal', name: 'memberModal');
    }

    public function sendSmsToApplicationMember(int $memberId): void
    {
        $member = $this->application->groupMembers->where('id', $memberId)->first();
        if (!$member) {
            return;
        }

        $this->dispatch('open-send-sms-modal',
            recipientType: 'application_member',
            constituencyCode: $this->constituency->constituency_code,
            tempGroupMemberId: $member->id,
            memberName: $member->name,
            memberPhone: $member->phone_number,
        )->to(SendCustomSms::class);
    }

    public function sendSmsToApplicationGroup(): void
    {
        $this->dispatch('open-send-sms-modal',
            recipientType: 'application_group',
            constituencyCode: $this->constituency->constituency_code,
            applicationId: $this->application->application_id,
            groupName: $this->application->group_name,
        )->to(SendCustomSms::class);
    }

    public function exportInstitutionalInfo(){

        try {
            return Excel::download(new ApplicationInstitutionalDataExport($this->application, $this->constituency), $this->application->group_name.'_institutional_information.xlsx');
        } catch (\Throwable $th) {
            $this->dispatch('swal:modal', [
                'type'  => 'error',
                'title' => "Invalid Action",
                'text'  => $th->getMessage(),
            ]);
        }
    }

    public function downloadStatement($loanId)
    {
        $loan = GroupLoan::with(['group', 'loanPhase', 'loanReceipts'])
            ->where('loan_status', 'financed')
            ->findOrFail($loanId);

        $pdf = Pdf::loadView('exports.loan-statement', [
            'loan' => $loan
        ])->setPaper('a4', 'portrait')->output();

        return response()->streamDownload(
            fn () => print($pdf),
            "Statement_{$loan->id}.pdf"
        );
    }

    #[Computed]
    public function mn($id)
    {

    }

    public function getMemberIdViewUrl($path)
    {
        if (empty($path)) {
            return null;
        }

        if (is_array($path)) {
            $path = $path['path'] ?? $path['url'] ?? null;
        }

        if (empty($path)) {
            return null;
        }

        $path = str_replace('storage/storage/', 'storage/', $path);
        $path = str_replace('storage/app/public/', '', $path);
        $path = preg_replace('#^/?storage/#', '', $path);
        $path = ltrim($path, '/');

        $encodedPath = base64_encode($path);

        return route('const.app.application.file.member.download', [
                'county' => $this->county->slug,
                'constituency' => $this->constituency->slug,
                'applicationId' => $this->application->application_id,
                'action' => 'view',
            ]) . '?path=' . $encodedPath;
    }

    public function render()
    {
        $view = config('applications.review_v2_enabled')
            ? 'livewire.loan.app.application-view-v2'
            : 'livewire.loan.app.application-view-legacy';

        return view($view);
    }
}
