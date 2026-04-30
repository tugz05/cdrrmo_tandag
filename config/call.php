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

];
