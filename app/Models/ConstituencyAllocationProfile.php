<?php

namespace App\Models;

use App\Support\ConstituencyAllocationFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConstituencyAllocationProfile extends Model
{
    protected $fillable = [
        'constituency_code',
        'allocation_2013_2014',
        'allocation_2014_2015',
        'allocation_2015_2016',
        'allocation_2016_2017',
        'allocation_2017_2018',
        'allocation_2018_2019',
        'allocation_2019_2020',
        'allocation_2020_2021',
        'allocation_2021_2022',
        'allocation_2022_2023',
        'allocation_2023_2024',
        'allocation_2024_2025',
        'allocation_2025_2026',
        'transfer_levels',
        'phase_issued_amounts',
        'balance_main_account',
        'updated_by',
    ];

    protected $casts = [
        'allocation_2013_2014' => 'decimal:2',
        'allocation_2014_2015' => 'decimal:2',
        'allocation_2015_2016' => 'decimal:2',
        'allocation_2016_2017' => 'decimal:2',
        'allocation_2017_2018' => 'decimal:2',
        'allocation_2018_2019' => 'decimal:2',
        'allocation_2019_2020' => 'decimal:2',
        'allocation_2020_2021' => 'decimal:2',
        'allocation_2021_2022' => 'decimal:2',
        'allocation_2022_2023' => 'decimal:2',
        'allocation_2023_2024' => 'decimal:2',
        'allocation_2024_2025' => 'decimal:2',
        'allocation_2025_2026' => 'decimal:2',
        'transfer_levels' => 'array',
        'phase_issued_amounts' => 'array',
        'balance_main_account' => 'decimal:2',
    ];

    public function constituency(): BelongsTo
    {
        return $this->belongsTo(Constituency::class, 'constituency_code', 'constituency_code');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * @return list<array{level:int,amount:float}>
     */
    public function normalizedTransferLevels(): array
    {
        $levels = collect($this->transfer_levels ?? ConstituencyAllocationFields::defaultTransferLevels())
            ->map(function ($row) {
                return [
                    'level' => (int) ($row['level'] ?? 0),
                    'amount' => round((float) ($row['amount'] ?? 0), 2),
                ];
            })
            ->filter(fn (array $row) => $row['level'] > 0)
            ->sortBy('level')
            ->values()
            ->all();

        return $levels ?: ConstituencyAllocationFields::defaultTransferLevels();
    }

    /**
     * @return list<array{phase:int,amount:float}>
     */
    public function normalizedPhaseIssuedAmounts(): array
    {
        return collect($this->phase_issued_amounts ?? [])
            ->map(function ($row) {
                return [
                    'phase' => (int) ($row['phase'] ?? 0),
                    'amount' => round((float) ($row['amount'] ?? 0), 2),
                ];
            })
            ->filter(fn (array $row) => $row['phase'] > 0)
            ->sortBy('phase')
            ->values()
            ->all();
    }

    public function totalAllocations(): float
    {
        return collect(ConstituencyAllocationFields::allocationKeys())
            ->sum(fn (string $key) => (float) $this->{$key});
    }

    public function totalTransfers(): float
    {
        return collect($this->normalizedTransferLevels())
            ->sum(fn (array $row) => $row['amount']);
    }

    public function totalPhaseIssuedAmounts(): float
    {
        return collect($this->normalizedPhaseIssuedAmounts())
            ->sum(fn (array $row) => $row['amount']);
    }

    public function totalLoanPortfolio(): float
    {
        return $this->totalAllocations() + $this->totalTransfers();
    }

    public function totalApprovals(): float
    {
        return $this->totalLoanPortfolio() + $this->totalPhaseIssuedAmounts();
    }
}
