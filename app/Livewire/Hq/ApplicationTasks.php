<?php

namespace App\Livewire\Hq;

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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use App\Services\Loan\LoanPhaseService;
use App\Services\Loan\TempApplicationService;
use App\Services\LocationService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use App\Models\LoanPhase;
use App\Livewire\AddAppsToPhaseModal;
use App\Support\PhaseWorkflowStatus;


class ApplicationTasks extends Component
{
    use WithPagination, WithFileUploads;

    protected $paginationTheme = 'bootstrap';

    protected LoanPhaseService $phaseService;
    protected TempApplicationService $appService;
    protected LocationService $locationService;


    #[Url(except: '')]
    public $search = '';
    public $statusFilter = '';
    public $countyFilter = '';
    public $constituencyFilter = '';
    public $wardFilter = '';

    #[Url(except: '')]
    public $dateFrom = '';

    #[Url(except: '')]
    public $dateTo = '';

    #[Url(except: 15)]
    public $perPage = 15;

    public $countyOptions = [];
    public $constituencyOptions = [];

    public $constituency;
    public $selectedPhase = null;

    #[Url(except: 'processing')]
    public $activeTab = 'processing';

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

    #[Url(except: '')]
    public $sortField = 'submission';
    public $sortDirection = 'desc';

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

    public function boot(LoanPhaseService $phaseService, TempApplicationService $appService, LocationService $locationService)
    {
        $this->phaseService = $phaseService;
        $this->appService = $appService;
        $this->locationService = $locationService;
    }

    public function mount()
    {
        $this->loadCountyOptions();
    }

    private function loadCountyOptions()
    {
        $this->countyOptions = $this->locationService->getAllCounties()
            ->map(fn($c) => [
                'id' => $c->county_code,
                'name' => $c->county_name
            ])
            ->toArray();
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }
    public function updatingCountyFilter() { $this->resetPage(); }
    public function updatingConstituencyFilter() { $this->resetPage(); }
    public function updatingPerPage() { $this->resetPage(); }
    public function updatingDateFrom() { $this->resetPage(); }
    public function updatingDateTo() { $this->resetPage(); }

    public function updatedCountyFilter($value)
    {
        $this->constituencyFilter = '';

        $this->reset('constituencyOptions');

        if (!empty($value)) {
            $this->loadConstituencies($value);
        }

        $this->resetPage();
    }

    public function updatedPerPage ($value) {if ($value === null) $this->perPage = 15;}

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

    #[Computed]
    public function summary()
    {
        return $this->phaseService->getPhasesSummary();
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

    #[Computed]
    public function completedCount()
    {
        return $this->summary['completed_phases']
            ?? (($this->summary['disbursed'] ?? 0) + ($this->summary['declined'] ?? 0));
    }

    #[Computed]
    public function phases()
    {
        $statusMap = [
            'processing' => PhaseWorkflowStatus::processingStatuses(),
            'completed'  => PhaseWorkflowStatus::completedStatuses(),
        ];

        $statuses = $statusMap[$this->activeTab] ?? PhaseWorkflowStatus::processingStatuses();

        return $this->phaseService->getDetailedPhasesQuery(
            null,
            $statuses,
            $this->getFilters(),
            $this->sortField,
            $this->sortDirection
        );
    }

    public function updatedSearch($value)
    {
        $this->search = trim($value);

        $this->resetPage();
    }


    public function vettingPercentage($total_apps, $pending): int
    {
        return $this->phaseService->getVettingPercentage($total_apps, $pending);
    }

    public function refreshData()
    {
        $phaseTag = config('cache.tags.phases.national');

        Cache::tags([$phaseTag])->flush();

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
        $this->reset(['search', 'statusFilter', 'countyFilter', 'constituencyFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function clearFilter($filterName)
    {
        $this->reset($filterName);
    }

    public function getStatusOptionsProperty()
    {
        $statusMap = $this->activeTab === 'completed'
            ? [
                'disbursed' => ['label' => 'Disbursed'],
                'rejected'  => ['label' => 'Rejected'],
            ]
            : [
                'submitted_to_hq' => ['label' => 'Pending HQ Approval'],
                'approved'        => ['label' => 'HQ Approved'],
                'in_training'     => ['label' => 'In Training'],
            ];

        return collect($statusMap)->map(fn($data, $value) => [
            'value'    => $value,
            'name'  => $data['label'],
        ])->values()->all();
    }

    public function setActiveTab(string $tab): void
    {
        if (! in_array($tab, ['processing', 'completed'], true)) {
            return;
        }

        $this->activeTab = $tab;
        $this->statusFilter = '';
        $this->resetPage();
    }

    public function getPhaseDisplayStatus(LoanPhase $phase): array
    {
        return PhaseWorkflowStatus::forPhase($phase);
    }

    public function getStatusLabel($value)
    {
        return collect($this->statusOptions)->firstWhere('value', $value)['name'] ?? $value;
    }

    public function getStatusName($value)
    {
        return collect($this->statusOptions)->firstWhere('value', $value)['name'] ?? $value;
    }

    #[Computed]
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

    private function getFilters(): array
    {
        return [
            'search' => $this->search,
            'status' => $this->statusFilter,
            'county_code'  => $this->countyFilter,
            'constituency_code'  => $this->constituencyFilter,
            'date_from' => $this->dateFrom,
            'date_to'   => $this->dateTo,
        ];
    }

    public function loadConstituencies($countyCode = null)
    {
        if (!$countyCode) {
            $this->constituencyOptions = [];
            return;
        }

        $this->constituencyOptions = $this->locationService->getAllConstituencies()
            ->where('county_code', $countyCode)
            ->map(fn($c) => [
                'id' => $c->constituency_code,
                'name' => Str::ucfirst(Str::lower($c->constituency_name))
            ])
            ->values()
            ->toArray();
    }

    public function render()
    {
        // dd($this->phases());
        return view('livewire.hq.application-tasks', [
            'phases' => $this->phases->paginate((int)$this->perPage),
            'summary' => $this->summary,
            'processingPhases' => $this->processingCount,
            'completedPhases' => $this->completedCount,
            'pendingApplications' => $this->pendingCount,
            'approved' => $this->approvedCount,
            'disbursed' => $this->disbursedCount,
            'declined' => $this->declinedCount
        ]);
    }


}
