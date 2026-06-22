<?php

namespace App\Support;

final class ConstituencyAllocationFields
{
    public const ALLOCATION_FIRST_YEAR = 2013;

    /** Last allocation FY start year (2025/2026). */
    public const ALLOCATION_LAST_YEAR = 2025;

    public const DEFAULT_TRANSFER_COUNT = 5;

    /**
     * @return list<string>
     */
    public static function allocationKeys(): array
    {
        $keys = [];

        for ($year = self::ALLOCATION_FIRST_YEAR; $year <= self::ALLOCATION_LAST_YEAR; $year++) {
            $keys[] = sprintf('allocation_%d_%d', $year, $year + 1);
        }

        return $keys;
    }

    /**
     * @return array<string, string>
     */
    public static function allocationLabels(): array
    {
        $labels = [];

        foreach (self::allocationKeys() as $key) {
            $labels[$key] = self::allocationLabelForKey($key);
        }

        return $labels;
    }

    public static function allocationLabelForKey(string $key): string
    {
        if (preg_match('/^allocation_(\d{4})_(\d{4})$/', $key, $matches)) {
            return "{$matches[1]}/{$matches[2]} Allocation";
        }

        throw new \InvalidArgumentException("Unknown allocation key: {$key}");
    }

    /**
     * @return list<array{level:int,amount:float|int}>
     */
    public static function defaultTransferLevels(): array
    {
        $levels = [];

        for ($level = 1; $level <= self::DEFAULT_TRANSFER_COUNT; $level++) {
            $levels[] = ['level' => $level, 'amount' => 0];
        }

        return $levels;
    }

    /**
     * @return list<array{phase:int,amount:float|int}>
     */
    public static function defaultPhaseIssuedAmounts(): array
    {
        return [
            ['phase' => 1, 'amount' => 0],
        ];
    }

    public static function transferLabelForLevel(int $level): string
    {
        return self::ordinal($level) . ' Transfer';
    }

    public static function phaseIssuedLabel(int $phase): string
    {
        return "Actual Amount Issued in Phase {$phase}";
    }

    public static function allEditableKeys(): array
    {
        return array_merge(
            self::allocationKeys(),
            ['transfer_levels', 'phase_issued_amounts']
        );
    }

    public static function emptyPayload(): array
    {
        return [
            ...array_fill_keys(self::allocationKeys(), 0),
            'transfer_levels' => self::defaultTransferLevels(),
            'phase_issued_amounts' => self::defaultPhaseIssuedAmounts(),
        ];
    }

    public static function ordinal(int $number): string
    {
        $suffix = 'th';
        $remainder = $number % 100;

        if ($remainder < 11 || $remainder > 13) {
            $suffix = match ($number % 10) {
                1 => 'st',
                2 => 'nd',
                3 => 'rd',
                default => 'th',
            };
        }

        return $number . $suffix;
    }
}
