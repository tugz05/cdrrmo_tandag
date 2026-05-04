<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Staff online window
    |--------------------------------------------------------------------------
    |
    | Operators are considered "online" only if they sent a heartbeat within
    | this many seconds (dashboard open with Twilio ready).
    |
    */
    'staff_heartbeat_ttl' => (int) env('CALL_STAFF_HEARTBEAT_TTL', 90),

    /*
    |--------------------------------------------------------------------------
    | Availability response cache
    |--------------------------------------------------------------------------
    |
    | Short TTL to absorb bursts of mobile clients checking capacity without
    | hammering the database on every request.
    |
    */
    'availability_cache_seconds' => (int) env('CALL_AVAILABILITY_CACHE_SECONDS', 2),

    /*
    |--------------------------------------------------------------------------
    | Staff presence gate on Twilio Voice webhook
    |--------------------------------------------------------------------------
    |
    | When true, /twilio/voice returns busy audio + hangup if no operator has a
    | fresh heartbeat (same rule as set-location). When false, TwiML still dials
    | ADMIN_IDENTITY — for local Client-to-Client tests without the admin
    | dashboard heartbeat. The Twilio.Device for that identity must still be
    | registered or the Client leg will fail (often seen as 31005).
    |
    */
    'require_staff_presence_for_voice_twiml' => filter_var(
        env('CALL_REQUIRE_STAFF_PRESENCE_FOR_VOICE_TWIML', true),
        FILTER_VALIDATE_BOOL
    ),

    /*
    |--------------------------------------------------------------------------
    | Presence lookup fail-open
    |--------------------------------------------------------------------------
    |
    | When the staff presence check fails (DB outage, migration lock, etc.),
    | Twilio may receive a 500 from /twilio/voice and callers see a generic
    | 31005 "gateway hangup". When true, the webhook will proceed to dial
    | ADMIN_IDENTITY instead of failing the request.
    |
    | Recommended: true on local/dev; evaluate for production.
    |
    */
    'presence_fail_open' => filter_var(
        env('CALL_PRESENCE_FAIL_OPEN', env('APP_ENV') === 'local'),
        FILTER_VALIDATE_BOOL
    ),

];
