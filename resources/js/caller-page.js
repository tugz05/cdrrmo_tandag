/**
 * Caller test page — uses the same @twilio/voice-sdk as the admin SPA (see package.json).
 * Loaded only from resources/views/caller.blade.php via @vite.
 */
import { Device } from '@twilio/voice-sdk';

const cfg = window.__CALLER_CONFIG__ || {};
const callerUserId = String(cfg.callerUserId ?? '5');
const adminIdentity = cfg.adminIdentity != null ? String(cfg.adminIdentity) : '';

let device = null;
let deviceReady = false;
let activeCall = null;
let voiceBootstrapStarted = false;

const statusEl = document.getElementById('status');
const btnStart = document.getElementById('btnStart');
const btnHangup = document.getElementById('btnHangup');
const btnCall = document.getElementById('btnCall');

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
    const code = typeof e.code !== 'undefined' ? e.code : undefined;
    const msg = typeof e.message === 'string' ? e.message : '';
    const expl = typeof e.explanation === 'string' ? e.explanation : '';
    const lines = [];
    if (code != null && code !== '') {
        lines.push(`[${code}]`);
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
        const ts = e.toString();
        if (ts && ts !== '[object Object]') {
            return ts;
        }
    }
    try {
        const s = JSON.stringify(e);
        if (s !== '{}') {
            return s;
        }
    } catch {
        /* ignore */
    }
    if (e.name) {
        return String(e.name);
    }
    return 'Unknown error — expand the red error object in DevTools → Console';
}

