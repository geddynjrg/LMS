<?php

namespace App\Support;

class GfsCode
{
    private const DASHED_PATTERN = '/^(\d)-(\d{3})-(\d{3})-(\d{7})-(\d{3})-(\d{3,5})$/';

    /**
     * Normalize a GFS code for validation and storage.
     *
     * Digits-only (20–22) or dashed forms are accepted. Missing dashes are
     * inserted so the database always stores the canonical dashed code.
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

        $digits = preg_replace('/\D/', '', $value) ?? '';
        $length = strlen($digits);

        $layouts = [
            20 => [1, 3, 3, 7, 3, 3],
            21 => [1, 3, 3, 7, 3, 4],
            22 => [1, 3, 3, 7, 3, 5],
        ];

        if (!isset($layouts[$length])) {
            return null;
        }

        $parts = [];
        $offset = 0;
        foreach ($layouts[$length] as $size) {
            $parts[] = substr($digits, $offset, $size);
            $offset += $size;
        }

        return implode('-', $parts);
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
