<?php

namespace App\Livewire;

use App\Models\Constituency;
use App\Models\County;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Group;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GroupsListExport;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use App\Services\Loan\LoanAnalysisService;
use App\Services\Group\GroupsService;

class GroupsInfo extends Component
{
    use WithPagination;

    protected LoanAnalysisService $analysisService;
    protected GroupsService $groupsService;
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
    public $sortField = 'group_name';

    public $sortDirection = 'asc';

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

    public function boot(LoanAnalysisService $analysisService, GroupsService $groupsService)
    {
        $this->analysisService = $analysisService;
        $this->groupsService = $groupsService;
    }

    public function mount(County $county, Constituency $constituency)
    {
        $data = $this->analysisService->getConstituencyStats($this->constituency);

        $this->stats = $data;

        $this->wards = $constituency->wards;
    }

    public function getFilters(): array
    {
        return [
            'search' => $this->search,
            'ward_name' => $this->wardFilter,
            'category'  => $this->categoryFilter,
            'sector' => $this->sectorFilter,
        ];
    }

    public function resetFilters()
    {
        $this->reset(['search', 'wardFilter', 'categoryFilter', 'sectorFilter']);
        $this->resetPage();
        $this->dispatch('clear-selects');
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
        $totalGroups = $this->stats['totalGroups'] ?? 0;
        $totalIssued = $this->stats['totalAmountIssued'] ?? 0;

        return [
            [
                'label' => 'Women Groups',
                'val' => $this->stats['womenGroups'] ?? 0,
                'amount' => $this->stats['womenAmount'],
                'color' => 'pink',
                'icon' => 'fa-female',
                'cat' => 'Women'
            ],
            [
                'label' => 'Youth Groups',
                'val' => $this->stats['youthGroups'] ?? 0,
                'amount' => $this->stats['youthAmount'],
                'color' => 'orange',
                'icon' => 'fa-child',
                'cat' => 'Youth'
            ],
            [
                'label' => 'PWD Groups',
                'val' => $this->stats['pwdGroups'] ?? 0,
                'amount' => $this->stats['pwdAmount'],
                'color' => 'purple',
                'icon' => 'fa-wheelchair',
                'cat' => 'PWD'
            ],
            [
                'label' => 'Registry Size',
                'val' => $totalGroups,
                'amount' => $totalIssued,
                'color' => 'primary',
                'icon' => 'fa-users',
                'cat' => null
            ]
        ];
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc'; // Reset to asc for new field
        }

        $this->resetPage();
    }

    public function refreshData()
    {
        $groupsTag = config('cache.tags.phases.constituency_summary' . $this->constituency->constituency_code);
        Cache::tags([$groupsTag])->flush();
        $this->resetPage();
    }

    public function loadGroups()
    {
        $groupsTag = config('cache.tags.phases.constituency_summary') . $this->constituency->constituency_code;
        if (empty($this->getFilters())) {
            $cacheKey = "groups_list_{$this->constituency->constituency_code}_page_{$this->getPage()}";
            return Cache::tags([$groupsTag])->remember($cacheKey, now()->addMinutes(15), function () {
                return $this->groupsService->getGroupsQuery($this->constituency->constituency_code, [],$this->sortField, $this->sortDirection);
            });
        }

        return $this->groupsService->getGroupsQuery($this->constituency->constituency_code, $this->getFilters(),$this->sortField, $this->sortDirection);
    }

    public function exportGroupList()
    {
        $query = $this->loadGroups();

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
        return view('livewire.group.groups-info', [
            'groups' => $this->loadGroups()->paginate((int)$this->perPage),
        ]);
    }
}
