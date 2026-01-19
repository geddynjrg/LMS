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
use App\Services\Loan\TempApplicationService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use App\Models\LoanPhase;
use App\Livewire\AddAppsToPhaseModal;


class PhaseManager extends Component
{
    use WithPagination, WithFileUploads;

    protected $paginationTheme = 'bootstrap';

    protected LoanPhaseService $phaseService;
    protected TempApplicationService $appService;

    #[Url(except: '')]
    public $search;

    public $perPage = 15;

    public $constituency;
    public $selectedPhase = null;

    public $activeTab = 'tab-pending';

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
    public function declinedCount()
    {
        return $this->summary['declined'] ?? 0;
    }

    #[Computed]
    public function disbursedCount()
    {
        return $this->summary['disbursed'] ?? 0;
    }

    public function phases()
    {
        $statusMap = [
            'tab-pending'    => 'draft',
            'tab-processing' => 'submitted_to_hq',
            'tab-approved'   => 'approved',
            'tab-declined'   => 'declined',
            'tab-disbursed'  => 'disbursed',
        ];
        $status = $statusMap[$this->activeTab] ?? 'draft';

        return $this->phaseService->getDetailedPhases(
            $this->constituency->constituency_code,
            $this->search,
            (int)$this->perPage,
            $status,
            $this->getPage()
        );
    }

    public function updatedSearch($value)
    {
        $this->search = trim($value);

        $this->resetPage();
    }

    public function createNewPhase()
    {
        $this->dispatch('open-modal', name: 'create-phase-modal');
    }

    public function createPhase()
    {
        $validated = $this->validate([
            'phaseName' => 'required|string|max:255',
            'reviewDate' => 'required|date|after:today',
            'reviewVenue' => 'nullable|string|max:255',
        ]);

        try {
            $result = $this->phaseService->createPhase($validated, $this->constituency->constituency_code);

            $this->dispatch('close-modal', name: 'create-phase-modal');
            $this->reset(['phaseName', 'reviewVenue']);


            $this->dispatch('flash-success', message: 'Phase created successfully');
            $this->dispatch('app-updated')->to(SideNavBadge::class);
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

    public function deletePhase($id)
    {
        $phase = $this->phaseService->getPhaseStats($id);

        if (!$phase){
            $this->dispatch('swal:modal', [
                'type'  => 'error',
                'title' => "Error Deleting Phase",
                'text'  => 'Phase Invalid',
            ]);
            return false;
        }

        if ($phase->total_apps > 0){
             $this->dispatch('swal:modal', [
                'type'  => 'info',
                'title' => "Deleting Phase",
                'text'  => "You have {$phase->total_apps} application(s) in this phase, you need to remove them first",
            ]);
            return false;
        } else {
            $this->dispatch('confirm-action',
                title: "Delete {$phase->phase}",
                text: 'This will decouple all application from this current phase.',
                icon: 'warning',
                confirmText: 'Yes, Remove it!',
                componentId: $this->getId(),
                action: 'confirmDeletePhase',
                params: $phase->id
            );
        }

    }

    public function confirmDeletePhase($id)
    {
        $phase = $this->phaseService->getPhase($id);

        if (!$phase){
            $this->dispatch('swal:modal', [
                'type'  => 'error',
                'title' => "Error Deleting Phase",
                'text'  => 'Phase Invalid',
            ]);
            return false;
        }

        DB::transaction(function () use ($phase) {
            $phase->delete();

             $this->dispatch('swal:modal', [
                'type'               => 'success',
                'title'              => 'Success!',
                'text'               => 'Phase Deleted successfully.',
                'timer'              => 3000,
                'showConfirmButton'  => false,
            ]);

            $this->dispatch('app-updated')->to(SideNavBadge::class);
            $this->refreshData();
        });
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
        unset($this->declinedCount);
        unset($this->disbursedCount);
        unset($this->phases);

        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['search']);
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.loan.app.phase-manager', [
            'phases' => $this->phases(),
            'summary' => $this->summary,
            'processingPhases' => $this->processingCount,
            'pendingApplications' => $this->pendingCount,
            'approved' => $this->approvedCount,
            'disbursed' => $this->disbursedCount,
            'declined' => $this->declinedCount
        ]);
    }


}
