<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache connection that gets used while
    | using this caching library. This connection is used when another is
    | not explicitly specified when executing a given caching function.
    |
    */

    'default' => env('CACHE_DRIVER', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache "stores" for your application as
    | well as their drivers. You may even define multiple stores for the
    | same cache driver to group types of items stored in your caches.
    |
    | Supported drivers: "apc", "array", "database", "file",
    |         "memcached", "redis", "dynamodb", "octane", "null"
    |
    */

    'stores' => [

        'apc' => [
            'driver' => 'apc',
        ],

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'cache',
            'connection' => null,
            'lock_connection' => null,
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'lock_connection' => 'default',
        ],

        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
        ],

        'octane' => [
            'driver' => 'octane',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing the APC, database, memcached, Redis, or DynamoDB cache
    | stores there might be other applications using the same cache. For
    | that reason, you may prefix every cache key to avoid collisions.
    |
    */

    'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_cache_'),
    'members' => [
        'ttl' => env('MEMBERS_CACHE_TTL', 3600), // Default 1 hour
        'prefix' => env('MEMBERS_CACHE_PREFIX', 'constituency_all_members'),
    ],
    'multi_members' => [
        'ttl' => env('MULTI_MEMBERS_CACHE_TTL', 1800), // Default 30 minutes
        'prefix' => env('MULTI_MEMBERS_CACHE_PREFIX', 'constituency_multi_group_members'),
    ],
    'summary' => [
        'ttl' => env('SUMMARY_CACHE_TTL', 7200), // Default 2 hours
        'prefix' => env('SUMMARY_CACHE_PREFIX', 'constituency_summary'),
    ],
    'groups' => [ // NEW: Groups configuration
        'ttl' => env('GROUPS_CACHE_TTL', 3600), // Default 1 hour
        'prefix' => env('GROUPS_CACHE_PREFIX', 'constituency_groups'),
    ],
    'export' => [
        'ttl' => env('EXPORT_CACHE_TTL', 3600), // Default 1 hour
        'prefix' => env('EXPORT_CACHE_PREFIX', 'constituency_export'),
    ],
    'reports' => [
        'ttl' => env('REPORTS_CACHE_TTL', 1800), // Default 30 minutes
        'prefix' => env('REPORTS_CACHE_PREFIX', 'dynamic_reports'),
    ],

    'tags' => [
        'dashboard' => [
            'constituency_stats'  => 'const_',
            'national_stats'      => 'hq_',
        ],
        'phases' => [
            'constituency_summary'  => 'const_phases_summary_',
            'constituency_detailed' => 'phases_detailed_',
            'individual_summary'    => 'phase_summary_',
            'individual_detailed'   => 'phase_detailed_',
            'national'              => 'hq_',
        ],
        'applications' => [
            'constituency_apps'    => 'cons_apps_',
            'national_apps'        => 'hq_apps_',
            'constituency_stats'   => 'cons_app_stats_',
            'national_stats'       => 'hq_app_stats_',
        ],

        'groups' => [
            'constituency_stats' => 'groups_constituency_',
            'groups' => 'groups_',
            'group' => 'group_',
            'national'        => 'hq_',
        ],

        'groups_loans' => [
            'constituency_loans' => 'group_loans_constituency_',
            'group_loan' => 'group_loan_',
            'national'        => 'hq_',
        ],

        'users' => [
            'per_role' => 'user_role_scope_',
            'national'        => 'hq_',
        ],

        'location' => [
            'counties' => 'location_counties',
            'constituencies' => 'location_constituencies',
            'wards' => 'location_wards',
        ],
        'receipts' => [
            'constituency_stats' => 'const_receipts_stats_',
            'national_stats'     => 'hq_receipts_stats_',
            'constituency_trends'=> 'const_receipts_trends_',
            'national_trends'    => 'hq_receipts_trends_',
            'constituency_groups'=> 'const_receipt_groups_',
            'daily_stats'        => 'receipts_daily_',
            'weekly_stats'       => 'receipts_weekly_',
            'monthly_stats'      => 'receipts_monthly_',
            'top_groups'         => 'receipts_top_groups_',
            'payment_methods'    => 'receipts_payment_methods_',
            'recent'             => 'receipts_recent_',
            'date_range'         => 'receipts_date_range_',
        ],
    ],
];
