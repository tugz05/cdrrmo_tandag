/**
 * Standalone admin receiver test — same @twilio/voice-sdk as package.json / admin SPA.
 */
import { Device } from '@twilio/voice-sdk';
import { applyTwilioOutputDevices } from './utils/twilioVoiceAudio.js';

const adminIdentity = window.__RECEIVER_CONFIG__?.adminIdentity ?? '';

let device;
let activeCall;

const statusEl = document.getElementById('status');
const endBtn = document.getElementById('endCall');

if (!adminIdentity) {
    statusEl.textContent = 'ADMIN_IDENTITY is not configured on the server.';
} else {
    fetch(`/twilio/token?identity=${encodeURIComponent(adminIdentity)}`)
        .then((res) => res.json())
        .then(async (data) => {
            device = new Device(data.token, {
                codecPreferences: ['opus', 'pcmu'],
                logLevel: 'error',
            });

            device.on('registered', async () => {
                console.log('Receiver registered');
                await applyTwilioOutputDevices(device);
                statusEl.textContent = 'Ready to receive calls';
            });

            device.on('incoming', async (call) => {
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

            device.on('error', (error) => console.error('Receiver error:', error));

            try {
                await device.register();
            } catch (e) {
                console.error('Receiver register failed:', e);
            }
        })
        .catch((e) => {
            console.error(e);
            statusEl.textContent = 'Token load failed — check console.';
        });
}

endBtn?.addEventListener('click', () => {
    if (activeCall) {
        activeCall.disconnect();
        activeCall = null;
        statusEl.textContent = 'Call ended';
        endBtn.style.display = 'none';
    }
});
