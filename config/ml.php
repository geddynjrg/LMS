<?php

return [
    'enabled' => (bool) env('ML_SCORE_ENABLED', true),
    // Reachable from uwezo-lms-app via docker bridge / ExtraHosts host-gateway
    'url' => rtrim((string) env('ML_SCORE_URL', 'http://uwezo.ddev.site:8095'), '/'),
    'timeout' => (int) env('ML_SCORE_TIMEOUT', 8),
    'cache_seconds' => (int) env('ML_SCORE_CACHE_SECONDS', 300),
];
