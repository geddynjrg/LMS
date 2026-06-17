<?php

namespace App\Livewire\Hq;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Computed;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\CodeListExport;
use App\Exports\StandardVettedExport;
use App\Exports\DisaggregatedDataExport;
use App\Exports\DisbursementDataExport;
use App\Services\Loan\LoanPhaseService;
use App\Services\Exports\ExportService;
use App\Services\Loan\TempApplicationService;
use App\Services\Loan\LoanAnalysisService;
use App\Services\IdempotencyService;
use App\Models\LoanPhase;
use App\Models\GroupLoan;
use App\Models\Group;
use Carbon\Carbon;

class PhaseApplicationTask extends Component
{
    use WithFileUploads, WithPagination;

    protected $paginationTheme = 'bootstrap';

    protected LoanPhaseService $phaseService;
    protected ExportService $exportService;
    protected TempApplicationService $appService;
    protected LoanAnalysisService $analysisService;
    protected IdempotencyService $idempotencyService;

    public $phase;
    public $global_duplicate_count = 0;
    public $global_default_count = 0;


    #[Url(except: '')]
    public $search = '';

    #[Url(except: '')]
    public $integrityFilter = '';

    public $statusFilter = '';
    public $riskFilter = '';
    public $perPage = 15;

    public $remarks;

    #[Url(except: '')]
    public $sortField = 'updated';
    public $sortDirection = 'desc';

    public $generatedReports = [
        ['id' => 'code_list', 'name' => 'Code List', 'meta' => 'GFS Codes, Loan Levels, Contact Persons & Financials'],
        ['id' => 'standard_vetted', 'name' => 'Standard Vetted Form', 'meta' => 'Full demographics, Bank info, & Sectors'],
        ['id' => 'disaggregated', 'name' => 'Disaggregated Data', 'meta' => 'Gender & PWD breakdown by Ward'],
        ['id' => 'disbursement', 'name' => 'Disbursement Report', 'meta' => 'Bank Accounts & Final Amounts'],
    ];

    public $codeListFile = null;
    public $extraUploads = [];
    public $returnRemarks = '';

    public $approvedStatuses = ['pending_submission_to_hq', 'pending_hq_approval', 'in_training', 'approved'];

    public function boot(
        LoanPhaseService $phaseService,
        ExportService $exportService,
        TempApplicationService $appService,
        LoanAnalysisService $analysisService,
        IdempotencyService $idempotencyService
        )
    {
        $this->phaseService = $phaseService;
        $this->exportService = $exportService;
        $this->appService = $appService;
        $this->analysisService = $analysisService;
        $this->idempotencyService = $idempotencyService;
    }

