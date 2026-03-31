<?php

namespace App\Livewire\Hq;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\Log;
use App\Services\ReceiptsService;
use App\Services\Cache\AppCacheService;

class LoanReceipts extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    protected ReceiptsService $receiptsService;
    protected AppCacheService $cacheService;

    public $search;
    public $methodsFilter;
    public $statusFilter;
    public $dateFrom;
    public $dateTo;

    public $perPage = 15;

    #[Url(except: '')]
    public $sortField = 'transaction_date';
    public $sortDirection = 'desc';

    public function boot(
        ReceiptsService $receiptsService,
        AppCacheService $appCacheService
        )
    {
        $this->receiptsService = $receiptsService;
        $this->cacheService = $appCacheService;
    }

    #[Computed]
    public function getStats()
    {
        return $this->receiptsService->getDashboardStats();
    }

    #[Computed]
    public function receipts()
    {
        $hasActiveFilters = collect($this->getFilters())->filter()->isNotEmpty();

        if ($hasActiveFilters){
            return $this->fetchReceipts();
        }

        $cacheKey = md5(json_encode([
            'page' => $this->getPage(),
            'perPage' => (int)$this->perPage,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
        ]));

        return $this->cacheService->rememberReceipts(
            $cacheKey,
            'national_trends',
            fn() => $this->fetchReceipts(),
            null,
            10
        );
    }

    private function fetchReceipts()
    {
        return $this->receiptsService->getRecentReceiptsQuery(
            null,
            $this->getFilters(),
            $this->sortField,
            $this->sortDirection
        )->paginate((int)$this->perPage);
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
    public function methodsOptions()
    {
        return [
            ['id' => 1, 'name' => 'Mpesa'],
            ['id' => 2, 'name' => 'Bank'],
        ];
    }

    #[Computed]
    public function statusOptions()
    {
        return [
            ['value' => 'active', 'name' => 'Posted'],
            ['value' => 'voided', 'name' => 'Canceled'],
        ];
    }

    private function getFilters(): array
    {
        return [
            'search' => $this->search,
            'payment_method' => $this->methodsFilter,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'status' => $this->statusFilter,
        ];
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

    public function refreshData()
    {
        $this->cacheService->flashCache(
            'receipts',
            'national_trends'
        );
        $this->cacheService->flashCache(
            'receipts',
            'national_stats'
        );

        unset($this->getStats);
        unset($this->receipts);
        unset($this->groupsOptions);
        unset($this->populateGroups);

       $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset(['search', 'methodsFilter', 'dateFrom', 'dateTo', 'statusFilter']);
        $this->resetPage();
    }

    public function updatingSearch(){$this->resetPage();}
    public function updatingMethodsFilter() { $this->resetPage(); }
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

    public function render()
    {
        return view('livewire.hq.loan-receipts');
    }
}
