<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mock mode
    |--------------------------------------------------------------------------
    | When true, no real HTTP calls are made to ZynlePay. Payments are
    | simulated locally so the full USSD journey can be tested end-to-end
    | without live gateway credentials. Set ZYNLEPAY_MOCK=false in .env
    | once real credentials below are supplied.
    */
    'mock' => env('ZYNLEPAY_MOCK', true),

    // Simulated outcome when mock=true: success | failed | random
    'mock_outcome' => env('ZYNLEPAY_MOCK_OUTCOME', 'success'),

    'base_url' => env('ZYNLEPAY_BASE_URL', 'https://payments.zynlepay.com/zynlepay/jsonapi/'),
    'status_url' => env('ZYNLEPAY_STATUS_URL', 'https://payments.zynlepay.com/zynlepay/paymentstatus'),

    'merchant_id' => env('ZYNLEPAY_MERCHANT_ID', 'CHANGE_ME_MERCHANT_ID'),
    'api_id' => env('ZYNLEPAY_API_ID', 'CHANGE_ME_API_ID'),
    'api_key' => env('ZYNLEPAY_API_KEY', 'CHANGE_ME_API_KEY'),
    'service_id' => env('ZYNLEPAY_SERVICE_ID', 'CHANGE_ME_SERVICE_ID'),
    'channel' => env('ZYNLEPAY_CHANNEL', 'momo'),

    'status_api_id' => env('ZYNLEPAY_STATUS_API_ID', 'CHANGE_ME_STATUS_API_ID'),
    'status_api_key' => env('ZYNLEPAY_STATUS_API_KEY', 'CHANGE_ME_STATUS_API_KEY'),

    'max_status_retries' => env('ZYNLEPAY_MAX_RETRIES', 6),
    'status_retry_seconds' => env('ZYNLEPAY_RETRY_INTERVAL', 2),
];
