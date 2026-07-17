<?php

namespace App\Support;

use App\Models\LoanPhase;
use App\Models\User;

class SecretariatAccess
{
    /** Roles that may use secretariat routes. */
    public const ROUTE_ROLES = ['super-admin', 'head_of_secretariat'];

    /** Phase statuses the Head of Secretariat may review. */
    public const REVIEWABLE_PHASE_STATUSES = [
        'approved',
        'in_training',
        'pending_disbursement',
        'disbursed',
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
