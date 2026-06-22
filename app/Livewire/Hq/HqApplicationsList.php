<?php

namespace App\Livewire\Hq;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\PhaseApplication;
use App\Exports\ApplicationsExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\Loan\LoanPhaseService;
use App\Services\Loan\TempApplicationService;
use App\Services\LocationService;
use App\Services\Cache\AppCacheService;

class HqApplicationsList extends Component
{

    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    protected LoanPhaseService $phaseService;
    protected TempApplicationService $appService;
    protected LocationService $locationService;
    protected AppCacheService $cacheService;

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

    #[Url(except: '')]
    public $sortField = 'created';
    public $sortDirection = 'desc';

    public function boot(
        LoanPhaseService $phaseService,
        TempApplicationService $appService,
        LocationService $locationService,
        AppCacheService $cacheService
    )
    {
        $this->phaseService = $phaseService;
        $this->appService = $appService;
        $this->locationService = $locationService;
        $this->cacheService = $cacheService;
    }

    public function mount ()
    {
        $this->loadCountyOptions();
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

    public function resetFilters()
    {
        $this->reset(['search', 'statusFilter', 'countyFilter', 'constituencyFilter', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    public function refreshData()
    {
        $this->cacheService->flashCache('applications', 'national_apps');
        $this->cacheService->flashCache('applications', 'national_stats');

        unset($this->stats, $this->categoryDistribution, $this->demographics);

        $this->resetPage();
    }

    public function clearFilter($filterName)
    {
        $this->reset($filterName);
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

    public function getStatusOptionsProperty()
    {
        return $this->appService->getHqStatusOptionsProperty();
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

    public function getStatusName($value)
    {
        return collect($this->statusOptions)->firstWhere('value', $value)['name'] ?? $value;
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

    #[Computed]
    public function stats()
    {
        return $this->appService->getStats();
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
                'label' => 'Pending HQ Approval',
                'val' => $stats['pending_hq'],
                'amount' => 0,
                'text' => null,
                'icon' => 'fa-clock',
                'color' => 'success',
                'action' => '$set("statusFilter", "pending")',
                'cat' => 'pending_hq',
                'footer' => 'Review Needed',
                'showProgress' => true,
                'percentage' => $stats['total'] > 0 ? round(($stats['pending_hq'] / $stats['total']) * 100) : 0,
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

    protected function getFilteredApplicationsQuery()
    {
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
        //         null,
        //         $this->getFilters(),
        //         $this->sortField,
        //         $this->sortDirection
        //     ),
        //     null,
        //     5
        // );
        return $this->appService->getFilteredQuery(
                null,
                $this->getFilters(),
                $this->sortField,
                $this->sortDirection
            );
    }

    public function exportApplications()
    {
        $query = $this->getFilteredApplicationsQuery();

        return Excel::download(
            new ApplicationsExport($query, null),
            'hq-loan-applications-' . now()->format('Y-m-d-His') . '.xlsx'
        );
    }

    public function render()
    {
        $applications = $this->getFilteredApplicationsQuery()->paginate((int)$this->perPage);
        return view('livewire.hq.hq-applications-list', [
            'applications' => $applications,
        ]);
    }
}
