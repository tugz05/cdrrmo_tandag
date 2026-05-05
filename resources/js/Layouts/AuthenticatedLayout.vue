<script setup>
import { onMounted, onUnmounted, ref, computed, nextTick } from 'vue';
import SideBar from './SideBar.vue';
import Footer from './Footer.vue';
import JConfirmDialog from '@/Components/JConfirmDialog.vue';
import JToast from '@/Components/JToast.vue';
import JModal from '@/Components/JModal.vue';
import { toggleModal, hideModal } from '@/Helpers/JModal';
import JButton from '@/Components/JButton.vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
/** @twilio/voice-sdk version is pinned in package.json â€” keep in sync with caller-page.js / receiver-page.js */
import { Device } from '@twilio/voice-sdk';
import { applyTwilioOutputDevices, primeMicrophoneForTwilio } from '@/utils/twilioVoiceAudio.js';
import { attachTokenWillExpireHandler, createTwilioDeviceOptions, logTwilioErrorDetails } from '@/utils/twilioVoiceSdk.js';

const page = usePage();
const canAccessAdmin = computed(() => page.props.auth?.canAccessAdmin === true);
/**
 * Twilio Voice JS SDK Client identity for this operator (sanitized user id).
 * Falls back to legacy admin_identity when operator_client_identity is absent.
 */
const twilioOperatorIdentity = computed(() => {
    const fromUser = String(page.props.twilio?.operator_client_identity ?? '').trim();
    if (fromUser !== '') {
        return fromUser;
    }
    return String(page.props.twilio?.admin_identity ?? '').trim();
});
const twilioVoiceSdkEdge = computed(() => String(page.props.twilio?.voice_sdk_edge ?? '').trim());
const callStatus = ref('Someone is calling...');
const isAnswering = ref(false);
const showAnswerButton = ref(true);
const currentCaller = ref({})
const callerName = ref('')
const callReportId = ref(Number);
const voicePipelineReady = ref(false);

let device = null;
let activeCall = null;
let heartbeatTimer = null;

let sharedAudioContext = null;

/** Twilio Voice SDK should initialize after a user gesture or Chrome spams AudioContext warnings (autoplay policy). */
let twilioVoiceBootstrapped = false;
let twilioSetupInFlight = false;
let keepTwilioOnline = false;

let staffHeartbeatSeq = 0;

/** Dedupe pointer/key listeners when re-attaching after Device unregistered. */
let twilioGestureBootstrapHandler = null;

let incomingTitleFlashTimer = null;
let savedDocumentTitleBeforeFlash = '';

/** Browser Notification instance for incoming ring; close when call ends or caller hangs up. */
let incomingBrowserNotification = null;

/** Prevents double POST to call/ended when both disconnect and destroy run. */
let callEndNotifyDone = false;

/** Bound once: dismiss modal (X) rejects a pending ring so Twilio + UI stay in sync. */
let modalCallHiddenHandlerBound = false;

function finalizeVoiceCallEnd(callInstance) {
    if (activeCall !== callInstance) {
        return;
    }
    activeCall = null;
    callStatus.value = 'Call ended.';
    dismissIncomingCallUi();
    if (!callEndNotifyDone) {
        callEndNotifyDone = true;
        void handleCallEnded(callReportId.value);
    }
}

function onModalCallHiddenBootstrap() {
    const c = activeCall;
    if (!c || isAnswering.value) {
        return;
    }
    try {
        c.reject();
    } catch (e) {
        console.warn('[Twilio] reject after modal close', e);
        finalizeVoiceCallEnd(c);
    }
}

function dismissIncomingBrowserNotification() {
    try {
        incomingBrowserNotification?.close?.();
    } catch {
        /* ignore */
    }
    incomingBrowserNotification = null;
}

function dismissIncomingCallUi() {
    stopIncomingTitleFlash();
    dismissIncomingBrowserNotification();
    hideModal('', 'modal-call');
    isAnswering.value = false;
    showAnswerButton.value = true;
}

