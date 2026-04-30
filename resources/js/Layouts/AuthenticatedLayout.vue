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

const page = usePage();
const canAccessAdmin = computed(() => page.props.auth?.canAccessAdmin === true);
const callStatus = ref('Someone is calling...');
const isAnswering = ref(false);
const showAnswerButton = ref(true);
const currentCaller = ref({})
const callerName = ref('')
const callReportId = ref(Number)

let device = null;
let activeCall = null;
let heartbeatTimer = null;

function staffHeartbeat() {
    axios.post(route('admin.staff.heartbeat')).catch(() => {});
}

function setupTwilio(token) {
    device = new Twilio.Device(token, {
        codecPreferences: ['opus', 'pcmu'],
        fakeLocalDTMF: true,
        enableRingingState: true
    });

    device.on('ready', () => {
        console.log('Twilio Device ready');
        staffHeartbeat();
        if (heartbeatTimer) {
            clearInterval(heartbeatTimer);
        }
        heartbeatTimer = setInterval(staffHeartbeat, 30000);
    });


    device.on('incoming', async call => {
        // console.log('Incoming call...', call);
        activeCall = call;

        // Parse caller information
        let callerData = {};
        try {
            // callerData = call.parameters.From || '{}';
            // callerData = JSON.parse(call.parameters || '{}');
            console.log('caller data', call.parameters.From);
            const clientString = call.parameters.From;
            const clientId = clientString.split(":")[1]; // Returns "1" as string
            // const numericId = parseInt(clientId); // Convert to number if needed
            const callerResponse = await fetchCaller(clientId)
            console.log('caller response: ', callerResponse)
            callerName.value = callerResponse.fname + ' ' + callerResponse.lname
            callReportId.value = callerResponse.latest_call[0].id
            // return console.log('latest report id', callerResponse.latest_call[0].id)


        } catch (e) {
            console.error('Error parsing caller info:', e);
        }

        // Update UI with caller info
        // callStatus.value = callerData.name
        //     ? `Call from ${callerData.name} (${callerData.department})`
        //     : 'Incoming call';

        // Store caller data for display
        // currentCaller.value = callerData;

        // console.log('current caller', currentCaller.value.name)

        showAnswerButton.value = true;
        isAnswering.value = false;
        toggleModal('Incoming Call', 'modal-call');
    });


    device.on('error', error => {
        console.error('Twilio error:', error);
    });
}

async function answerCall() {
    if (activeCall) {
        callStatus.value = 'Call in progress...';
        isAnswering.value = true;
        showAnswerButton.value = false;
        activeCall.accept();
        handleCallAnswered(callReportId.value)
        // await updateCallStarted() 
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
    toggleModal('', 'modal-call'); // Close the modal
    handleCallEnded(callReportId.value);
}

onMounted(() => {
    if (!canAccessAdmin.value) {
        return;
    }
    fetch('/twilio/token?identity=admin_user')
        .then(res => res.json())
        .then(data => setupTwilio(data.token))
        .catch(err => console.error('Token fetch error:', err));
});

onUnmounted(() => {
    if (heartbeatTimer) {
        clearInterval(heartbeatTimer);
        heartbeatTimer = null;
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

// When call is ended/canceled
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
            <!-- <div class="fs-sm text-muted">{{ callStatus }}</div> -->

            <!-- Display caller information if available -->
            <div v-if="callerName" class="caller-info mt-3">
                <p>{{ callerName }}</p>
            </div>

            <div class="d-flex justify-content-center gap-3 mt-5">
                <!-- Buttons remain the same -->
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