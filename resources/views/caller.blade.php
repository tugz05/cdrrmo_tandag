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
        When you place a call here, Twilio rings the <strong>admin app</strong> (<code>/admin/*</code>): the Inertia layout
        (<code>AuthenticatedLayout.vue</code>) registers the same <code>ADMIN_IDENTITY</code> client and shows an incoming-call modal,
        desktop notification (if allowed), tab title flash, and vibration where supported. Keep an operator dashboard tab open,
        click once so voice loads, and ensure heartbeat / availability rules pass so TwiML can dial that client.
    </small>

    @php
        use App\Support\TwilioClientIdentity;
        $qs = request()->query();
        $callerId = $qs['user_id'] ?? $qs['identity'] ?? '5';
        $callerPageConfig = [
            'callerUserId' => (string) $callerId,
            'adminIdentity' => TwilioClientIdentity::sanitize((string) config('services.twilio.admin_identity')),
            'voiceSdkEdge' => (string) config('services.twilio.voice_sdk_edge'),
        ];
    @endphp
    <script>
        window.__CALLER_CONFIG__ = @json($callerPageConfig);
    </script>
</body>
</html>
