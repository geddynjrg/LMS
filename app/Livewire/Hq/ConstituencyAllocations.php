<?php

namespace App\Livewire\Hq;

use App\Imports\ConstituencyAllocationTemplateImport;
use App\Models\ConstituencyAllocationProfile;
use App\Services\Constituency\ConstituencyAllocationService;
use App\Services\LocationService;
use App\Support\ConstituencyAllocationFields;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class ConstituencyAllocations extends Component
{
    use WithFileUploads;

    public string $countyFilter = '';
    public string $constituencyFilter = '';
    public array $countyOptions = [];
    public array $constituencyOptions = [];
    public array $allocations = [];
    /** @var list<array{level:int,amount:string}> */
    public array $transferLevels = [];
    /** @var list<array{phase:int,amount:string}> */
    public array $phaseIssuedAmounts = [];
    public $uploadFile;

    protected LocationService $locationService;
    protected ConstituencyAllocationService $allocationService;

    public function boot(LocationService $locationService, ConstituencyAllocationService $allocationService): void
    {
        $this->locationService = $locationService;
        $this->allocationService = $allocationService;
    }

    public function mount(): void
    {
        $this->loadCountyOptions();
        $this->resetAmounts();
    }

    public function updatedCountyFilter(): void
    {
        $this->constituencyFilter = '';
        $this->loadConstituencyOptions();
        $this->resetAmounts();
    }

    public function updatedConstituencyFilter(): void
    {
        $this->loadProfile();
    }

    public function addTransferLevel(): void
    {
        $nextLevel = collect($this->transferLevels)->max('level') ?? 0;
        $this->transferLevels[] = [
            'level' => $nextLevel + 1,
            'amount' => '0',
        ];
    }

    public function addPhaseIssuedAmount(): void
    {
        $nextPhase = collect($this->phaseIssuedAmounts)->max('phase') ?? 0;
        $this->phaseIssuedAmounts[] = [
            'phase' => $nextPhase + 1,
            'amount' => '0',
        ];
    }

    public function loadProfile(): void
    {
        $this->resetAmounts();

        if ($this->constituencyFilter === '') {
            return;
        }

        $profile = $this->allocationService->forConstituency($this->constituencyFilter);

        if (! $profile) {
            return;
        }

        foreach (ConstituencyAllocationFields::allocationKeys() as $key) {
            $this->allocations[$key] = (string) $profile->{$key};
        }

        $this->transferLevels = collect($profile->normalizedTransferLevels())
            ->map(fn (array $row) => [
                'level' => $row['level'],
                'amount' => (string) $row['amount'],
            ])
            ->values()
            ->all();

        $this->phaseIssuedAmounts = collect($profile->normalizedPhaseIssuedAmounts())
            ->map(fn (array $row) => [
                'phase' => $row['phase'],
                'amount' => (string) $row['amount'],
            ])
            ->values()
            ->all();

        if ($this->phaseIssuedAmounts === []) {
            $this->phaseIssuedAmounts = collect(ConstituencyAllocationFields::defaultPhaseIssuedAmounts())
                ->map(fn (array $row) => [
                    'phase' => $row['phase'],
                    'amount' => (string) $row['amount'],
                ])
                ->all();
        }
    }

    public function getComputedTotalAllocationsProperty(): float
    {
        return round(collect($this->allocations)->sum(fn ($value) => (float) $value), 2);
    }

    public function getComputedTotalTransfersProperty(): float
    {
        return round(collect($this->transferLevels)->sum(fn ($row) => (float) ($row['amount'] ?? 0)), 2);
    }

    public function getComputedTotalLoanPortfolioProperty(): float
    {
        return round($this->computedTotalAllocations + $this->computedTotalTransfers, 2);
    }

    public function getComputedTotalApprovalsProperty(): float
    {
        return round(collect($this->phaseIssuedAmounts)->sum(fn ($row) => (float) ($row['amount'] ?? 0)), 2);
    }

    public function getComputedBalanceMainAccountProperty(): float
    {
        return round($this->computedTotalLoanPortfolio - $this->computedTotalApprovals, 2);
    }

    public function save(): void
    {
        $this->validate([
            'constituencyFilter' => 'required|exists:constituencies,constituency_code',
            'allocations' => 'array',
            'allocations.*' => 'nullable|numeric|min:0',
            'transferLevels' => 'array',
            'transferLevels.*.level' => 'required|integer|min:1',
            'transferLevels.*.amount' => 'nullable|numeric|min:0',
            'phaseIssuedAmounts' => 'array',
            'phaseIssuedAmounts.*.phase' => 'required|integer|min:1',
            'phaseIssuedAmounts.*.amount' => 'nullable|numeric|min:0',
        ]);

        $payload = array_merge($this->allocations, [
            'transfer_levels' => $this->transferLevels,
            'phase_issued_amounts' => $this->phaseIssuedAmounts,
        ]);

        $this->allocationService->save($this->constituencyFilter, $payload);

        $this->dispatch('swal:toast',
            title: 'Saved',
            message: 'Constituency allocation profile updated successfully.',
            type: 'success'
        );
    }

    public function importTemplate(): void
    {
        $this->validate([
            'constituencyFilter' => 'required|exists:constituencies,constituency_code',
            'uploadFile' => 'required|file|mimes:xlsx,xls|max:2048',
        ]);

        $rows = Excel::toArray(new ConstituencyAllocationTemplateImport(), $this->uploadFile->getRealPath())[0] ?? [];

        $fileConstituencyCode = null;
        $labelRows = [];

        foreach ($rows as $row) {
            $label = trim((string) ($row[0] ?? ''));
            $value = $row[1] ?? null;

            if (strcasecmp($label, 'Constituency Code') === 0) {
                $fileConstituencyCode = trim((string) $value);
            }

            if ($label !== '' && ! in_array($label, [
                'UWEZO FUND — CONSTITUENCY ALLOCATION PROFILE',
                'Constituency Code',
                'Constituency Name',
                'County',
                'ALLOCATIONS BY YEAR',
                'TRANSFERS',
                'PHASE ISSUED AMOUNTS',
            ], true)) {
                $labelRows[] = ['label' => $label, 'amount' => $value];
            }
        }

        if ($fileConstituencyCode && $fileConstituencyCode !== $this->constituencyFilter) {
            $this->addError('uploadFile', 'The template constituency code does not match the selected constituency.');
            return;
        }

        $this->allocationService->importFromRows($this->constituencyFilter, $labelRows);
        $this->reset('uploadFile');
        $this->loadProfile();

        $this->dispatch('swal:toast',
            title: 'Imported',
            message: 'Allocation data imported from Excel successfully.',
            type: 'success'
        );
    }

    protected function resetAmounts(): void
    {
        $this->allocations = [];
        $this->transferLevels = collect(ConstituencyAllocationFields::defaultTransferLevels())
            ->map(fn (array $row) => [
                'level' => $row['level'],
                'amount' => (string) $row['amount'],
            ])
            ->all();
        $this->phaseIssuedAmounts = collect(ConstituencyAllocationFields::defaultPhaseIssuedAmounts())
            ->map(fn (array $row) => [
                'phase' => $row['phase'],
                'amount' => (string) $row['amount'],
            ])
            ->all();

        foreach (ConstituencyAllocationFields::allocationKeys() as $key) {
            $this->allocations[$key] = '0';
        }
    }

    protected function loadCountyOptions(): void
    {
        $this->countyOptions = $this->locationService->getAllCounties()
            ->map(fn ($c) => ['id' => $c->county_code, 'name' => $c->county_name])
            ->values()
            ->all();
    }

    protected function loadConstituencyOptions(): void
    {
        if ($this->countyFilter === '') {
            $this->constituencyOptions = [];

            return;
        }

        $this->constituencyOptions = $this->locationService->getConstituenciesByCounty($this->countyFilter)
            ->map(fn ($c) => ['id' => $c->constituency_code, 'name' => $c->constituency_name])
            ->values()
            ->all();
    }

    public function getSelectedConstituencyProperty()
    {
        if ($this->constituencyFilter === '') {
            return null;
        }

        return $this->locationService->getAllConstituencies()
            ->firstWhere('constituency_code', $this->constituencyFilter);
    }

    public function getSavedProfilesCountProperty(): int
    {
        return ConstituencyAllocationProfile::query()->count();
    }

    public function render()
    {
        return view('livewire.hq.constituency-allocations', [
            'allocationLabels' => ConstituencyAllocationFields::allocationLabels(),
        ]);
    }
}
