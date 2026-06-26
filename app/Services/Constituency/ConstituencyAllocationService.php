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

        $labelMap = array_change_key_case($this->allocationLabelMap(), CASE_LOWER);
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

                if (is_array($labelMap[$labelLower])) {
                    $this->upsertAllocationYear(
                        $data,
                        (int) $labelMap[$labelLower]['year_start'],
                        (int) $labelMap[$labelLower]['year_end'],
                        $amount
                    );
                } else {
                    if (preg_match('/^allocation_(\d{4})_(\d{4})$/', (string) $labelMap[$labelLower], $matches)) {
                        $this->upsertAllocationYear($data, (int) $matches[1], (int) $matches[2], $amount);
                    } else {
                        $data[$labelMap[$labelLower]] = $amount;
                    }
                }

                continue;
            }

            if (preg_match('/^(\d{4})\/(\d{4})\s+allocation$/i', $label, $matches)) {
                $this->upsertAllocationYear($data, (int) $matches[1], (int) $matches[2], $amount);

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

        return $this->syncAllSubmittedPhasesForConstituency($constituencyCode, $phase);
    }

    /**
     * Merge recommended totals from every HQ-submitted loan phase into the allocation profile.
     * Preserves manually entered phase rows that have no matching loan phase.
     */
    public function syncAllSubmittedPhasesForConstituency(string $constituencyCode, ?LoanPhase $includePhase = null): ?ConstituencyAllocationProfile
    {
        $submittedPhases = LoanPhase::query()
            ->where('constituency_code', $constituencyCode)
            ->whereNotNull('submitted_to_hq_at')
            ->with('phaseApplications')
            ->orderBy('submitted_to_hq_at')
            ->orderBy('id')
            ->get();

        if ($includePhase && ! $submittedPhases->contains('id', $includePhase->id)) {
            $includePhase->loadMissing('phaseApplications');
            $submittedPhases = $submittedPhases->push($includePhase);
        }

        if ($submittedPhases->isEmpty()) {
            return $includePhase ? $this->syncPhaseIssuedFromLoanPhase($includePhase) : null;
        }

        $existing = $this->forConstituency($constituencyCode);
        $payload = $existing
            ? $this->profileToPayload($existing)
            : ConstituencyAllocationFields::emptyPayload();

        $syncedPhaseNumbers = [];

        foreach ($submittedPhases as $phase) {
            $phaseNumber = self::extractPhaseNumber((string) ($phase->phase ?? ''));

            if ($phaseNumber === null) {
                continue;
            }

            $syncedPhaseNumbers[] = $phaseNumber;

            $totalRecommended = (float) $phase->phaseApplications
                ->whereNotIn('status', ['rejected', 'deferred', 'pending'])
                ->sum('amount_approved');

            $this->upsertPhaseIssuedAmount($payload, $phaseNumber, round($totalRecommended, 2));
        }

        $payload['phase_issued_amounts'] = collect($payload['phase_issued_amounts'] ?? [])
            ->reject(function (array $row) use ($syncedPhaseNumbers) {
                $phaseNumber = (int) ($row['phase'] ?? 0);
                $amount = (float) ($row['amount'] ?? 0);

                return $phaseNumber > 0
                    && $amount === 0.0
                    && ! in_array($phaseNumber, $syncedPhaseNumbers, true);
            })
            ->values()
            ->all();

        return $this->save($constituencyCode, $payload);
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
        $payload = [
            'allocation_years' => $profile->normalizedAllocationYears(),
        ];

        foreach (ConstituencyAllocationFields::allocationKeys() as $key) {
            $payload[$key] = (float) $profile->{$key};
        }

        $payload['transfer_levels'] = $profile->normalizedTransferLevels();
        $payload['phase_issued_amounts'] = $profile->normalizedPhaseIssuedAmounts();

        return $payload;
    }

    /**
     * @return array<string, array{year_start:int,year_end:int}|string>
     */
    protected function allocationLabelMap(): array
    {
        $map = [];

        foreach (ConstituencyAllocationFields::allocationLabels() as $key => $label) {
            $map[$label] = $key;
        }

        return $map;
    }

    public function normalizePayload(array $data): array
    {
        $payload = ConstituencyAllocationFields::emptyPayload();

        if (array_key_exists('allocation_years', $data)) {
            $payload['allocation_years'] = $this->normalizeAllocationYears($data['allocation_years']);
        }

        foreach (ConstituencyAllocationFields::allocationKeys() as $key) {
            if (array_key_exists($key, $data)) {
                $payload[$key] = $this->parseAmount($data[$key]) ?? 0;
            }
        }

        if (! array_key_exists('allocation_years', $data)) {
            $payload['allocation_years'] = $this->allocationYearsFromLegacyColumns($payload);
        }

        $payload = $this->syncLegacyAllocationColumns($payload);

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
        $totalAllocations = collect($payload['allocation_years'] ?? [])
            ->sum(fn (array $row) => (float) ($row['amount'] ?? 0));

        $totalTransfers = collect($payload['transfer_levels'] ?? [])
            ->sum(fn (array $row) => (float) ($row['amount'] ?? 0));

        $totalApprovals = collect($payload['phase_issued_amounts'] ?? [])
            ->sum(fn (array $row) => (float) ($row['amount'] ?? 0));

        return round(($totalAllocations + $totalTransfers) - $totalApprovals, 2);
    }

    /**
     * @return list<array{year_start:int,year_end:int,amount:float}>
     */
    protected function normalizeAllocationYears(mixed $years): array
    {
        $normalized = collect($years ?: [])
            ->map(function ($row) {
                $yearStart = (int) ($row['year_start'] ?? 0);
                $yearEnd = (int) ($row['year_end'] ?? 0);

                if ($yearEnd <= 0 && $yearStart > 0) {
                    $yearEnd = $yearStart + 1;
                }

                return [
                    'year_start' => $yearStart,
                    'year_end' => $yearEnd,
                    'amount' => $this->parseAmount($row['amount'] ?? 0) ?? 0,
                ];
            })
            ->filter(fn (array $row) => $row['year_start'] > 0 && $row['year_end'] === $row['year_start'] + 1)
            ->unique(fn (array $row) => $row['year_start'])
            ->sortBy('year_start')
            ->values()
            ->all();

        return $normalized ?: ConstituencyAllocationFields::defaultAllocationYears();
    }

    /**
     * @return list<array{year_start:int,year_end:int,amount:float}>
     */
    protected function allocationYearsFromLegacyColumns(array $payload): array
    {
        $years = [];

        foreach (ConstituencyAllocationFields::allocationKeys() as $key) {
            if (! preg_match('/^allocation_(\d{4})_(\d{4})$/', $key, $matches)) {
                continue;
            }

            $years[] = [
                'year_start' => (int) $matches[1],
                'year_end' => (int) $matches[2],
                'amount' => (float) ($payload[$key] ?? 0),
            ];
        }

        return $years;
    }

    protected function syncLegacyAllocationColumns(array $payload): array
    {
        foreach ($payload['allocation_years'] ?? [] as $row) {
            $key = ConstituencyAllocationFields::allocationKeyForYears(
                (int) $row['year_start'],
                (int) $row['year_end']
            );

            if (in_array($key, ConstituencyAllocationFields::allocationKeys(), true)) {
                $payload[$key] = (float) ($row['amount'] ?? 0);
            }
        }

        return $payload;
    }

    protected function upsertAllocationYear(array &$data, int $yearStart, int $yearEnd, float $amount): void
    {
        if ($yearEnd !== $yearStart + 1) {
            return;
        }

        $years = collect($data['allocation_years'] ?? ConstituencyAllocationFields::defaultAllocationYears())
            ->reject(fn (array $row) => (int) ($row['year_start'] ?? 0) === $yearStart)
            ->push([
                'year_start' => $yearStart,
                'year_end' => $yearEnd,
                'amount' => $amount,
            ])
            ->sortBy('year_start')
            ->values()
            ->all();

        $data['allocation_years'] = $years;
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
