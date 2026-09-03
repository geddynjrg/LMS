<?php

namespace App\Support;

class GroupNameMatcher
{
    /**
     * Treat spreadsheet name variants as the same group
     * (e.g. "Faith S.H.G" vs "Faith Self Help Group").
     */
    public static function isSameGroup(string $a, string $b): bool
    {
        $catA = self::detectCategory($a);
        $catB = self::detectCategory($b);
        if ($catA !== null && $catB !== null && $catA !== $catB) {
            return false;
        }

        $na = self::normalize($a);
        $nb = self::normalize($b);

        if ($na === '' || $nb === '') {
            return false;
        }

        if ($na === $nb) {
            return true;
        }

        $shorter = strlen($na) <= strlen($nb) ? $na : $nb;
        $longer = strlen($na) <= strlen($nb) ? $nb : $na;
        if (strlen($shorter) >= 8 && str_contains($longer, $shorter)) {
            return true;
        }

        if (strlen($na) >= 5 && strlen($nb) >= 5) {
            $maxLen = max(strlen($na), strlen($nb));
            if (levenshtein($na, $nb) <= 1 && $maxLen <= 24) {
                return true;
            }
        }

        return false;
    }

    /**
     * Expand abbreviations but keep category tokens (women/youth/pwd/selfhelp).
     * Use this for within-document GFS consistency so "Makena W.G" does not
     * collide with "Makena Youth Group".
     */
    public static function normalizeWithCategory(string $name): string
    {
        $s = self::expandAbbreviations($name);

        foreach (['group', 'groups', 'the'] as $stop) {
            $s = (string) preg_replace('/\b' . $stop . '\b/', ' ', $s);
        }

        $s = (string) preg_replace('/\s+/', ' ', $s);

        return trim($s);
    }

    public static function normalize(string $name): string
    {
        $s = self::expandAbbreviations($name);

        foreach (['selfhelp', 'women', 'youth', 'pwd', 'group', 'groups', 'the'] as $stop) {
            $s = (string) preg_replace('/\b' . $stop . '\b/', ' ', $s);
        }

        $s = (string) preg_replace('/\s+/', ' ', $s);

        return trim($s);
    }

    public static function detectCategory(string $name): ?string
    {
        $s = self::expandAbbreviations($name);

        foreach (['women', 'youth', 'pwd', 'selfhelp'] as $category) {
            if (preg_match('/\b' . $category . '\b/', $s)) {
                return $category;
            }
        }

        return null;
    }

    public static function normalizeCategory(string $value): string
    {
        $compact = strtolower((string) preg_replace('/[\s.]+/', '', $value));

        $aliases = [
            'pwd' => 'pwd',
            'pwds' => 'pwd',
            'personswithdisability' => 'pwd',
            'personswithdisabilities' => 'pwd',
            'personwithdisability' => 'pwd',
            'youth' => 'youth',
            'women' => 'women',
            'woman' => 'women',
        ];

        return $aliases[$compact] ?? strtolower(trim($value));
    }

    private static function expandAbbreviations(string $name): string
    {
        $s = strtolower(trim($name));
        $s = str_replace(['&', '/', '-', '_'], ' ', $s);

        $s = (string) preg_replace('/\bs\.?\s*h\.?\s*g\.?\b/i', ' selfhelp ', $s);
        $s = (string) preg_replace('/\bw\.?\s*g\.?\b/i', ' women ', $s);
        $s = (string) preg_replace('/\bw\.?\s*group\b/i', ' women ', $s);
        $s = (string) preg_replace('/\bself[\s-]*help\s+groups?\b/i', ' selfhelp ', $s);
        $s = (string) preg_replace('/\bself\s+groups?\b/i', ' selfhelp ', $s);
        $s = (string) preg_replace('/\bwomen\s+groups?\b/i', ' women ', $s);
        $s = (string) preg_replace('/\byouth\s+groups?\b/i', ' youth ', $s);
        $s = (string) preg_replace('/\bpersons?\s+with\s+disabilit(?:y|ies)\b/i', ' pwd ', $s);
        $s = (string) preg_replace('/\bp\.?\s*w\.?\s*d\.?s?\b/i', ' pwd ', $s);
        $s = (string) preg_replace('/\bdisabled\b/i', ' pwd ', $s);

        return (string) preg_replace('/[^a-z0-9\s]/', '', $s);
    }
}
