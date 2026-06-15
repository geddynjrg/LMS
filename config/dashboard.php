<?php

return [
    /*
    |--------------------------------------------------------------------------
    | HQ Dashboard Version
    |--------------------------------------------------------------------------
    |
    | When true, HQ users see the SPSS-inspired analytics workspace (v2).
    | When false, the original dashboard is shown unchanged.
    |
    | Rollback: set HQ_DASHBOARD_V2=false and run `php artisan config:clear`.
    |
    */
    'hq_v2_enabled' => env('HQ_DASHBOARD_V2', false),

    /*
    |--------------------------------------------------------------------------
    | Constituency Dashboard Version
    |--------------------------------------------------------------------------
    |
    | When true, constituency officers see the SPSS-inspired analytics workspace.
    | When false, the original constituency dashboard is shown unchanged.
    |
    | Rollback: set CONST_DASHBOARD_V2=false and run `php artisan config:clear`.
    |
    */
    'const_v2_enabled' => env('CONST_DASHBOARD_V2', false),
];