function stopIncomingTitleFlash() {
    if (incomingTitleFlashTimer !== null) {
        clearInterval(incomingTitleFlashTimer);
        incomingTitleFlashTimer = null;
    }
    if (typeof document !== 'undefined' && savedDocumentTitleBeforeFlash !== '') {
        document.title = savedDocumentTitleBeforeFlash;
        savedDocumentTitleBeforeFlash = '';
    }
}

function startIncomingTitleFlash() {
    if (typeof document === 'undefined') {
        return;
    }
    stopIncomingTitleFlash();
    savedDocumentTitleBeforeFlash = document.title;
    const base = document.title || 'Dashboard';
    let on = false;
    incomingTitleFlashTimer = window.setInterval(() => {
        on = !on;
        document.title = on ? `Incoming call â€” ${base}` : base;
    }, 900);
}

function notifyIncomingVoiceCall(displayName) {
    dismissIncomingBrowserNotification();
    const body = displayName ? `${displayName} is calling` : 'Incoming emergency voice call';
    try {
        if (typeof Notification !== 'undefined' && Notification.permission === 'granted') {
            incomingBrowserNotification = new Notification('CDRRMO — Voice call', {
                body,
                tag: 'cdrrmo-voice-incoming',
                requireInteraction: true,
            });
            window.setTimeout(() => {
                dismissIncomingBrowserNotification();
            }, 45000);
        }
    } catch {
        /* ignore */
    }
    try {
        if (navigator.vibrate) {
            navigator.vibrate([200, 120, 200]);
        }
    } catch {
        /* ignore */
    }
    startIncomingTitleFlash();
}

/**
 * Console diagnostics for POST /admin/staff/heartbeat (Twilio routing / caller availability).
 * On by dev build, on /admin/* in the browser, or force with: sessionStorage.setItem('cdrrmo_staff_debug','1')
 */
function staffPresenceDebugEnabled() {
    if (import.meta.env.DEV) {
        return true;
    }
    if (typeof window === 'undefined') {
        return false;
    }
    try {
        if (window.sessionStorage?.getItem('cdrrmo_staff_debug') === '1') {
            return true;
        }
    } catch {
        /* storage blocked */
    }
    return window.location.pathname.startsWith('/admin');
}

function logStaffPresence(level, ...args) {
    if (!staffPresenceDebugEnabled()) {
        return;
    }
    const fn = level === 'warn' ? console.warn : level === 'error' ? console.error : console.info;
    fn('[StaffPresence]', ...args);
}

/**
 * Resume our optional helper AudioContext only if it already exists (must not create here — no user gesture).
 */
function resumeSharedAudioContextOnly() {
    try {
        if (!sharedAudioContext || typeof sharedAudioContext.resume !== 'function') {
            return;
        }
        if (sharedAudioContext.state === 'suspended') {
            sharedAudioContext.resume().catch(() => {});
        }
    } catch {
        /* ignore */
    }
}

/**
 * Create/resume helper AudioContext — call only from a user gesture stack (pointerdown, Answer click, etc.).
 */
function createSharedAudioContextAfterUserGesture() {
    try {
        const Ctor = window.AudioContext || window.webkitAudioContext;
        if (!Ctor) {
            return;
        }
        if (!sharedAudioContext) {
            sharedAudioContext = new Ctor();
        }
        resumeSharedAudioContextOnly();
    } catch {
        /* ignore */
    }
}

