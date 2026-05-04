<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Caller (test)</title>
    <script src="https://cdn.jsdelivr.net/npm/@twilio/voice-sdk@2.18.2/dist/twilio.min.js"></script>
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
        VoIP test page. Caller Twilio identity and <code>user_id</code> must match so the admin can load your profile
        (<code>?user_id=</code> in the URL, default 5).
    </p>

    <div id="status">Click or tap anywhere once (or use the button below) to load Twilio — required for browser audio policy.</div>

    <p>
        <button type="button" id="btnStart" onclick="startVoice()">Load voice / microphone</button>
        <button type="button" id="btnCall" onclick="callAdmin()" disabled>Call admin</button>
        <button type="button" id="btnHangup" onclick="hangUp()" disabled>Hang up</button>
    </p>
    <small>Open the admin dashboard in another tab; an operator must be online (heartbeat) for the call to connect.</small>

    <script>
        const params = new URLSearchParams(window.location.search);
        const callerUserId = String(params.get('user_id') || params.get('identity') || '5');
        const adminIdentity = @json(config('services.twilio.admin_identity'));

        let device = null;
        let deviceReady = false;
        let activeCall = null;
        let voiceBootstrapStarted = false;

        const statusEl = document.getElementById('status');
        const btnStart = document.getElementById('btnStart');
        const btnCall = document.getElementById('btnCall');
        const btnHangup = document.getElementById('btnHangup');

        /** Safe string for any thrown value / Twilio SDK error (ConnectionError hides fields from JSON.stringify) */
        function formatErr(e) {
            if (e === undefined || e === null || e === '') {
                return 'Signaling failed (31000): invalid JWT, wrong TWILIO_API_SECRET, or API Key not from same account as TWILIO_ACCOUNT_SID — verify .env and php artisan config:clear.';
            }
            if (typeof e === 'string') {
                return e;
            }
            if (typeof e !== 'object') {
                return String(e);
            }
            if (typeof e.cause !== 'undefined' && e.cause) {
                return formatErr(e.cause);
            }
            var code = typeof e.code !== 'undefined' ? e.code : undefined;
            var msg = typeof e.message === 'string' ? e.message : '';
            var expl = typeof e.explanation === 'string' ? e.explanation : '';
            var lines = [];
            if (code != null && code !== '') {
                lines.push('[' + code + ']');
            }
            if (msg) {
                lines.push(msg);
            }
            if (expl && expl !== msg) {
                lines.push(expl);
            }
            if (lines.length) {
                return lines.join(' ');
            }
            if (typeof e.twilioError !== 'undefined' && e.twilioError) {
                return formatErr(e.twilioError);
            }
            if (typeof e.originalError !== 'undefined' && e.originalError) {
                return formatErr(e.originalError);
            }
            if (typeof e.toString === 'function') {
                var ts = e.toString();
                if (ts && ts !== '[object Object]') {
                    return ts;
                }
            }
            try {
                var s = JSON.stringify(e);
                if (s !== '{}') {
                    return s;
                }
            } catch (_) { /* ignore */ }
            if (e.name) {
                return String(e.name);
            }
            return 'Unknown error — expand the red error object in DevTools → Console';
        }

        function signalingHint(code) {
            var n = typeof code === 'number' ? code : parseInt(code, 10);
            if (n === 31000 || n === 53000) {
                return '\n\nFix: In Twilio Console create an API Key (SK…), set TWILIO_API_KEY + TWILIO_API_SECRET + TWILIO_ACCOUNT_SID + TWIML_APP_SID (AP…) in .env, run php artisan config:clear. API Key must belong to the same account as the Account SID.';
            }
            return '';
        }

        function setStatus(msg, kind) {
            statusEl.textContent = msg;
            statusEl.className = kind || '';
        }

        function startVoice() {
            if (voiceBootstrapStarted) {
                return;
            }
            voiceBootstrapStarted = true;
            btnStart.disabled = true;
            setStatus('Loading token…');
            initDevice();
        }

        // Same as admin dashboard: first user gesture unlocks audio + token load
        window.addEventListener('pointerdown', function once() {
            window.removeEventListener('pointerdown', once, true);
            startVoice();
        }, true);

        function initDevice() {
            if (!adminIdentity) {
                setStatus('Server is not configured: set ADMIN_IDENTITY in .env', 'error');
                voiceBootstrapStarted = false;
                btnStart.disabled = false;
                return;
            }

            const tokenUrl = '/twilio/token?identity=' + encodeURIComponent(callerUserId);

            fetch(tokenUrl)
                .then(function (res) {
                    return res.text().then(function (text) {
                        let data;
                        try {
                            data = text ? JSON.parse(text) : null;
                        } catch (parseErr) {
                            throw new Error(
                                'Token URL did not return JSON (HTTP ' + res.status + '). Body starts with: ' +
                                String(text).slice(0, 120)
                            );
                        }
                        if (!res.ok) {
                            throw new Error(
                                data && data.message
                                    ? data.message
                                    : 'Token HTTP ' + res.status + (data && data.exception ? ' — see server logs' : '')
                            );
                        }
                        if (!data || typeof data.token !== 'string' || !data.token.length) {
                            throw new Error('Token response missing "token". Check TWILIO_* and TWIML_APP_SID in .env');
                        }
                        return data;
                    });
                })
                .then(async function (data) {
                    if (device) {
                        try {
                            device.destroy();
                        } catch (x) { /* ignore */ }
                        device = null;
                        deviceReady = false;
                    }

                    device = new Twilio.Device(data.token, {
                        codecPreferences: ['opus', 'pcmu'],
                        logLevel: 'warn',
                        closeProtection: true,
                    });

                    /**
                     * Twilio emits ConnectionError 31000 on "error" before/affecting register();
                     * the register() promise sometimes rejects with undefined — do not duplicate UI from catch.
                     */
                    device.on('error', function (err) {
                        console.error('Twilio Device error:', err);
                        var code = err && (err.code != null ? err.code : (err.twilioError && err.twilioError.code));
                        var txt = 'Twilio: ' + formatErr(err) + signalingHint(code);
                        setStatus(txt, 'error');
                        voiceBootstrapStarted = false;
                        btnStart.disabled = false;
                        btnCall.disabled = true;
                        deviceReady = false;
                    });

                    device.on('registered', function () {
                        deviceReady = true;
                        setStatus('Ready. Twilio identity: ' + callerUserId + ' — you can call the admin.', 'ok');
                        btnCall.disabled = false;
                    });

                    device.register().catch(function (rejectVal) {
                        if (rejectVal !== undefined && rejectVal !== null) {
                            console.error('device.register() rejected:', rejectVal);
                            setStatus(formatErr(rejectVal) + signalingHint(rejectVal && rejectVal.code), 'error');
                            voiceBootstrapStarted = false;
                            btnStart.disabled = false;
                        }
                    });
                })
                .catch(function (err) {
                    console.error(err);
                    setStatus('Token request failed: ' + formatErr(err), 'error');
                    voiceBootstrapStarted = false;
                    btnStart.disabled = false;
                });
        }

        async function storeLocation(callerData) {
            const res = await fetch('/api/v1/caller-details/set-location', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    user_id: parseInt(callerData.userId, 10),
                    latitude: callerData.latitude,
                    longitude: callerData.longitude,
                    accuracy: callerData.accuracy,
                }),
            });
            const body = await res.json().catch(function () { return {}; });

            if (res.status === 503) {
                return { ok: false, busy: true, message: body.message || 'All operators are busy.' };
            }
            if (!res.ok) {
                return { ok: false, busy: false, message: body.message || ('HTTP ' + res.status) };
            }
            return { ok: true, reportId: body.report_id, body: body };
        }

        async function callAdmin() {
            if (!device || !deviceReady) {
                alert('Voice is not ready yet. Use “Load voice” or click the page once, then wait until status says Ready.');
                return;
            }
            if (!adminIdentity) {
                alert('ADMIN_IDENTITY is not configured on the server.');
                return;
            }

            btnCall.disabled = true;
            setStatus('Getting location & creating call report…');

            try {
                const position = await new Promise(function (resolve, reject) {
                    navigator.geolocation.getCurrentPosition(resolve, reject, {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 0,
                    });
                });

                const callerData = {
                    userId: parseInt(callerUserId, 10),
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                };

                const stored = await storeLocation(callerData);
                if (!stored.ok) {
                    setStatus(stored.message, 'error');
                    alert(stored.message);
                    btnCall.disabled = false;
                    return;
                }

                const callerInfo = JSON.stringify({
                    userId: callerData.userId,
                    reportId: stored.reportId,
                });

                setStatus('Connecting to admin…');
                activeCall = await device.connect({
                    params: {
                        To: adminIdentity,
                        callerInfo: callerInfo,
                    },
                });

                activeCall.on('disconnect', onCallEnded);
                activeCall.on('cancel', onCallEnded);
                activeCall.on('error', function (err) {
                    console.error('Call error:', err);
                    setStatus('Call error: ' + formatErr(err), 'error');
                });

                setStatus('Call in progress — speak when the admin answers.');
                btnHangup.disabled = false;
            } catch (error) {
                console.error('Call flow error:', error);
                setStatus('Falling back to call without location: ' + formatErr(error), 'error');

                try {
                    const callerInfo = JSON.stringify({
                        userId: parseInt(callerUserId, 10),
                        error: 'Location not available',
                    });
                    activeCall = await device.connect({
                        params: {
                            To: adminIdentity,
                            callerInfo: callerInfo,
                        },
                    });
                    activeCall.on('disconnect', onCallEnded);
                    activeCall.on('cancel', onCallEnded);
                    setStatus('Call in progress (no location).');
                    btnHangup.disabled = false;
                    alert("Call started but location could not be shared: " + formatErr(error));
                } catch (e2) {
                    setStatus('Could not connect: ' + formatErr(e2), 'error');
                    alert('Could not start call: ' + formatErr(e2));
                }
            } finally {
                if (!activeCall) {
                    btnCall.disabled = false;
                }
            }
        }

        function onCallEnded() {
            activeCall = null;
            btnHangup.disabled = true;
            btnCall.disabled = false;
            setStatus('Call ended. Ready to call again.', 'ok');
        }

        function hangUp() {
            if (activeCall && typeof activeCall.disconnect === 'function') {
                activeCall.disconnect();
            } else if (device && typeof device.disconnectAll === 'function') {
                device.disconnectAll();
            }
            onCallEnded();
            setStatus('Hung up.', 'ok');
        }
    </script>
</body>
</html>
