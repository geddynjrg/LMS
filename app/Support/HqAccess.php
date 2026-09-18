<?php

namespace App\Support;

use App\Models\LoanPhase;
use App\Models\User;

class HqAccess
{
    /** Roles that may use HQ / national administration routes. */
    public const ROUTE_ROLES = ['super-admin', 'hq_officer', 'admin'];

    /** Phase statuses visible on the HQ review page. */
    public const REVIEWABLE_PHASE_STATUSES = [
        'submitted_to_hq',
        'approved',
        'in_training',
        'disbursed',
        'rejected',
    ];

    /** Phase statuses where HQ may upload documents or approve batches. */
    public const EDITABLE_PHASE_STATUSES = [
        'submitted_to_hq',
    ];

    public static function userCanAccessRoutes(?User $user): bool
    {
        return $user !== null && $user->hasAnyRole(self::ROUTE_ROLES);
    }

    public static function userCanApprovePhases(?User $user): bool
    {
        return $user !== null
            && self::userCanAccessRoutes($user)
            && $user->can('approve application');
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

    public static function phaseCanBeEditedBy(?User $user, LoanPhase $phase): bool
    {
        if ($phase->is_locked && ! LmsGracePeriod::isActive()) {
            return false;
        }

        if (! self::userCanApprovePhases($user)) {
            return false;
        }

        if ($user->hasRole('super-admin')) {
            return in_array($phase->status, self::EDITABLE_PHASE_STATUSES, true);
        }

        return in_array($phase->status, self::EDITABLE_PHASE_STATUSES, true);
    }
}
