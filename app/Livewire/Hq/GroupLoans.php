<?php

namespace App\Livewire\Hq;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\GroupLoan;
use App\Services\Group\GroupsService;
use App\Services\Loan\LoanPhaseService;
use App\Services\Cache\AppCacheService;
use App\Services\LocationService;

class GroupLoans extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    protected GroupsService $groupsService;
    protected LoanPhaseService $loanPhaseService;
    protected AppCacheService $cacheService;
    protected LocationService $locationService;

    public $search;
    public $dateFrom;
    public $dateTo;
    public $statusFilter;
    public $wardFilter;
    public $constituencyFilter;
    public $countyFilter;
    public $phaseFilter;

    public $countyOptions = [];
    public $constituencyOptions = [];
    public $wardsOptions = [];
    public $phaseOptions = [];

    public $perPage = 15;

    #[Url(except: '')]
    public $sortField = 'disbursement_date';
    public $sortDirection = 'desc';

    public $selectedGroupLoan;

    public function boot(
        GroupsService $groupsService,
        LoanPhaseService $loanPhaseService,
        AppCacheService $appCacheService,
        LocationService $locationService
    )
    {
        $this->groupsService = $groupsService;
        $this->loanPhaseService = $loanPhaseService;
        $this->cacheService = $appCacheService;
        $this->locationService = $locationService;
    }

    public function mount ()
    {
        $this->loadCountyOptions();
    }

    private function getFilters(): array
    {
        return [
            'search' => $this->search,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'status' => $this->statusFilter,
            'ward' => $this->wardFilter,
            'constituency_code' => $this->constituencyFilter,
            'county_code' => $this->countyFilter,
            'phase' => $this->phaseFilter,
        ];
    }

    #[Computed]
    public function perPageOptions()
    {
        return [
            ['id' => 10, 'name' => '10 per page'],
            ['id' => 15, 'name' => '15 per page'],
            ['id' => 25, 'name' => '25 per page'],
            ['id' => 50, 'name' => '50 per page'],
            ['id' => 100, 'name' => '100 per page'],
        ];
    }

    #[Computed]
    public function statusOptions()
    {
        return [
            ['value' => 'active', 'name' => 'Performing'],
            ['value' => 'defaulted', 'name' => 'Defaulted'],
            ['value' => 'completed', 'name' => 'Cleared'],
        ];
    }

    #[Computed]
    public function getStats()
    {
        return $this->groupsService->getGroupLoansDashboardStats();
    }

    #[Computed]
    public function groupLoans()
    {
        $hasActiveFilters = collect($this->getFilters())->filter()->isNotEmpty();

        if ($hasActiveFilters){
            return $this->fetchGroupLoans();
        }

        return $this->cacheService->rememberGroupLoans(
            fn() => $this->fetchGroupLoans(),
            null,
            $this->getPage(),
            (int)$this->perPage,
            $this->sortField,
            $this->sortDirection
        );
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

    private function fetchGroupLoans()
    {
        return $this->groupsService->getGroupLoansQuery(
            null,
            $this->getFilters(),
            $this->sortField,
            $this->sortDirection
        )->paginate((int)$this->perPage);
    }

    #[Computed]
    private function loadCountyOptions()
    {
        $this->countyOptions = $this->locationService->getAllCounties()
            ->map(fn($c) => [
                'id' => $c->county_code,
                'name' => $c->county_name
            ])
            ->toArray();
    }


    public function updatedCountyFilter($value)
    {
        $this->constituencyFilter = '';
        $this->wardFilter = '';
        $this->phaseFilter = '';

        $this->reset('constituencyOptions');
        $this->reset('wardsOptions');
        $this->reset('phaseOptions');

        if (!empty($value)) {
            $this->loadConstituencies($value);
        }

        $this->resetPage();
    }

    public function updatedConstituencyFilter($value)
    {
        $this->wardFilter = '';
        $this->phaseFilter = '';

        $this->reset('wardsOptions');

        if (!empty($value)) {
            $this->loadWards($value);
            $this->loadPhases($value);
        }

        $this->resetPage();
    }

    private function loadConstituencies($countyCode = null)
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

    private function loadWards($constituencyCode = null)
    {
        if (!$constituencyCode) {
            $this->wardsOptions = [];
            return;
        }

        $this->wardsOptions = $this->locationService->getAllWards()
            ->where('constituency_code', $constituencyCode)
            ->map(fn($w) => [
                'id' => $w->id,
                'name' => Str::ucfirst(Str::lower($w->ward_name)),
            ])
            ->values()
            ->toArray();
    }

    private function loadPhases($constituencyCode = null)
    {
        if (!$constituencyCode) {
            $this->phaseOptions = [];
            return;
        }

        $this->phaseOptions = $this->loanPhaseService->getPhasesForConstituencyByStatus(
            $constituencyCode,
            'disbursed'
        );
    }

    public function refreshData()
    {
        $this->cacheService->flushGroupLoansCache();

        unset($this->groupLoans);

        $this->resetPage();
    }


    public function resetFilters()
    {
        $this->reset(['search', 'statusFilter', 'wardFilter', 'constituencyFilter', 'countyFilter', 'phaseFilter', 'dateFrom', 'dateTo']);
    }

    public function updatingSearch(){$this->resetPage();}
    public function updatingWardFilter() { $this->resetPage(); }
    public function updatingConstituencyFilter() { $this->resetPage(); }
    public function updatingCountyFilter() { $this->resetPage(); }
    public function updatingPhaseFilter() { $this->resetPage(); }
    public function updatingPerPage() { $this->resetPage(); }
    public function updatingDateFrom() { $this->resetPage(); }
    public function updatingDateTo() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }

    public function updatedPerPage ($value) {if ($value === null) $this->perPage = 15;}

    public function viewGroupLoan($groupLoanId)
    {
        $this->selectedGroupLoan = $this->groupsService->getGroupLoanHistory($groupLoanId);
        $this->dispatch('open-modal', name: 'loan-info-modal');
    }

    public function closeGroupLoanModal()
    {
        $this->reset(['selectedGroupLoan']);
    }

    public function downloadStatement($loanId)
    {
        $loan = GroupLoan::with(['group', 'loanPhase', 'loanReceipts'])
            ->where('loan_status', 'financed')
            ->findOrFail($loanId);

        $pdf = Pdf::loadView('exports.loan-statement', [
            'loan' => $loan
        ])->setPaper('a4', 'portrait')->output();

        return response()->streamDownload(
            fn () => print($pdf),
            "Statement_{$loan->id}.pdf"
        );
    }

    public function render()
    {
        return view('livewire.hq.group-loans');
    }
}
