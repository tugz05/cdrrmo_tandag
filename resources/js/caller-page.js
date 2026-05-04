/**
 * Caller test page — uses the same @twilio/voice-sdk as the admin SPA (see package.json).
 * Loaded only from resources/views/caller.blade.php via @vite.
 *
 * Outbound `device.connect({ params: { To: adminIdentity } })` hits Twilio → `/twilio/voice` → `<Dial><Client>`.
 * Admin notification UI (modal, Notification API, title flash, vibrate) lives on `/admin/*` in AuthenticatedLayout.vue.
 */
import { Device } from '@twilio/voice-sdk';
import { applyTwilioOutputDevices, primeMicrophoneForTwilio } from './utils/twilioVoiceAudio.js';
import {
    attachTokenWillExpireHandler,
    buildClientDialParams,
    createTwilioDeviceOptions,
    logTwilioErrorDetails,
} from './utils/twilioVoiceSdk.js';

const cfg = window.__CALLER_CONFIG__ || {};
const callerUserId = String(cfg.callerUserId ?? '5');
const adminIdentity = cfg.adminIdentity != null ? String(cfg.adminIdentity) : '';
const voiceSdkEdge = String(cfg.voiceSdkEdge ?? '').trim();

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
            '• Optional: set TWILIO_VOICE_SDK_EDGE (e.g. singapore) for browser signaling; must be a valid edge name or leave empty.\n' +
            '• Network: https://networktest.twilio.com/ — firewalls can drop WebSocket/WebRTC and surface as 31005.\n' +
            '• Run php artisan config:clear. Local only: GET /twilio/token-debug?identity=5'
        );
    }
    if (n === 31005) {
        return (
            '\n\n31005 (gateway HANGUP):\n' +
            '• TwiML App Voice URL must be a public HTTPS URL that hits /twilio/voice — not http://127.0.0.1 or *.test (use ngrok/cloudflare tunnel and php artisan config:clear).\n' +
            '• laravel.log: “Twilio handleVoice request” = webhook OK. “blocked by staff presence” = open admin dashboard (heartbeat) or set CALL_REQUIRE_STAFF_PRESENCE_FOR_VOICE_TWIML=false for local tests.\n' +
            '• Admin Twilio.Device must be registered with identity exactly equal to ADMIN_IDENTITY (dashboard: click page once so voice loads).\n' +
            '• Allow microphone for this site; blocked mic can break the audio pipeline and show as a gateway hangup.\n' +
            '• Match JWT region (TWILIO_VOICE_HOME_REGION), TwiML App account, and TWILIO_VOICE_SDK_EDGE — invalid edge breaks signaling (31005).\n' +
            '• Keep tokens fresh (tokenWillExpire is handled in the app); long calls need a valid network path to Twilio.\n' +
            '• https://www.twilio.com/docs/voice/sdks/javascript#twiml-app'
        );
    }
    if (n === 31603) {
        return (
            '\n\n31603 (Decline — callee did not accept / not registered):\n' +
            '• No Twilio Voice client is currently REGISTERED for the dialed identity (often ADMIN_IDENTITY). Heartbeat alone is not enough.\n' +
            '• Operator: open /admin, click once to allow microphone, wait until the console logs Device registered / voice ready.\n' +
            '• Token identity from /twilio/token?identity= must equal exactly what TwiML <Dial><Client> dials (case-sensitive).\n' +
            '• Compare Twilio Debugger + laravel.log “client_identity” with .env ADMIN_IDENTITY.\n' +
            '• See https://www.twilio.com/docs/api/errors/31603'
        );
    }
    return '';
}

function setStatus(msg, kind) {
    statusEl.textContent = msg;
    statusEl.className = kind || '';
}

/** User-visible line for common outbound failures (31603 = dial target not registered on Twilio). */
function shortMessageForCallErrorCode(code, dialTarget) {
    const n = typeof code === 'number' ? code : parseInt(code, 10);
    if (n === 31603) {
        const target = dialTarget ? `"${dialTarget}"` : 'ADMIN_IDENTITY';
        return (
            `No dispatch client answered (31603). Twilio has no registered browser/app for ${target}. ` +
            `Keep an admin tab open, click once to load voice, and confirm the token identity matches .env exactly (case-sensitive).`
        );
    }
    if (n === 31005) {
        return 'Connection lost (31005). Check network (networktest.twilio.com), TWILIO_VOICE_SDK_EDGE, webhook URL, then try again.';
    }
    return null;
}

