<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Inbound gateway authentication
    |--------------------------------------------------------------------------
    | The api_id / api_key the USSD aggregator (gateway) must send in the
    | "auth" block of every request for it to be accepted. Mirrors the
    | pattern used in altusMiddleware's UssdController.
    */
    'api_id' => env('USSD_GATEWAY_API_ID', 'CHANGE_ME_GATEWAY_API_ID'),
    'api_key' => env('USSD_GATEWAY_API_KEY', 'CHANGE_ME_GATEWAY_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Shortcodes
    |--------------------------------------------------------------------------
    | Every dial code this deployment must respond to, exactly as the
    | provider sends it in MESSAGE on the very first request of a session.
    */
    'shortcodes' => array_filter(explode(',', env('USSD_SHORTCODES', '*262*22#,22'))),

    /*
    |--------------------------------------------------------------------------
    | Session / resume behaviour
    |--------------------------------------------------------------------------
    */
    'session_ttl_seconds' => env('USSD_SESSION_TTL', 300),
    'resume_ttl_seconds' => env('USSD_RESUME_TTL', 180),
];
