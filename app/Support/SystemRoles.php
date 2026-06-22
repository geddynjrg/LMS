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

    public const ACCOUNTANT = 'accountant';

    public const CONSTITUENCY_OFFICER = 'constituency_officer';

    /** Roles that may only be held by one active user at a time. */
    public const SINGLETON_ROLES = [
        self::HEAD_OF_SECRETARIAT,
        self::ACCOUNTANT,
    ];

    /**
     * Active Head of Secretariat user, if one is assigned.
     */
    public static function headOfSecretariatUser(): ?User
    {
        return self::activeUserWithRole(self::HEAD_OF_SECRETARIAT);
    }

    /**
     * Active accountant user, if one is assigned.
     */
    public static function accountantUser(): ?User
    {
        return self::activeUserWithRole(self::ACCOUNTANT);
    }

    public static function isSingletonRole(string $role): bool
    {
        return in_array($role, self::SINGLETON_ROLES, true);
    }

    /**
     * Whether another active user already holds a singleton role.
     */
    public static function singletonRoleTaken(string $role, ?int $exceptUserId = null): bool
    {
        if (! self::isSingletonRole($role)) {
            return false;
        }

        $query = User::role($role)->active();

        if ($exceptUserId !== null) {
            $query->where('id', '!=', $exceptUserId);
        }

        return $query->exists();
    }

    public static function activeUserWithRole(string $role): ?User
    {
        return User::role($role)
            ->active()
            ->orderBy('id')
            ->first();
    }
}