/** One combined gesture: unlock AudioContext + fetch token + register Voice SDK Device (microphone requested on answer). */
function attachTwilioVoiceAfterUserGesture() {
    if (twilioGestureBootstrapHandler) {
        window.removeEventListener('pointerdown', twilioGestureBootstrapHandler, true);
        window.removeEventListener('keydown', twilioGestureBootstrapHandler, true);
        twilioGestureBootstrapHandler = null;
    }

    const once = async () => {
        window.removeEventListener('pointerdown', once, true);
        window.removeEventListener('keydown', once, true);
        twilioGestureBootstrapHandler = null;

        const identity = twilioOperatorIdentity.value;
        if (!identity) {
            console.error('[Twilio] operator_client_identity is empty — set ADMIN_IDENTITY in .env for fallback or ensure you are logged in as admin.');
            return;
        }

        createSharedAudioContextAfterUserGesture();
        if (typeof Notification !== 'undefined' && Notification.permission === 'default') {
            Notification.requestPermission().catch(() => {});
        }
        // If we are not registered yet, use this user gesture to bootstrap voice.
        if (!voicePipelineReady.value && !twilioSetupInFlight) {
            await bootstrapTwilioVoice({ source: 'user_gesture' });
        }
    };

    twilioGestureBootstrapHandler = once;
    window.addEventListener('pointerdown', once, true);
    window.addEventListener('keydown', once, true);
}

async function bootstrapTwilioVoice({ source } = {}) {
    if (!canAccessAdmin.value) {
        return;
    }
    if (voicePipelineReady.value) {
        return;
    }
    if (twilioSetupInFlight) {
        return;
    }

    const identity = twilioOperatorIdentity.value;
    if (!identity) {
        console.error('[Twilio] operator_client_identity is empty — set ADMIN_IDENTITY in .env for fallback or ensure you are logged in as admin.');
        return;
    }

    twilioSetupInFlight = true;
    twilioVoiceBootstrapped = true;

    try {
        const res = await fetch('/twilio/token?identity=' + encodeURIComponent(identity));
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            throw new Error(data?.message || `Token request failed (HTTP ${res.status})`);
        }
        if (!data?.token) {
            throw new Error(data?.message || 'Token response missing "token"');
        }
        await setupTwilio(data.token);
    } catch (err) {
        console.error('[Twilio] bootstrap failed', { source }, err);
        twilioVoiceBootstrapped = false;
    } finally {
        twilioSetupInFlight = false;
    }
}

function beaconVoiceReadyFalse() {
    if (!canAccessAdmin.value) {
        return;
    }
    if (typeof navigator === 'undefined' || typeof navigator.sendBeacon !== 'function') {
        return;
    }
    let csrf = '';
    try {
        csrf = String(document?.querySelector?.('meta[name=\"csrf-token\"]')?.getAttribute?.('content') ?? '').trim();
    } catch {
        csrf = '';
    }
    if (!csrf) {
        return;
    }
    let url;
    try {
        url = route('admin.staff.heartbeat');
    } catch {
        return;
    }
    try {
        const fd = new FormData();
        fd.append('_token', csrf);
        fd.append('twilio_voice_ready', '0');
        navigator.sendBeacon(url, fd);
    } catch {
        /* ignore */
    }
}

function destroyTwilioDevice() {
    if (!device) {
        return;
    }
    const legExisted = activeCall !== null;
    if (heartbeatTimer) {
        clearInterval(heartbeatTimer);
        heartbeatTimer = null;
    }
    pingVoiceClientReadyFalse();
    try {
        device.disconnectAll?.();
        device.destroy?.();
    } catch {
        /* ignore */
    }
    device = null;
    activeCall = null;
    if (legExisted && !callEndNotifyDone) {
        callEndNotifyDone = true;
        void handleCallEnded(callReportId.value);
    }
    dismissIncomingCallUi();
    voicePipelineReady.value = false;
}

function pingVoiceClientReadyFalse() {
    if (!canAccessAdmin.value) {
        return;
    }
    const url = route('admin.staff.heartbeat');
    axios
        .post(url, { twilio_voice_ready: false })
        .then(() => logStaffPresence('info', 'Reported twilio_voice_ready: false (voice offline)'))
        .catch(() => {});
}

