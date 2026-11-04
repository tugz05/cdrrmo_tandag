/** Alarm for emergency alerts. Twilio plays the same file itself as the ringtone for incoming calls. */
export const ALARM_SOUND_URL = '/assets/sounds/alarm.mp3';

let audio = null;
let active = false;
let muted = false;
let waitingForGesture = false;

/** Browsers can block sound until the first interaction, so try again on that interaction. */
function retryOnFirstGesture() {
    if (waitingForGesture) {
        return;
    }
    waitingForGesture = true;
    const retry = () => {
        window.removeEventListener('pointerdown', retry, true);
        window.removeEventListener('keydown', retry, true);
        waitingForGesture = false;
        sync();
    };
    window.addEventListener('pointerdown', retry, true);
    window.addEventListener('keydown', retry, true);
}

function sync() {
    if (!audio) {
        if (!active) {
            return;
        }
        audio = new Audio(ALARM_SOUND_URL);
        audio.loop = true;
    }
    if (active && !muted) {
        audio.play().catch((e) => {
            if (e?.name === 'NotAllowedError') {
                retryOnFirstGesture();
            } else if (e?.name !== 'AbortError') {
                console.warn('[Alarm] could not play sound', e);
            }
        });
        return;
    }
    audio.pause();
    audio.currentTime = 0;
}

export function startAlarm() {
    active = true;
    sync();
}

export function stopAlarm() {
    active = false;
    sync();
}

/** Keeps the alarm out of a ringing or live voice call; it resumes afterwards if still unacknowledged. */
export function muteAlarm(value) {
    muted = value;
    sync();
}
