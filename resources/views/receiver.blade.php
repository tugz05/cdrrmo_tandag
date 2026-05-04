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

    <script>
        window.__RECEIVER_CONFIG__ = @json([
            'adminIdentity' => (string) config('services.twilio.admin_identity'),
        ]);
    </script>
</body>
</html>
