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
        // Phase Management Tags
        'phases' => [
            'constituency_summary'  => 'phases_summary_',
            'constituency_detailed' => 'phases_detailed_',
            'individual_summary'    => 'phase_summary_',
            'individual_detailed'   => 'phase_detailed_',
        ],
        'application' => [
            'per_application' => 'application_data',
            'constituency'    => 'application_constituency_',
        ],
        'groups' => [
            'constituency' => 'groups_constituency_',
            'group' => 'group_'
        ],
        // User
        'users' => [
            'per_role' => 'user_role_scope_',
        ],
    ],
];
