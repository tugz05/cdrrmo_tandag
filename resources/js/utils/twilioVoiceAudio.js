/**
 * Request mic access once, then stop tracks so Twilio can open its own stream later.
 * Call after a user gesture and before `new Device(...)` to avoid failed audio init / gateway hangup.
 *
 * @returns {Promise<void>}
 * @throws {Error} user-facing message; original DOMException on `.cause` when available
 */
export async function primeMicrophoneForTwilio() {
    if (typeof navigator === 'undefined' || !navigator.mediaDevices?.getUserMedia) {
        throw new Error('This browser does not support microphone access (getUserMedia).');
    }

    let stream;
    try {
        stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    } catch (e) {
        const name = e && e.name ? e.name : 'Error';
        let msg = `Could not open microphone: ${e && e.message ? e.message : name}.`;
        if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
            msg =
                'Microphone permission was blocked. Allow the microphone for this site (lock icon in the address bar), then try again.';
        } else if (name === 'NotFoundError' || name === 'DevicesNotFoundError') {
            msg = 'No microphone was found. Connect a microphone and try again.';
        } else if (name === 'NotReadableError' || name === 'TrackStartError') {
            msg = 'Microphone is in use or unavailable. Close other apps using the mic and try again.';
        }
        const err = new Error(msg);
        err.cause = e;
        err.name = name;
        throw err;
    }

    try {
        stream.getTracks().forEach((t) => t.stop());
    } catch {
        /* ignore */
    }
}

/**
 * Twilio Voice SDK may call setSinkId('default'), which throws on some browsers
 * (InvalidArgumentError: Devices not found: default). Prefer a concrete audiooutput id.
 * Run after {@link primeMicrophoneForTwilio} when possible so enumerateDevices has stable labels.
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
