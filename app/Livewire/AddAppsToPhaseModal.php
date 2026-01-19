<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Application; // Adjust to your model path
use Livewire\WithPagination;
use App\Services\Loan\LoanPhaseService;
use App\Services\Loan\TempApplicationService;
use Livewire\Attributes\On;
use App\Models\LoanPhase;
use Illuminate\Support\Facades\Route;

class AddAppsToPhaseModal extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $isOpen = false;
    public $selectedPhaseId;
    public $selectedApplications = [];
    public $selectAll = false;
    public $search = '';
    public $perPage = 10;
    public $constituency_code = '';
    public $phase;
    public $phaseName;

    protected LoanPhaseService $phaseService;
    protected TempApplicationService $appService;

    #[On('open-add-apps-modal')]
    public function initialize($phaseId, $constituency_code)
    {
        $this->constituency_code = $constituency_code;
        $this->selectedPhaseId = $phaseId;

        $this->phase = $this->phaseService->getPhase($phaseId);
        $this->phaseName = $this->phase ? $this->phase->phase : $phaseId;

        $this->reset(['selectedApplications', 'selectAll', 'search']);
        $this->resetPage();
        $this->isOpen = true;
    }

    public function boot(LoanPhaseService $phaseService, TempApplicationService $appService)
    {
        $this->phaseService = $phaseService;
        $this->appService = $appService;
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedApplications = $this->getPendingApplications()
                ->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedApplications = [];
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function addApplicationsToPhase()
    {
        if (empty($this->selectedApplications)) {
            return;
        }

        if (!$this->phase){
            $this->dispatch('swal:modal', [
                'type'  => 'error',
                'title' => "Error Deleting Phase",
                'text'  => 'Phase Invalid',
            ]);
            return false;
        }

        $applicationIds = collect($this->selectedApplications)
            ->map(fn($id) => (int) $id)
            ->toArray();

        try {
            $this->phaseService->bulkAssignApplications($this->phase, $applicationIds);
            $this->reset(['selectedApplications', 'selectAll', 'search']);

            $this->phaseService->invalidatePhasesCache(
                $this->constituency_code
            );

            $this->isOpen = false;
            $this->dispatch('swal:modal', [
                'title' => 'Success',
                'text' => count($applicationIds) . ' applications assigned.',
                'type' => 'success'
            ]);

            $this->dispatch('refreshPhaseData')->to(PhaseManager::class);
            $this->dispatch('refresh-phase-app-data')->to(ApplicationPhaseDetails::class);
        } catch (\Throwable $th) {
            $this->dispatch('swal:modal', [
                'type'  => 'error',
                'title' => "Error Deleting Phase",
                'text'  => "Phase Invalid{$th->getMessage()}",
            ]);
            return false;
        }
    }

    public function toggleApplication($appId)
    {
        // Convert to string to match wire:model value type
        $appId = (string) $appId;

        if (in_array($appId, $this->selectedApplications)) {
            $this->selectedApplications = array_diff($this->selectedApplications, [$appId]);
        } else {
            $this->selectedApplications[] = $appId;
        }

        // Optional: Update SelectAll state based on current page count
        $this->selectAll = count($this->selectedApplications) === $this->getPendingApplications()->count();
    }

    public function getPendingApplications()
    {
        return $this->appService->getPendingApplicationsProperty(
            $this->constituency_code,
            $this->search
        )->orderBy('created_at', 'desc')
        ->paginate((int)$this->perPage);
    }

    public function render()
    {
        return view('livewire.add-apps-to-phase-modal', [
            'pendingApplications' => $this->getPendingApplications()
        ]);
    }
}
