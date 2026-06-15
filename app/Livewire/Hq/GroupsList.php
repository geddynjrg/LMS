<?php

namespace App\Livewire\Hq;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\Loan\LoanAnalysisService;
use App\Services\Group\GroupsService;
use App\Services\Cache\AppCacheService;
use App\Services\LocationService;
use App\Exports\GroupsListExport;

class GroupsList extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    protected LocationService $locationService;
    protected LoanAnalysisService $analysisService;
    protected GroupsService $groupsService;
    protected AppCacheService $cacheService;

    #[Url(except: '')]
    public $search = '';

    #[Url(except: '')]
    public $categoryFilter;

    #[Url(except: '')]
    public $sectorFilter = '';

    public $wardFilter = '';

    public $constituencyFilter = '';

    public $countyFilter = '';

    public $countyOptions = [];
    public $constituencyOptions = [];
    public $wardsOptions = [];

    #[Url(except: 15)]
    public $perPage = 15;

    #[Url(except: '')]
    public $sortField = 'groupName';
    public $sortDirection = 'desc';

    public function boot(
        LoanAnalysisService $analysisService,
        GroupsService $groupsService,
        AppCacheService $appCacheService,
        LocationService $locationService
        )
    {
        $this->analysisService = $analysisService;
        $this->groupsService = $groupsService;
        $this->cacheService = $appCacheService;
        $this->locationService = $locationService;
    }

    public function mount ()
    {
        $this->loadCountyOptions();
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
    public function demographics()
    {
        return $this->groupsService->getGroupsDashboardStarts();
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
            null,
            $this->getPage(),
            (int)$this->perPage,
            $this->sortField,
            $this->sortDirection
        );
    }

    public function fetchGroups()
    {
        return $this->groupsService->getGroupsQuery(
            null,
            $this->getFilters(),
            $this->sortField,
            $this->sortDirection
        )->paginate((int)$this->perPage);
    }

    public function updatedCountyFilter($value)
    {
        $this->constituencyFilter = '';
        $this->wardFilter = '';

        $this->reset('constituencyOptions');
        $this->reset('wardsOptions');

        if (!empty($value)) {
            $this->loadConstituencies($value);
        }

        $this->resetPage();
    }

    public function updatedConstituencyFilter($value)
    {
        $this->wardFilter = '';

        $this->reset('wardsOptions');

        if (!empty($value)) {
            $this->loadWards($value);
        }

        $this->resetPage();
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

        $this->wardsOptions = $this->locationService->mapWardsForSelect($constituencyCode);
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

    public function getFilters(): array
    {
        return [
            'search' => $this->search,
            'ward' => $this->wardFilter,
            'constituency_code' => $this->constituencyFilter,
            'county_code' => $this->countyFilter,
            'category'  => $this->categoryFilter,
            'sector' => $this->sectorFilter,
        ];
    }

    public function resetFilters()
    {
        $this->reset(['search', 'wardFilter', 'categoryFilter', 'sectorFilter', 'constituencyFilter', 'countyFilter']);
        $this->resetPage();
    }

    public function refreshData()
    {
        $this->cacheService->flushGroupsCache();
        unset($this->loadGroups);
        unset($this->demographics);
        $this->resetPage();
    }

    public function exportGroupList()
    {
        $query = $this->groupsService->getGroupsQuery(
            null,
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

        $fileName = 'GROUP-LIST' . $filterString . '-' . now()->format('Y-m-d-His') . '.xlsx';

        // 4. Return the download
        return Excel::download(
            new GroupsListExport($query, null),
            $fileName
        );
    }

    public function updatedPerPage ($value) {if ($value === null) $this->perPage = 15;}

    public function render()
    {
        return view('livewire.hq.groups-list');
    }
}
