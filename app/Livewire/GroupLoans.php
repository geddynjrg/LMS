<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Constituency;
use App\Models\County;
use App\Models\GroupLoan;
use App\Livewire\Concerns\ManagesGroupDetailEditing;
use App\Services\Group\GroupsService;
use App\Services\Loan\LoanPhaseService;
use App\Services\Cache\AppCacheService;

class GroupLoans extends Component
{
    use WithPagination, ManagesGroupDetailEditing;

    protected $paginationTheme = 'bootstrap';

    protected GroupsService $groupsService;
    protected LoanPhaseService $loanPhaseService;
    protected AppCacheService $cacheService;

    public $search;
    public $dateFrom;
    public $dateTo;
    public $statusFilter;
    public $wardFilter;

    #[Url(except: '')]
    public $phaseFilter;

    public $perPage = 15;

    #[Url(except: '')]
    public $sortField = 'disbursement_date';
    public $sortDirection = 'desc';

    public $selectedGroupLoan;

    public Constituency $constituency;

    public function boot(
        GroupsService $groupsService,
        LoanPhaseService $loanPhaseService,
        AppCacheService $appCacheService,
    )
    {
        $this->groupsService = $groupsService;
        $this->loanPhaseService = $loanPhaseService;
        $this->cacheService = $appCacheService;
    }

    public function mount (Constituency $constituency)
    {
        $this->constituency = $constituency->load(['wards']);
    }

    private function getFilters(): array
    {
        return [
            'search' => $this->search,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'status' => $this->statusFilter,
            'ward' => $this->wardFilter,
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
    public function wardsOptions()
    {
        return $this->constituency->wards->map(fn($w) => [
                'id' => $w->id,
                'name' => ucwords($w->ward_name),
            ])
            ->toArray();
    }

    #[Computed]
    public function phasesOptions()
    {
        return $this->loanPhaseService->getPhasesForConstituencyByStatus(
            $this->constituency->constituency_code,
            'disbursed'
        );
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
            $this->constituency->constituency_code,
            $this->getPage(),
            (int)$this->perPage,
            $this->sortField,
            $this->sortDirection
        );

    }

    #[Computed]
    public function getStats()
    {
        return $this->groupsService->getGroupLoansDashboardStats($this->constituency->constituency_code);
    }

    public function refreshData()
    {
        $this->cacheService->flushGroupLoansCache($this->constituency->constituency_code);

        unset($this->groupLoans);

        $this->resetPage();
    }


    public function resetFilters()
    {
        $this->reset(['search', 'statusFilter', 'wardFilter', 'phaseFilter', 'dateFrom', 'dateTo']);
    }

    public function updatingSearch(){$this->resetPage();}
    public function updatingWardFilter() { $this->resetPage(); }
    public function updatingPhaseFilter() { $this->resetPage(); }
    public function updatingPerPage() { $this->resetPage(); }
    public function updatingDateFrom() { $this->resetPage(); }
    public function updatingDateTo() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }
    public function updatedPerPage ($value) {if ($value === null) $this->perPage = 15;}

    public function updatedDateFrom($value)
    {
        $today = now()->toDateString();

        if ($value > $today) {
            $this->dateFrom = $today;
        }
    }

    public function updatedDateTo($value)
    {
        $today = now()->toDateString();

        if ($value > $today) {
            $this->dateTo = $today;
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


    private function fetchGroupLoans()
    {
        return $this->groupsService->getGroupLoansQuery(
            $this->constituency->constituency_code,
            $this->getFilters(),
            $this->sortField,
            $this->sortDirection
        )->paginate((int)$this->perPage);
    }

    public function viewGroupLoan($groupLoanId)
    {
        $this->selectedGroupLoan = $this->groupsService->getGroupLoanHistory($groupLoanId, $this->constituency->constituency_code);
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
        // dd($this->groupsService->getGroupLoansBaseQuery($this->constituency->constituency_code));
        return view('livewire.loan.group-loans');
    }


}
