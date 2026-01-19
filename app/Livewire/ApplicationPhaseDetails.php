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
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedStatusFilter() { $this->resetPage(); }
    public function updatedRiskFilter() { $this->resetPage(); }

    public function resetFilters()
    {
        $this->reset(['search', 'statusFilter', 'riskFilter', 'perPage']);
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
        $this->dispatch('confirm-submission-to-hq',
            title       : 'Final Submission to HQ',
            html        : "<div class='text-left px-2' style='max-height: 450px; overflow-y: auto; overflow-x: hidden;'>
                            <div class='alert alert-warning border-left-warning shadow-sm mb-4' style='position: sticky; top: 0; z-index: 10;'>
                                <div class='d-flex align-items-center'>
                                    <i class='fas fa-exclamation-triangle fa-2x mr-3 text-warning'></i>
                                    <div>
                                        <strong>STERN WARNING:</strong><br>
                                        <small>Once submitted, this phase is <strong>LOCKED</strong>. No further corrections are possible.</small>
                                    </div>
                                </div>
                            </div>

                            <p class='mb-2 text-muted'>Review the phase details below:</p>
                            <h6 class='text-primary mb-3'><strong>{$this->phase->phase}</strong></h6>

                            <table class='table table-sm table-hover border mb-3'>
                                <tr>
                                    <td><strong>Total Applications:</strong></td>
                                    <td class='text-right'><strong>" . number_format($this->detailedPhase->total_applications) . "</strong></td>
                                </tr>
                                <tr>
                                    <td><strong>Total Disbursement:</strong></td>
                                    <td class='text-right text-success'><strong>KES " . number_format($this->detailedPhase->total_approved, 2) . "</strong></td>
                                </tr>
                                <tr>
                                    <td><strong>Constituency:</strong></td>
                                    <td class='text-right'>{$this->constituency->constituency_name}</td>
                                </tr>
                                <tr>
                                    <td><strong>Approved:</strong></td>
                                    <td class='text-right'>{$this->phase->pending_submission_to_hq_count}</td>
                                </tr>
                                <tr>
                                    <td><strong>Rejected:</strong></td>
                                    <td class='text-right'>{$this->detailedPhase->rejected_count}</td>
                                </tr>
                                <tr class='bg-light'>
                                    <td><strong>Submission Date:</strong></td>
                                    <td class='text-right'>" . now()->format('d-M-Y H:i') . "</td>
                                </tr>
                            </table>

                            <div class='form-group'>
                                <label class='text-xs font-weight-bold text-uppercase'>Final Remarks (Optional)</label>
                                <textarea class='form-control form-control-sm' id='submissionRemarks' rows='3' placeholder='Enter any final notes for HQ...'></textarea>
                            </div>
                        </div>",
            icon        : '',
            confirmText : 'Confirm & Submit to HQ',
            componentId : $this->getId(),
            action      : 'executeHqSubmission',
            params      : null
        );
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

        // 1. Fetch the phase with its applications
        // $phase = ReviewPhase::with('applications')->findOrFail($this->phaseId);

        // // 2. Guard Clause: Prevent double submission
        // if ($phase->status === 'submitted_to_hq') {
        //     $this->dispatch('swal:modal', [
        //         'type'  => 'error',
        //         'title' => 'Access Denied',
        //         'text'  => 'This phase has already been submitted and is locked.'
        //     ]);
        //     return;
        // }

        // // 3. Validation: Ensure all applications in this phase are vetted
        // $unvettedCount = $phase->applications()->where('status', 'pending')->count();
        // if ($unvettedCount > 0) {
        //     $this->dispatch('swal:modal', [
        //         'type'  => 'warning',
        //         'title' => 'Incomplete Phase',
        //         'text'  => "There are still {$unvettedCount} pending applications. All must be approved or rejected before HQ submission."
        //     ]);
        //     return;
        // }

        // try {
        //     DB::transaction(function () use ($phase, $data) {
        //         // 4. Generate Unique Submission Reference
        //         // Format: UWEZO/HQ/[CONSTITUENCY_CODE]/[YEAR]/[RANDOM]
        //         $ref = 'UWEZO/HQ/' . $this->constituencyCode . '/' . date('Y') . '/' . strtoupper(bin2hex(random_bytes(3)));

        //         // 5. Update the Phase Status
        //         $phase->update([
        //             'status'                => 'submitted_to_hq',
        //             'submitted_at'          => now(),
        //             'submitted_by'          => auth()->id(),
        //             'submission_ref'        => $ref,
        //             'final_remarks'         => $data['remarks'] ?? null,
        //             'is_locked'             => true, // The "No Corrections" enforcer
        //         ]);

        //         // 6. Lock all related applications
        //         $phase->applications()->update([
        //             'is_finalized' => true,
        //             'locked_at'    => now()
        //         ]);

        //         // 7. Log Activity for Audit Trail
        //         ActivityLog::create([
        //             'user_id'   => auth()->id(),
        //             'action'    => 'HQ_SUBMISSION',
        //             'reference' => $ref,
        //             'details'   => "Phase {$phase->name} submitted to HQ with " . $phase->applications()->count() . " records."
        //         ]);
        //     });

        //     $this->dispatch('swal:modal', [
        //         'type'  => 'success',
        //         'title' => 'Submitted Successfully',
        //         'text'  => 'Phase has been locked and moved to HQ Review.'
        //     ]);

        //     return redirect()->route('phases.index');

        // } catch (\Exception $e) {
        //     Log::error("HQ Submission Failed: " . $e->getMessage());
        //     $this->dispatch('swal:modal', [
        //         'type'  => 'error',
        //         'title' => 'System Error',
        //         'text'  => 'Could not finalize submission. Please try again or contact support.'
        //     ]);
        // }
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
