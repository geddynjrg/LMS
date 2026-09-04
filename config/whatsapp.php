<?php

return [
    'enabled' => (bool) env('WHATSAPP_ENABLED', false),
    'graph_api_version' => env('WHATSAPP_GRAPH_API_VERSION', 'v19.0'),
    'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
    'verify_token' => env('WHATSAPP_VERIFY_TOKEN'),
    'app_secret' => env('WHATSAPP_APP_SECRET'),
    'support_number' => env('WHATSAPP_SUPPORT_NUMBER'),
];
