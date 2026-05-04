/**
 * Standalone admin receiver test — same @twilio/voice-sdk as package.json / admin SPA.
 */
import { Device } from '@twilio/voice-sdk';
import { applyTwilioOutputDevices, primeMicrophoneForTwilio } from './utils/twilioVoiceAudio.js';
import { attachTokenWillExpireHandler, createTwilioDeviceOptions, logTwilioErrorDetails } from './utils/twilioVoiceSdk.js';

const operatorIdentity = String(window.__RECEIVER_CONFIG__?.operatorIdentity ?? '').trim();
const voiceSdkEdge = String(window.__RECEIVER_CONFIG__?.voiceSdkEdge ?? '').trim();

let device;
let activeCall;
let bootstrapStarted = false;

const statusEl = document.getElementById('status');
const endBtn = document.getElementById('endCall');

async function startReceiverAfterGesture() {
    if (bootstrapStarted || !operatorIdentity) {
        return;
    }
    bootstrapStarted = true;

    try {
        statusEl.textContent = 'Allow microphone when the browser asks…';
        await primeMicrophoneForTwilio();
    } catch (e) {
        console.error(e);
        statusEl.textContent = e && e.message ? e.message : 'Microphone permission denied.';
        bootstrapStarted = false;
        return;
    }

    try {
        statusEl.textContent = 'Loading token…';
        const res = await fetch(`/twilio/token?identity=${encodeURIComponent(operatorIdentity)}`);
        const data = await res.json();
        if (!res.ok || !data.token) {
            throw new Error(data.message || 'Token response invalid');
        }

        device = new Device(
            data.token,
            createTwilioDeviceOptions({
                edge: voiceSdkEdge,
                logLevel: 'error',
            }),
        );
        attachTokenWillExpireHandler(device, operatorIdentity);

        device.on('registered', async () => {
            console.log('Receiver registered');
            await applyTwilioOutputDevices(device);
            statusEl.textContent = 'Ready to receive calls';
        });

        device.on('incoming', async (call) => {
            call.on('error', (e) => logTwilioErrorDetails('Receiver call', e));
            console.log('Incoming call...');
            const confirmAccept = confirm('Incoming call. Do you want to accept it?');
            if (confirmAccept) {
                await applyTwilioOutputDevices(device);
                call.accept();
                activeCall = call;
                statusEl.textContent = 'Call in progress...';
                endBtn.style.display = 'inline';
            } else {
                call.reject();
            }
        });

        device.on('error', (error) => {
            logTwilioErrorDetails('Receiver Device', error);
        });

        await device.register();
    } catch (e) {
        console.error(e);
        statusEl.textContent = e && e.message ? e.message : 'Token load or register failed — check console.';
        bootstrapStarted = false;
    }
}

if (!operatorIdentity) {
    statusEl.textContent = 'No operator identity: add ?identity=USER_ID to the URL, log in as admin, or set ADMIN_IDENTITY in .env.';
} else {
    statusEl.textContent = 'Click anywhere on this page once to allow the microphone and connect to Twilio.';
    window.addEventListener(
        'pointerdown',
        function once() {
            window.removeEventListener('pointerdown', once, true);
            startReceiverAfterGesture();
        },
        true,
    );
}

endBtn?.addEventListener('click', () => {
    if (activeCall) {
        activeCall.disconnect();
        activeCall = null;
        statusEl.textContent = 'Call ended';
        endBtn.style.display = 'none';
    }
});
