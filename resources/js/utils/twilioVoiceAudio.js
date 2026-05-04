/**
 * Twilio Voice SDK may call setSinkId('default'), which throws on some browsers
 * (InvalidArgumentError: Devices not found: default). Prefer a concrete audiooutput id.
 *
 * @param {import('@twilio/voice-sdk').Device | null | undefined} device
 */
export async function applyTwilioOutputDevices(device) {
    const audio = device?.audio;
    if (!audio?.speakerDevices?.set || typeof navigator === 'undefined') {
        return;
    }

    let outputs = [];
    try {
        if (navigator.mediaDevices?.getUserMedia) {
            try {
                await navigator.mediaDevices.getUserMedia({ audio: true });
            } catch {
                /* enumerate may still work */
            }
        }
        if (!navigator.mediaDevices?.enumerateDevices) {
            return;
        }
        const all = await navigator.mediaDevices.enumerateDevices();
        outputs = all.filter((d) => d.kind === 'audiooutput');
    } catch {
        return;
    }

    if (!outputs.length) {
        return;
    }

    const concrete =
        outputs.find((d) => d.deviceId && d.deviceId !== 'default') || outputs.find((d) => d.deviceId) || outputs[0];

    const id = concrete?.deviceId;
    if (!id) {
        return;
    }

    try {
        await audio.speakerDevices.set(id);
    } catch {
        /* ignore */
    }
    try {
        await audio.ringtoneDevices.set(id);
    } catch {
        /* ignore */
    }
}
