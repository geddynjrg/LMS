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
use App\Services\Cache\AppCacheService;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ApplicationDashboard extends Component
{
    use WithPagination;

    public $constituency;

    protected LoanPhaseService $phaseService;
    protected TempApplicationService $appService;
    protected AppCacheService $cacheService;

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
    // #[Url(except: [], history: true)]
    public $selectedApplications = [];
    public $selectedCount = 0;

    public $selectAll = '';

    // Quick phase assignment
    public $selectedPhaseForQuickAssign = '';

    // Phase creation
    public $phaseName = '';
    public $reviewDate;
    public $reviewVenue = '';

    // Computed properties (we'll use getters)
    public $selectedAppsList = [];

    public $selectedApplicationId;
    public $currentPhaseId;

    #[Url(except: '')]
    public $sortField = 'created';
    public $sortDirection = 'desc';

    public function boot(
        LoanPhaseService $phaseService, TempApplicationService $appService, AppCacheService $cacheService)
    {
        $this->phaseService = $phaseService;
        $this->appService = $appService;
        $this->cacheService = $cacheService;
    }

    public function mount($constituency)
    {
        $this->constituency = $constituency->load('county');
        $this->reviewDate = now()->addDays(7)->format('Y-m-d');
        if (empty($this->selectedApplications)) {
            $this->selectedApplications = [];
        }
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


    public function updatedSelectAll($value)
    {
        // dd($this->selectAll);
        if ($this->selectAll) {
            // Use the current page state to ensure we ONLY get visible IDs
            $this->selectedApplications = collect($this->getFilteredApplicationsQuery()
                ->whereDoesntHave('phaseApplication')
                ->paginate($this->perPage, ['*'], 'page', $this->getPage()) // Explicitly lock to current page
                ->items())
                ->pluck('id')
                ->map(fn($id) => (string)$id)
                ->toArray();
        } else {
            $this->selectedApplications = [];
            $this->reset(['selectedApplications', 'selectAll']);
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

    public function updatedDateFrom($value)
    {
        $this->validateDate($value, 'from');
        $this->resetPage();
    }

    public function updatedDateTo($value)
    {
        $this->validateDate($value, 'to');
        $this->resetPage();
    }

    protected function validateDate($value, $label)
    {
        if ($value && \Carbon\Carbon::parse($value)->isFuture()) {

            $this->dispatch('swal:toast',
                title: 'Invalid Date 😕',
                message: 'The selected date cannot be in the future. Resetting to today\'s date.',
                type: 'error'
            );

            if ($label === 'from') {
                $this->dateFrom = now()->format('Y-m-d');
            } else {
                $this->dateTo = now()->format('Y-m-d');
            }
        }
    }

    public function updatedPage()
    {
        // $this->selectAll = '';
    }

    public function getStatusOptionsProperty()
    {
        return $this->appService->getConsStatusOptionsProperty();
    }

    public function getStatusLabel($value)
    {
        return collect($this->statusOptions)->firstWhere('value', $value)['name'] ?? $value;
    }

    public function getStatusClasses($value): string
    {
        return $this->appService->getStatusClasses($value);
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

    public function loadSelectedAppsList()
    {
        if (empty($this->selectedApplications)) {
            $this->selectedAppsList = [];
            return;
        }

        $this->selectedAppsList = TempApplication::whereIn('id', $this->selectedApplications)
            ->take(5)
            ->get(['application_id', 'group_name'])
            ->toArray(); // No need for manual mapping if keys match exactly
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
    }

    public function clearFilter($filterName)
    {
        $this->reset($filterName);
    }

    public function clearDateFilter()
    {
        $this->reset(['dateFrom', 'dateTo']);
    }

    public function clearSelection()
    {
        $this->selectedApplications = [];
        $this->selectAll = false;
    }

    public function refreshData()
    {
        $this->cacheService->flashCache('applications', 'constituency_apps', $this->constituency->constituency_code);
        $this->cacheService->flashCache('applications', 'constituency_stats', $this->constituency->constituency_code);

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
        return $this->appService->getFilteredQuery(
                $this->constituency->constituency_code,
                $this->getFilters(),
                $this->sortField,
                $this->sortDirection
            );

        // $key = md5(json_encode([
        //     'filters' => $this->getFilters(),
        //     'page' => $this->getPage(),
        //     'perPage' => (int)$this->perPage,
        //     'sortField' => $this->sortField,
        //     'sortDirection' => $this->sortDirection,
        // ]));


        // return $this->cacheService->rememberApplications(
        //     $key,
        //     fn() => $this->appService->getFilteredQuery(
        //         $this->constituency->constituency_code,
        //         $this->getFilters(),
        //         $this->sortField,
        //         $this->sortDirection
        //     ),
        //     $this->constituency->constituency_code,
        //     5
        // );
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

    #[Computed]
    public function categoryDistribution()
    {
        return [
            'youth' => $this->stats['total_youth'] ?? 0,
            'women' => $this->stats['total_women'] ?? 0,
            'pwd' => $this->stats['total_pwd'] ?? 0,
        ];
    }


    #[Computed(persist: true)]
    public function demographics ()
    {
        $totalApplications = $this->stats['total'];
        $totalAmountApplied = $this->stats['total_amount'] ?? 0;
        $stats = $this->stats;

        return [
            [
                'label' => 'Total Applications',
                'val' => $totalApplications,
                'amount' => $totalAmountApplied,
                'text' => "KES: " . number_format($totalAmountApplied),
                'icon' => 'fa-file-alt',
                'color' => 'info',
                'action' => 'resetFilters',
                'footer' => 'View all',
                'showProgress' => false,
                'percentage' => 0,
                'cat' => 'total',
            ],

            [
                'label' => 'Not in Phase',
                'val' => $stats['not_in_phase'],
                'amount' => 0,
                'text' => null,
                'icon' => 'fa-clock',
                'color' => 'success',
                'action' => '$set("phaseFilter", "not_in_phase")',
                'cat' => 'not_in_phase',
                'footer' => 'Review Needed',
                'showProgress' => true,
                'percentage' => $stats['total'] > 0 ? round(($stats['not_in_phase'] / $stats['total']) * 100) : 0,
            ],

            [
                'label' => 'In Phase',
                'val' => $stats['in_phase'],
                'amount' => 0,
                'text' => null,
                'icon' => 'fa-layer-group',
                'color' => 'pink',
                'action' => '$set("phaseFilter", "in_phase")',
                'url' => route('const.app.app_phases', [$this->constituency->county->slug, $this->constituency->slug]),
                'cat' => 'in_phase',
                'footer' => 'Manage Phases',
                'showProgress' => true,
                'percentage' => $stats['total'] > 0 ? round(($stats['in_phase'] / $stats['total']) * 100) : 0,
            ],

            [
                'label' => 'Approved',
                'val' => $stats['approved'],
                'amount' => $stats['approved_amount'] ?? 0,
                'text' => 'KES: ' . number_format($stats['approved_amount']),
                'icon' => 'fa-check-circle',
                'color' => 'primary',
                'action' => '$set("statusFilter", "approved")',
                'cat' => 'approved',
                'footer' => 'View List',
                'showProgress' => true,
                'percentage' => $stats['total'] > 0 ? round(($stats['approved'] / $stats['total']) * 100) : 0,
            ],

            [
                'label' => 'Rejected',
                'val' => $stats['rejected'],
                'amount' => $stats['declined_amount'],
                'text' => '',
                'icon' => 'fa-times-circle',
                'color' => 'pink',
                'action' => '$set("statusFilter", "rejected")',
                'cat' => 'rejected',
                'footer' => 'View List',
                'showProgress' => true,
                'percentage' => $stats['total'] > 0 ? round(($stats['rejected'] / $stats['total']) * 100) : 0,
            ],
        ];
    }

    public function createNewPhase()
    {
        $this->reset(['phaseName', 'reviewVenue']);
        $this->resetErrorBag();
        $this->dispatch('open-modal', name: 'create-new-phase-modal');
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

            $this->dispatch('close-modal', name: 'create-new-phase-modal');
            $this->reset(['phaseName', 'reviewVenue']);

            // Clear all validation messages
            $this->resetErrorBag();

            $this->dispatch('swal:toast',
                title: 'Phase Created 😊',
                message: 'Phase created successfully.',
                type: 'success'
            );

            $this->dispatch('app-updated')->to(SideNavBadge::class);
            $this->refreshData();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
                $this->dispatch('swal:toast',
                    title: 'Error 😕',
                    message: "Please correct the errors in the form.",
                    type: 'error'
                );
            }
        } catch (\Exception $e) {
            $this->dispatch('swal:toast',
                title: 'Error 😕',
                message: "An error occured please try again.",
                type: 'error'
            );
        }
    }

    public function assignToPhaseModal()
    {
        $this->loadSelectedAppsList();

        $this->dispatch('open-modal', name: 'assign-to-phase-modal');
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
            $this->dispatch('swal:toast',
                title: 'Error 😕',
                message: "No unassigned applications selected.",
                type: 'error'
            );
            return;
        }

        try {
            $phase = LoanPhase::findOrFail($this->selectedPhaseForQuickAssign);

            $this->phaseService->bulkAssignApplications($phase, $unassignedIds);

            $this->reset(['selectedApplications', 'selectAll', 'selectedPhaseForQuickAssign']);

            $this->dispatch('swal:toast',
                title: 'Application Assigned 😊',
                message: 'Applications assigned to phase successfully.',
                type: 'success'
            );

            $this->dispatch('close-modal', name: 'assign-to-phase-modal');

            unset($this->stats);
            unset($this->activePhases);

            $this->refreshData();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                    $this->dispatch('swal:toast',
                        title: 'Error 😕',
                        message: collect($e->errors())->flatten()->first(),
                        type: 'error'
                    );
                }

            }
        } catch (\Exception $e) {
            $this->dispatch('swal:toast',
                title: 'Error 😕',
                message: "An Error Occured please try again",
                type: 'error'
            );
        }
    }

    public function confirmRemoveFromPhase($phaseId, $phaseApplicationId)
    {
        $this->selectedApplicationId = $phaseApplicationId;
        $this->currentPhaseId = $phaseId;

        $this->dispatch('open-modal', name: 'modal-remove-from-phase');
    }

    public function removeFromPhase()
    {
        if (!$this->selectedApplicationId) return;

        try {
            $phase = LoanPhase::findOrFail($this->currentPhaseId);

            $this->phaseService->removeApplication($phase, $this->selectedApplicationId);
            $this->dispatch('close-modal', name: 'modal-remove-from-phase');

            $this->dispatch('swal:toast',
                title: 'Application Removed 😊',
                message: 'Successfully removed application from phase.',
                type: 'success'
            );

            $this->reset(['selectedApplicationId', 'currentPhaseId']);
            $this->refreshData();

        } catch (ValidationException $e) {
            $this->dispatch('swal:toast',
                title: 'Action Failed 😕',
                message: collect($e->errors())->flatten()->first(),
                type: 'error'
            );
        } catch (\Exception $e) {
            $this->dispatch('swal:toast',
                title: 'Action Failed 😕',
                message: 'An unexpected error occurred. Please try again.',
                type: 'error'
            );
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

    public function singleAssignToPhaseModal($applicationId)
    {
        if (!in_array($applicationId, $this->selectedApplications)) {
            $this->selectedApplications[] = $applicationId;
        }

        // collections for a "one-liner":
        // $this->selectedApplications = collect($this->selectedApplications)->push($applicationId)->unique()->toArray();

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
            'isLoading' => $this->isLoading(),
        ]);
    }
}
