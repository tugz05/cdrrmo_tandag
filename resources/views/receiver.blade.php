<!DOCTYPE html>
<html>
<head>
    <title>Receiver</title>
<<<<<<< HEAD
    <script src="https://media.twiliocdn.com/sdk/js/client/v1.13/twilio.min.js"></script>
=======
    <script src="https://cdn.jsdelivr.net/npm/@twilio/voice-sdk@2.18.2/dist/twilio.min.js"></script>
>>>>>>> 328d54f (new release)
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
<<<<<<< HEAD
            .then(data => {
                device = new Twilio.Device(data.token, {
                    codecPreferences: ['opus', 'pcmu'],
                    fakeLocalDTMF: true,
                    enableRingingState: true
                });

                device.on('ready', () => {
                    console.log('Receiver ready');
=======
            .then(async (data) => {
                device = new Twilio.Device(data.token, {
                    codecPreferences: ['opus', 'pcmu'],
                    logLevel: 'error',
                });

                device.on('registered', () => {
                    console.log('Receiver registered');
>>>>>>> 328d54f (new release)
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
<<<<<<< HEAD
=======

                try {
                    await device.register();
                } catch (e) {
                    console.error('Receiver register failed:', e);
                }
>>>>>>> 328d54f (new release)
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



{{-- <!DOCTYPE html>
<html>
<head>
    <title>Receiver</title>
<<<<<<< HEAD
    <script src="https://media.twiliocdn.com/sdk/js/client/v1.13/twilio.min.js"></script>
=======
    <script src="https://cdn.jsdelivr.net/npm/@twilio/voice-sdk@2.18.2/dist/twilio.min.js"></script>
>>>>>>> 328d54f (new release)
</head>
<body>
    <h1>Admin Receiver</h1>

    <script>
        let device;
        fetch('/twilio/token?identity={{ env("ADMIN_IDENTITY") }}')
            .then(res => res.json())
            .then(data => {
                device = new Twilio.Device(data.token, {
                    codecPreferences: ['opus', 'pcmu'],
                    fakeLocalDTMF: true,
                    enableRingingState: true
                });

                device.on('ready', () => console.log('Receiver ready'));
                
                device.on('incoming', call => {
                    console.log('Incoming call...');
                    call.accept();
                });

                device.on('error', error => console.error('Receiver error:', error));
            });
    </script>
</body>
</html> --}}
