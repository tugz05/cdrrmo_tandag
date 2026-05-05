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
    | Note: GET /api/v1/call/availability uses an uncached snapshot so dial targets stay
    | aligned with GET /api/v1/voice/token and TwiML; this cache is for other readers
    | (e.g. /twilio/health) only.
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

    /*
    |--------------------------------------------------------------------------
    | Twilio webhook operator freshness (vs availability API)
    |--------------------------------------------------------------------------
    |
    | The /twilio/voice webhook uses a fresh DB read (not the short availability cache).
    | When > 1.0, heartbeat + voice_client_ready cutoffs are multiplied ONLY for TwiML
    | routing so a momentary gap between heartbeat and Twilio registration does not
    | drop operators that the REST availability API would still show as busy-preparing.
    | Keep close to 1.0 in production unless you see false "all operators busy" on voice.
    |
    */
    'twiml_operator_ttl_multiplier' => (float) env('CALL_TWIML_OPERATOR_TTL_MULTIPLIER', 1.25),

    'twiml_voice_ready_grace_seconds' => max(60, (int) env('CALL_TWIML_VOICE_READY_GRACE_SECONDS', 900)),

    'twiml_fallback_heartbeat_only_operators' => filter_var(
        env('CALL_TWIML_FALLBACK_HEARTBEAT_ONLY_OPERATORS', true),
        FILTER_VALIDATE_BOOL
    ),

    /*
    |--------------------------------------------------------------------------
    | Re-dial after first <Dial> ring cycle fails
    |--------------------------------------------------------------------------
    |
    | When true, Dial `action` callback may issue a second parallel <Dial> with a
    | freshly resolved operator list (e.g. operator registered during the first ring).
    | Max value is how many **extra** Dial attempts after the first failure (0 = none).
    |
    */
    'voice_dial_retry_on_no_answer' => filter_var(
        env('CALL_VOICE_DIAL_RETRY_ON_NO_ANSWER', true),
        FILTER_VALIDATE_BOOL
    ),

    'voice_dial_max_retries' => max(0, (int) env('CALL_VOICE_DIAL_MAX_RETRIES', 1)),

    /** Seconds to keep outbound dial session data for Twilio Dial `action` retries. */
    'voice_dial_session_ttl_seconds' => max(120, (int) env('CALL_VOICE_DIAL_SESSION_TTL_SECONDS', 420)),

];
