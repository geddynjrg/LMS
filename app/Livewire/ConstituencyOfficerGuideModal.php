<?php

namespace App\Livewire;

use Livewire\Component;

class ConstituencyOfficerGuideModal extends Component
{
    public bool $showModal = false;

    public bool $dontShowAgain = false;

    public function mount(): void
    {
        $user = auth()->user();

        if (! $user?->hasRole('constituency_officer')) {
            return;
        }

        if ($user->dismissed_officer_guide_at !== null) {
            return;
        }

        if (session()->get('officer_guide_dismissed_session')) {
            return;
        }

        $this->showModal = true;
    }

    public function dismiss(): void
    {
        if ($this->dontShowAgain) {
            auth()->user()->update([
                'dismissed_officer_guide_at' => now(),
            ]);
        } else {
            session()->put('officer_guide_dismissed_session', true);
        }

        $this->showModal = false;
    }

    public function render()
    {
        return view('livewire.constituency-officer-guide-modal');
    }
}
