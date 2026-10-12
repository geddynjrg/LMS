<?php
// app/Livewire/PhaseManager.php
namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\LoanReviewPhase;
use App\Models\TempApplication;
use App\Models\PhaseDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\Loan\LoanPhaseService;
use App\Services\Loan\PhaseDetailEditService;
use App\Services\Loan\TempApplicationService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use App\Models\LoanPhase;
use App\Livewire\AddAppsToPhaseModal;
use App\Support\PhaseWorkflowStatus;
use App\Livewire\Concerns\ManagesPhaseDetailEditing;
use App\Support\LmsGracePeriod;


class PhaseManager extends Component
{
    use WithPagination, WithFileUploads, ManagesPhaseDetailEditing;

    protected $paginationTheme = 'bootstrap';

    protected LoanPhaseService $phaseService;
    protected TempApplicationService $appService;

    #[Url(except: '')]
    public $search;

    #[Url(except: '')]
    public $statusFilter;

    public $perPage = 15;

    public $constituency;
    public $selectedPhase = null;

    #[Url(except: 'pending')]
    public $activeTab = 'pending';

    // Phase creation
    public $phaseName = '';
    public $reviewDate;
    public $reviewVenue = '';

    // Document uploads
    public $documentFile;
    public $documentType = 'committee_minutes';
    public $documentDescription = '';

    // Modals
    public $showCreateModal = false;
    public $showAddAppsModal = false;
    public $showUploadDocModal = false;
    public $showCommitteeModal = false;

    // Selection
    public $selectedApplications = [];
    public $selectAll = false;

    public $selectedPhaseId;
    public ?string $deletingPhaseName = null;
    public int $deletingPhaseGroupCount = 0;
    public int $deletingPhaseMemberCount = 0;

    #[Url(except: '')]
    public $sortField = 'name';

    public $sortDirection = 'asc';

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

    public function boot(LoanPhaseService $phaseService, TempApplicationService $appService)
    {
        $this->phaseService = $phaseService;
        $this->appService = $appService;
    }

    public function mount($constituency)
    {
        $this->constituency = $constituency;
        $this->reviewDate = now()->addDays(7)->format('Y-m-d');
    }

    #[Computed]
    public function summary()
    {
        return $this->phaseService->getPhasesSummary($this->constituency->constituency_code);
    }

    #[Computed]
    public function pendingCount()
    {
        return $this->summary['pending_submission'] ?? 0;
    }

    #[Computed]
    public function processingCount()
    {
        return $this->summary['processing_hq'] ?? 0;
    }

    #[Computed]
    public function approvedCount()
    {
        return $this->summary['approved_ready'] ?? 0;
    }

    #[Computed]
    public function completedCount()
    {
        return $this->summary['completed_phases']
            ?? (($this->summary['disbursed'] ?? 0) + ($this->summary['declined'] ?? 0));
    }

    #[Computed]
    public function declinedCount()
    {
        return $this->summary['declined'] ?? 0;
    }

    #[Computed]
    public function disbursedCount()
    {
        return $this->summary['disbursed'] ?? 0;
    }

    #[Computed]
    public function getStatusClasses($value): string
    {
        return $this->appService->getStatusClasses($value);
    }

    public function getStatusOptionsProperty()
    {
        $statusMap = [
            'disbursed' => ['label' => 'Disbursed'],
            'rejected'  => ['label' => 'Rejected'],
        ];

        return collect($statusMap)->map(fn($data, $value) => [
            'value'    => $value,
            'name'  => $data['label'],
        ])->values()->all();
    }

    public function getStatusName($value)
    {
        return collect($this->statusOptions)->firstWhere('value', $value)['name'] ?? $value;
    }

    public function phases()
    {

        $statusMap = [
            'pending'    => ['draft'],
            'processing' => PhaseWorkflowStatus::processingStatuses(),
            'completed'  => PhaseWorkflowStatus::completedStatuses(),
        ];

        $status = $statusMap[$this->activeTab] ?? ['draft'];

        return $this->phaseService->getDetailedPhasesQuery(
            $this->constituency->constituency_code,
            $status,
            $this->getFilters(),
            $this->sortField,
            $this->sortDirection
        );
    }

    private function getFilters(): array
    {
        return [
            'search' => $this->search,
            'status' => $this->statusFilter,
        ];
    }

    public function updatedSearch($value)
    {
        $this->search = trim($value);

        $this->resetPage();
    }

    public function createNewPhase()
    {
        $this->dispatch('open-modal', name: 'create-new-phase-modal');
    }

