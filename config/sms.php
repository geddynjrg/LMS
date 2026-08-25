<?php

return [
    'ebiscloud' => [
        'api_key' => env('EBISCLOUD_SMS_API_KEY'),
        'api_url' => env('EBISCLOUD_SMS_API_URL', 'https://sms.ebisclouderp.com/api/sms/sendsms'),
        'shortcode' => env('EBISCLOUD_SMS_SHORTCODE', 'Uwezo Fund'),
        'service_id' => (int) env('EBISCLOUD_SMS_SERVICE_ID', 0),
    ],
    'send_attempts' => (int) env('SMS_SEND_ATTEMPTS', 3),
    'retry_pause_microseconds' => (int) env('SMS_RETRY_PAUSE_MICROSECONDS', 250000),
    'bulk_pause_microseconds' => (int) env('SMS_BULK_PAUSE_MICROSECONDS', 300000),
];
