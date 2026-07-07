<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Extended edit/delete grace period
    |--------------------------------------------------------------------------
    |
    | Until this datetime (inclusive), constituency officers may edit phases and
    | delete applications/phases at any workflow stage. After this date the
    | system reverts to the stricter rules (unassigned-only application delete,
    | empty-phase-only phase delete, draft/completed-only edits).
    |
    | Rollback: LMS_GRACE_PERIOD_ENDS_AT= && php artisan config:clear
    |
    */
    'grace_period_ends_at' => env('LMS_GRACE_PERIOD_ENDS_AT', '2026-09-05 23:59:59'),
];
