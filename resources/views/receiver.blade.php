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
        $idFromQuery = request()->query('identity');
        if (is_string($idFromQuery) && trim($idFromQuery) !== '') {
            $operatorIdentity = TwilioClientIdentity::sanitize($idFromQuery);
        } elseif (auth()->check() && auth()->user()->hasRole(['admin', 'super_admin'])) {
            $operatorIdentity = TwilioClientIdentity::sanitize((string) auth()->id());
        } else {
            $operatorIdentity = TwilioClientIdentity::sanitize((string) config('services.twilio.admin_identity'));
        }
        $receiverPageConfig = [
            'operatorIdentity' => $operatorIdentity,
            'voiceSdkEdge' => (string) config('services.twilio.voice_sdk_edge'),
        ];
    @endphp
    <script>
        window.__RECEIVER_CONFIG__ = @json($receiverPageConfig);
    </script>
</body>
</html>
