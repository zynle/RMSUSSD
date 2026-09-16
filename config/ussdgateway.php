<?php

$shortcodes = array_filter(explode(',', env('USSD_SHORTCODES', '885*228')));

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
    | Comma-separated in USSD_SHORTCODES if more than one is needed.
    */
    'shortcodes' => $shortcodes,

    /*
    |--------------------------------------------------------------------------
    | Display shortcode
    |--------------------------------------------------------------------------
    | The dial code shown back to the user in USSD prompts (e.g. "dial this
    | again to continue"). Defaults to the first entry in USSD_SHORTCODES, but
    | can be overridden separately if the user-facing code differs. Used
    | verbatim wherever the app needs to tell the user what to dial — never
    | hard-code a shortcode in a State, reference this instead.
    */
    'display_shortcode' => env('USSD_DISPLAY_SHORTCODE', $shortcodes[0] ?? '885*228'),

    /*
    |--------------------------------------------------------------------------
    | Session / resume behaviour
    |--------------------------------------------------------------------------
    */
    'session_ttl_seconds' => env('USSD_SESSION_TTL', 300),
    'resume_ttl_seconds' => env('USSD_RESUME_TTL', 180),
];