function signalingHint(code) {
    const n = typeof code === 'number' ? code : parseInt(code, 10);
    if (n === 31000 || n === 53000) {
        return (
            '\n\n53000/31000 checklist:\n' +
            '• TWILIO_API_SECRET must match the SK key exactly (create a new API Key if unsure).\n' +
            '• API Key must be Standard or Restricted with Voice/Client permissions — IP-locked keys can block browsers.\n' +
            '• TWIML_APP_SID must be from the same Twilio account as TWILIO_ACCOUNT_SID.\n' +
            '• Optional: set TWILIO_VOICE_HOME_REGION=us1 (or ie1, au1) if your account is regional.\n' +
            '• Run php artisan config:clear. Local only: GET /twilio/token-debug?identity=5'
        );
    }
    if (n === 31005) {
        return (
            '\n\n31005 (gateway HANGUP):\n' +
            '• Voice webhook: Twilio Console TwiML App “Voice URL” must match GET or POST to this app’s /twilio/voice and be reachable (HTTPS + public URL).\n' +
            '• Admin must register the same Client identity as ADMIN_IDENTITY (dashboard open, click once).\n' +
            '• See laravel.log for “Twilio handleVoice request”. If absent, Twilio never hit your server.\n' +
            '• https://www.twilio.com/docs/voice/sdks/javascript#twiml-app'
        );
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

window.addEventListener(
    'pointerdown',
    function once() {
        window.removeEventListener('pointerdown', once, true);
        startVoice();
    },
    true,
);

function initDevice() {
    if (!adminIdentity) {
        setStatus('Server is not configured: set ADMIN_IDENTITY in .env', 'error');
        voiceBootstrapStarted = false;
        btnStart.disabled = false;
        return;
    }

    const tokenUrl = `/twilio/token?identity=${encodeURIComponent(callerUserId)}`;

    fetch(tokenUrl)
        .then((res) =>
            res.text().then((text) => {
                let data;
                try {
                    data = text ? JSON.parse(text) : null;
                } catch {
                    throw new Error(
                        `Token URL did not return JSON (HTTP ${res.status}). Body starts with: ${String(text).slice(0, 120)}`,
                    );
                }
                if (!res.ok) {
                    throw new Error(
                        data && data.message
                            ? data.message
                            : `Token HTTP ${res.status}${data && data.exception ? ' — see server logs' : ''}`,
                    );
                }
                if (!data || typeof data.token !== 'string' || !data.token.length) {
                    throw new Error('Token response missing "token". Check TWILIO_* and TWIML_APP_SID in .env');
                }
                return data;
            }),
        )
        .then(async (data) => {
            if (device) {
                try {
                    device.destroy();
                } catch {
                    /* ignore */
                }
                device = null;
                deviceReady = false;
            }

            device = new Device(data.token, {
                codecPreferences: ['opus', 'pcmu'],
                logLevel: 'warn',
                closeProtection: true,
            });

            device.on('error', (err) => {
                console.error('Twilio Device error:', err);
                const code = err && (err.code != null ? err.code : err.twilioError && err.twilioError.code);
                const txt = `Twilio: ${formatErr(err)}${signalingHint(code)}`;
                setStatus(txt, 'error');
                voiceBootstrapStarted = false;
                btnStart.disabled = false;
                btnCall.disabled = true;
                deviceReady = false;
            });

            device.on('registered', () => {
                deviceReady = true;
                setStatus(`Ready. Twilio identity: ${callerUserId} — you can call the admin.`, 'ok');
                btnCall.disabled = false;
            });

            device.register().catch((rejectVal) => {
                if (rejectVal !== undefined && rejectVal !== null) {
                    console.error('device.register() rejected:', rejectVal);
                    setStatus(formatErr(rejectVal) + signalingHint(rejectVal && rejectVal.code), 'error');
                    voiceBootstrapStarted = false;
                    btnStart.disabled = false;
                }
            });
        })
        .catch((err) => {
            console.error(err);
            setStatus(`Token request failed: ${formatErr(err)}`, 'error');
            voiceBootstrapStarted = false;
            btnStart.disabled = false;
        });
}

async function storeLocation(callerData) {
    const res = await fetch('/api/v1/caller-details/set-location', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
        },
        body: JSON.stringify({
            user_id: parseInt(callerData.userId, 10),
            latitude: callerData.latitude,
            longitude: callerData.longitude,
            accuracy: callerData.accuracy,
        }),
    });
    const body = await res.json().catch(() => ({}));

    if (res.status === 503) {
        return { ok: false, busy: true, message: body.message || 'All operators are busy.' };
    }
    if (!res.ok) {
        return { ok: false, busy: false, message: body.message || `HTTP ${res.status}` };
    }
    return { ok: true, reportId: body.report_id, body };
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
        const position = await new Promise((resolve, reject) => {
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
                callerInfo,
            },
        });

        activeCall.on('disconnect', onCallEnded);
        activeCall.on('cancel', onCallEnded);
        activeCall.on('error', (err) => {
            console.error('Call error:', err);
            const c = err && typeof err.code !== 'undefined' ? err.code : null;
            setStatus(`Call error: ${formatErr(err)}${signalingHint(c)}`, 'error');
        });

        setStatus('Call in progress — speak when the admin answers.');
        btnHangup.disabled = false;
    } catch (error) {
        console.error('Call flow error:', error);
        setStatus(`Falling back to call without location: ${formatErr(error)}`, 'error');

        try {
            const callerInfo = JSON.stringify({
                userId: parseInt(callerUserId, 10),
                error: 'Location not available',
            });
            activeCall = await device.connect({
                params: {
                    To: adminIdentity,
                    callerInfo,
                },
            });
            activeCall.on('disconnect', onCallEnded);
            activeCall.on('cancel', onCallEnded);
            setStatus('Call in progress (no location).');
            btnHangup.disabled = false;
            alert(`Call started but location could not be shared: ${formatErr(error)}`);
        } catch (e2) {
            setStatus(`Could not connect: ${formatErr(e2)}`, 'error');
            alert(`Could not start call: ${formatErr(e2)}`);
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

btnStart?.addEventListener('click', () => startVoice());
btnCall?.addEventListener('click', () => callAdmin());
btnHangup?.addEventListener('click', () => hangUp());
