<!DOCTYPE html>
<html>
<head>
    <title>Caller</title>
    <script src="https://cdn.jsdelivr.net/npm/@twilio/voice-sdk@2.18.2/dist/twilio.min.js"></script>
</head>
<body>
    <h1>Caller</h1>
    <button onclick="call()">Call Admin</button>

    <script>
        let device;
        let deviceReady = false;

        fetch('/twilio/token?identity=5')
            .then(res => res.json())
            .then(async (data) => {
                device = new Twilio.Device(data.token, {
                    codecPreferences: ['opus', 'pcmu'],
                    logLevel: 'error',
                });

                device.on('error', error => console.error('Device Error:', error));
                try {
                    await device.register();
                    deviceReady = true;
                    console.log('Device registered');
                } catch (e) {
                    console.error('Device register failed:', e);
                }
            });


            async function call() {
                if (!device || !deviceReady) {
                    alert('Voice is not ready yet. Wait a moment and try again.');
                    return;
                }
                try {
                    const position = await new Promise((resolve, reject) => {
                        navigator.geolocation.getCurrentPosition(resolve, reject, {
                            enableHighAccuracy: true,
                            timeout: 10000,
                            maximumAge: 0
                        });
                    });

                    const callerData = {
                        userId: 1,
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                    };

                    const response = await storeLocation(callerData)

                    if (!response.success)
                        return console('error storing location');

                    await device.connect({
                        params: {
                            To: '{{ env("ADMIN_IDENTITY") }}',
                        },
                    });

                } catch (error) {
                    console.error("Error in call process:", error);

                    await device.connect({
                        params: {
                            To: '{{ env("ADMIN_IDENTITY") }}',
                            callerInfo: JSON.stringify({
                                userId: 1,
                                error: "Location not available"
                            })
                        }
                    });

                    alert("Call initiated but location couldn't be shared: " + error.message);
                }
            }

            async function storeLocation(data) {
                const response = await fetch('/api/v1/caller-details/set-location', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        user_id: data.userId,
                        latitude: data.latitude,
                        longitude: data.longitude,
                        accuracy: data.accuracy
                    })
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Failed to store location');
                }

                return response.json();
            }
    </script>
</body>
</html>
