<?php

namespace App\Support;

use App\Models\LoanPhase;
use App\Models\User;

class PhaseCodeListAccess
{
    public static function phaseCanBeViewedBy(?User $user, LoanPhase $phase): bool
    {
        return HqAccess::phaseCanBeViewedBy($user, $phase)
            || SecretariatAccess::phaseCanBeViewedBy($user, $phase);
    }
}
