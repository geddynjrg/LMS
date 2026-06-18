<?php

namespace App\Support;

use App\Models\User;

/**
 * Resolves well-known system role holders for workflow notifications.
 */
class SystemRoles
{
    public const HEAD_OF_SECRETARIAT = 'head_of_secretariat';

    public const HQ_OFFICER = 'hq_officer';

    public const CONSTITUENCY_OFFICER = 'constituency_officer';

    /**
     * Active Head of Secretariat user, if one is assigned.
     */
    public static function headOfSecretariatUser(): ?User
    {
        return User::role(self::HEAD_OF_SECRETARIAT)
            ->active()
            ->orderBy('id')
            ->first();
    }
}