/** Stabilize UI when the call leg errors (31005 = gateway / signaling lost). */
function wireOutboundCallSession(call, dialTargetForErrors) {
    if (!call) {
        return;
    }
    call.on('disconnect', onCallEnded);
    call.on('cancel', onCallEnded);
    call.on('error', (err) => {
        logTwilioErrorDetails('Outbound call', err);
        const c = err && typeof err.code !== 'undefined' ? err.code : null;
        const shortMsg = shortMessageForCallErrorCode(c, dialTargetForErrors);
        if (shortMsg) {
            setStatus(shortMsg, 'error');
        } else {
            setStatus(`Call error: ${formatErr(err)}${signalingHint(c)}`, 'error');
        }
        activeCall = null;
        btnHangup.disabled = true;
        btnCall.disabled = false;
    });
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

            try {
                setStatus('Allow microphone when the browser asks…');
                await primeMicrophoneForTwilio();
            } catch (micErr) {
                console.error(micErr);
                setStatus(formatErr(micErr), 'error');
                voiceBootstrapStarted = false;
                btnStart.disabled = false;
                return;
            }

            device = new Device(
                data.token,
                createTwilioDeviceOptions({
                    edge: voiceSdkEdge,
                    logLevel: 'warn',
                    closeProtection: true,
                }),
            );
            attachTokenWillExpireHandler(device, callerUserId);

            device.on('error', (err) => {
                logTwilioErrorDetails('Device', err);
                const code = err && (err.code != null ? err.code : err.twilioError && err.twilioError.code);
                const txt = `Twilio: ${formatErr(err)}${signalingHint(code)}`;
                setStatus(txt, 'error');
                voiceBootstrapStarted = false;
                btnStart.disabled = false;
                btnCall.disabled = true;
                deviceReady = false;
            });

            device.on('registered', async () => {
                await applyTwilioOutputDevices(device);
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

/** Same operator availability as set-location / TwiML gate; use before every device.connect (incl. geo fallback). */
async function checkOperatorAvailability() {
    const res = await fetch('/api/v1/call/availability', { headers: { Accept: 'application/json' } });
    const body = await res.json().catch(() => ({}));
    if (res.ok) {
        return { ok: true, message: body.message || 'OK', body };
    }
    return {
        ok: false,
        message: body.message || 'All emergency operators are currently busy.',
        resolution: body.resolution_hint || '',
    };
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
    setStatus('Checking operator availability…');

    const avail = await checkOperatorAvailability();
    if (!avail.ok) {
        const extra = avail.resolution ? `\n${avail.resolution}` : '';
        setStatus(`${avail.message}${extra}`, 'error');
        alert(`${avail.message}${extra}`);
        btnCall.disabled = false;
        return;
    }
    const dialIdentity = String(avail.body?.twilio_dial_identity || adminIdentity || '').trim();
    if (!dialIdentity) {
        setStatus('Server is not configured: missing Twilio dial identity (ADMIN_IDENTITY).', 'error');
        btnCall.disabled = false;
        return;
    }

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

        setStatus('Connecting to admin…');
        try {
            await primeMicrophoneForTwilio();
        } catch (micErr) {
            console.error(micErr);
            setStatus(formatErr(micErr), 'error');
            alert(formatErr(micErr));
            return;
        }
        await applyTwilioOutputDevices(device);
        const dialParams = buildClientDialParams(dialIdentity, {
            userId: callerData.userId,
            reportId: stored.reportId,
        });
        console.info('[Caller] device.connect', { To: dialParams.To, callerIdentity: callerUserId });
        let call;
        try {
            call = await device.connect({ params: dialParams });
        } catch (e) {
            logTwilioErrorDetails('device.connect', e);
            const c = e && typeof e.code !== 'undefined' ? e.code : null;
            const shortMsg = shortMessageForCallErrorCode(c, dialIdentity);
            if (shortMsg) {
                setStatus(shortMsg, 'error');
            } else {
                setStatus(`Could not connect: ${formatErr(e)}${signalingHint(c)}`, 'error');
            }
            return;
        }
        activeCall = call;
        wireOutboundCallSession(call, dialIdentity);

        setStatus('Call in progress — speak when the admin answers.');
        btnHangup.disabled = false;
    } catch (error) {
        console.error('Call flow error:', error);
        setStatus(`Falling back to call without location: ${formatErr(error)}`, 'error');

        try {
            const avail2 = await checkOperatorAvailability();
            if (!avail2.ok) {
                const extra = avail2.resolution ? `\n${avail2.resolution}` : '';
                setStatus(`${avail2.message}${extra}`, 'error');
                alert(`${avail2.message}${extra}`);
                return;
            }

            const callerInfo = JSON.stringify({
                userId: parseInt(callerUserId, 10),
                error: 'Location not available',
            });
            try {
                await primeMicrophoneForTwilio();
            } catch (micErr) {
                console.error(micErr);
                setStatus(formatErr(micErr), 'error');
                alert(formatErr(micErr));
                return;
            }
            await applyTwilioOutputDevices(device);
            const dialParamsFb = buildClientDialParams(dialIdentity, {
                userId: parseInt(callerUserId, 10),
                error: 'Location not available',
            });
            console.info('[Caller] device.connect (fallback)', { To: dialParamsFb.To, callerIdentity: callerUserId });
            let callFb;
            try {
                callFb = await device.connect({ params: dialParamsFb });
            } catch (e2) {
                logTwilioErrorDetails('device.connect (fallback)', e2);
                const c2 = e2 && typeof e2.code !== 'undefined' ? e2.code : null;
                const shortMsg2 = shortMessageForCallErrorCode(c2, dialIdentity);
                if (shortMsg2) {
                    setStatus(shortMsg2, 'error');
                } else {
                    setStatus(`Could not connect: ${formatErr(e2)}${signalingHint(c2)}`, 'error');
                }
                alert(`Could not start call: ${formatErr(e2)}`);
                return;
            }
            activeCall = callFb;
            wireOutboundCallSession(callFb, dialIdentity);
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
