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
    | fresh heartbeat (same rule as set-location). When false, TwiML still resolves
    | dial targets (ring group → voice-ready operator Client identities, or
    | ADMIN_IDENTITY fallback) — for local Client-to-Client tests without the admin
    | dashboard heartbeat. At least one Twilio Voice client must still be registered
    | or the Client leg will fail (often seen as 31005).
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
        env('CALL_PRESENCE_FAIL_OPEN', false),
        FILTER_VALIDATE_BOOL
    ),

    /*
    |--------------------------------------------------------------------------
    | Voice client ready gate (31603 prevention)
    |--------------------------------------------------------------------------
    |
    | When true, an operator counts as "available for voice" only if staff_presences.voice_client_ready_at
    | is fresh. The admin SPA sets this when Twilio.Device emits "registered" (see heartbeat JSON body).
    | Flutter dispatch must POST twilio_voice_ready: true when its Voice client is registered, or set false here.
    |
    */
    'require_voice_client_ready' => filter_var(
        env('CALL_REQUIRE_VOICE_CLIENT_READY', true),
        FILTER_VALIDATE_BOOL
    ),

    /*
    |--------------------------------------------------------------------------
    | Outbound VoIP ring group (Twilio <Client>)
    |--------------------------------------------------------------------------
    |
    | Callers (browser / mobile) pass this as device.connect({ params: { To } }).
    | Twilio posts it to /twilio/voice; the app expands it to every voice-ready
    | operator identity (each admin registers as their user id) so multiple
    | operators do not share one Client name (which caused 31603 / declines).
    |
    */
    'dispatch_ring_group_client_name' => (string) env('TWILIO_DISPATCH_RING_GROUP', 'dispatch'),

    /*
    |--------------------------------------------------------------------------
    | Max <Client> legs per <Dial> (TwiML size / Twilio limits)
    |--------------------------------------------------------------------------
    */
    'max_simultaneous_client_dials' => (int) env('CALL_MAX_SIMULTANEOUS_CLIENT_DIALS', 20),

];
