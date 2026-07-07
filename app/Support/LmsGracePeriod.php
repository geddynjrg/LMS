<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class LmsGracePeriod
{
    public static function isActive(): bool
    {
        $endsAt = static::endsAt();

        return $endsAt !== null && now()->lte($endsAt);
    }

    public static function endsAt(): ?CarbonInterface
    {
        $value = config('lms.grace_period_ends_at');

        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value);
    }
}
