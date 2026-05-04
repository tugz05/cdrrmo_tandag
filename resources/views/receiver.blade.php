<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receiver</title>
    @vite(['resources/js/receiver-page.js'])
</head>
<body>
    <h1>Admin Receiver</h1>

    <div id="status">Waiting for call...</div>
    <button id="endCall" style="display:none">End Call</button>

    @php
        use App\Support\TwilioClientIdentity;
        $receiverPageConfig = [
            'adminIdentity' => TwilioClientIdentity::sanitize((string) config('services.twilio.admin_identity')),
            'voiceSdkEdge' => (string) config('services.twilio.voice_sdk_edge'),
        ];
    @endphp
    <script>
        window.__RECEIVER_CONFIG__ = @json($receiverPageConfig);
    </script>
</body>
</html>
