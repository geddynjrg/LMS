<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait DrupalMemberOtpFallback
{
    /**
     * Drupal bases to query for member OTP fallback (staging + production).
     *
     * @return list<string>
     */
    protected function drupalOtpFallbackBases(): array
    {
        $urls = [];
        if (!empty($this->baseUrl)) {
            $urls[] = rtrim((string) $this->baseUrl, '/');
        }

        $peers = config('drupal.peer_urls', ['http://prod-admin']);
        foreach ((array) $peers as $peer) {
            $peer = rtrim(trim((string) $peer), '/');
            if ($peer !== '' && !in_array($peer, $urls, true)) {
                $urls[] = $peer;
            }
        }

        return $urls;
    }

    protected function portalLabelForDrupalBase(string $base): string
    {
        if (str_contains($base, 'prod-admin')) {
            return 'loans.uwezo.go.ke';
        }

        return 'loans-staging.uwezo.go.ke';
    }

    /**
     * Fetch SMS-failed member confirmation OTPs for an officer's constituencies.
     *
     * @param  list<string>  $constituencyNames
     * @return list<array<string, mixed>>
     */
    public function getMemberOtpFallback(array $constituencyNames): array
    {
        $constituencyNames = array_values(array_filter(array_map('trim', $constituencyNames)));
        if ($constituencyNames === [] || empty($this->apiKey)) {
            return [];
        }

        $items = [];
        foreach ($this->drupalOtpFallbackBases() as $base) {
            try {
                $response = Http::withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Accept' => 'application/json',
                ])->timeout(4)->get("{$base}/api/v1/lms/member-otp-fallback", [
                    'constituencies' => implode(',', $constituencyNames),
                    '_format' => 'json',
                ]);
            } catch (\Throwable $e) {
                Log::warning('Member OTP fallback request failed', [
                    'host' => $base,
                ]);
                continue;
            }

            if (!$response->successful()) {
                Log::warning('Member OTP fallback HTTP error', [
                    'host' => $base,
                    'status' => $response->status(),
                ]);
                continue;
            }

            $payload = $response->json();
            foreach (($payload['items'] ?? []) as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $item['portal_label'] = $item['portal_label'] ?? $this->portalLabelForDrupalBase($base);
                $item['source_host'] = $base;
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * Tell Drupal which officer viewed which fallback codes.
     *
     * @param  list<array<string, mixed>>  $items
     */
    public function recordMemberOtpReveal(User $user, array $items): void
    {
        if (empty($this->apiKey) || $items === []) {
            return;
        }

        $payloadItems = [];
        $hosts = [];
        foreach ($items as $item) {
            $payloadItems[] = [
                'application_id' => (string) ($item['application_id'] ?? ''),
                'national_id_code' => (string) ($item['national_id_code'] ?? ''),
                'member_id' => (string) ($item['member_id'] ?? ''),
            ];
            $host = rtrim((string) ($item['source_host'] ?? ''), '/');
            if ($host !== '') {
                $hosts[$host] = true;
            }
        }

        $officer = [
            'officer_id' => (string) $user->id,
            'officer_name' => trim((string) ($user->name ?? $user->full_name ?? '')),
            'officer_email' => (string) ($user->email ?? ''),
            'officer_constituency' => (string) ($user->currentConstituency?->constituency_name ?? ''),
            'officer_ip' => (string) request()->ip(),
            'items' => $payloadItems,
        ];

        $bases = array_keys($hosts);
        if ($bases === []) {
            $bases = $this->drupalOtpFallbackBases();
        }

        foreach ($bases as $base) {
            try {
                Http::withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->timeout(4)->post("{$base}/api/v1/lms/member-otp-fallback?_format=json", $officer);
            } catch (\Throwable $e) {
                Log::warning('Member OTP fallback reveal failed', [
                    'host' => $base,
                ]);
            }
        }
    }
}
