<!DOCTYPE html>
<html>
<head>
    <title>Caller</title>
    <script src="https://media.twiliocdn.com/sdk/js/client/v1.13/twilio.min.js"></script>
</head>
<body>
    <h1>Caller</h1>
    <button onclick="call()">Call Admin</button>

    <script>
        let device;

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
            });


            async function call() {
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
                    device.connect({ 
                        To: '{{ env("ADMIN_IDENTITY") }}',
                        // callerInfo: JSON.stringify({
                        //     userId: callerData.userId,
                        //     locationId: storeResult.locationId // If your API returns an ID
                        // })
                    });

                } catch (error) {
                    console.error("Error in call process:", error);
                    
                    // Fallback - initiate call without location if storage fails
                    device.connect({ 
                        To: '{{ env("ADMIN_IDENTITY") }}',
                        callerInfo: JSON.stringify({
                            userId: 1,
                            error: "Location not available"
                        })
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
