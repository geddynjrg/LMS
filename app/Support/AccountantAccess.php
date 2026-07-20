<?php

namespace App\Support;

use App\Models\LoanPhase;
use App\Models\User;

class AccountantAccess
{
    /** Roles that may use accountant routes. */
    public const ROUTE_ROLES = ['super-admin', 'accountant'];

    /** Phase statuses the accountant may review (HQ-submitted batches with processed code lists). */
    public const REVIEWABLE_PHASE_STATUSES = [
        'submitted_to_hq',
    ];

    public static function userCanAccessRoutes(?User $user): bool
    {
        return $user !== null && $user->hasAnyRole(self::ROUTE_ROLES);
    }

    public static function phaseCanBeViewedBy(?User $user, LoanPhase $phase): bool
    {
        if (! self::userCanAccessRoutes($user)) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return true;
        }

        return in_array($phase->status, self::REVIEWABLE_PHASE_STATUSES, true);
    }
}
