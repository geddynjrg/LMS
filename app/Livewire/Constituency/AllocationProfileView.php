<?php

namespace App\Livewire\Constituency;

use App\Models\Constituency;
use App\Services\Constituency\ConstituencyAllocationService;
use Livewire\Component;

class AllocationProfileView extends Component
{
    public Constituency $constituency;

    public function mount(Constituency $constituency): void
    {
        $this->constituency = $constituency->loadMissing('county');
    }

    public function render(ConstituencyAllocationService $service)
    {
        $profile = $service->forConstituency($this->constituency->constituency_code)
            ?? new \App\Models\ConstituencyAllocationProfile([
                'constituency_code' => $this->constituency->constituency_code,
            ]);

        return view('livewire.constituency.allocation-profile-view', [
            'profile' => $profile,
            'allocationYears' => $profile->normalizedAllocationYears(),
            'transferLevels' => $profile->normalizedTransferLevels(),
            'phaseIssuedAmounts' => $profile->normalizedPhaseIssuedAmounts(),
        ]);
    }
}
