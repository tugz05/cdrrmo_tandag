/**
 * Twilio Voice JS SDK helpers: edge, token refresh, connect param size, structured errors.
 * @see https://www.twilio.com/docs/voice/sdks/javascript
 */

/** Documented limit for custom parameters on outbound connect (leave margin for keys/encoding). */
export const TWILIO_CONNECT_PARAMS_MAX_BYTES = 760;

/**
 * Align with App\Support\TwilioClientIdentity: never send `client:foo` as `To`.
 * TwiML `<Client>` expects the bare identity (wrong form contributes to 31603).
 *
 * @param {unknown} identity
 * @returns {string}
 */
export function sanitizeClientDialIdentity(identity) {
    let s = String(identity ?? '').trim();
    if (s === '') {
        return 'guest';
    }
    const lower = s.toLowerCase();
    if (lower.startsWith('client:')) {
        s = s.slice('client:'.length).trim();
    }
    s = s.replace(/[^A-Za-z0-9_]/g, '_');
    s = s.slice(0, 256);
    return s !== '' ? s : 'guest';
}

/**
 * Base Device constructor options; omit invalid empty edge (dynamic edge breaks signaling).
 *
 * @param {object} [overrides]
 * @param {string} [overrides.edge] e.g. singapore, ashburn, sydney — must match regional deployment
 * @param {string} [overrides.logLevel]
 * @param {boolean} [overrides.closeProtection]
 */
export function createTwilioDeviceOptions(overrides = {}) {
    const { edge, logLevel = 'warn', closeProtection } = overrides;
    /** @type {Record<string, unknown>} */
    const out = {
        codecPreferences: ['opus', 'pcmu'],
        logLevel,
        // Twilio JS SDK: improves error specificity (avoids many failures surfacing only as 31005).
        enableImprovedSignalingErrorPrecision: true,
    };
    if (typeof closeProtection === 'boolean') {
        out.closeProtection = closeProtection;
    }
    const e = edge != null ? String(edge).trim() : '';
    if (e !== '') {
        out.edge = e;
    }
    return out;
}

/**
 * @param {import('@twilio/voice-sdk').Device} device
 * @param {string} identity passed to /twilio/token?identity=
 */
export function attachTokenWillExpireHandler(device, identity) {
    if (!device || !identity) {
        return;
    }
    const id = String(identity);
    device.on('tokenWillExpire', async () => {
        try {
            const res = await fetch(`/twilio/token?identity=${encodeURIComponent(id)}`);
            const data = await res.json();
            if (data && typeof data.token === 'string' && data.token.length) {
                await device.updateToken(data.token);
            }
        } catch (e) {
            console.error('[Twilio] tokenWillExpire: failed to refresh token', e);
        }
    });
}

/**
 * @param {Record<string, string | number | boolean | undefined | null>} params
 * @returns {number} UTF-8 byte size of serialized custom param payload (rough)
 */
export function estimateConnectParamsBytes(params) {
    if (typeof TextEncoder === 'undefined') {
        return JSON.stringify(params).length;
    }
    const enc = new TextEncoder();
    let n = 0;
    for (const [k, v] of Object.entries(params)) {
        n += enc.encode(String(k)).length;
        n += enc.encode(String(v ?? '')).length;
    }
    return n;
}

/**
 * Build outbound Client dial params; trims payload if near Twilio custom-parameter limits.
 *
 * @param {string} toIdentity ADMIN_IDENTITY / dial target
 * @param {{ userId: number, reportId?: number, error?: string }} data
 */
export function buildClientDialParams(toIdentity, data) {
    const to = sanitizeClientDialIdentity(toIdentity);
    let callerInfo = JSON.stringify({
        userId: data.userId,
        ...(data.reportId != null ? { reportId: data.reportId } : {}),
        ...(data.error ? { error: String(data.error).slice(0, 120) } : {}),
    });
    const params = { To: to, callerInfo };
    if (estimateConnectParamsBytes(params) > TWILIO_CONNECT_PARAMS_MAX_BYTES) {
        callerInfo = JSON.stringify({ userId: data.userId });
    }
    return { To: to, callerInfo };
}

/**
 * @param {unknown} error
 */
export function logTwilioErrorDetails(context, error) {
    const o = error && typeof error === 'object' ? error : {};
    const rec = {
        code: o.code,
        name: o.name,
        message: o.message,
        description: o.description,
        explanation: o.explanation,
    };
    if (Array.isArray(o.causes)) {
        rec.causes = o.causes;
    }
    if (Array.isArray(o.solutions)) {
        rec.solutions = o.solutions;
    }
    console.error(`[Twilio] ${context}:`, rec, error);
}
