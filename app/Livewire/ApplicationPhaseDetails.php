<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use App\Exports\CodeListExport;
use App\Exports\StandardVettedExport;
use App\Exports\DisaggregatedDataExport;
use App\Exports\DisbursementDataExport;
use App\Models\Constituency;
use App\Models\LoanPhase;
use App\Models\Group;
use App\Models\PhaseApplication;
use App\Services\Loan\LoanPhaseService;
use App\Services\Exports\ExportService;
use App\Services\Loan\TempApplicationService;
use App\Services\IdempotencyService;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ApplicationPhaseDetails extends Component
{
    use WithFileUploads, WithPagination;

    protected $paginationTheme = 'bootstrap';

    protected LoanPhaseService $phaseService;
    protected ExportService $exportService;
    protected TempApplicationService $appService;
    protected IdempotencyService $idempotencyService;

    public $phase;
    public Constituency $constituency;
    public $wardMap = [];

    public $search = '';
    public $statusFilter = '';
    public $riskFilter = '';
    public $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'riskFilter' => ['except' => ''],
    ];

    public $hasExistingIdentifier = false;

    public $generatedReports = [
        ['id' => 'code_list', 'name' => 'Code List', 'meta' => 'GFS Codes, Loan Levels, Contact Persons & Financials'],
        ['id' => 'standard_vetted', 'name' => 'Standard Vetted Form', 'meta' => 'Full demographics, Bank info, & Sectors'],
        ['id' => 'disaggregated', 'name' => 'Disaggregated Data', 'meta' => 'Gender & PWD breakdown by Ward'],
        ['id' => 'disbursement', 'name' => 'Disbursement Report', 'meta' => 'Bank Accounts & Final Amounts'],
    ];

    public $trainingStartDate, $trainingEndDate, $trainingVenue, $trainingNotes;

    public $approvedStatuses = ['pending_submission_to_hq', 'pending_hq_approval', 'in_training', 'approved'];


    #[Url(except: '')]
    public $sortField = 'updated';
    public $sortDirection = 'desc';

    public $remarks = '';

    public $extraUploads = [];
    public $minutesFile;
    public $selectedAppId;
    public $selectedGroupId;
    public $selectedRejectionKey;
    public $rejectionComment = '';

    public $app_amount_issued;
    public $app_approved_amount;
    public $app_cheque_number;
    public $app_group_identifier;
    public $app_disbursement_comment;

    public function boot(
        LoanPhaseService $phaseService,
        ExportService $exportService,
        TempApplicationService $appService,
        IdempotencyService $idempotencyService
        )
    {
        $this->phaseService = $phaseService;
        $this->exportService = $exportService;
        $this->appService = $appService;
        $this->idempotencyService = $idempotencyService;
    }

    public function mount(Constituency $constituency, LoanPhase $phase)
    {
        $this->constituency = $constituency->load(['county', 'wards']);
        $this->wardMap = $this->constituency->wards->pluck('ward_name', 'id')->toArray();
        $this->phase = $phase;
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function getFilters(): array
    {
        return [
            'search' => $this->search,
            'status' => $this->statusFilter,
            'risk'  => $this->riskFilter,
        ];
    }

    #[On('refresh-phase-app-data')]
    public function refreshData()
    {
        $this->phaseService->invalidatePhaseCache($this->phase->id);
        $this->appService->invalidateStatsCache($this->phase->constituency_code);

        $this->phase->refresh();

        unset($this->detailedPhase);
        unset($this->phaseSummary);
        unset($this->applicationsSummary);
        unset($this->phaseApplicationDocuments);
        unset($this->vettingPercentage);
        unset($this->documentsCount);

        $this->resetFilters();
    }

    public function clearFilter($filterName)
    {
        $this->reset($filterName);
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }
    public function updatingRiskFilter() { $this->resetPage(); }
    public function updatedPerPage ($value) {if ($value === null) $this->perPage = 10;}

    public function getStatusOptionsProperty()
    {
        return $this->appService->getConsStatusOptionsProperty();
    }

    public function getStatusClasses($value): string
    {
        return $this->appService->getStatusClasses($value);
    }

    public function getStatusName($value)
    {
        return collect($this->statusOptions)->firstWhere('value', $value)['name'] ?? $value;
    }

    public function getRiskOptionsProperty()
    {
        return [
            ['value' => 'high', 'name' => 'High Risk', 'color' => '#10b981'],
            ['value' => 'medium', 'name' => 'Medium Risk', 'color' => '#f59e0b'],
            ['value' => 'low', 'name' => 'Low Risk', 'color' => '#f59e0b'],
        ];
    }

    public function getRiskName($value)
    {
        return collect($this->riskOptions)->firstWhere('value', $value)['name'] ?? $value;
    }

    public function getPerPageOptionsProperty()
    {
        return [
            ['id' => 10, 'name' => '10 per page'],
            ['id' => 15, 'name' => '15 per page'],
            ['id' => 25, 'name' => '25 per page'],
            ['id' => 50, 'name' => '50 per page'],
            ['id' => 100, 'name' => '100 per page'],
        ];
    }

    public function resetFilters()
    {
        $this->reset(['search', 'statusFilter', 'riskFilter', 'perPage']);
        $this->resetPage();
    }

    // add extra documents
    public function addUploadRow()
    {
        $this->extraUploads[] = ['title' => '', 'file' => null];
    }

    public function removeUploadRow($index)
    {
        unset($this->extraUploads[$index]);
        $this->extraUploads = array_values($this->extraUploads);
    }

    public function uploadExtraDocuments()
    {
        $this->validate([
            'extraUploads.*.title' => 'required|string|max:100',
            'extraUploads.*.file' => 'required|file|max:2048', // 2MB limit
        ], [
            'extraUploads.*.title.required' => 'The document title is required',
            'extraUploads.*.file.required' => 'The document file is required',
            'extraUploads.*.file.max' => 'The upload file is greator than 2Mb',
        ]);
        $uploaded_files = 0;
        foreach ($this->extraUploads as $index => $upload) {

            try {
                // Attempt to upload each document
                $this->phaseService->uploadPhaseDocument($this->phase, $upload['file'], $upload['title'], 'supporting_docs');
                unset($this->extraUploads[$index]);
                $uploaded_files++;
            } catch (ValidationException $ve) {
                $this->addError("extraUploads.$index.file", $ve->errors()['file'][0]);
            }
            catch (\Throwable $th) {
                logger()->error('Extra document upload error: ' . $th->getMessage());
                $this->addError("extraUploads.$index.file", 'Document upload failed: ' . $th->getMessage());
            }
        }

        if ($uploaded_files > 0){
            $this->dispatch('swal:toast',
                title: 'Document Upload 😊',
                message: $uploaded_files . ' ' . Str::plural('file', $uploaded_files). ' uploaded successfully! ',
                type: 'success'
            );
        }

    }

    public function uploadMinutes()
    {
        $this->validate([
            'minutesFile' => 'required|file|mimes:pdf,doc,docx|max:2048',
        ],
        [
            'minutesFile.max'       => 'The document is too heavy. Please keep it under 2MB.',
            'minutesFile.mimes'     => 'Only PDF and Word documents are accepted.',
            'minutesFile.required'  => 'Please attach the required document before proceeding.',
        ]);

        try {
            $filePayload = [
                'fileName'      => 'vetting-minutes-',
                'modal'         => 'minutesFile',
                'documentType'  => 'minutes',
                'title'         => 'Signed Committee Minutes'
            ];
            $this->phaseService->uploadPhaseTypedDocument($this->phase, $this->minutesFile, $filePayload);

            $this->reset('minutesFile');

            $this->refreshData();

            $this->dispatch('swal:toast',
                title: 'Minutes Upload 😊',
                message: 'Minutes uploaded successfully! ',
                type: 'success'
            );
        } catch (ValidationException $ve) {
            //add error to the minutesFile field
            $this->addError('minutesFile', $ve->errors()['minutesFile'][0]);
        }
        catch (\Exception $e) {
            logger()->error('Minutes upload error: ' . $e->getMessage());
            $this->dispatch('swal:toast',
                title: 'Minutes upload 😕',
                message: 'Failed to upload your document. Please try again later',
                type: 'error'
            );
            return;
        }

    }

    public function deleteDocument($docId)
    {
        $document = \App\Models\PhaseApplicationDocument::findOrFail($docId);

        if (!$this->canDeleteDocument($document)) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied 😕',
                message: 'You do not have the right to perform this action',
                type: 'error'
            );
            return;
        }

        try {
            $this->phaseService->deleteApplicationPhaseDocument($document);

            $this->refreshData();

            $this->dispatch('swal:toast',
                title: 'Delete Document 😊',
                message: 'Document deleted successfully! ',
                type: 'success'
            );

        } catch (\Exception $e) {
            logger()->error('Document deletion error: ' . $e->getMessage());
            $this->dispatch('swal:toast',
                title: 'Remove Document 😕',
                message: 'Failed to delete your document. Please try again later',
                type: 'error'
            );
            return;
        }
    }

    public function submitToHq()
    {
        if ( !($this->phase->has_minutes && $this->detailedPhase->pending_count == 0))
        {
            $this->dispatch('swal:toast',
                title: 'Submitting to HQ 😕',
                message: 'Failed to submit phase. Please complete pending actions and try again later',
                type: 'error'
            );

            return;
        }

        if (!$this->phase->canBeEditedBy(auth()->user())) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied 😕',
                message: 'You Dont have permissions to overwite this phase',
                type: 'error'
            );
            return;
        }

        $this->dispatch('open-modal', name: 'phase-submission-modal');
    }

    public function executeHqSubmission()
    {
        if (!$this->canSubmitApplication()) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied 😕',
                message: 'You do not have the right to perform this action',
                type: 'error'
            );
            $this->dispatch('close-modal', name: 'phase-submission-modal');
            return;
        }

        if (!$this->phase->canBeEditedBy(auth()->user())) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied 😕',
                message: 'You Dont have permissions to overwite this phase',
                type: 'error'
            );
            $this->dispatch('close-modal', name: 'phase-submission-modal');
            return;
        }

        $this->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        $idempotencyKey = "phase_submission_to_hq_{$this->phase->id}";

        $payload = [
            'phase_id' => $this->phase->id,
            'remarks'  => $this->remarks
        ];

        if (!$this->idempotencyService->acquire($idempotencyKey, $payload)) {
            $this->dispatch('swal:toast',
                title: 'Action in progress: 😕',
                message: "This phase is already being processed.",
                type: 'error'
            );

            $this->dispatch('close-modal', name: 'phase-submission-modal');

            return;
        }

        try {

            $this->phaseService->submitToHq($this->detailedPhase, $this->remarks, $idempotencyKey);

            $this->refreshData();

            $this->dispatch('close-modal', name: 'phase-submission-modal');

            $this->dispatch('swal:toast',
                title: 'Submitted Successfully 😊',
                message: 'Phase has been locked and moved to HQ Review.',
                type: 'success'
            );
        } catch (ValidationException $e) {
            $this->dispatch('swal:toast',
                title: 'System Error 😕',
                message: $e->getMessage(),
                type: 'error'
            );

            $this->dispatch('close-modal', name: 'phase-submission-modal');
            // $this->addError('phase_submission', $e->getMessage());

            $this->idempotencyService->release($idempotencyKey);
        }   catch (\Exception $e) {
            $this->dispatch('swal:toast',
                title: 'System Error 😕',
                message: "Could not finalize submission. Please try again or contact support.",
                type: 'error'
            );

            $this->idempotencyService->release($idempotencyKey);
        }
    }

    public function canSubmitApplication() : bool
    {
        $user = auth()->user();
        return $user->hasPermissionTo('submit application');
    }

    public function canDeleteDocument($doc) : bool
    {
        $user = auth()->user();

        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($doc->uploaded_by === $user->id && $this->phase->status === 'draft') {
            return true;
        }

        return false;
    }

    public function getFileMetadata($file)
    {
        return $this->phaseService->getFileMetadata($file);
    }

    public function scheduleTraining()
    {
        if (!$this->phase->canBeEditedBy(auth()->user())) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied 😕',
                message: 'You Dont have permissions to overwite this phase',
                type: 'error'
            );
            $this->dispatch('close-modal', name: 'set-training-schedule-modal');
            return;
        }

        $this->resetErrorBag(['trainingStartDate', 'trainingEndDate', 'trainingVenue']);

        $this->dispatch('open-modal', name: 'set-training-schedule-modal');
    }

    public function saveTrainingSchedule()
    {
        if (!$this->phase->canBeEditedBy(auth()->user())) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied 😕',
                message: 'You Dont have permissions to overwite this phase',
                type: 'error'
            );

            $this->dispatch('close-modal', name: 'set-training-schedule-modal');

            return;
        }

        $this->validate( [
            'trainingStartDate' => 'required|date|after_or_equal:today',
            'trainingEndDate'   => 'required|date|after_or_equal:trainingStartDate',
            'trainingVenue'     => 'required|string|max:255',
        ]);

        $idempotencyKey = "phase_in_training_{$this->phase->id}";

        $payload = [
            'phase_id' => $this->phase->id,
            'remarks'  => $this->remarks
        ];

        if (!$this->idempotencyService->acquire($idempotencyKey, $payload)) {
            $this->dispatch('swal:toast',
                title: 'Action in progress: 😕',
                message: "This phase is already being processed.",
                type: 'error'
            );

            $this->dispatch('close-modal', name: 'set-training-schedule-modal');

            return;
        }

        try {
            $data = [
                'trainingStartDate' => $this->trainingStartDate,
                'trainingEndDate' => $this->trainingEndDate,
                'trainingVenue' => $this->trainingVenue,
            ];

            $this->phaseService->saveTrainingSchedule($this->detailedPhase, $idempotencyKey, $data);

            $this->refreshData();

            $this->dispatch('close-modal', name: 'set-training-schedule-modal');

            $this->dispatch('swal:toast',
                title: 'Training Set Successfully 😊',
                message: 'Phase has moved to Training.',
                type: 'success'
            );

            $this->reset('trainingStartDate', 'trainingEndDate', 'trainingVenue', 'trainingNotes');
        } catch (\Exception $th) {

            $this->dispatch('swal:toast',
                title: 'System Error 😕',
                message: "Could not Set the training schedule. Please try again or contact support.",
                type: 'error'
            );

            $this->dispatch('close-modal', name: 'set-training-schedule-modal');
            $this->reset('trainingStartDate', 'trainingEndDate', 'trainingVenue', 'trainingNotes');
            $this->idempotencyService->release($idempotencyKey);
        }
    }

    public function toggleAttendance($appId)
    {
        if (!$this->phase->canBeEditedBy(auth()->user())) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied 😕',
                message: 'You Dont have permissions to overwite this phase',
                type: 'error'
            );
            $this->dispatch('close-modal', name: 'confirm-training-modal');

            return;
        }

        $this->selectedAppId = $appId;

        $this->dispatch('open-modal', name: 'confirm-training-modal');
    }

    public function confirmTraining()
    {
        if (!$this->phase->canBeEditedBy(auth()->user())) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied 😕',
                message: 'You Dont have permissions to overwite this phase',
                type: 'error'
            );

            $this->dispatch('close-modal', name: 'confirm-training-modal');

            $this->reset('selectedAppId');

            return;
        }

        try {
            $this->appService->confirmTraining($this->selectedAppId);

            $this->dispatch('close-modal', name: 'confirm-training-modal');

            $this->dispatch('swal:toast',
                title: 'Traning  Updated 😊',
                message: 'Application Set Trained',
                type: 'success'
            );

            $this->reset('selectedAppId');

            $this->refreshData();


        } catch (\Exception $th) {
            $this->dispatch('swal:toast',
                title: 'System Error 😕',
                message: "Could not mark training. Please try again or contact support.",
                type: 'error'
            );

            $this->reset('selectedAppId');

            $this->dispatch('close-modal', name: 'confirm-training-modal');
        }
    }

    public function toggleDisbursement($appId)
    {
        $this->resetErrorBag(['app_amount_issued', 'app_cheque_number', 'app_group_identifier', 'app_disbursement_comment']);

        if (!$this->phase->canBeEditedBy(auth()->user())) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied 😕',
                message: 'You Dont have permissions to overwite this phase',
                type: 'error'
            );
            $this->dispatch('close-modal', name: 'set-training-schedule-modal');
            return;
        }

        $this->initializeData($appId);

        $this->dispatch('open-modal', name: 'approve-disbursement-modal');
    }

    private function initializeData($appId)
    {

        $application = PhaseApplication::with(['linkedGroup' => function($query) {
                $query->select(['id', 'name', 'code', 'identifier']);
            }])
            ->findOrFail($appId, ['id', 'group_id', 'amount_approved']);

        $this->selectedAppId = $appId;
        $this->selectedGroupId = $application->linkedGroup->id;
        $this->app_approved_amount = $application->amount_approved;
        $this->app_amount_issued = $application->amount_approved;

        if ($application->linkedGroup->identifier) {
            $this->app_group_identifier = $application->linkedGroup->identifier;
            $this->hasExistingIdentifier = true;
        } else {
            $this->app_group_identifier = $this->appService->generateIdentifier($application->linkedGroup->name, $application->linkedGroup->code);
            $this->hasExistingIdentifier = false;
        }
    }

    public function processDisbursement()
    {
        if (!$this->phase->canBeEditedBy(auth()->user())) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied 😕',
                message: 'You Dont have permissions to overwite this phase',
                type: 'error'
            );
            $this->dispatch('close-modal', name: 'approve-disbursement-modal');

            $this->reset('app_disbursement_comment', 'selectedAppId', 'selectedGroupId', 'app_amount_issued', 'app_cheque_number', 'app_group_identifier');

            return;
        }

        $this->validate([
            'app_amount_issued' => [
                'required',
                'numeric',
                'min:50000',
                "max:{$this->app_approved_amount}"
            ],
            'app_cheque_number' => 'required|string|max:50',
            'app_group_identifier' => 'required|string|min:6',
            'app_disbursement_comment' => [
                Rule::requiredIf($this->app_approved_amount != $this->app_amount_issued),
                'nullable',
                'string',
                'min:20'
            ],
        ], [
            'app_amount_issued.max' => 'The amount issued must not be greater than the approved amount',
            'app_disbursement_comment.required' => 'A detailed comment (min 20 chars) is required when the issued amount differs from the approved amount.',
            'app_disbursement_comment.min' => 'The justification for the amount discrepancy must be at least 20 characters.'
        ]);

        $exists = Group::where('identifier', strtoupper($this->app_group_identifier))
            ->whereHas('ward', function ($query) {
                $query->where('constituency_code', $this->phase->constituency_code);
            })
            ->where('id', '!=', $this->selectedGroupId)
            ->exists();

        if ($exists) {
            $this->addError('app_group_identifier', 'This identifier is already assigned to another group in this constituency.');
            return;
        }

        $idempotencyKey = "application_action_{$this->selectedAppId}";

        $payload = [
            'temp_id' => $this->phase->id,
            'group_id' => $this->selectedGroupId,
        ];

        if (!$this->idempotencyService->acquire($idempotencyKey, $payload)) {
            $this->dispatch('swal:toast',
                title: 'Action in progress: 😕',
                message: "This Application is already being processed.",
                type: 'error'
            );

            $this->dispatch('close-modal', name: 'approve-disbursement-modal');

            $this->reset('app_disbursement_comment', 'selectedAppId', 'selectedGroupId', 'app_amount_issued', 'app_cheque_number', 'app_group_identifier');
            return;
        }

        try {
            $data = [
                'app_amount_issued' => $this->app_amount_issued,
                'app_cheque_number' => $this->app_cheque_number,
                'app_group_identifier' => $this->app_group_identifier,
                'app_disbursement_comment' => $this->app_disbursement_comment,
            ];

            $this->appService->approveApplicationDisbursement($this->selectedAppId, $idempotencyKey, $data);

            $this->refreshData();

            $this->dispatch('close-modal', name: 'approve-disbursement-modal');

            $this->dispatch('swal:toast',
                title: 'Disbursed Successfully 😊',
                message: 'Loan Disbursed.',
                type: 'success'
            );

            $this->reset('app_disbursement_comment', 'selectedAppId', 'selectedGroupId', 'app_amount_issued', 'app_cheque_number', 'app_group_identifier');
        } catch (\Exception $th) {
            $this->dispatch('swal:toast',
                title: 'System Error 😕',
                message: "Could not Set the training schedule. Please try again or contact support.",
                type: 'error'
            );

            $this->reset('app_disbursement_comment', 'selectedAppId', 'selectedGroupId', 'app_amount_issued', 'app_cheque_number', 'app_group_identifier');
            $this->dispatch('close-modal', name: 'approve-disbursement-modal');
            $this->idempotencyService->release($idempotencyKey);
        }

    }

    public function cancelApplication($appId)
    {
        $this->resetErrorBag(['selectedRejectionKey', 'rejectionComment']);

        if (!$this->phase->canBeEditedBy(auth()->user())) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied 😕',
                message: 'You Dont have permissions to overwite this phase',
                type: 'error'
            );
            $this->dispatch('close-modal', name: 'modal-reject-application');
            return;
        }

        $this->selectedAppId = $appId;

        $this->dispatch('open-modal', name: 'modal-reject-application');
    }

    public function confirmRejectApplication()
    {
        if (!$this->phase->canBeEditedBy(auth()->user())) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied 😕',
                message: 'You Dont have permissions to overwite this phase',
                type: 'error'
            );
            $this->dispatch('close-modal', name: 'modal-reject-application');
            $this->reset('selectedAppId');
            return;
        }

        $this->validate([
            'selectedRejectionKey' => [
                'required',
                Rule::in(array_column($this->formattedRejectionReasons(), 'id')),
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

        $idempotencyKey = "application_action_{$this->selectedAppId}";

        $payload = [
            'temp_id' => $this->phase->id,
            'group_id' => $this->selectedGroupId,
        ];

        if (!$this->idempotencyService->acquire($idempotencyKey, $payload)) {
            $this->dispatch('swal:toast',
                title: 'Action in progress: 😕',
                message: "This Application is already being processed.",
                type: 'error'
            );

            $this->dispatch('close-modal', name: 'modal-reject-application');

            $this->reset('selectedRejectionKey', 'rejectionComment', 'selectedAppId');
            return;
        }

        try {
            $data = [
                'status' => 'in_training',
                'level' => 'constituency',
                'action' => 'rejected_at_training_stage',
                'rejectionKey' => $this->selectedRejectionKey,
                'rejectionComment' => $this->rejectionComment,
                'rejection_label' => $this->getRejectionLabel($this->selectedRejectionKey)
            ];

            $this->appService->rejectApplication($this->selectedAppId, $idempotencyKey, $data);

            $this->refreshData();

            $this->dispatch('close-modal', name: 'modal-reject-application');

            $this->dispatch('swal:toast',
                title: 'Cancelled Successfully 😊',
                message: 'Application Cancelled.',
                type: 'success'
            );

            $this->reset('selectedRejectionKey', 'rejectionComment', 'selectedAppId');
        } catch (\Exception $th) {
            $this->dispatch('swal:toast',
                title: 'System Error 😕',
                message: "Could not Set the training schedule. Please try again or contact support.",
                type: 'error'
            );

            $this->reset('selectedRejectionKey', 'rejectionComment', 'selectedAppId');

            $this->dispatch('close-modal', name: 'modal-reject-application');

            $this->idempotencyService->release($idempotencyKey);
        }

    }

    public function getRejectionLabel($id)
    {
        $reasons = collect($this->formattedRejectionReasons());

        return $reasons->firstWhere('id', $id)['name'] ?? null;
    }

    public function finalizePhase()
    {
        if (!$this->phase->canBeEditedBy(auth()->user())) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied 😕',
                message: 'You Dont have permissions to overwite this phase',
                type: 'error'
            );
            $this->dispatch('close-modal', name: 'confirm-close-modal');

            return;
        }

        $this->dispatch('open-modal', name: 'confirm-close-modal');
    }

    public function confirmFinalizePhase()
    {
        if (!$this->phase->canBeEditedBy(auth()->user())) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied 😕',
                message: 'You Dont have permissions to overwite this phase',
                type: 'error'
            );

            $this->dispatch('close-modal', name: 'confirm-close-modal');

            return;
        }

        try {
            $this->phaseService->finalizePhase($this->detailedPhase);

            $this->dispatch('close-modal', name: 'confirm-close-modal');

            $this->dispatch('swal:toast',
                title: 'Phase  Closed 😊',
                message: 'Phase Set Disbursed and Closed',
                type: 'success'
            );

            $this->refreshData();

        } catch (\Exception $th) {
            $this->dispatch('swal:toast',
                title: 'System Error 😕',
                message: "Could not close. Please try again or contact support.",
                type: 'error'
            );

            $this->dispatch('close-modal', name: 'confirm-close-modal');
        }
    }

    #[Computed]
    public function formattedRejectionReasons()
    {
        return [
            ['id' => 'incomplete_information', 'name' => 'Suspicious/Fraudulent Activity'],
            ['id' => 'eligibility_criteria', 'name' => 'Unreal Group (Non-Existent)'],
            ['id' => 'duplicate_application', 'name' => 'Duplicate Application'],
            ['id' => 'insufficient_collateral', 'name' => 'Missing Mandatory Training/Docs'],
            ['id' => 'other', 'name' => 'Other (Specify Below)'],
        ];
    }

    #[Computed]
    public function phaseSummary()
    {
        return $this->phaseService->getDetailedPhase(
            $this->phase,
            $this->perPage,
            $this->getFilters(),
            $this->getPage(),
            $this->sortField,
            $this->sortDirection
        );
    }

    #[Computed]
    public function detailedPhase()
    {
        return $this->phaseSummary['phase'];
    }

    #[Computed]
    public function applicationsSummary()
    {
        return $this->phaseService->getPhasesByStatus(
            $this->constituency->constituency_code,
            $this->search
        );
    }

    #[Computed]
    public function phaseApplicationDocuments()
    {
        return $this->phaseService->getPhasesByStatus(
            $this->constituency->constituency_code,
            $this->search
        );
    }

    #[Computed]
    public function vettingPercentage(): int
    {
        return $this->phaseService->getVettingPercentage($this->detailedPhase->total_applications, $this->detailedPhase->pending_count);
    }

    #[Computed]
    public function applications()
    {
        return $this->phaseSummary['applications'];
    }

    public function exportReport($document)
    {
        switch ($document) {
            case 'code_list':
                $query  = $this->exportService->exportCodeList($this->phase->id);
                try {
                    $fileName = "Code_List_{$this->constituency->constituency_name}_" . now()->format('Ymd_His') . ".xlsx";

                    return Excel::download(
                        new CodeListExport(
                                $query
                            ),
                        $fileName
                    );

                } catch (\Exception $e) {
                    logger()->error('Excel export error: ' . $e->getMessage());
                    $this->dispatch('swal:toast',
                        title: 'Export failed 😕',
                        message: "Could not downoload your document. Please try again.",
                        type: 'error'
                    );
                    return null;
                }
                break;

            case 'standard_vetted':
                $query  = $this->exportService->exportStandardVettedQuery($this->phase->id);
                try {
                    $fileName = "Standard_Vetted_Form_{$this->constituency->constituency_name}_" . now()->format('Ymd_His') . ".xlsx";

                    return Excel::download(
                        new StandardVettedExport(
                                $query
                            ),
                        $fileName
                    );

                } catch (\Exception $e) {
                    logger()->error('Excel export error: ' . $e->getMessage());
                    $this->dispatch('swal:toast',
                        title: 'Export failed 😕',
                        message: "Could not downoload your document. Please try again.",
                        type: 'error'
                    );
                    return null;
                }
                break;

            case 'disaggregated':
                $query  = $this->exportService->exportdisaggregatedDataQuery($this->phase->id);
                try {
                    $fileName = "Disaggregated_Data_Form_{$this->constituency->constituency_name}_" . now()->format('Ymd_His') . ".xlsx";

                    return Excel::download(
                        new DisaggregatedDataExport(
                                $query
                            ),
                        $fileName
                    );

                } catch (\Exception $e) {
                    logger()->error('Excel export error: ' . $e->getMessage());
                    $this->dispatch('swal:toast',
                        title: 'Export failed 😕',
                        message: "Could not downoload your document. Please try again.",
                        type: 'error'
                    );
                    return null;
                }
                break;

            case 'disbursement':
                $query  = $this->exportService->exportdisbursementDataQuery($this->phase->id);
                try {
                    $fileName = "Disbursement_Data_Form_{$this->constituency->constituency_name}_" . now()->format('Ymd_His') . ".xlsx";

                    return Excel::download(
                        new DisbursementDataExport(
                                $query
                            ),
                        $fileName
                    );

                } catch (\Exception $e) {
                    logger()->error('Excel export error: ' . $e->getMessage());
                    $this->dispatch('swal:toast',
                        title: 'Export failed 😕',
                        message: "Could not downoload your document. Please try again.",
                        type: 'error'
                    );
                    return null;
                }
                break;

            default:
                # code...
                break;
        }

    }

    public function render()
    {
        return view('livewire.loan.app.application-phase-details', [
            'detailedPhase' => $this->detailedPhase,
        ]);
    }
}