    public function mount(LoanPhase $phase)
    {
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
            'integrity' => $this->integrityFilter,
        ];
    }

    public function resetFilters()
    {
        $this->reset(['search', 'statusFilter', 'riskFilter', 'integrityFilter']);
        $this->resetPage();
    }

    public function updated($propertyName)
    {
        if ($propertyName === 'returnRemarks') {
            return;
        }

        $this->resetValidation($propertyName);
    }

    public function updatingRiskFilter() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }
    public function updatedPerPage ($value) {if ($value === null) $this->perPage = 10;}

    public function clearFilter($filterName)
    {
        $this->reset($filterName);
    }

    public function refreshData()
    {
        $this->phaseService->invalidatePhaseCache($this->phase->id);
        $this->appService->invalidateStatsCache($this->phase->constituency_code);

        $this->phase->refresh();

        unset($this->documentsCount);
        unset($this->detailedPhase);
        unset($this->phaseSummary);
        unset($this->phaseAnalytics);

        $this->resetFilters();
    }

    public function getStatusOptionsProperty()
    {
        return $this->appService->getHqStatusOptionsProperty();
    }

    #[Computed]
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

    #[Computed]
    public function integrityOptions()
    {
        return [
            ['id'=> 'is_defaulter', 'name'=> 'Defaulters Only'],
            ['id'=> 'is_duplicate', 'name'=> 'Duplicates Only'],
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

    public function addUploadRow()
    {
        $this->extraUploads[] = ['title' => '', 'file' => null];
    }

    public function removeUploadRow($index)
    {
        unset($this->extraUploads[$index]);
        $this->extraUploads = array_values($this->extraUploads);
    }

    public function uploadCodeList()
    {
        if (!$this->codeListFile) {
            $this->addError('codeListFile', 'Please select a file first.');
            return;
        }
        $this->validate([
            'codeListFile' => 'required|file|mimes:pdf,doc,docx,xls,xlsx|max:2048',
        ],
        [
            'codeListFile.max' => 'The document is too heavy. Please keep it under 2MB.',
            'codeListFile.mimes' => 'Only PDF, Word, and Excel documents are accepted.',
            'codeListFile.required' => 'Please attach the required document before proceeding.',
        ]);

        if (!$this->canUploadDocument()) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied 😕',
                message: 'You do not have the right to perform this action',
                type: 'error'
            );
            return;
        }

        try {
            $filePayload = [
                'fileName'      => 'approved-code-list-',
                'modal'         => 'codeListFile',
                'documentType'  => 'approved_code_list',
                'title'         => 'Approved Code List'
            ];

            $this->phaseService->uploadPhaseTypedDocument($this->phase, $this->codeListFile, $filePayload);

            $this->reset('codeListFile');

            $this->refreshData();

            $this->dispatch('swal:toast',
                title: 'Code List Upload 😊',
                message: 'Code List uploaded successfully! ',
                type: 'success'
            );
        }catch (\Exception $e) {
            logger()->error('Code List upload error: ' . $e->getMessage());
            $this->dispatch('swal:toast',
                title: 'Code List upload 😕',
                message: 'Failed to upload your document. Please try again later',
                type: 'error'
            );
            return;
        }


    }

    public function getFileMetadata($file)
    {
        return $this->phaseService->getFileMetadata($file);
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
                title: 'Remove Document 😊',
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

    public function canDeleteDocument($doc) : bool
    {
        $user = auth()->user();

        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($doc->uploaded_by === $user->id && $this->phase->status === 'submitted_to_hq') {
            return true;
        }

        return false;
    }

    public function canUploadDocument() : bool
    {
        $user = auth()->user();

        if ($user->hasRole('super-admin')) {
            return true;
        }

        if ($this->canApproveApplication() && $this->phase->status === 'submitted_to_hq') {
            return true;
        }

        return false;
    }

    public function canApproveApplication() : bool
    {
        $user = auth()->user();
        return $user->hasPermissionTo('approve application');
    }

    public function exportReport($document)
    {
        switch ($document) {
            case 'code_list':
                $query  = $this->exportService->exportCodeList($this->phase->id);
                try {
                    $fileName = "Code_List_{$this->phase->constituency->constituency_name}_" . now()->format('Ymd_His') . ".xlsx";

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
                    $fileName = "Standard_Vetted_Form_{$this->phase->constituency->constituency_name}_" . now()->format('Ymd_His') . ".xlsx";

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
                        message: "Could not downoload your document. Please try again." .$e->getMessage(),
                        type: 'error'
                    );
                    return null;
                }
                break;

            case 'disaggregated':
                $query  = $this->exportService->exportdisaggregatedDataQuery($this->phase->id);
                try {
                    $fileName = "Disaggregated_Data_Form_{$this->phase->constituency->constituency_name}_" . now()->format('Ymd_His') . ".xlsx";

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
                    $fileName = "Disbursement_Data_Form_{$this->phase->constituency->constituency_name}_" . now()->format('Ymd_His') . ".xlsx";

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

    public function canReturnToConstituency(): bool
    {
        $status = $this->detailedPhase->status ?? $this->phase->status;

        return $this->canApproveApplication()
            && $status === 'submitted_to_hq'
            && ($this->detailedPhase->pending_hq_approval_count ?? 0) > 0;
    }

    public function returnPhaseToConstituency(): void
    {
        if (!$this->canReturnToConstituency()) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied',
                message: 'You cannot return this phase to the constituency.',
                type: 'error'
            );
            return;
        }

        $this->resetErrorBag(['returnRemarks']);
        $this->dispatch('open-modal', name: 'phase-return-modal');
    }

    public function executeReturnToConstituency(): void
    {
        if (!$this->canReturnToConstituency()) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied',
                message: 'You cannot return this phase to the constituency.',
                type: 'error'
            );
            $this->dispatch('close-modal', name: 'phase-return-modal');
            return;
        }

        $this->validate([
            'returnRemarks' => 'required|string|min:20|max:1000',
        ], [
            'returnRemarks.required' => 'Please explain why this phase is being returned for amendment.',
            'returnRemarks.min' => 'The return reason must be at least 20 characters.',
        ]);

        $idempotencyKey = "phase_return_to_constituency_{$this->phase->id}";

        if (!$this->idempotencyService->acquire($idempotencyKey, [
            'phase_id' => $this->phase->id,
            'remarks' => $this->returnRemarks,
        ])) {
            $this->dispatch('swal:toast',
                title: 'Action in progress',
                message: 'This phase is already being processed.',
                type: 'error'
            );
            $this->dispatch('close-modal', name: 'phase-return-modal');
            return;
        }

        try {
            $this->phaseService->returnPhaseToConstituency(
                $this->detailedPhase,
                $this->returnRemarks,
                $idempotencyKey,
            );

            $this->refreshData();
            $this->reset('returnRemarks');
            $this->dispatch('close-modal', name: 'phase-return-modal');
            $this->dispatch('swal:toast',
                title: 'Phase returned',
                message: 'The phase has been sent back to the constituency dashboard for amendment.',
                type: 'success'
            );
        } catch (ValidationException $e) {
            $this->dispatch('swal:toast',
                title: 'Validation error',
                message: collect($e->errors())->flatten()->first() ?? $e->getMessage(),
                type: 'error'
            );
            $this->idempotencyService->release($idempotencyKey);
        } catch (\Exception $e) {
            $this->dispatch('swal:toast',
                title: 'System error',
                message: 'Could not return the phase. Please try again or contact support.',
                type: 'error'
            );
            $this->idempotencyService->release($idempotencyKey);
        }
    }

    public function approvePhase()
    {
        if (!$this->canApproveApplication()) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied 😕',
                message: 'You do not have the right to perform this action',
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

        $this->dispatch('open-modal', name: 'phase-approval-modal');
    }

    public function executeApproval()
    {
        if (!$this->canApproveApplication()) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied 😕',
                message: 'You do not have the right to perform this action',
                type: 'error'
            );
            $this->dispatch('close-modal', name: 'phase-approval-modal');
            return;
        }

        if (!$this->phase->canBeEditedBy(auth()->user())) {
            $this->dispatch('swal:toast',
                title: 'Permission Denied 😕',
                message: 'You Dont have permissions to overwite this phase',
                type: 'error'
            );
            $this->dispatch('close-modal', name: 'phase-approval-modal');
            return;
        }

        $this->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        $idempotencyKey = "phase_approval_{$this->phase->id}";

        $payload = [
            'phase_id' => $this->phase->id,
            'remarks'  => $this->remarks,
            'count'    => $this->detailedPhase->phaseApplications()->where('status', 'pending_hq_approval')->count()
        ];

        // if (!$this->idempotencyService->acquire($idempotencyKey, $payload)) {
        //     $this->dispatch('swal:toast',
        //         title: 'Action in progress: 😕',
        //         message: "This phase is already being processed.",
        //         type: 'error'
        //     );

        //     $this->dispatch('close-modal', name: 'phase-approval-modal');

        //     return;
        // }

        try {
            $this->phaseService->approvePhaseApplications($this->detailedPhase, $this->remarks, $idempotencyKey);

            $this->refreshData();

            $this->dispatch('close-modal', name: 'phase-approval-modal');

            $this->dispatch('swal:toast',
                title: 'Approval Successfully 😊',
                message: 'Phase has been Approved',
                type: 'success'
            );
        } catch (ValidationException $e) {
            $this->dispatch('swal:toast',
                title: 'System Error 😕',
                message: $e->getMessage(),
                type: 'error'
            );

            // $this->addError('phase_submission', $e->getMessage());

            $this->idempotencyService->release($idempotencyKey);
        } catch (\Exception $e) {
            $this->dispatch('close-modal', name: 'phase-approval-modal');

            $this->dispatch('swal:toast',
                title: 'System Error 😕',
                message: "Could not finalize approval. Please try again or contact support." . $e->getMessage(),
                type: 'error'
            );

            $this->idempotencyService->release($idempotencyKey);
        }

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
    public function applications()
    {
        return $this->phaseSummary['applications'];
    }

    #[Computed]
    public function getGlobalConflictCount()
    {
        // dd($this->phaseSummary['global_conflict_count']);
        return $this->phaseSummary['global_conflict_count'];
    }

    #[Computed]
    public function getGlobalDebtCount()
    {
        return $this->phaseSummary['global_debt_count'];
    }

    #[computed]
    public function phaseAnalytics()
    {
        $phaseTag = config('cache.tags.phases.individual_detailed') . $this->phase->id;

        $regNumbers = $this->applications->getCollection()->pluck('tempApplication.group_registration_number')->filter()->toArray();

        return $this->analysisService->performGlobalCheck($regNumbers, $this->phase->id);
        // return Cache::tags([$phaseTag])->remember('phase_integrity_stats', now()->addMinutes(15), function () use ($regNumbers) {
        //     return $this->analysisService->performGlobalCheck($regNumbers, $this->phase->id);
        // });
    }

    public function render()
    {
        $this->global_duplicate_count = $this->phaseAnalytics['current']->count();
        $this->global_default_count = $this->phaseAnalytics['legacy']->count();

        return view('livewire.hq.phase-application-task');
    }
}