function staffHeartbeat() {
    staffHeartbeatSeq += 1;
    const seq = staffHeartbeatSeq;
    const url = route('admin.staff.heartbeat');
    logStaffPresence('info', `heartbeat #${seq} -> POST`, url);

    axios
        .post(url, { twilio_voice_ready: voicePipelineReady.value === true })
        .then((res) => {
            logStaffPresence('info', `heartbeat #${seq} OK`, res.status, res.data);
        })
        .catch((err) => {
            const status = err?.response?.status;
            const data = err?.response?.data;
            console.warn('[StaffPresence] heartbeat failed â€” operators may show offline for voice routing', {
                seq,
                url,
                status: status ?? '(no response)',
                data: data ?? err?.message,
                hint:
                    status === 419
                        ? 'CSRF: refresh page or ensure XSRF-TOKEN cookie is set (same-site cookies).'
                        : status === 401 || status === 403
                          ? 'Not authorized â€” session may have expired.'
                          : 'See Network tab for full response.',
            });
        });
}

/** Start POST /admin/staff/heartbeat on an interval. Call only after Twilio Device is registered so callers are not offered voice while no Client is online (avoids 31603). */
function startStaffPresenceHeartbeat() {
    const url = route('admin.staff.heartbeat');
    logStaffPresence(
        'info',
        'Starting staff presence heartbeat (every 30s + immediate first ping). Endpoint:',
        url,
        '(sessionStorage cdrrmo_staff_debug=1 forces logs on any path)',
    );
    staffHeartbeat();
    if (heartbeatTimer) {
        clearInterval(heartbeatTimer);
    }
    heartbeatTimer = setInterval(staffHeartbeat, 30000);
}

function handleTwilioDeviceError(error) {
    logTwilioErrorDetails('Device', error);
    const msg = String(error?.message ?? error ?? '');
    const code = error?.code;
    const lower = msg.toLowerCase();
    if (
        lower.includes('audio output') ||
        lower.includes('devices not found') ||
        lower.includes('invalidargumenterror') ||
        lower.includes('notreadableerror') ||
        lower.includes('no audio') ||
        lower.includes('microphone') ||
        lower.includes('permission') ||
        lower.includes('notallowederror')
    ) {
        console.warn(
            '[Twilio] Audio device:',
            msg || error,
            '- Allow microphone for this site, check speakers/headphones, OS sound output, and click the page once if calls stay silent.'
        );
        return;
    }
    if (code === 31005 || (lower.includes('gateway') && lower.includes('hangup'))) {
        console.warn(
            '[Twilio] 31005 / gateway hangup - confirm microphone permission, public TwiML webhook URL, and admin Client registered (see laravel.log).',
            error
        );
        return;
    }
    if (code === 31603 || lower.includes('31603') || lower.includes('decline')) {
        console.warn(
            '[Twilio] 31603 Decline — no registered Client for the dialed leg, or callee rejected. Operators register with their user id; callers use ring-group To from /api/v1/call/availability (TWILIO_DISPATCH_RING_GROUP).',
            error,
        );
        return;
    }
    if (code === 31480 || lower.includes('31480') || lower.includes('temporarilyunavailable')) {
        console.warn(
            '[Twilio] 31480 Temporarily unavailable (SIP) — all Client legs may be offline/busy, or firewall/UDP/edge blocked signaling/media. Operators: register Device on /admin + twilio_voice_ready; callers: retry. https://www.twilio.com/docs/api/errors/31480',
            error,
        );
        return;
    }
    if (code === 31205 || lower.includes('token')) {
        console.error('[Twilio] Token / registration:', code, msg, error);
        return;
    }
    if (code === 31007 || lower.includes('client version not supported')) {
        console.error(
            '[Twilio] Voice SDK is outdated or blocked - rebuild frontend assets and ensure @twilio/voice-sdk is bundled (not legacy v1 client).',
            error
        );
        return;
    }
    console.error('[Twilio]', code, msg, error);
}

