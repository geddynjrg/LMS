<?php

return [
    'base_url' => env('DRUPAL_API_URL', 'https://loans-staging-admin.uwezo.go.ke'),
    'api_key' => env('DRUPAL_API_KEY'),
    'peer_urls' => array_values(array_filter(array_map(
        static fn ($url) => rtrim(trim($url), '/'),
        explode(',', (string) env('DRUPAL_PEER_API_URLS', 'http://prod-admin'))
    ))),
];
