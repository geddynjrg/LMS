<?php

namespace App\Livewire\Reports;

use App\Models\Constituency;
use App\Models\County;
use App\Services\Audit\CountyAuditTrailService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CountyAuditTrail extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public County $county;

    public ?Constituency $constituency = null;

    #[Url(except: '')]
    public string $filterConstituency = '';

    #[Url(except: '')]
    public string $filterLogName = '';

    #[Url(except: '')]
    public string $filterCauserId = '';

    #[Url(except: '')]
    public string $filterDateFrom = '';

    #[Url(except: '')]
    public string $filterDateTo = '';

    #[Url(except: '')]
    public string $search = '';

    public int $perPage = 15;

    protected CountyAuditTrailService $auditTrailService;

    public function boot(CountyAuditTrailService $auditTrailService): void
    {
        $this->auditTrailService = $auditTrailService;
    }

    public function mount(County $county, ?Constituency $constituency = null): void
    {
        $this->county = $county;
        $this->constituency = $constituency;

        $viewer = auth()->user();
        abort_unless($this->auditTrailService->userCanView($viewer, $county), 403);

        if ($constituency !== null) {
            abort_unless(
                in_array($constituency->constituency_code, $this->auditTrailService->getAllowedConstituencyCodes($county, $viewer), true),
                403,
            );
            $this->filterConstituency = $constituency->constituency_code;
        }
    }

    public function updatingFilterConstituency(): void
    {
        $this->resetPage();
    }

    public function updatingFilterLogName(): void
    {
        $this->resetPage();
    }

    public function updatingFilterCauserId(): void
    {
        $this->resetPage();
    }

    public function updatingFilterDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingFilterDateTo(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->filterConstituency = $this->constituency?->constituency_code ?? '';
        $this->filterLogName = '';
        $this->filterCauserId = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->search = '';
        $this->resetPage();
    }

    /**
     * @return array<string, string>
     */
    protected function constituencyNameMap(Collection $constituencies): array
    {
        return $constituencies
            ->mapWithKeys(fn (Constituency $constituency) => [
                $constituency->constituency_code => $constituency->constituency_name,
            ])
            ->all();
    }

    public function render()
    {
        $viewer = auth()->user();
        $constituencies = $this->auditTrailService->getFilterConstituencies($this->county, $viewer);
        $officers = $this->auditTrailService->getCountyOfficers($this->county, $viewer);

        $activities = $this->auditTrailService->getAuditEntries(
            $this->county,
            $viewer,
            [
                'constituency_code' => $this->filterConstituency ?: null,
                'log_name' => $this->filterLogName ?: null,
                'causer_id' => $this->filterCauserId ? (int) $this->filterCauserId : null,
                'date_from' => $this->filterDateFrom ?: null,
                'date_to' => $this->filterDateTo ?: null,
                'search' => $this->search ?: null,
            ],
            $this->perPage,
        );

        return view('livewire.reports.county-audit-trail', [
            'activities' => $activities,
            'constituencies' => $constituencies,
            'officers' => $officers,
            'constituencyNameMap' => $this->constituencyNameMap($constituencies),
        ]);
    }
}
