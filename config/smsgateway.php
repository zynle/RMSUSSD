<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mock mode
    |--------------------------------------------------------------------------
    | When true, SMS notifications are logged to the sms_logs table and the
    | application log instead of being sent to the live gateway. Set
    | SMS_MOCK=false once real credentials below are supplied.
    */
    'mock' => env('SMS_MOCK', true),

    'base_url' => env('SMS_BASE_URL', 'https://usersms.zynle.com/api/v2/SendSMS'),

    'sender_id' => env('SMS_SENDER_ID', 'CHANGE_ME_SENDER'),
    'api_key' => env('SMS_API_KEY', 'CHANGE_ME_SMS_API_KEY'),
    'client_id' => env('SMS_CLIENT_ID', 'CHANGE_ME_CLIENT_ID'),
];
