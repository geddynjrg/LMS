<?php
// app/Livewire/ApplicationsDashboard.php
namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use App\Models\TempApplication;
use App\Models\LoanPhase;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ApplicationsExport;
use App\Services\Loan\LoanPhaseService;
use App\Services\Loan\TempApplicationService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ApplicationDashboard extends Component
{
    use WithPagination;

    public $constituency;

    protected LoanPhaseService $phaseService;
    protected TempApplicationService $appService;

    protected $paginationTheme = 'bootstrap';

    // test
    public $selectedOption;

    #[Url(except: '')]
    public $search = '';

    #[Url(except: '')]
    public $statusFilter = '';

    #[Url(except: '')]
    public $phaseFilter = '';

    #[Url(except: '')]
    public $dateFrom = '';

    #[Url(except: '')]
    public $dateTo = '';

    public $title = '';

    #[Url(except: 15)]
    public $perPage = 15;

    // Bulk actions
    #[Url(except: [], history: true)]
    public $selectedApplications = [];

    public $selectAll = false;

    // Quick phase assignment
    public $selectedPhaseForQuickAssign = '';

    // Phase creation
    public $phaseName = '';
    public $reviewDate;
    public $reviewVenue = '';

    // Computed properties (we'll use getters)
    public $selectedAppsList = [];

    public function boot(LoanPhaseService $phaseService, TempApplicationService $appService)
    {
        $this->phaseService = $phaseService;
        $this->appService = $appService;
    }

    public function mount($constituency)
    {
        $this->constituency = $constituency->load('county');
        $this->reviewDate = now()->addDays(7)->format('Y-m-d');
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            // Only select IDs from the CURRENT visible page
            $this->selectedApplications = $this->getFilteredApplicationsQuery()
                ->whereDoesntHave('phaseApplication')
                ->forPage($this->page, $this->perPage) // Only current page
                ->pluck('id')
                ->map(fn($id) => (string)$id)
                ->toArray();
        } else {
            $this->selectedApplications = [];
        }

        $this->loadSelectedAppsList();
    }

    public function updatedSelectedApplications()
    {
        $this->loadSelectedAppsList();
    }

    public function updatedStatusFilter() { $this->resetPage(); }

    public function updatedPhaseFilter() { $this->resetPage(); }

    public function updatedSearch() { $this->resetPage(); }

    public function updatedDateFrom() { $this->resetPage(); }

    public function updatedDateTo() { $this->resetPage(); }


    public function loadSelectedAppsList()
    {
        if (empty($this->selectedApplications)) {
            $this->selectedAppsList = [];
            return;
        }

        $this->selectedAppsList = TempApplication::whereIn('id', $this->selectedApplications)
            ->take(5)
            ->get(['application_id', 'group_name'])
            ->map(function ($app) {
                return [
                    'application_id' => $app->application_id,
                    'group_name' => $app->group_name,
                ];
            })
            ->toArray();
    }

    // Computed properties using getters
    public function getSelectedAppsCountProperty()
    {
        return count($this->selectedApplications);
    }

    public function getSelectedAppsDisplayListProperty()
    {
        return $this->selectedAppsList;
    }

    public function getSelectedAppsExtraCountProperty()
    {
        $count = $this->selectedAppsCount;
        return $count > 5 ? $count - 5 : 0;
    }

    public function resetFilters()
    {
        $this->reset(['search', 'statusFilter', 'phaseFilter', 'dateFrom', 'dateTo', 'selectedApplications', 'selectAll']);
        $this->selectedAppsList = [];
        $this->resetPage();
        $this->dispatch('clear-selects');
    }

    public function refreshData()
    {
        $appsTag = config('cache.tags.applications.constituency' . $this->constituency->constituency_code);

        $this->phaseService->invalidatePhasesCache(
            $this->constituency->constituency_code
        );

        Cache::tags([$appsTag])->flush();
        $code = $this->constituency->constituency_code;
        $this->appService->invalidateStatsCache($code);
        unset($this->stats);
        unset($this->activePhases);
        unset($this->allPhases);
        $this->resetPage();
    }

    public function getFilters(): array
    {
        return [
            'search' => $this->search,
            'status' => $this->statusFilter,
            'phase'  => $this->phaseFilter,
            'date_from' => $this->dateFrom,
            'date_to'   => $this->dateTo,
        ];
    }

    protected function getFilteredApplicationsQuery()
    {
        $appsTag = config('cache.tags.applications.constituency' . $this->constituency->constituency_code);

        if (empty($this->getFilters())) {
            $cacheKey = "applications_list_{$this->constituency->constituency_code}_page_{$this->getPage()}";
            return Cache::tags([$appsTag])->remember($cacheKey, now()->addMinutes(15), function () {
                return $this->appService->getFilteredQuery(
                        $this->constituency->constituency_code
                    )->orderBy('created_at', 'desc');
            });
        }

        return $this->appService->getFilteredQuery(
            $this->constituency->constituency_code,
            $this->getFilters()
        )->orderBy('created_at', 'desc');
    }

    #[Computed]
    public function stats()
    {
        return $this->appService->getStats($this->constituency->constituency_code);
    }

    #[Computed]
    public function activePhases()
    {
        return $this->phaseService->getActivePhasesForConstituency(
            $this->constituency->constituency_code
        );
    }

    #[Computed]
    public function allPhases()
    {
        return $this->phaseService->getAllPhasesForConstituency(
            $this->constituency->constituency_code
        );
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

    public function quickAssignToPhase()
    {
        $this->validate([
            'selectedPhaseForQuickAssign' => 'required|exists:loan_phases,id',
        ]);

        $unassignedIds = $this->getFilteredApplicationsQuery()
            ->whereDoesntHave('phaseApplication')
            ->pluck('id')
            ->intersect($this->selectedApplications)
            ->toArray();

        if (empty($unassignedIds)) {
            $this->dispatch('flash-error', message: 'No unassigned applications selected.');
            return;
        }

        try {
            $phase = LoanPhase::findOrFail($this->selectedPhaseForQuickAssign);

            $this->phaseService->bulkAssignApplications($phase, $unassignedIds);

            $this->reset(['selectedApplications', 'selectAll', 'selectedPhaseForQuickAssign']);

            $this->dispatch('flash-success', message: 'Applications assigned to phase successfully');

            $this->dispatch('close-modal', name: 'assign-to-phase-modal');

            // Invalidate after bulk change
            $this->appService->invalidateStatsCache($this->constituency->constituency_code);
            unset($this->stats);
            unset($this->activePhases);

            $this->refreshData();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                    $this->dispatch('flash-error', message: $message);
                }

            }
        } catch (\Exception $e) {
            $this->dispatch('flash-error', message: $e->getMessage());
        }
    }

    public function removeFromPhase($phaseId, $applicationId)
    {
        try {
            $phase = LoanPhase::findOrFail($phaseId);
            $this->phaseService->removeApplication($phase, $applicationId);
            $this->dispatch('flash-success', message: 'Application removed from phase');
            $this->refreshData();

        } catch (ValidationException $e) {
            $this->dispatch('flash-error', message: collect($e->errors())->flatten()->first());
        } catch (\Exception $e) {
            $this->dispatch('flash-error', message: $e->getMessage());
        }
    }

    #[Computed]
    public function suggestedPhaseName()
    {
        $count = $this->allPhases->count() + 1;
        return "Phase {$count}";
    }

    // Helper method for status badges
    public function getStatusBadgeClass($status)
    {
        $badges = [
            'submitted' => 'secondary',
            'under_review' => 'primary',
            'approved' => 'success',
            'rejected' => 'danger',
            'disbursed' => 'warning',
            'closed' => 'dark',
        ];
        return $badges[$status] ?? 'secondary';
    }

    public function getStatusBadgeText($status)
    {
        return $this->appService->getStatusText($status);
    }

    public function assignToPhaseModal($applicationId)
    {
        // add to selected applications
        $this->selectedApplications = [...$this->selectedApplications, $applicationId];

        $this->loadSelectedAppsList();

        $this->dispatch('open-modal', name: 'assign-to-phase-modal');
    }

    public function isLoading()
    {
        return false;
    }

    public function exportApplications()
    {
        $query = $this->getFilteredApplicationsQuery();

        return Excel::download(
            new ApplicationsExport($query, $this->constituency),
            'loan-applications-' . now()->format('Y-m-d-His') . '.xlsx'
        );
    }

    public function createNewPhase()
    {
        $this->dispatch('open-modal', name: 'create-phase-modal');
    }

    public function render()
    {
        $applications = $this->getFilteredApplicationsQuery()->paginate((int)$this->perPage);
        return view('livewire.loan.app.application-dashboard', [
            'applications' => $applications,
            'stats' => $this->stats,
            'activePhases' => $this->activePhases,
            'allPhases' => $this->allPhases,
            'suggestedPhaseName' => $this->suggestedPhaseName(),
        ])->with([
            // pass a flag to know when data is fresh
            'isLoading' => $this->isLoading(),
        ]);
    }
}
