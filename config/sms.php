<?php

return [
    'ebiscloud' => [
        'api_key' => env('EBISCLOUD_SMS_API_KEY'),
        'api_url' => env('EBISCLOUD_SMS_API_URL', 'https://sms.ebisclouderp.com/api/sms/sendsms'),
        'shortcode' => env('EBISCLOUD_SMS_SHORTCODE', 'Uwezo Fund'),
        'service_id' => (int) env('EBISCLOUD_SMS_SERVICE_ID', 0),
    ],
];
