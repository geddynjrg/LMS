<?php

namespace App\Livewire;

use App\Models\HistoricalCorrectionRequest;
use App\Services\Historical\HistoricalCorrectionService;
use Livewire\Component;

class HistoricalCorrectionPromptModal extends Component
{
    public bool $showModal = false;

    /** @var list<array<string, mixed>> */
    public array $requests = [];

    public function mount(HistoricalCorrectionService $service): void
    {
        $user = auth()->user();

        if (! $user?->hasRole('constituency_officer')) {
            return;
        }

        $pending = $service->pendingForOfficer($user);
        if ($pending->isEmpty()) {
            return;
        }

        $this->requests = $pending->map(function (HistoricalCorrectionRequest $request) {
            return [
                'id' => $request->id,
                'type' => $request->type,
                'group' => $request->groupLoan?->group?->name,
                'phase' => $request->groupLoan?->loanPhase?->phase,
                'fields' => $request->fieldLabels(),
                'message' => $request->message,
                'requested_by' => $request->requester?->name,
                'url' => $this->resolveUrl($request),
            ];
        })->values()->all();

        $this->showModal = true;
    }

    public function acknowledgeAllocation(int $requestId, HistoricalCorrectionService $service): void
    {
        $user = auth()->user();
        $request = HistoricalCorrectionRequest::query()->find($requestId);

        if (! $user || ! $request || ! $request->isPending()) {
            return;
        }

        if ($request->type !== HistoricalCorrectionRequest::TYPE_ALLOCATION_PROFILE) {
            return;
        }

        $codes = $user->constituencies()->pluck('constituency_code');
        if ($user->currentConstituency?->constituency_code) {
            $codes->push($user->currentConstituency->constituency_code);
        }

        if (! $codes->contains($request->constituency_code)) {
            return;
        }

        $service->markCompleted($request, $user);
        $this->requests = array_values(array_filter(
            $this->requests,
            fn ($row) => (int) $row['id'] !== $requestId
        ));

        if ($this->requests === []) {
            $this->showModal = false;
        }
    }

    public function dismissForNow(): void
    {
        $this->showModal = false;
    }

    protected function resolveUrl(HistoricalCorrectionRequest $request): ?string
    {
        $request->loadMissing(['groupLoan.loanPhase.constituency.county', 'constituency.county']);

        if ($request->type === HistoricalCorrectionRequest::TYPE_ALLOCATION_PROFILE) {
            $constituency = $request->constituency;
            if ($constituency?->slug && $constituency->county?->slug) {
                return route('const.allocations.index', [
                    $constituency->county->slug,
                    $constituency->slug,
                ]);
            }

            return null;
        }

        $phase = $request->groupLoan?->loanPhase;
        $countySlug = $phase?->constituency?->county?->slug;
        $constituencySlug = $phase?->constituency?->slug;

        if (! $countySlug || ! $constituencySlug) {
            return null;
        }

        return route('const.loans.grouploans', [$countySlug, $constituencySlug]);
    }

    public function render()
    {
        return view('livewire.historical-correction-prompt-modal');
    }
}
