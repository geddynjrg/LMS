<?php

namespace App\Services\Constituency;

use App\Models\Constituency;
use App\Models\ConstituencyAllocationProfile;
use App\Models\LoanPhase;
use App\Support\ConstituencyAllocationFields;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConstituencyAllocationService
{
    public function forConstituency(string $constituencyCode): ?ConstituencyAllocationProfile
    {
        return ConstituencyAllocationProfile::query()
            ->where('constituency_code', $constituencyCode)
            ->first();
    }

    public function save(string $constituencyCode, array $data, ?int $userId = null): ConstituencyAllocationProfile
    {
        Constituency::query()->where('constituency_code', $constituencyCode)->firstOrFail();

        $payload = $this->normalizePayload($data);
        $payload['constituency_code'] = $constituencyCode;
        $payload['updated_by'] = $userId ?? auth()->id();

        return DB::transaction(function () use ($constituencyCode, $payload) {
            return ConstituencyAllocationProfile::query()->updateOrCreate(
                ['constituency_code' => $constituencyCode],
                $payload
            );
        });
    }

    public function importFromRows(string $constituencyCode, array $labelAmountRows, ?int $userId = null): ConstituencyAllocationProfile
    {
        $data = ConstituencyAllocationFields::emptyPayload();

        $labelMap = array_change_key_case(ConstituencyAllocationFields::allocationLabels(), CASE_LOWER);
        $labelMap['balance in the main account'] = '__skip__';
        $labelMap['total allocations'] = '__skip__';
        $labelMap['total transfers'] = '__skip__';
        $labelMap['total loan portfolio'] = '__skip__';
        $labelMap['total approvals'] = '__skip__';

        foreach ($labelAmountRows as $row) {
            $label = trim((string) ($row['label'] ?? ''));
            $amount = $this->parseAmount($row['amount'] ?? null);

            if ($label === '' || $amount === null) {
                continue;
            }

            $labelLower = strtolower($label);

            if (isset($labelMap[$labelLower])) {
                if ($labelMap[$labelLower] === '__skip__') {
                    continue;
                }

                $data[$labelMap[$labelLower]] = $amount;

                continue;
            }

            if (preg_match('/^(\d+)(?:st|nd|rd|th)\s+transfer$/i', $label, $matches)) {
                $this->upsertTransferLevel($data, (int) $matches[1], $amount);

                continue;
            }

            if (preg_match('/^actual amount issued in phase\s+(\d+)$/i', $label, $matches)) {
                $this->upsertPhaseIssuedAmount($data, (int) $matches[1], $amount);
            }
        }

        return $this->save($constituencyCode, $data, $userId);
    }

    public function syncPhaseIssuedFromLoanPhase(LoanPhase $phase): ?ConstituencyAllocationProfile
    {
        $phaseNumber = self::extractPhaseNumber((string) ($phase->phase ?? ''));

        if ($phaseNumber === null) {
            return null;
        }

        $phase->loadMissing('phaseApplications');

        $totalRecommended = (float) $phase->phaseApplications
            ->whereNotIn('status', ['rejected', 'deferred', 'pending'])
            ->sum('amount_approved');

        $existing = $this->forConstituency($phase->constituency_code);
        $payload = $existing
            ? $this->profileToPayload($existing)
            : ConstituencyAllocationFields::emptyPayload();

        $payload['phase_issued_amounts'] = collect($payload['phase_issued_amounts'] ?? [])
            ->reject(fn (array $row) => (int) ($row['phase'] ?? 0) === $phaseNumber)
            ->push(['phase' => $phaseNumber, 'amount' => round($totalRecommended, 2)])
            ->sortBy('phase')
            ->values()
            ->all();

        return $this->save($phase->constituency_code, $payload);
    }

    public function syncLatestActivePhaseForConstituency(string $constituencyCode): ?ConstituencyAllocationProfile
    {
        $phase = LoanPhase::query()
            ->where('constituency_code', $constituencyCode)
            ->whereNotNull('submitted_to_hq_at')
            ->orderByDesc('submitted_to_hq_at')
            ->orderByDesc('id')
            ->first();

        if (! $phase) {
            return null;
        }

        return $this->syncPhaseIssuedFromLoanPhase($phase);
    }

    public static function extractPhaseNumber(string $phaseLabel): ?int
    {
        if (preg_match('/phase\s*(\d+)/i', $phaseLabel, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    protected function profileToPayload(ConstituencyAllocationProfile $profile): array
    {
        $payload = [];

        foreach (ConstituencyAllocationFields::allocationKeys() as $key) {
            $payload[$key] = (float) $profile->{$key};
        }

        $payload['transfer_levels'] = $profile->normalizedTransferLevels();
        $payload['phase_issued_amounts'] = $profile->normalizedPhaseIssuedAmounts();

        return $payload;
    }

    public function normalizePayload(array $data): array
    {
        $payload = ConstituencyAllocationFields::emptyPayload();

        foreach (ConstituencyAllocationFields::allocationKeys() as $key) {
            if (array_key_exists($key, $data)) {
                $payload[$key] = $this->parseAmount($data[$key]) ?? 0;
            }
        }

        if (array_key_exists('transfer_levels', $data)) {
            $payload['transfer_levels'] = $this->normalizeTransferLevels($data['transfer_levels']);
        }

        if (array_key_exists('phase_issued_amounts', $data)) {
            $payload['phase_issued_amounts'] = $this->normalizePhaseIssuedAmounts($data['phase_issued_amounts']);
        }

        $payload['balance_main_account'] = $this->computeBalanceMainAccount($payload);

        return $payload;
    }

    protected function computeBalanceMainAccount(array $payload): float
    {
        $totalAllocations = collect(ConstituencyAllocationFields::allocationKeys())
            ->sum(fn (string $key) => (float) ($payload[$key] ?? 0));

        $totalTransfers = collect($payload['transfer_levels'] ?? [])
            ->sum(fn (array $row) => (float) ($row['amount'] ?? 0));

        $totalApprovals = collect($payload['phase_issued_amounts'] ?? [])
            ->sum(fn (array $row) => (float) ($row['amount'] ?? 0));

        return round(($totalAllocations + $totalTransfers) - $totalApprovals, 2);
    }

    /**
     * @return list<array{level:int,amount:float}>
     */
    protected function normalizeTransferLevels(mixed $levels): array
    {
        return collect($levels ?: [])
            ->map(function ($row) {
                return [
                    'level' => (int) ($row['level'] ?? 0),
                    'amount' => $this->parseAmount($row['amount'] ?? 0) ?? 0,
                ];
            })
            ->filter(fn (array $row) => $row['level'] > 0)
            ->sortBy('level')
            ->values()
            ->all();
    }

    /**
     * @return list<array{phase:int,amount:float}>
     */
    protected function normalizePhaseIssuedAmounts(mixed $phases): array
    {
        return collect($phases ?: [])
            ->map(function ($row) {
                return [
                    'phase' => (int) ($row['phase'] ?? 0),
                    'amount' => $this->parseAmount($row['amount'] ?? 0) ?? 0,
                ];
            })
            ->filter(fn (array $row) => $row['phase'] > 0)
            ->sortBy('phase')
            ->values()
            ->all();
    }

    protected function upsertTransferLevel(array &$data, int $level, float $amount): void
    {
        $levels = collect($data['transfer_levels'] ?? [])
            ->reject(fn (array $row) => (int) ($row['level'] ?? 0) === $level)
            ->push(['level' => $level, 'amount' => $amount])
            ->sortBy('level')
            ->values()
            ->all();

        $data['transfer_levels'] = $levels;
    }

    protected function upsertPhaseIssuedAmount(array &$data, int $phase, float $amount): void
    {
        $phases = collect($data['phase_issued_amounts'] ?? [])
            ->reject(fn (array $row) => (int) ($row['phase'] ?? 0) === $phase)
            ->push(['phase' => $phase, 'amount' => $amount])
            ->sortBy('phase')
            ->values()
            ->all();

        $data['phase_issued_amounts'] = $phases;
    }

    protected function parseAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return round((float) $value, 2);
        }

        $clean = preg_replace('/[^\d.\-]/', '', (string) $value);

        if ($clean === '' || ! is_numeric($clean)) {
            throw ValidationException::withMessages([
                'amount' => 'Invalid amount value in allocation data.',
            ]);
        }

        return round((float) $clean, 2);
    }
}
