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

    // How many status polls to report "pending" before mock_outcome above
    // is returned — set > 0 to exercise CheckZynlePayPaymentJob's
    // retry/backoff loop realistically instead of resolving instantly.
    'mock_pending_ticks' => env('ZYNLEPAY_MOCK_PENDING_TICKS', 0),

    'base_url' => env('ZYNLEPAY_BASE_URL', 'https://payments.zynlepay.com/zynlepay/jsonapi/'),
    'status_url' => env('ZYNLEPAY_STATUS_URL', 'https://payments.zynlepay.com/zynlepay/paymentstatus'),

    'merchant_id' => env('ZYNLEPAY_MERCHANT_ID', 'CHANGE_ME_MERCHANT_ID'),
    'api_id' => env('ZYNLEPAY_API_ID', 'CHANGE_ME_API_ID'),
    'api_key' => env('ZYNLEPAY_API_KEY', 'CHANGE_ME_API_KEY'),
    'service_id' => env('ZYNLEPAY_SERVICE_ID', 'CHANGE_ME_SERVICE_ID'),
    'channel' => env('ZYNLEPAY_CHANNEL', 'momo'),

    'status_api_id' => env('ZYNLEPAY_STATUS_API_ID', 'CHANGE_ME_STATUS_API_ID'),
    'status_api_key' => env('ZYNLEPAY_STATUS_API_KEY', 'CHANGE_ME_STATUS_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Background status polling (App\Jobs\CheckZynlePayPaymentJob)
    |--------------------------------------------------------------------------
    | The USSD session ends as soon as the payment prompt is sent — it
    | cannot stay open waiting for the customer to approve it. Instead a
    | queued job polls the gateway with backoff and only then sends the
    | success/failure SMS. poll_max_attempts x poll_interval_seconds is the
    | total window the customer has to approve the prompt before it's
    | given up on as failed (defaults to 24 x 5s = 2 minutes). Requires a
    | queue worker running — see docs/README §"Running the queue worker".
    */
    'poll_first_delay_seconds' => env('ZYNLEPAY_POLL_FIRST_DELAY', 5),
    'poll_max_attempts' => env('ZYNLEPAY_POLL_MAX_ATTEMPTS', 24),
    'poll_interval_seconds' => env('ZYNLEPAY_POLL_INTERVAL', 5),
];
