<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Caller (test)</title>
    @vite(['resources/js/caller-page.js'])
    <style>
        body { font-family: system-ui, sans-serif; max-width: 32rem; margin: 2rem auto; padding: 0 1rem; }
        #status { margin: 1rem 0; padding: .75rem 1rem; background: #f4f4f5; border-radius: 8px; font-size: .9rem; white-space: pre-wrap; }
        #status.error { background: #fef2f2; color: #991b1b; }
        #status.ok { background: #ecfdf5; color: #065f46; }
        button { padding: .5rem 1rem; margin-right: .5rem; cursor: pointer; }
        button:disabled { opacity: .5; cursor: not-allowed; }
        small { color: #71717a; display: block; margin-top: .5rem; }
    </style>
</head>
<body>
    <h1>Caller (test)</h1>
    <p>
        VoIP test page. Uses the same <code>@twilio/voice-sdk</code> version as the admin app (see <code>package.json</code>), bundled with Vite — not the CDN global.
    </p>
    <p>
        Caller Twilio identity and <code>user_id</code> must match so the admin can load your profile
        (<code>?user_id=</code> in the URL, default 5).
    </p>

    <div id="status">Click or tap anywhere once (or use the button below) to load Twilio — required for browser audio policy.</div>

    <p>
        <button type="button" id="btnStart">Load voice / microphone</button>
        <button type="button" id="btnCall" disabled>Call admin</button>
        <button type="button" id="btnHangup" disabled>Hang up</button>
    </p>
    <small>
        When you place a call here, Twilio rings every voice-ready operator on the dashboard: each operator registers VoIP with their own user id,
        and your app uses the ring-group <code>To</code> from <code>/api/v1/call/availability</code> (see <code>TWILIO_DISPATCH_RING_GROUP</code>).
        Keep an operator tab open on <code>/admin/*</code>, click once so Twilio Voice loads, and ensure heartbeat rules pass.
    </small>

    @php
        use App\Support\TwilioClientIdentity;
        $qs = request()->query();
        $callerId = $qs['user_id'] ?? $qs['identity'] ?? '5';
        $callerPageConfig = [
            'callerUserId' => (string) $callerId,
            'adminIdentity' => TwilioClientIdentity::sanitize((string) config('services.twilio.admin_identity')),
            'dispatchRingIdentity' => TwilioClientIdentity::sanitize((string) config('call.dispatch_ring_group_client_name', 'dispatch')),
            'voiceSdkEdge' => (string) config('services.twilio.voice_sdk_edge'),
        ];
    @endphp
    <script>
        window.__CALLER_CONFIG__ = @json($callerPageConfig);
    </script>
</body>
</html>
