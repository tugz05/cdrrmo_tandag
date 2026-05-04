<script setup>
import { onMounted, onUnmounted, ref, computed } from 'vue';
import SideBar from './SideBar.vue';
import Footer from './Footer.vue';
import JConfirmDialog from '@/Components/JConfirmDialog.vue';
import JToast from '@/Components/JToast.vue';
import JModal from '@/Components/JModal.vue';
import { toggleModal } from '@/Helpers/JModal';
import JButton from '@/Components/JButton.vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { Device } from '@twilio/voice-sdk';

const page = usePage();
const canAccessAdmin = computed(() => page.props.auth?.canAccessAdmin === true);
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

function tryResumeAudioContext() {
    try {
        const Ctor = window.AudioContext || window.webkitAudioContext;
        if (!Ctor) {
            return;
        }
        if (!sharedAudioContext) {
            sharedAudioContext = new Ctor();
        }
        if (sharedAudioContext.state === 'suspended' && typeof sharedAudioContext.resume === 'function') {
            sharedAudioContext.resume().catch(() => {});
        }
    } catch {
        /* ignore */
    }
}

/** One combined gesture: unlock AudioContext + fetch token + build Voice SDK Device (stops pre-gesture AudioContext spam). */
function attachTwilioVoiceAfterUserGesture() {
    const once = () => {
        if (twilioVoiceBootstrapped) {
            return;
        }
        twilioVoiceBootstrapped = true;
        window.removeEventListener('pointerdown', once, true);
        window.removeEventListener('keydown', once, true);

        tryResumeAudioContext();
        fetch('/twilio/token?identity=admin_user')
            .then(res => res.json())
            .then(data => setupTwilio(data.token))
            .catch(err => {
                console.error('Token fetch error:', err);
                twilioVoiceBootstrapped = false;
            });
    };

    window.addEventListener('pointerdown', once, true);
    window.addEventListener('keydown', once, true);
}

function destroyTwilioDevice() {
    if (!device) {
        return;
    }
    try {
        device.disconnectAll?.();
        device.destroy?.();
    } catch {
        /* ignore */
    }
    device = null;
    activeCall = null;
}

function staffHeartbeat() {
    axios.post(route('admin.staff.heartbeat')).catch(() => {});
}

/** Presence for API availability — MUST NOT depend on Twilio Device registration (audio blocks / SDK errors would strand operators offline). */
function startStaffPresenceHeartbeat() {
    staffHeartbeat();
    if (heartbeatTimer) {
        clearInterval(heartbeatTimer);
    }
    heartbeatTimer = setInterval(staffHeartbeat, 30000);
}

function handleTwilioDeviceError(error) {
    const msg = String(error?.message ?? error ?? '');
    const code = error?.code;
    const lower = msg.toLowerCase();
    if (
        lower.includes('audio output') ||
        lower.includes('devices not found') ||
        lower.includes('invalidargumenterror') ||
        lower.includes('notreadableerror') ||
        lower.includes('no audio')
    ) {
        console.warn(
            '[Twilio] Audio device:',
            msg || error,
            '— Check speakers/headphones, OS sound output, and click anywhere on the page once if calls stay silent.'
        );
        return;
    }
    if (code === 31205 || lower.includes('token')) {
        console.error('[Twilio] Token / registration:', code, msg, error);
        return;
    }
    if (code === 31007 || lower.includes('client version not supported')) {
        console.error(
            '[Twilio] Voice SDK is outdated or blocked — rebuild frontend assets and ensure @twilio/voice-sdk is bundled (not legacy v1 client).',
            error
        );
        return;
    }
    console.error('[Twilio]', code, msg, error);
}

async function setupTwilio(token) {
    destroyTwilioDevice();

    device = new Device(token, {
        codecPreferences: ['opus', 'pcmu'],
        logLevel: 'error',
    });

    device.on('registered', () => {
        voicePipelineReady.value = true;
        tryResumeAudioContext();
        staffHeartbeat();
    });

    device.on('incoming', async call => {
        activeCall = call;

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
        toggleModal('Incoming Call', 'modal-call');
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
        tryResumeAudioContext();
        callStatus.value = 'Call in progress...';
        isAnswering.value = true;
        showAnswerButton.value = false;
        activeCall.accept();
        handleCallAnswered(callReportId.value)
    }
}

function rejectCall() {
    if (activeCall) {
        activeCall.reject();
        endCall();
    }
}

function endCall() {
    if (activeCall) {
        activeCall.disconnect();
    }
    callStatus.value = 'Call ended';
    isAnswering.value = false;
    showAnswerButton.value = true;
    activeCall = null;
    toggleModal('', 'modal-call');
    handleCallEnded(callReportId.value);
}

onMounted(() => {
    if (!canAccessAdmin.value) {
        return;
    }
    startStaffPresenceHeartbeat();
    attachTwilioVoiceAfterUserGesture();
});

onUnmounted(() => {
    if (heartbeatTimer) {
        clearInterval(heartbeatTimer);
        heartbeatTimer = null;
    }
    destroyTwilioDevice();
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
            Click or tap anywhere once to enable incoming emergency voice calls — required by your browser’s audio
            policy.
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