async function setupTwilio(token) {
    destroyTwilioDevice();

    device = new Device(
        token,
        createTwilioDeviceOptions({
            edge: twilioVoiceSdkEdge.value,
            logLevel: 'error',
        }),
    );
    attachTokenWillExpireHandler(device, twilioOperatorIdentity.value);

    device.on('registered', async () => {
        console.info('[Twilio] Admin Device registered for incoming VoIP Client:', twilioOperatorIdentity.value);
        await applyTwilioOutputDevices(device);
        voicePipelineReady.value = true;
        startStaffPresenceHeartbeat();
    });

    device.on('unregistered', () => {
        voicePipelineReady.value = false;
        pingVoiceClientReadyFalse();
        if (heartbeatTimer) {
            clearInterval(heartbeatTimer);
            heartbeatTimer = null;
        }
        console.warn(
            '[Twilio] Device unregistered - emergency voice will not ring until you click the page and Twilio registers again.',
        );
        if (keepTwilioOnline) {
            window.setTimeout(() => {
                attachTwilioVoiceAfterUserGesture();
            }, 500);
        }
    });

    device.on('incoming', async call => {
        callEndNotifyDone = false;
        activeCall = call;

        call.on('disconnect', () => {
            finalizeVoiceCallEnd(call);
        });

        call.on('error', err => {
            logTwilioErrorDetails('Incoming / active call', err);
            if (err && err.code === 31005) {
                callStatus.value = 'Connection lost (31005). Check network, edge (TWILIO_VOICE_SDK_EDGE), and TwiML URL.';
            } else if (err && err.code === 31603) {
                callStatus.value =
                    'Call declined (31603). Caller may be using a stale dial target, or no operator had Twilio Voice registered.';
            } else if (err && err.code === 31480) {
                callStatus.value =
                    'Temporarily unavailable (31480). Signaling/media may be blocked, or no operator Client could take the call — see Twilio Debugger and https://www.twilio.com/docs/api/errors/31480';
            }
            if (activeCall === call) {
                try {
                    activeCall.disconnect();
                } catch {
                    finalizeVoiceCallEnd(call);
                }
            }
        });

        try {
            console.log('caller data', call.parameters.From);
            const clientString = call.parameters.From;
            const clientId = clientString.split(":")[1];
            const callerResponse = await fetchCaller(clientId)
            console.log('caller response: ', callerResponse)
            callerName.value = callerResponse.fname + ' ' + callerResponse.lname
            callReportId.value = callerResponse.latest_call[0].id

        } catch (e) {
            console.error('Error parsing caller info:', e);
        }

        showAnswerButton.value = true;
        isAnswering.value = false;
        callStatus.value = 'Incoming call — answer or reject.';
        toggleModal('Incoming Call', 'modal-call');
        notifyIncomingVoiceCall(callerName.value);
    });

    device.on('error', handleTwilioDeviceError);

    try {
        await device.register();
    } catch (err) {
        handleTwilioDeviceError(err);
        destroyTwilioDevice();
        twilioVoiceBootstrapped = false;
    }
}

async function answerCall() {
    if (activeCall) {
        stopIncomingTitleFlash();
        createSharedAudioContextAfterUserGesture();
        try {
            await primeMicrophoneForTwilio();
        } catch (e) {
            console.error('[Twilio] Cannot answer without microphone:', e);
            callStatus.value = e && e.message ? e.message : 'Microphone permission is required to answer.';
            return;
        }
        await applyTwilioOutputDevices(device);
        resumeSharedAudioContextOnly();
        callStatus.value = 'Call in progress...';
        isAnswering.value = true;
        showAnswerButton.value = false;
        activeCall.accept();
        handleCallAnswered(callReportId.value)
    }
}

function rejectCall() {
    const c = activeCall;
    if (!c) {
        return;
    }
    try {
        c.reject();
    } catch (e) {
        console.warn('[Twilio] reject', e);
        finalizeVoiceCallEnd(c);
    }
}

function endCall() {
    const c = activeCall;
    if (!c) {
        dismissIncomingCallUi();
        return;
    }
    try {
        c.disconnect();
    } catch (e) {
        console.warn('[Twilio] disconnect', e);
        finalizeVoiceCallEnd(c);
    }
}