    public function createPhase()
    {
        $validated = $this->validate([
            'phaseName' => 'required|string|max:255',
            'reviewDate' => 'required|date|after_or_equal:today',
            'reviewVenue' => 'nullable|string|max:255',
        ]);

        try {
            $result = $this->phaseService->createPhase($validated, $this->constituency->constituency_code);

            $this->dispatch('close-modal', name: 'create-new-phase-modal');
            $this->reset(['phaseName', 'reviewVenue']);

            $this->dispatch('swal:toast',
                title: 'Phase Created 😊',
                message: 'Phase created successfully',
                type: 'success'
            );

            $this->dispatch('app-updated')->to(SideNavBadge::class);

            $this->refreshData();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }
        } catch (\Exception $e) {
            Log::error('Phase creation failed', [
                'constituency_code' => $this->constituency->constituency_code ?? null,
                'phaseName' => $this->phaseName ?? null,
                'error' => $e->getMessage(),
            ]);
            $this->dispatch('swal:toast',
                title: 'Error 😕',
                message: "Could not create new phase. Please try again or contact support.",
                type: 'error'
            );
        }
    }

    public function deletePhase($phaseId)
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

    protected function resetPhaseDeleteState(): void
    {
        $this->reset([
            'selectedPhaseId',
            'deletingPhaseName',
            'deletingPhaseGroupCount',
            'deletingPhaseMemberCount',
        ]);
    }

    public function confirmDeletePhase()
    {
        $phase = $this->phaseService->getPhaseStats($this->selectedPhaseId);

        if (!$phase){
            $this->dispatch('swal:toast',
                title: 'Invalid Phase 😕',
                message: "Could not delete phase. Please try again or contact support.",
                type: 'error'
            );

            $this->resetPhaseDeleteState();

            $this->dispatch('close-modal', name: 'confirm-deletePhase-modal');

            return;
        }

        if ($phase->total_apps > 0 && !LmsGracePeriod::isActive()) {

            $this->dispatch('swal:toast',
                title: 'Deleting Phase 😕',
                message: "You have {$phase->total_apps} application(s) in this phase, you need to remove them first",
                type: 'error'
            );

            $this->resetPhaseDeleteState();

            $this->dispatch('close-modal', name: 'confirm-deletePhase-modal');

            return;
        }

        try {
            app(PhaseDetailEditService::class)->deleteWithAudit(
                LoanPhase::findOrFail($this->selectedPhaseId),
                auth()->user(),
            );

            $this->resetPhaseDeleteState();

            $this->dispatch('close-modal', name: 'confirm-deletePhase-modal');

            $this->dispatch('swal:toast',
                title: 'Phase Deleted 😊',
                message: 'Phase removed successfully.',
                type: 'success'
            );

            $this->dispatch('app-updated')->to(SideNavBadge::class);

            $this->refreshData();
        } catch (ValidationException $e) {
            $this->dispatch('swal:toast',
                title: 'Deleting Phase 😕',
                message: collect($e->errors())->flatten()->first(),
                type: 'error'
            );

            $this->resetPhaseDeleteState();

            $this->dispatch('close-modal', name: 'confirm-deletePhase-modal');
        } catch (\Throwable $th) {
            $this->dispatch('swal:toast',
                title: 'Deleting Phase 😕',
                message: $th->getMessage() ?: 'Could not delete phase. Please try again later.',
                type: 'error'
            );

            $this->resetPhaseDeleteState();

            $this->dispatch('close-modal', name: 'confirm-deletePhase-modal');

        }

    }


    public function openAddAppsModal($id)
    {
        $this->dispatch('open-add-apps-modal',
            phaseId: $id,
            constituency_code: $this->constituency->constituency_code
        )->to(AddAppsToPhaseModal::class);

        $this->selectedPhase = $id;
        $this->showAddAppsModal = true;
    }


    public function vettingPercentage($total_apps, $pending): int
    {
        return $this->phaseService->getVettingPercentage($total_apps, $pending);
    }

    /**
     * @return array{label: string, value: string, class: string}
     */
    public function getPhaseDisplayStatus(LoanPhase $phase): array
    {
        return PhaseWorkflowStatus::forPhase($phase);
    }

    #[On('refreshPhaseData')]
    public function refreshData()
    {
        $this->phaseService->invalidatePhasesCache(
            $this->constituency->constituency_code
        );

        unset($this->summary);
        unset($this->pendingCount);
        unset($this->approvedCount);
        unset($this->processingCount);
        unset($this->completedCount);
        unset($this->declinedCount);
        unset($this->disbursedCount);
        unset($this->phases);

        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'statusFilter']);
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.loan.app.phase-manager', [
            'phases' => $this->phases()->paginate((int)$this->perPage),
            'summary' => $this->summary,
            'processingPhases' => $this->processingCount,
            'pendingApplications' => $this->pendingCount,
            'approved' => $this->approvedCount,
            'disbursed' => $this->disbursedCount,
            'declined' => $this->declinedCount
        ]);
    }


}
