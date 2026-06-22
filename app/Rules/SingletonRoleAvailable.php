<?php

namespace App\Rules;

use App\Support\SystemRoles;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SingletonRoleAvailable implements ValidationRule
{
    public function __construct(
        protected ?int $exceptUserId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! SystemRoles::isSingletonRole($value)) {
            return;
        }

        if (SystemRoles::singletonRoleTaken($value, $this->exceptUserId)) {
            $label = ucwords(str_replace('_', ' ', $value));
            $fail("An active {$label} user already exists. Only one {$label} account is allowed.");
        }
    }
}
