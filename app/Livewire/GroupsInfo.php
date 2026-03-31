<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Constituency;
use App\Models\County;
use App\Models\Group;
use App\Services\Loan\LoanAnalysisService;
use App\Services\Group\GroupsService;
use App\Services\Cache\AppCacheService;
use App\Exports\GroupsListExport;

class GroupsInfo extends Component
{
    use WithPagination;

    protected LoanAnalysisService $analysisService;
    protected GroupsService $groupsService;
    protected AppCacheService $cacheService;

    protected $paginationTheme = 'bootstrap';

    public $stats = [];

    #[Url(except: '')]
    public $search = '';

    #[Url(except: '')]
    public $wardFilter = '';

    public $wards = [];

    #[Url(except: '')]
    public $categoryFilter;

    #[Url(except: '')]
    public $sectorFilter = '';

    #[Url(except: '')]
    public $sortField = 'groupName';
    public $sortDirection = 'desc';

    #[Url(except: 15)]
    public $perPage = 15;

    public $totalgroups = 0;
    public $youths = 0;
    public $women = 0;
    public $pwds = 0;
    public Constituency $constituency;
    public $county;
    public $cacheHit = false;
    public $lastUpdated = null;
    public $loading = true;

    // Cache configuration from config file
    protected $cacheTtl;
    protected $cachePrefix;

    public function boot(
        LoanAnalysisService $analysisService,
        GroupsService $groupsService,
        AppCacheService $appCacheService
        )
    {
        $this->analysisService = $analysisService;
        $this->groupsService = $groupsService;
        $this->cacheService = $appCacheService;
    }

    public function mount(County $county, Constituency $constituency)
    {
        $this->constituency = $constituency->load(['wards']);
    }

    public function getFilters(): array
    {
        return [
            'search' => $this->search,
            'ward' => $this->wardFilter,
            'category'  => $this->categoryFilter,
            'sector' => $this->sectorFilter,
        ];
    }

    public function resetFilters()
    {
        $this->reset(['search', 'wardFilter', 'categoryFilter', 'sectorFilter']);
        $this->resetPage();
    }

    public function clearFilter($filterName)
    {
        $this->reset($filterName);

        // Pass the specific filter name to the browser
        $this->dispatch('clear-select-single', target: $filterName);
    }

    #[Computed]
    public function demographics()
    {
        return $this->groupsService->getGroupsDashboardStarts($this->constituency->constituency_code);
    }

    #[Computed]
    public function loadGroups()
    {
        return $this->fetchGroups();
        $hasActiveFilters = collect($this->getFilters())->filter()->isNotEmpty();

        if ($hasActiveFilters){
            return $this->fetchGroups();
        }

        return $this->cacheService->rememberGroups(
            fn() => $this->fetchGroups(),
            $this->constituency->constituency_code,
            $this->getPage(),
            (int)$this->perPage,
            $this->sortField,
            $this->sortDirection
        );
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
    public function categoryOptions()
    {
        return [
            ['value' => 'youth', 'name' => 'Youth'],
            ['value' => 'women', 'name' => 'Women'],
            ['value' => 'pwd', 'name' => 'PWD'],
        ];
    }

    #[Computed]
    public function sectorOptions()
    {
        return [
            ['value' => 'trade', 'name' => 'Trade'],
            ['value' => 'service', 'name' => 'Service'],
            ['value' => 'manufacturing', 'name' => 'Manufacturing'],
            ['value' => 'agriculture', 'name' => 'Agriculture'],
            ['value' => 'other', 'name' => 'Other'],
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

    public function updatedPerPage ($value) {if ($value === null) $this->perPage = 15;}

    public function refreshData()
    {
        $this->cacheService->flushGroupsCache($this->constituency->constituency_code);
        unset($this->loadGroups);
        unset($this->demographics);
        $this->resetPage();
    }

    public function fetchGroups()
    {
        return $this->groupsService->getGroupsQuery(
            $this->constituency->constituency_code,
            $this->getFilters(),
            $this->sortField,
            $this->sortDirection
        )->paginate((int)$this->perPage);
    }

    public function exportGroupList()
    {
        $query = $this->groupsService->getGroupsQuery(
            $this->constituency->constituency_code,
            $this->getFilters(),
            $this->sortField,
            $this->sortDirection
        );

        $filtersUsed = array_filter([
            $this->wardFilter ? str_replace(' ', '-', $this->wardFilter) : null,
            $this->categoryFilter ? str_replace(' ', '-', $this->categoryFilter) : null,
            $this->sectorFilter ? str_replace(' ', '-', $this->sectorFilter) : null,
        ]);

        $filterString = count($filtersUsed) > 0 ? '-' . implode('-', $filtersUsed) : '';
        $constituencyName = str_replace(' ', '-', ucwords($this->constituency->constituency_name));

        $fileName = $constituencyName . '-GROUP-LIST' . $filterString . '-' . now()->format('Y-m-d-His') . '.xlsx';

        // 4. Return the download
        return Excel::download(
            new GroupsListExport($query, $this->constituency->constituency_name),
            $fileName
        );
    }

    public function render()
    {
        // dd($this->loadGroups[0]);
        return view('livewire.group.groups-info', [
            'groups' => $this->loadGroups,
        ]);
    }
}
