<?php

namespace App\Livewire;

use App\Exports\CodeListExport;
use App\Exports\StandardVettedExport;
use App\Exports\DisaggregatedDataExport;
use App\Exports\DisbursementDataExport;
use App\Models\Constituency;
use App\Models\LoanPhase;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use App\Services\Loan\LoanPhaseService;
use App\Services\Exports\ExportService;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;



class ApplicationPhaseDetails extends Component
{
    use WithFileUploads, WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $phase;
    public Constituency $constituency;
    public $wardMap = [];

    public $activeTab = 'applications';
    public $search = '';
    public $statusFilter = '';
    public $riskFilter = '';
    public $perPage = 15;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'riskFilter' => ['except' => ''],
    ];

    public $remarks = '';

    public $extraUploads = [];
    public $minutesFile;
    public $selectedAppId;

    protected LoanPhaseService $phaseService;
    protected ExportService $exportService;

    public function boot(LoanPhaseService $phaseService, ExportService $exportService)
    {
        $this->phaseService = $phaseService;
        $this->exportService = $exportService;
    }

    public function mount(Constituency $constituency, LoanPhase $phase)
    {
        $this->constituency = $constituency->load(['county', 'wards']);
        $this->wardMap = $this->constituency->wards->pluck('ward_name', 'id')->toArray();
        $this->phase = $phase;
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
        $this->phaseService->invalidatePhaseCache();
        unset($this->stats);
        unset($this->activePhases);
        unset($this->allPhases);
        $this->resetPage();
        $this->resetFilters();
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedStatusFilter() { $this->resetPage(); }
    public function updatedRiskFilter() { $this->resetPage(); }

    public function resetFilters()
    {
        $this->reset(['search', 'statusFilter', 'riskFilter', 'perPage', 'remarks']);
    }

    #[Computed]
    public function documentsCount()
    {
        return $this->phase->phaseApplicationDocuments->count() ?? 0;
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

        $this->dispatch('flash-info', message: $uploaded_files . ' ' . Str::plural('file', $uploaded_files). ' uploaded successfully! ');
    }

    public function uploadMinutes()
    {
        $this->validate([
            'minutesFile' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ]);

        try {
            $this->phaseService->uploadPhaseMinutes($this->phase, $this->minutesFile);

            $this->reset('minutesFile');

            $this->dispatch('flash-success', message: 'Minutes uploaded successfully');
        } catch (ValidationException $ve) {
            //add error to the minutesFile field
            $this->addError('minutesFile', $ve->errors()['minutesFile'][0]);
        }
        catch (\Exception $e) {
            logger()->error('Minutes upload error: ' . $e->getMessage());
            $this->dispatch('flash-error', message: 'Minutes upload failed: ' . $e->getMessage());
            return;
        }

    }

    public function deleteDocument($docId)
    {
        try {
            $this->phaseService->deleteApplicationPhaseDocument($docId);

            $this->dispatch('flash-success', message: 'Document deleted successfully');
        } catch (\Exception $e) {
            logger()->error('Document deletion error: ' . $e->getMessage());
            $this->dispatch('flash-error', message: 'Document deletion failed: ' . $e->getMessage());
            return;
        }
    }

    public function submitToHq()
    {
        if ( !($this->phase->has_minutes && $this->detailedPhase->pending_count == 0))
        {
            $this->dispatch('swal:modal', [
                'type'  => 'error',
                'title' => "ERROR: Submitting to HQ",
                'text'  => 'The Phase has pending actions',
            ]);

            return false;
        }

        $this->dispatch('open-modal', name: 'hqSubmissionModal');
    }

    public function executeHqSubmission($data = null)
    {
        if (!$this->phase->canBeEditedBy(auth()->user())) {
            $this->dispatch('swal:modal', [
                'type'  => 'error',
                'title' => 'Access Denied',
                'text'  => 'You Dont have permissions to overwite this phase'
            ]);
            return;
        }

        $phase = $this->detailedPhase;

        if ($phase->status === 'submitted_to_hq' || $phase->is_locked) {
            $this->dispatch('swal:modal', [
                'type'  => 'error',
                'title' => 'Action Blocked',
                'text'  => 'This phase has already been submitted and is locked.'
            ]);
            return;
        }

        if ($this->detailedPhase->pending_count > 0) {
            $this->dispatch('swal:modal', [
                'type'  => 'warning',
                'title' => 'Incomplete Phase',
                'text'  => "There are still {$this->detailedPhase->pending_count} pending applications. All must be approved or rejected before HQ submission."
            ]);
            return;
        }

        $this->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        try {
            $this->phaseService->submitToHq($phase->id, $this->remarks, $this->constituency->constituency_code);

            $this->dispatch('swal:modal', [
                'type'  => 'success',
                'title' => 'Submitted Successfully',
                'text'  => 'Phase has been locked and moved to HQ Review.'
            ]);

            $this->refreshData();

            $this->dispatch('close-modal', name: 'hqSubmissionModal');
        } catch (\Exception $e) {
            $this->dispatch('swal:modal', [
                'type'  => 'error',
                'title' => 'System Error',
                'text'  => 'Could not finalize submission. Please try again or contact support.'.$e->getMessage(),
            ]);
        }
    }

    #[Computed]
    public function detailedPhase()
    {
        return $this->phaseSummary['phase'];
    }

    #[Computed]
    public function phaseSummary()
    {
        return $this->phaseService->getDetailedPhase(
            $this->phase,
            $this->perPage,
            $this->getFilters(),
            $this->getPage()
        );
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
                    $this->dispatch('export-error', [
                        'message' => 'Excel export failed: ' . $e->getMessage()
                    ]);
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
                    $this->dispatch('export-error', [
                        'message' => 'Excel export failed: ' . $e->getMessage()
                    ]);
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
                    $this->dispatch('export-error', [
                        'message' => 'Excel export failed: ' . $e->getMessage()
                    ]);
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
                    $this->dispatch('export-error', [
                        'message' => 'Excel export failed: ' . $e->getMessage()
                    ]);
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
            'applications' => $this->phaseSummary['applications'],
            'vettingPercentage' => $this->vettingPercentage,
        ]);
    }
}
