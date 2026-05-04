<!DOCTYPE html>
<html>
<head>
    <title>Receiver</title>
    <script src="https://cdn.jsdelivr.net/npm/@twilio/voice-sdk@2.18.2/dist/twilio.min.js"></script>
</head>
<body>
    <h1>Admin Receiver</h1>

    <div id="status">Waiting for call...</div>
    <button id="endCall" style="display:none;">End Call</button>

    <script>
        let device;
        let activeCall;

        fetch('/twilio/token?identity={{ env("ADMIN_IDENTITY") }}')
            .then(res => res.json())
            .then(async (data) => {
                device = new Twilio.Device(data.token, {
                    codecPreferences: ['opus', 'pcmu'],
                    logLevel: 'error',
                });

                device.on('registered', () => {
                    console.log('Receiver registered');
                    document.getElementById('status').textContent = 'Ready to receive calls';
                });

                device.on('incoming', call => {
                    console.log('Incoming call...');

                    const confirmAccept = confirm('Incoming call. Do you want to accept it?');

                    if (confirmAccept) {
                        call.accept();
                        activeCall = call;
                        document.getElementById('status').textContent = 'Call in progress...';
                        document.getElementById('endCall').style.display = 'inline';
                    } else {
                        call.reject();
                    }
                });

                device.on('error', error => console.error('Receiver error:', error));

                try {
                    await device.register();
                } catch (e) {
                    console.error('Receiver register failed:', e);
                }
            });

        document.getElementById('endCall').addEventListener('click', () => {
            if (activeCall) {
                activeCall.disconnect();
                activeCall = null;
                document.getElementById('status').textContent = 'Call ended';
                document.getElementById('endCall').style.display = 'none';
            }
        });
    </script>
</body>
</html>
