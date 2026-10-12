<?php

namespace App\Livewire;

use App\Services\DrupalApiService;
use Livewire\Component;

class MemberOtpFallbackModal extends Component
{
    public bool $showModal = false;

    public array $items = [];

    public string $appDownloadUrl = 'https://loans.uwezo.go.ke/uwezo-app/UwezoFund.apk';

    public function mount(): void
    {
        // Bulk SMS for member loan confirmation is live — do not show OTP codes in LMS.
        return;

        $user = auth()->user();

        if (! $user?->hasRole('constituency_officer')) {
            return;
        }

        if (session()->get('member_otp_fallback_dismissed')) {
            return;
        }

        $names = $user->constituencies()
            ->pluck('constituency_name')
            ->filter()
            ->values()
            ->all();

        $currentName = $user->currentConstituency?->constituency_name;
        if (is_string($currentName) && $currentName !== '') {
            $names[] = $currentName;
        }

        $names = array_values(array_unique(array_filter($names)));
        if ($names === []) {
            return;
        }

        try {
            $items = app(DrupalApiService::class)->getMemberOtpFallback($names);
        } catch (\Throwable $e) {
            \Log::warning('Member OTP fallback lookup failed', [
                'officer_id' => $user->id,
            ]);
            return;
        }

        if ($items === []) {
            return;
        }

        $this->items = $items;
        $this->showModal = true;

        try {
            app(DrupalApiService::class)->recordMemberOtpReveal($user, $items);
        } catch (\Throwable $e) {
            \Log::warning('Member OTP fallback reveal failed', [
                'officer_id' => $user->id,
            ]);
        }

        activity('member_confirmation_otp')
            ->causedBy($user)
            ->withProperties([
                'action' => 'lms_reveal',
                'count' => count($items),
                'application_ids' => array_values(array_unique(array_filter(array_map(
                    static fn ($item) => $item['application_id'] ?? null,
                    $items
                )))),
                'national_ids' => array_values(array_unique(array_filter(array_map(
                    static fn ($item) => $item['national_id_code'] ?? null,
                    $items
                )))),
            ])
            ->log('Constituency officer viewed SMS-fallback member confirmation codes');
    }

    public function dismiss(): void
    {
        session()->put('member_otp_fallback_dismissed', true);
        $this->showModal = false;
    }

    public function render()
    {
        return view('livewire.member-otp-fallback-modal');
    }
}
