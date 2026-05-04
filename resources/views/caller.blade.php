<!DOCTYPE html>
<html>
<head>
    <title>Caller</title>
<<<<<<< HEAD
    <script src="https://media.twiliocdn.com/sdk/js/client/v1.13/twilio.min.js"></script>
=======
    <script src="https://cdn.jsdelivr.net/npm/@twilio/voice-sdk@2.18.2/dist/twilio.min.js"></script>
>>>>>>> 328d54f (new release)
</head>
<body>
    <h1>Caller</h1>
    <button onclick="call()">Call Admin</button>

    <script>
        let device;
<<<<<<< HEAD

        fetch('/twilio/token?identity=5')
            .then(res => res.json())
            .then(data => {
                device = new Twilio.Device(data.token, {
                    codecPreferences: ['opus', 'pcmu'],
                    fakeLocalDTMF: true,
                    enableRingingState: true
                });

                device.on('ready', () => console.log('Device ready'));
                device.on('error', error => console.error('Device Error:', error));
=======
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
>>>>>>> 328d54f (new release)
            });


            async function call() {
<<<<<<< HEAD
=======
                if (!device || !deviceReady) {
                    alert('Voice is not ready yet. Wait a moment and try again.');
                    return;
                }
>>>>>>> 328d54f (new release)
                try {
                    // First get the user's location
                    const position = await new Promise((resolve, reject) => {
                        navigator.geolocation.getCurrentPosition(resolve, reject, {
                            enableHighAccuracy: true,
                            timeout: 10000, // 10 seconds timeout
                            maximumAge: 0 // Don't use cached position
                        });
                    });

                    // return console.log(position)

                    // Prepare caller data with location
                    const callerData = {
                        userId: 1, // Replace with actual user ID
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        // timestamp: new Date().toISOString()
                    };

                    const response = await storeLocation(callerData)

                    // const storeResult = await storeResponse.json();
                    // console.log("Location stored successfully:", storeResult);

                    if (!response.success)
                        return console('error storing location');

                    // Only initiate call after location is stored
<<<<<<< HEAD
                    device.connect({ 
                        To: '{{ env("ADMIN_IDENTITY") }}',
                        // callerInfo: JSON.stringify({
                        //     userId: callerData.userId,
                        //     locationId: storeResult.locationId // If your API returns an ID
                        // })
=======
                    await device.connect({
                        params: {
                            To: '{{ env("ADMIN_IDENTITY") }}',
                        },
>>>>>>> 328d54f (new release)
                    });

                } catch (error) {
                    console.error("Error in call process:", error);
                    
                    // Fallback - initiate call without location if storage fails
<<<<<<< HEAD
                    device.connect({ 
                        To: '{{ env("ADMIN_IDENTITY") }}',
                        callerInfo: JSON.stringify({
                            userId: 1,
                            error: "Location not available"
                        })
=======
                    await device.connect({
                        params: {
                            To: '{{ env("ADMIN_IDENTITY") }}',
                            callerInfo: JSON.stringify({
                                userId: 1,
                                error: "Location not available"
                            })
                        }
>>>>>>> 328d54f (new release)
                    });
                    
                    // Optionally show error to user
                    alert("Call initiated but location couldn't be shared: " + error.message);
                }
            }

            async function storeLocation(data) {
                const response = await fetch('/api/v1/caller-details/set-location', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        // 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
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

        // function call() {
        //     const callerData = {
        //         userId: 1,
        //         latitude: Number,
        //         longitude: Number
        //     };
            
        //     // store location here. if response is ok, then proceed

            
        //     console.log("Sending caller data:", callerData); // Debug log
            
        //     device.connect({ 
        //         To: '{{ env("ADMIN_IDENTITY") }}',
        //         callerInfo: JSON.stringify(callerData)
        //     });
        // }
    </script>
</body>
</html>
