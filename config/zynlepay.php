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

    // Set true to simulate the gateway rejecting the push outright (e.g.
    // gateway down, bad request) — exercises SendPaymentPushJob's own
    // retry/failure path, distinct from a customer never approving.
    'mock_reject_push' => env('ZYNLEPAY_MOCK_REJECT_PUSH', false),

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
    | cannot stay open waiting for the customer to approve it, and a real
    | mobile money approval can take anywhere from a few seconds to
    | several minutes. So instead of a fixed interval, polling follows a
    | schedule of gaps (in seconds) between checks: quick at first, then
    | spreading out — e.g. 10s, 10s, 15s, 20s, 25s, 30s, 40s, 50s, 60s,
    | 60s (~5.3 minutes of increasingly-spaced checks), then every 120s
    | (the last array value repeats for every attempt beyond the array's
    | length) until poll_total_window_minutes is reached, at which point
    | it's given up on as a timeout/failure. An early success or failure
    | from the gateway resolves it immediately regardless of where in the
    | schedule it happens. Requires a queue worker running — see
    | docs/README §"Running the queue worker".
    */
    'poll_schedule_seconds' => array_map(
        'intval',
        array_filter(explode(',', env('ZYNLEPAY_POLL_SCHEDULE', '10,10,15,20,25,30,40,50,60,60,120')))
    ),
    'poll_total_window_minutes' => env('ZYNLEPAY_POLL_TOTAL_WINDOW_MINUTES', 10),
];
