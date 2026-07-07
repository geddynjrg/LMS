<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use App\Exports\ConstituencyCodeListExport;
use App\Exports\StandardVettedExport;
use App\Exports\DisaggregatedDataExport;
use App\Exports\DisbursementDataExport;
use App\Models\Constituency;
use App\Models\LoanPhase;
use App\Models\Group;
use App\Models\PhaseApplication;
use App\Services\Loan\BankReconciliationService;
use App\Services\Loan\LoanPhaseService;
use App\Services\Exports\ExportService;
use App\Services\Loan\TempApplicationService;
use App\Services\Loan\TrainingCertificateService;
use App\Services\Loan\SecretariatApprovalService;
use App\Services\Loan\TrainingAttendanceService;
use App\Services\Exports\SignedCodeListPdfService;
use App\Services\Exports\LetterOfAuthorityPdfService;
use App\Services\IdempotencyService;
use App\Support\PhaseDocumentType;
use App\Support\PhaseWorkflowStatus;
use App\Livewire\Concerns\ManagesPhaseDetailEditing;
use App\Livewire\Concerns\ManagesPhaseDisbursement;
use App\Models\TempApplication;
use App\Services\Application\TempApplicationDeleteService;
use App\Services\Loan\PhaseDetailEditService;
use App\Support\LmsGracePeriod;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ApplicationPhaseDetails extends Component
{
    use WithFileUploads, WithPagination, ManagesPhaseDisbursement, ManagesPhaseDetailEditing;

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
    public $schedulingTrainingAppId;

    public $approvedStatuses = ['pending_submission_to_hq', 'pending_hq_approval', 'in_training', 'approved'];


    #[Url(except: '')]
    public $sortField = 'updated';
    public $sortDirection = 'desc';

    public $remarks = '';

    public $extraUploads = [];
    public $minutesFile;
    public $bankReconciliationMainFile;
    public $bankReconciliationMainPeriod;
    public $bankReconciliationRepaymentFile;
    public $bankReconciliationRepaymentPeriod;
    public $transferLetterFile;
    public $selectedAppId;
    public $selectedGroupId;
    public $selectedRejectionKey;
    public $rejectionComment = '';

    public $app_amount_issued;
    public $app_approved_amount;
    public $app_cheque_number;
    public $app_group_identifier;
    public $app_disbursement_comment;
    public $batchChequePrefix = '';
    public $batchDisbursementComment = '';

    public ?int $selectedPhaseId = null;
    public string $deletingPhaseName = '';
    public int $deletingPhaseGroupCount = 0;
    public int $deletingPhaseMemberCount = 0;

    public ?int $deletingApplicationId = null;
    public string $deletingApplicationLabel = '';
    public bool $gracePeriodActive = false;
    public string $gracePeriodEndsAt = '';

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
        $this->gracePeriodActive = LmsGracePeriod::isActive();
        $this->gracePeriodEndsAt = LmsGracePeriod::endsAt()?->format('j M Y') ?? '';
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
        unset($this->isHistoricalPhase);
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

    public function uploadBankReconciliationMain(): void
    {
        $this->uploadBankReconciliationOfType(
            PhaseDocumentType::BANK_RECONCILIATION_MAIN_ACCOUNT,
            'Main account bank reconciliation',
            $this->bankReconciliationMainFile,
            $this->bankReconciliationMainPeriod,
            'bankReconciliationMainFile',
            'bankReconciliationMainPeriod',
        );
        $this->reset('bankReconciliationMainFile', 'bankReconciliationMainPeriod');
    }

    public function uploadBankReconciliationLoanRepayment(): void
    {
        $this->uploadBankReconciliationOfType(
            PhaseDocumentType::BANK_RECONCILIATION_LOAN_REPAYMENT,
            'Loan repayment bank reconciliation',
            $this->bankReconciliationRepaymentFile,
            $this->bankReconciliationRepaymentPeriod,
            'bankReconciliationRepaymentFile',
            'bankReconciliationRepaymentPeriod',
        );
        $this->reset('bankReconciliationRepaymentFile', 'bankReconciliationRepaymentPeriod');
    }

    protected function uploadBankReconciliationOfType(
        string $documentType,
        string $title,
        $file,
        ?string $statementPeriod,
        string $fileField,
        string $periodField,
    ): void {
        if (!$this->canUploadBankReconciliationType($documentType)) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied',
                message: 'You cannot upload this bank reconciliation for the current phase.',
                type: 'error'
            );
            return;
        }

        try {
            app(BankReconciliationService::class)->validateStatementPeriod($statementPeriod ?? '', $periodField);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }
            return;
        }

        $this->validate([
            $fileField => 'required|file|mimes:pdf,jpg,jpeg,png,gif,webp|max:5120',
        ], [
            "{$fileField}.max" => 'The document is too heavy. Please keep it under 5MB.',
            "{$fileField}.mimes" => 'Only PDF and image files (JPG, PNG, GIF, WEBP) are accepted.',
            "{$fileField}.required" => 'Please attach the bank reconciliation document before proceeding.',
        ]);

        try {
            $filePayload = [
                'fileName' => str_replace('_', '-', $documentType) . '-',
                'modal' => $fileField,
                'documentType' => $documentType,
                'title' => $title,
                'statementPeriod' => $statementPeriod,
            ];
            $this->phaseService->uploadPhaseTypedDocument($this->phase, $file, $filePayload);

            $this->refreshData();

            $this->dispatch('swal:toast',
                title: 'Bank Reconciliation Upload',
                message: $title . ' uploaded successfully.',
                type: 'success'
            );
        } catch (ValidationException $ve) {
            $this->addError($fileField, collect($ve->errors())->flatten()->first());
        } catch (\Exception $e) {
            logger()->error('Bank reconciliation upload error: ' . $e->getMessage());
            $this->dispatch('swal:toast',
                title: 'Upload failed',
                message: 'Failed to upload your document. Please try again later.',
                type: 'error'
            );
        }
    }

    public function uploadRequestForTransferLetter()
    {
        if (!$this->canUploadRequestForTransferLetter()) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied',
                message: 'You cannot upload this document for this phase.',
                type: 'error'
            );
            return;
        }

        $this->validate([
            'transferLetterFile' => 'required|file|mimes:pdf,doc,docx|max:2048',
        ], [
            'transferLetterFile.max' => 'The document is too heavy. Please keep it under 2MB.',
            'transferLetterFile.mimes' => 'Only PDF and Word documents are accepted.',
            'transferLetterFile.required' => 'Please attach the request for transfer letter.',
        ]);

        try {
            $filePayload = [
                'fileName' => 'request-for-transfer-letter-',
                'modal' => 'transferLetterFile',
                'documentType' => PhaseDocumentType::REQUEST_FOR_TRANSFER_LETTER,
                'title' => 'Request for Transfer Letter',
            ];
            $this->phaseService->uploadPhaseTypedDocument($this->phase, $this->transferLetterFile, $filePayload);

            $this->reset('transferLetterFile');
            $this->refreshData();

            $this->dispatch('swal:toast',
                title: 'Transfer Letter Upload',
                message: 'Request for transfer letter uploaded successfully.',
                type: 'success'
            );
        } catch (ValidationException $ve) {
            $this->addError('transferLetterFile', $ve->errors()['transferLetterFile'][0] ?? $ve->getMessage());
        } catch (\Exception $e) {
            logger()->error('Transfer letter upload error: ' . $e->getMessage());
            $this->dispatch('swal:toast',
                title: 'Upload failed',
                message: 'Failed to upload your document. Please try again later.',
                type: 'error'
            );
        }
    }

    public function canUploadBankReconciliationMain(): bool
    {
        return $this->canUploadBankReconciliationType(PhaseDocumentType::BANK_RECONCILIATION_MAIN_ACCOUNT);
    }

    public function canUploadBankReconciliationLoanRepayment(): bool
    {
        return $this->canUploadBankReconciliationType(PhaseDocumentType::BANK_RECONCILIATION_LOAN_REPAYMENT);
    }

    public function canUploadBankReconciliationType(string $documentType): bool
    {
        $bankRecon = app(BankReconciliationService::class);

        $alreadyUploaded = match ($documentType) {
            PhaseDocumentType::BANK_RECONCILIATION_MAIN_ACCOUNT => $bankRecon->hasMainAccountReconciliation($this->detailedPhase),
            PhaseDocumentType::BANK_RECONCILIATION_LOAN_REPAYMENT => $bankRecon->hasLoanRepaymentReconciliation($this->detailedPhase),
            default => false,
        };

        if ($alreadyUploaded) {
            return false;
        }

        return $this->canUploadConstituencyPhaseDocument();
    }

    public function canUploadRequestForTransferLetter(): bool
    {
        if ($this->detailedPhase->has_request_for_transfer_letter ?? false) {
            return false;
        }

        return $this->canUploadConstituencyPhaseDocument();
    }

    protected function canUploadConstituencyPhaseDocument(): bool
    {
        $user = auth()->user();

        if (!$user || !$user->can('submit application')) {
            return false;
        }

        if (!in_array($this->phase->status, ['draft', 'submitted_to_hq'], true)) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        if (!$user->hasRole('constituency_officer')) {
            return false;
        }

        return $user->constituencies->contains('constituency_code', $this->phase->constituency_code);
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
        if (!$this->hasAllMandatoryHqDocuments() || $this->detailedPhase->pending_count > 0)
        {
            $missing = $this->missingMandatoryHqDocuments();
            if ($this->detailedPhase->pending_count > 0) {
                $missing[] = 'completion of all application reviews';
            }

            $this->dispatch('swal:toast',
                title: 'Submitting to HQ',
                message: 'Cannot submit yet. Required: ' . implode(', ', $missing) . '.',
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
        if ($missing = $this->missingMandatoryHqDocuments()) {
            $this->dispatch('swal:toast',
                title: 'Cannot submit to HQ',
                message: 'Required before submission: ' . implode(', ', $missing) . '.',
                type: 'error'
            );
            $this->dispatch('close-modal', name: 'phase-submission-modal');

            return;
        }

        if ($this->detailedPhase->pending_count > 0) {
            $this->dispatch('swal:toast',
                title: 'Cannot submit to HQ',
                message: 'Complete reviewing all applications before submitting this phase.',
                type: 'error'
            );
            $this->dispatch('close-modal', name: 'phase-submission-modal');

            return;
        }

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

    /**
     * @return list<array{type: string, label: string, uploaded: bool}>
     */
    #[Computed]
    public function mandatoryHqSubmissionDocuments(): array
    {
        $phase = $this->detailedPhase;

        return collect(PhaseDocumentType::requiredForConstituencyHqSubmission())
            ->map(fn (string $type) => [
                'type' => $type,
                'label' => PhaseDocumentType::label($type),
                'uploaded' => match ($type) {
                    PhaseDocumentType::MINUTES => (bool) $phase->has_minutes,
                    PhaseDocumentType::BANK_RECONCILIATION_MAIN_ACCOUNT => (bool) ($phase->has_bank_reconciliation_main ?? false),
                    PhaseDocumentType::BANK_RECONCILIATION_LOAN_REPAYMENT => (bool) ($phase->has_bank_reconciliation_repayment ?? false),
                    PhaseDocumentType::REQUEST_FOR_TRANSFER_LETTER => (bool) ($phase->has_request_for_transfer_letter ?? false),
                    default => $phase->phaseApplicationDocuments->contains('document_type', $type),
                },
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public function missingMandatoryHqDocuments(): array
    {
        return collect($this->mandatoryHqSubmissionDocuments)
            ->reject(fn (array $document) => $document['uploaded'])
            ->pluck('label')
            ->values()
            ->all();
    }

    public function hasAllMandatoryHqDocuments(): bool
    {
        return $this->missingMandatoryHqDocuments() === [];
    }

    protected function trainingCertificateService(): TrainingCertificateService
    {
        return app(TrainingCertificateService::class);
    }

    protected function secretariatApprovalService(): SecretariatApprovalService
    {
        return app(SecretariatApprovalService::class);
    }

    protected function trainingAttendanceService(): TrainingAttendanceService
    {
        return app(TrainingAttendanceService::class);
    }

    /**
     * @return array{label: string, value: string, class: string}
     */
    public function phaseDisplayStatus(): array
    {
        return PhaseWorkflowStatus::forPhase($this->detailedPhase);
    }

    public function canDownloadSignedCodeList(): bool
    {
        return app(SignedCodeListPdfService::class)->canDownload($this->detailedPhase);
    }

    public function showsApprovedCodeListDownload(): bool
    {
        return $this->canDownloadSignedCodeList()
            || $this->detailedPhase->phaseApplicationDocuments
                ->contains('document_type', PhaseDocumentType::SIGNED_APPROVED_CODE_LIST);
    }

    public function canDownloadProcessedCodeList(): bool
    {
        $phase = $this->detailedPhase;

        return $phase->phaseApplications()->where('status', 'approved')->exists()
            && in_array($phase->status, [
                'submitted_to_hq',
                'pending_hq_approval',
                'hq_review',
                'approved',
                'in_training',
                'disbursed',
            ], true);
    }

    public function canDownloadLetterOfAuthority(): bool
    {
        return app(LetterOfAuthorityPdfService::class)->canDownload($this->detailedPhase);
    }

    public function canScheduleTraining(): bool
    {
        return $this->approvedGroupsAwaitingTrainingCount() > 0
            && $this->trainingSchedulingAllowed();
    }

    public function canScheduleGroupTraining(PhaseApplication $application): bool
    {
        return $application->status === 'approved' && $this->trainingSchedulingAllowed();
    }

    public function approvedGroupsAwaitingTrainingCount(): int
    {
        return $this->detailedPhase->phaseApplications()->where('status', 'approved')->count();
    }

    protected function trainingSchedulingAllowed(): bool
    {
        $phase = $this->detailedPhase;

        if (!$phase->canBeEditedBy(auth()->user())) {
            return false;
        }

        if (!in_array($phase->status, ['approved', 'in_training'], true)) {
            return false;
        }

        return $this->secretariatApprovalService()->missingTrainingGateApprovals($phase) === [];
    }

    /**
     * @return list<string>
     */
    public function trainingScheduleBlockers(): array
    {
        if (!in_array($this->detailedPhase->status, ['approved', 'in_training'], true)) {
            return [];
        }

        return $this->secretariatApprovalService()->missingTrainingGateApprovals($this->detailedPhase);
    }

    public function pendingTrainingCompletionCount(): int
    {
        if (!$this->showsTrainingDisbursementPanel()) {
            return 0;
        }

        return $this->detailedPhase->phaseApplications()
            ->where('status', 'in_training')
            ->where('is_trained', false)
            ->count();
    }

    public function showsTrainingDisbursementPanel(): bool
    {
        $phase = $this->detailedPhase;

        if ($phase->status === 'in_training') {
            return true;
        }

        return $phase->status === 'disbursed' && $phase->hasPendingTrainingOrDisbursement();
    }

    public function canFinalizePhase(): bool
    {
        if (!$this->phase->canBeEditedBy(auth()->user())) {
            return false;
        }

        return $this->detailedPhase->canFinalizeDisbursement();
    }

    public function canMarkAllTrainingComplete(): bool
    {
        return $this->showsTrainingDisbursementPanel()
            && $this->phase->canBeEditedBy(auth()->user())
            && $this->pendingTrainingCompletionCount() > 0;
    }

    public function openConfirmAllTrainingModal(): void
    {
        if (!$this->canMarkAllTrainingComplete()) {
            $this->dispatch('swal:toast', title: 'Nothing to update', message: 'All groups are already marked as training complete.', type: 'info');
            return;
        }

        $this->dispatch('open-modal', name: 'confirm-all-training-modal');
    }

    public function confirmAllTraining(): void
    {
        if (!$this->canMarkAllTrainingComplete()) {
            $this->dispatch('swal:toast', title: 'Permission Denied', message: 'You cannot update training for this phase.', type: 'error');
            $this->dispatch('close-modal', name: 'confirm-all-training-modal');
            return;
        }

        try {
            $results = $this->appService->confirmAllTrainingInPhase($this->phase->id);
            $this->refreshData();
            $this->dispatch('close-modal', name: 'confirm-all-training-modal');

            if ($results['success'] > 0 && $results['failed'] === 0) {
                $this->dispatch('swal:toast',
                    title: 'Training complete',
                    message: "{$results['success']} group(s) marked as training complete. Members notified by SMS.",
                    type: 'success'
                );
            } elseif ($results['success'] > 0) {
                $this->dispatch('swal:toast',
                    title: 'Partial update',
                    message: "{$results['success']} succeeded, {$results['failed']} failed.",
                    type: 'warning'
                );
            } else {
                $this->dispatch('swal:toast',
                    title: 'Training update failed',
                    message: $results['errors'][0] ?? 'No groups could be updated.',
                    type: 'error'
                );
            }
        } catch (\Exception $e) {
            $this->dispatch('swal:toast', title: 'Error', message: $e->getMessage(), type: 'error');
        }
    }

    public function canMarkTrainingComplete(PhaseApplication $application): bool
    {
        if (!$this->showsTrainingDisbursementPanel() || !$this->phase->canBeEditedBy(auth()->user())) {
            return false;
        }

        return $application->status === 'in_training' && !$application->is_trained;
    }

    public function canDownloadTrainingCertificate(PhaseApplication $application): bool
    {
        return $this->trainingCertificateService()->isOfficerCertificateAvailable($application);
    }

    public function trainingCertificateNoticeForApp(PhaseApplication $application): ?string
    {
        if (!$application->is_trained || $this->canDownloadTrainingCertificate($application)) {
            return null;
        }

        $application->loadMissing('loanPhase');

        return $this->trainingCertificateService()->certificateAvailabilityMessage(
            $application->loanPhase ?? $this->detailedPhase,
            $application,
        );
    }

    public function trainingCertificateNotice(): ?string
    {
        return $this->trainingCertificateService()->certificateAvailabilityMessage($this->detailedPhase);
    }

    public function canApproveDisbursement(PhaseApplication $application): bool
    {
        return $this->canMarkDisbursed($application, $this->detailedPhase);
    }

    public function pendingDisbursementCount(): int
    {
        return $this->eligibleForDisbursementCount($this->detailedPhase);
    }

    public function openBatchDisbursementModal(): void
    {
        if (!$this->canBatchDisbursePhase($this->detailedPhase)) {
            $this->dispatch('swal:toast', title: 'Nothing to disburse', message: 'No trained groups are awaiting disbursement.', type: 'info');
            return;
        }

        $this->resetErrorBag(['batchChequePrefix', 'batchDisbursementComment']);
        $this->batchChequePrefix = 'CHQ-' . now()->format('Ymd');
        $this->dispatch('open-modal', name: 'batch-disbursement-modal');
    }

    public function processBatchDisbursement(): void
    {
        if (!$this->phase->canManageDisbursementBy(auth()->user())) {
            $this->dispatch('swal:toast', title: 'Permission Denied', message: 'You cannot record disbursement for this phase.', type: 'error');
            $this->dispatch('close-modal', name: 'batch-disbursement-modal');
            return;
        }

        $this->validate([
            'batchChequePrefix' => ['required', 'string', 'min:2', 'max:40'],
            'batchDisbursementComment' => ['nullable', 'string', 'max:500'],
        ], [
            'batchChequePrefix.required' => 'Enter a cheque/reference prefix for this disbursement batch.',
        ]);

        $idempotencyKey = "phase_batch_disburse_{$this->phase->id}";

        if (!$this->idempotencyService->acquire($idempotencyKey, ['action' => 'batch_disburse'])) {
            $this->dispatch('swal:toast', title: 'Action in progress', message: 'A batch disbursement is already running for this phase.', type: 'error');
            return;
        }

        try {
            $results = $this->appService->disburseEligibleApplicationsInPhase(
                $this->phase->id,
                $this->batchChequePrefix,
                $this->batchDisbursementComment ?: null,
            );

            $this->refreshData();
            $this->dispatch('close-modal', name: 'batch-disbursement-modal');
            $this->reset('batchChequePrefix', 'batchDisbursementComment');

            if ($results['success'] > 0 && $results['failed'] === 0) {
                $this->dispatch('swal:toast',
                    title: 'Batch disbursement complete',
                    message: "{$results['success']} group(s) marked as disbursed. Members will be notified by SMS.",
                    type: 'success'
                );
            } elseif ($results['success'] > 0) {
                $this->dispatch('swal:toast',
                    title: 'Partial batch disbursement',
                    message: "{$results['success']} succeeded, {$results['failed']} failed.",
                    type: 'warning'
                );
            } else {
                $this->dispatch('swal:toast',
                    title: 'Batch disbursement failed',
                    message: $results['errors'][0] ?? 'No groups could be disbursed.',
                    type: 'error'
                );
            }
        } catch (\Exception $e) {
            $this->dispatch('swal:toast', title: 'Error', message: $e->getMessage(), type: 'error');
        } finally {
            $this->idempotencyService->release($idempotencyKey);
        }
    }

    public function isPendingAutoRejectionReview(PhaseApplication $application): bool
    {
        return $this->trainingAttendanceService()->isPendingAutoRejectionReview($application);
    }

    public function reinstateAutoRejectedApplication(int $phaseApplicationId): void
    {
        if (!$this->phase->canBeEditedBy(auth()->user())) {
            $this->dispatch('swal:toast', title: 'Permission Denied', message: 'You cannot modify this phase.', type: 'error');
            return;
        }

        try {
            $this->trainingAttendanceService()->reinstateAutoRejected($phaseApplicationId);
            $this->refreshData();
            $this->dispatch('swal:toast', title: 'Reinstated', message: 'Application returned to in-training.', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('swal:toast', title: 'Error', message: $e->getMessage(), type: 'error');
        }
    }

    public function confirmAutoRejectedApplication(int $phaseApplicationId): void
    {
        if (!$this->phase->canBeEditedBy(auth()->user())) {
            $this->dispatch('swal:toast', title: 'Permission Denied', message: 'You cannot modify this phase.', type: 'error');
            return;
        }

        try {
            $this->trainingAttendanceService()->confirmAutoRejected($phaseApplicationId);
            $this->refreshData();
            $this->dispatch('swal:toast', title: 'Confirmed', message: 'Auto-rejection confirmed.', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('swal:toast', title: 'Error', message: $e->getMessage(), type: 'error');
        }
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

        $this->schedulingTrainingAppId = null;
        $this->resetErrorBag(['trainingStartDate', 'trainingEndDate', 'trainingVenue']);
        $this->reset('trainingStartDate', 'trainingEndDate', 'trainingVenue', 'trainingNotes');

        $this->dispatch('open-modal', name: 'set-training-schedule-modal');
    }

    public function scheduleGroupTraining(int $phaseApplicationId): void
    {
        if (!$this->phase->canBeEditedBy(auth()->user())) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied 😕',
                message: 'You Dont have permissions to overwite this phase',
                type: 'error'
            );
            return;
        }

        $application = PhaseApplication::query()
            ->where('loan_phase_id', $this->phase->id)
            ->findOrFail($phaseApplicationId);

        if (!$this->canScheduleGroupTraining($application)) {
            $this->dispatch('swal:toast',
                title: 'Cannot schedule training',
                message: 'This group is not eligible for training scheduling.',
                type: 'error'
            );
            return;
        }

        $this->schedulingTrainingAppId = $phaseApplicationId;
        $this->resetErrorBag(['trainingStartDate', 'trainingEndDate', 'trainingVenue']);
        $this->reset('trainingStartDate', 'trainingEndDate', 'trainingVenue', 'trainingNotes');

        $this->dispatch('open-modal', name: 'set-group-training-schedule-modal');
    }

    public function saveGroupTrainingSchedule(): void
    {
        if (!$this->phase->canBeEditedBy(auth()->user())) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied 😕',
                message: 'You Dont have permissions to overwite this phase',
                type: 'error'
            );
            $this->dispatch('close-modal', name: 'set-group-training-schedule-modal');
            return;
        }

        $application = PhaseApplication::query()
            ->where('loan_phase_id', $this->phase->id)
            ->findOrFail($this->schedulingTrainingAppId);

        if (!$this->canScheduleGroupTraining($application)) {
            $this->dispatch('swal:toast',
                title: 'Cannot schedule training',
                message: 'This group is not eligible for training scheduling.',
                type: 'error'
            );
            $this->dispatch('close-modal', name: 'set-group-training-schedule-modal');
            return;
        }

        $this->validate([
            'trainingStartDate' => 'required|date|after_or_equal:today',
            'trainingEndDate'   => 'required|date|after_or_equal:trainingStartDate',
            'trainingVenue'     => 'required|string|max:255',
            'trainingNotes'     => 'nullable|string|max:1000',
        ]);

        $idempotencyKey = "phase_app_training_{$application->id}";

        if (!$this->idempotencyService->acquire($idempotencyKey, ['phase_application_id' => $application->id])) {
            $this->dispatch('swal:toast',
                title: 'Action in progress: 😕',
                message: 'This group is already being processed.',
                type: 'error'
            );
            $this->dispatch('close-modal', name: 'set-group-training-schedule-modal');
            return;
        }

        try {
            $data = [
                'trainingStartDate' => $this->trainingStartDate,
                'trainingEndDate' => $this->trainingEndDate,
                'trainingVenue' => $this->trainingVenue,
                'trainingNotes' => $this->trainingNotes,
            ];

            $this->phaseService->saveGroupTrainingSchedule($application, $idempotencyKey, $data);

            $this->refreshData();
            $this->dispatch('close-modal', name: 'set-group-training-schedule-modal');
            $this->dispatch('swal:toast',
                title: 'Training scheduled',
                message: 'Group members will be notified by SMS with dates and venue.',
                type: 'success'
            );
            $this->reset('trainingStartDate', 'trainingEndDate', 'trainingVenue', 'trainingNotes', 'schedulingTrainingAppId');
        } catch (\Exception $th) {
            $this->dispatch('swal:toast',
                title: 'System Error 😕',
                message: 'Could not set the training schedule. Please try again or contact support.',
                type: 'error'
            );
            $this->dispatch('close-modal', name: 'set-group-training-schedule-modal');
            $this->reset('trainingStartDate', 'trainingEndDate', 'trainingVenue', 'trainingNotes', 'schedulingTrainingAppId');
            $this->idempotencyService->release($idempotencyKey);
        }
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
            'trainingNotes'     => 'nullable|string|max:1000',
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
                'trainingNotes' => $this->trainingNotes,
            ];

            $scheduledCount = $this->approvedGroupsAwaitingTrainingCount();

            $this->phaseService->saveTrainingSchedule($this->detailedPhase, $idempotencyKey, $data);

            $this->refreshData();

            $this->dispatch('close-modal', name: 'set-training-schedule-modal');

            $this->dispatch('swal:toast',
                title: 'Training Set Successfully 😊',
                message: "{$scheduledCount} group(s) scheduled with the same dates and venue. Group members will be notified by SMS.",
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
                title: 'Training update failed',
                message: $th->getMessage() ?: 'Could not mark training. Please try again or contact support.',
                type: 'error'
            );

            $this->reset('selectedAppId');

            $this->dispatch('close-modal', name: 'confirm-training-modal');
        }
    }

    public function toggleNotDisbursed($appId)
    {
        $this->resetErrorBag(['disbursementFailureReason', 'disbursementFailureNotes']);

        if (!$this->phase->canManageDisbursementBy(auth()->user())) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied 😕',
                message: 'You cannot record disbursement for this phase',
                type: 'error'
            );
            return;
        }

        $this->selectedAppId = $appId;
        $this->dispatch('open-modal', name: 'not-disbursed-modal');
    }

    public function processNotDisbursed()
    {
        if (!$this->phase->canManageDisbursementBy(auth()->user())) {
            $this->dispatch('swal:toast', title: 'Permission Denied', message: 'You cannot modify this phase.', type: 'error');
            $this->dispatch('close-modal', name: 'not-disbursed-modal');
            return;
        }

        $this->validate([
            'disbursementFailureReason' => ['required', Rule::in(array_column($this->disbursementFailureReasonOptions(), 'id'))],
            'disbursementFailureNotes' => ['required', 'string', 'min:20', 'max:500'],
        ], [
            'disbursementFailureReason.required' => 'Select a reason disbursement was not completed.',
            'disbursementFailureNotes.required' => 'Provide details (at least 20 characters).',
        ]);

        $idempotencyKey = "application_action_{$this->selectedAppId}";

        if (!$this->idempotencyService->acquire($idempotencyKey, ['action' => 'not_disbursed'])) {
            $this->dispatch('swal:toast', title: 'Action in progress', message: 'This application is already being processed.', type: 'error');
            return;
        }

        try {
            $this->appService->recordDisbursementFailure($this->selectedAppId, $idempotencyKey, [
                'failure_reason' => $this->disbursementFailureReason,
                'failure_notes' => $this->disbursementFailureNotes,
            ]);

            $this->refreshData();
            $this->dispatch('close-modal', name: 'not-disbursed-modal');
            $this->dispatch('swal:toast', title: 'Recorded', message: 'Marked as not disbursed. All group members will be notified by SMS.', type: 'success');
            $this->reset('disbursementFailureReason', 'disbursementFailureNotes', 'selectedAppId');
        } catch (\Exception $e) {
            $this->idempotencyService->release($idempotencyKey);
            $this->dispatch('swal:toast', title: 'Error', message: $e->getMessage(), type: 'error');
        }
    }

    public function toggleDisbursement($appId)
    {
        $this->resetErrorBag(['app_amount_issued', 'app_cheque_number', 'app_group_identifier', 'app_disbursement_comment']);

        if (!$this->phase->canManageDisbursementBy(auth()->user())) {
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
        if (!$this->phase->canManageDisbursementBy(auth()->user())) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied 😕',
                message: 'You cannot record disbursement for this phase',
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
                message: 'Loan disbursed. All group members will be notified by SMS with repayment details.',
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
        if ($this->canMarkAllTrainingComplete()) {
            $this->openConfirmAllTrainingModal();
            return;
        }

        if ($this->canBatchDisbursePhase($this->detailedPhase)) {
            $this->openBatchDisbursementModal();
            return;
        }

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
                title: 'Cannot close phase',
                message: $th->getMessage() ?: 'Could not close. Please try again or contact support.',
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

    #[Computed]
    public function isHistoricalPhase(): bool
    {
        return (bool) ($this->phaseSummary['is_historical_upload'] ?? false);
    }

    #[Computed]
    public function historicalStatusOptions(): array
    {
        return [
            ['value' => 'active', 'name' => 'Performing'],
            ['value' => 'defaulted', 'name' => 'Defaulted'],
            ['value' => 'completed', 'name' => 'Cleared'],
        ];
    }

    public function exportReport($document)
    {
        switch ($document) {
            case 'code_list':
                $query  = $this->exportService->exportConstituencyCodeList($this->phase->id);
                try {
                    $fileName = "Code_List_{$this->constituency->constituency_name}_" . now()->format('Ymd_His') . ".xlsx";

                    return Excel::download(
                        new ConstituencyCodeListExport(
                                $query,
                                $this->phase
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

    public function canDeleteApplication(TempApplication $application): bool
    {
        return app(TempApplicationDeleteService::class)->canDelete($application, auth()->user());
    }

    public function canDeletePhaseApplication(PhaseApplication $phaseApplication): bool
    {
        $application = $phaseApplication->tempApplication;

        return $application !== null && $this->canDeleteApplication($application);
    }

    public function confirmDeleteApplication(int $applicationId): void
    {
        $application = TempApplication::query()
            ->where('constituency_code', $this->constituency->constituency_code)
            ->findOrFail($applicationId);

        if (!$this->canDeleteApplication($application)) {
            $this->dispatch('swal:toast',
                title: 'Delete Blocked',
                message: LmsGracePeriod::isActive()
                    ? 'You cannot delete this application. Check your permissions or constituency access.'
                    : 'Only unassigned applications can be deleted after the grace period.',
                type: 'error'
            );

            return;
        }

        $this->deletingApplicationId = $applicationId;
        $this->deletingApplicationLabel = trim(
            ($application->group_name ?: 'Application') . ' (' . $application->application_id . ')'
        );

        $this->dispatch('open-modal', name: 'modal-delete-application');
    }

    public function deleteApplication(): void
    {
        if (!$this->deletingApplicationId) {
            return;
        }

        try {
            $application = TempApplication::query()
                ->where('constituency_code', $this->constituency->constituency_code)
                ->findOrFail($this->deletingApplicationId);

            app(TempApplicationDeleteService::class)->deleteWithAudit($application, auth()->user());

            $this->reset(['deletingApplicationId', 'deletingApplicationLabel']);
            $this->dispatch('close-modal', name: 'modal-delete-application');
            $this->dispatch('swal:toast',
                title: 'Application Deleted',
                message: 'The application was deleted and a SQL backup was saved.',
                type: 'success'
            );

            $this->refreshData();
        } catch (ValidationException $e) {
            $this->dispatch('swal:toast',
                title: 'Delete Failed',
                message: collect($e->errors())->flatten()->first(),
                type: 'error'
            );
        } catch (\Throwable $e) {
            $this->dispatch('swal:toast',
                title: 'Delete Failed',
                message: $e->getMessage() ?: 'Could not delete this application.',
                type: 'error'
            );
        }
    }

    public function deletePhase(int $phaseId): void
    {
        $phase = LoanPhase::query()
            ->where('constituency_code', $this->constituency->constituency_code)
            ->findOrFail($phaseId);

        $counts = app(PhaseDetailEditService::class)->deletePreviewCounts($phase);

        $this->selectedPhaseId = $phaseId;
        $this->deletingPhaseName = $phase->phase;
        $this->deletingPhaseGroupCount = $counts['group_loans'];
        $this->deletingPhaseMemberCount = $counts['group_members'];

        $this->dispatch('open-modal', name: 'confirm-deletePhase-modal');
    }

    public function confirmDeletePhase(): void
    {
        if (!$this->selectedPhaseId) {
            return;
        }

        $phase = LoanPhase::query()
            ->where('constituency_code', $this->constituency->constituency_code)
            ->find($this->selectedPhaseId);

        if (!$phase) {
            $this->dispatch('swal:toast',
                title: 'Invalid Phase',
                message: 'Could not delete this phase.',
                type: 'error'
            );
            $this->resetPhaseDeleteState();
            $this->dispatch('close-modal', name: 'confirm-deletePhase-modal');

            return;
        }

        if ($phase->phaseApplications()->exists() && !LmsGracePeriod::isActive()) {
            $this->dispatch('swal:toast',
                title: 'Delete Blocked',
                message: 'Remove all applications from this phase before deleting it.',
                type: 'error'
            );
            $this->resetPhaseDeleteState();
            $this->dispatch('close-modal', name: 'confirm-deletePhase-modal');

            return;
        }

        try {
            app(PhaseDetailEditService::class)->deleteWithAudit($phase, auth()->user());

            $this->resetPhaseDeleteState();
            $this->dispatch('close-modal', name: 'confirm-deletePhase-modal');
            $this->dispatch('swal:toast',
                title: 'Phase Deleted',
                message: 'Phase removed successfully. A backup was saved.',
                type: 'success'
            );

            $this->redirect(route('const.app.app_phases', [
                $this->constituency->county->slug,
                $this->constituency->slug,
            ]));
        } catch (ValidationException $e) {
            $this->dispatch('swal:toast',
                title: 'Delete Failed',
                message: collect($e->errors())->flatten()->first(),
                type: 'error'
            );
            $this->resetPhaseDeleteState();
            $this->dispatch('close-modal', name: 'confirm-deletePhase-modal');
        } catch (\Throwable $e) {
            $this->dispatch('swal:toast',
                title: 'Delete Failed',
                message: $e->getMessage() ?: 'Could not delete this phase.',
                type: 'error'
            );
            $this->resetPhaseDeleteState();
            $this->dispatch('close-modal', name: 'confirm-deletePhase-modal');
        }
    }

    protected function resetPhaseDeleteState(): void
    {
        $this->reset([
            'selectedPhaseId',
            'deletingPhaseName',
            'deletingPhaseGroupCount',
            'deletingPhaseMemberCount',
        ]);
    }

    public function render()
    {
        return view('livewire.loan.app.application-phase-details', [
            'detailedPhase' => $this->detailedPhase,
        ]);
    }
}
