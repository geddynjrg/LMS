<?php

namespace App\Support;

class GfsCode
{
    private const DASHED_PATTERN = '/^(\d)-(\d{3})-(\d{3})-(\d{7})-(\d{3})-(\d{3,4})$/';

    /**
     * Normalize a GFS code for validation and storage.
     *
     * Non-digit characters (dashes, spaces) are stripped, the digit structure is
     * validated, and the canonical dashed format is returned.
     */
    public static function normalize(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match(self::DASHED_PATTERN, $value)) {
            return $value;
        }

        $digits = preg_replace('/\D/', '', $value);
        $length = strlen($digits);

        if ($length === 20) {
            return sprintf(
                '%s-%s-%s-%s-%s-%s',
                substr($digits, 0, 1),
                substr($digits, 1, 3),
                substr($digits, 4, 3),
                substr($digits, 7, 7),
                substr($digits, 14, 3),
                substr($digits, 17, 3)
            );
        }

        if ($length === 21) {
            return sprintf(
                '%s-%s-%s-%s-%s-%s',
                substr($digits, 0, 1),
                substr($digits, 1, 3),
                substr($digits, 4, 3),
                substr($digits, 7, 7),
                substr($digits, 14, 3),
                substr($digits, 17, 4)
            );
        }

        return null;
    }

    /**
     * @return array{full: string, init: string, county: string, constituency: string, middle: string, sector_part: string, group_suffix: string}|null
     */
    public static function parse(string $value): ?array
    {
        $normalized = self::normalize($value);
        if (!$normalized || !preg_match(self::DASHED_PATTERN, $normalized, $matches)) {
            return null;
        }

        return [
            'full' => $normalized,
            'init' => $matches[1],
            'county' => $matches[2],
            'constituency' => $matches[3],
            'middle' => $matches[4],
            'sector_part' => $matches[5],
            'group_suffix' => $matches[6],
        ];
    }

    public static function groupSuffix(string $value): ?string
    {
        $parsed = self::parse($value);

        return $parsed['group_suffix'] ?? null;
    }
}
