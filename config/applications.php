<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Automatic system verification after phase assignment
    |--------------------------------------------------------------------------
    |
    | When true, newly phase-assigned applications are auto-checked against
    | master group records:
    |   - No matches → marked as new group (Wezesha)
    |   - One strong match → auto-linked (Endeleza)
    |   - Multiple / ambiguous matches → left for manual review
    |
    | Rollback: AUTO_SYSTEM_VERIFICATION=false && php artisan config:clear
    |
    */
    'auto_system_verification' => env('AUTO_SYSTEM_VERIFICATION', false),

    /*
    |--------------------------------------------------------------------------
    | Application review UI (constituency application view)
    |--------------------------------------------------------------------------
    |
    | When true, constituency officers see the workflow stepper + action rail v2.
    | When false, the original application review layout is shown.
    |
    | Rollback: APPLICATION_REVIEW_V2=false && php artisan config:clear
    |
    */
    'review_v2_enabled' => env('APPLICATION_REVIEW_V2', false),
];