onMounted(() => {
    if (!canAccessAdmin.value) {
        return;
    }
    /** Do not start staff heartbeat until Twilio Device is registered (see startStaffPresenceHeartbeat in device.on("registered")). */
    keepTwilioOnline = true;
    /** Do not register Twilio.Device on mount — Chrome blocks AudioContext without a user gesture (autoplay policy). */
    attachTwilioVoiceAfterUserGesture();
    nextTick(() => {
        const modalEl = document.getElementById('modal-call');
        if (modalEl && !modalCallHiddenHandlerBound) {
            modalCallHiddenHandlerBound = true;
            modalEl.addEventListener('hidden.bs.modal', onModalCallHiddenBootstrap);
        }
    });
    try {
        window.addEventListener('beforeunload', beaconVoiceReadyFalse);
        window.addEventListener('pagehide', beaconVoiceReadyFalse);
    } catch {
        /* ignore */
    }
});

onUnmounted(() => {
    keepTwilioOnline = false;
    if (twilioGestureBootstrapHandler) {
        window.removeEventListener('pointerdown', twilioGestureBootstrapHandler, true);
        window.removeEventListener('keydown', twilioGestureBootstrapHandler, true);
        twilioGestureBootstrapHandler = null;
    }
    if (heartbeatTimer) {
        clearInterval(heartbeatTimer);
        heartbeatTimer = null;
    }
    if (modalCallHiddenHandlerBound) {
        const modalEl = document.getElementById('modal-call');
        if (modalEl) {
            modalEl.removeEventListener('hidden.bs.modal', onModalCallHiddenBootstrap);
        }
        modalCallHiddenHandlerBound = false;
    }
    destroyTwilioDevice();
    beaconVoiceReadyFalse();
    try {
        window.removeEventListener('beforeunload', beaconVoiceReadyFalse);
        window.removeEventListener('pagehide', beaconVoiceReadyFalse);
    } catch {
        /* ignore */
    }
});


const fetchCaller = async (callerId) => {
    const response = await fetch(route('caller-info.index', callerId), {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
    });

    if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.message || 'Failed to store location');
    }

    return response.json();
}

async function handleCallAnswered(reportId) {
    try {
        await axios.post(route('admin.staff.call-answered'));
        const response = await fetch('/api/v1/call/started', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                report_id: reportId
            })
        });

        const data = await response.json();
        console.log('Call started:', data);
    } catch (error) {
        console.error('Error recording call start:', error);
    }
}

async function handleCallEnded(reportId) {
    try {
        const response = await fetch('/api/v1/call/ended', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                report_id: reportId,
            })
        });

        const data = await response.json();
        console.log('Call ended:', data);
    } catch (error) {
        console.error('Error recording call end:', error);
    } finally {
        try {
            await axios.post(route('admin.staff.call-finished'));
        } catch (e) {
            console.error('Error releasing staff presence:', e);
        }
    }
}

</script>

<template>
    <div>
        <div
            v-if="canAccessAdmin && !voicePipelineReady"
            class="alert alert-info border-0 rounded-0 py-2 px-3 mb-0 small text-center"
            role="status"
        >
            Click or tap anywhere once (or press a key) to connect Twilio voice for incoming emergency calls.
            The browser requires this before audio can start; the microphone is only requested when you answer.
        </div>
        <div class="content">
            <SideBar />
            <main>
                <slot />
                <Footer />
            </main>
        </div>
    </div>

    <JModal id="modal-call">
        <div class="text-center mb-10">
            <h5 class="m-0 fw-bold">
                {{ isAnswering ? 'Active Call' : 'Incoming Call' }}
            </h5>

            <div v-if="callerName" class="caller-info mt-3">
                <p>{{ callerName }}</p>
            </div>

            <div class="d-flex justify-content-center gap-3 mt-5">
                <template v-if="!isAnswering">
                    <JButton danger text="Reject" @click="rejectCall" />
                    <JButton success text="Answer" @click="answerCall" />
                </template>
                <JButton v-if="isAnswering" danger text="End Call" @click="endCall" />
            </div>
        </div>
    </JModal>

    <JConfirmDialog />
    <JToast />
</template>
