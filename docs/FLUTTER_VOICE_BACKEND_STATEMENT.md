# Backend statement for Flutter — Twilio Programmable Voice

This document describes **what the Laravel API implements** so the Flutter apps (citizen + dispatch) can integrate Twilio Voice SDK correctly. Base path: `{BASE_URL}/api/v1` unless noted.

---

## 1. Voice access token (citizen + staff)

**`GET /api/v1/voice/token`**  
**Auth:** `Authorization: Bearer <Sanctum token>`  
**Throttle:** 60/min per user (approx.)

### What the backend does

- Builds a **Twilio Access Token (JWT)** for the Twilio Voice SDK (`setTokens` / equivalent).
- Sets JWT **identity** to the logged-in user’s id, **normalized** the same way everywhere (`TwilioClientIdentity::sanitize`): alphanumeric + underscore, max length Twilio allows. **Use this same string as the Client “from” identity** when registering the SDK.
- Sets **VoiceGrant** `outgoingApplicationSid` to the TwiML App SID from env (`TWIML_APP_SID`).
- **`incoming_allow` in JSON** (bool):
  - **`false`** for **citizens** (Laratrust role `user` only): **outbound emergency calls only**; no incoming Client grant in the JWT (matches Twilio’s “outgoing only” shape).
  - **`true`** for **dispatch operators** (Laratrust roles **`staff`**, **`admin`**, or **`super_admin`**): they can **receive** inbound `<Client>` legs from the TwiML app (required for FCM + incoming ring on staff devices).

### Response fields the Flutter client should rely on

| Field | Meaning |
|--------|--------|
| `token` | JWT string for the Voice SDK. |
| `dial_to` | **Opaque string** — pass as the SDK **`To`** parameter for `Voice.connect` / `place()` when the citizen places an outbound call. Same value as `twilio_dial_identity` in availability when polled together. |
| `identity` | Sanitized user id string; must match SDK registration identity. |
| `incoming_allow` | Whether this JWT allows **incoming** Client calls (`true` staff/admin/super_admin, `false` citizen). |
| `twilio_note` | Human-readable contract hint (not for UI logic). |
| `success`, `message`, `data` | Envelope pattern; `data` duplicates key fields for older parsers. |

On misconfiguration (missing Twilio env, wrong SID shapes, etc.) the endpoint returns **503** with `success: false` and a `message` explaining what is missing.

---

## 2. Staff presence (dispatch app)

**`POST /api/v1/staff/heartbeat`**  
**Auth:** Bearer Sanctum  

### Who may call it

Only users with Laratrust role **`staff`**, **`admin`**, or **`super_admin`**.  
Citizens receive **403** with `code: NOT_VOICE_DISPATCH_OPERATOR`.

### Body (JSON)

- **`twilio_voice_ready`** (optional bool):  
  - `true` — set when the Twilio Voice SDK has **registered** (Device ready); updates `voice_client_ready_at`.  
  - `false` — explicitly clear voice-ready.  
  - omit — do not change voice-ready timestamp.

### Behavior

- Updates **`last_heartbeat_at`** (and optional voice-ready) in `staff_presences` for that user.
- Operators are considered **online for voice** only if the heartbeat is within **`CALL_STAFF_HEARTBEAT_TTL`** seconds (default **90**), and (when `CALL_REQUIRE_VOICE_CLIENT_READY=true`) `voice_client_ready_at` is also fresh.

**Flutter dispatch:** ping about every **30s**; include `twilio_voice_ready: true` after the SDK is registered so `GET /api/v1/call/availability` can return `can_connect: true`.

---

## 3. Call availability (citizen + staff)

**`GET /api/v1/call/availability`**  
**Auth:** none (public, throttled 120/min)

### What it reflects

- Same **operator pool** as TwiML: users with roles **`staff`**, **`admin`**, **`super_admin`** who pass heartbeat + voice-ready rules.
- **`can_connect`**: `true` only if at least one operator would be dialed under TwiML rules (not only “someone exists in DB”).
- **`available_operators`**, **`total_operators`**, **`message`**, **`code`** (`OK` vs `ALL_OPERATORS_BUSY`).
- **`twilio_dial_identity`**: should match **`dial_to`** from `GET /api/v1/voice/token` if both are called back-to-back.
- **`twiml_dial_operator_identities`**: list of sanitized Client identities TwiML would try to ring (diagnostics / UI).

**`block_reason`:** e.g. `NO_VOICE_DISPATCH_USERS` (no one with dispatch roles), `NO_OPERATOR_ONLINE` (none within TTL / voice-ready).

---

## 4. Twilio webhooks (server-side; Flutter does not call these)

- **`GET|POST {PUBLIC_ORIGIN}/twilio/voice`** — TwiML App Voice URL: returns `<Dial><Client>…</Client></Dial>` to ring **online** dispatch operators; identities **match** JWT identities from §1.
- **`GET|POST …/twilio/voice/dial-status`** and **`…/twilio/voice/client-status`** — optional logging / dial retry support.
- **CSRF:** excluded for `twilio/*`.
- **Security:** when enabled (default **on** outside `local`/`testing`), requests must include valid **`X-Twilio-Signature`** for the **exact public URL** (`TWILIO_WEBHOOK_PUBLIC_ORIGIN` + path) and form params, using **`TWILIO_AUTH_TOKEN`**.

Flutter only needs to ensure **TwiML App Voice URL** in Twilio Console matches the deployed **`https://YOUR_DOMAIN/twilio/voice`**.

---

## 5. List all SIRs (staff / admin only)

**`GET /api/v1/situational-incident-reports/history/all`**  
**Auth:** Bearer — **`staff`**, **`admin`**, or **`super_admin`** only.

---

## 6. Twilio Console (ops checklist)

1. TwiML App **Voice URL** = `https://YOUR_DOMAIN/twilio/voice` (same host as `TWILIO_WEBHOOK_PUBLIC_ORIGIN` if you use a reverse proxy).
2. **API Key** (SK…) + secret for JWT; **Auth token** for webhook signature validation.
3. **Android FCM push credential** for staff incoming — configured in **Twilio**; Laravel supplies correct **incoming** grant for dispatch users (§1).

More detail: `docs/twilio-programmable-voice-verification.md`.

---

## Summary one-liner for Flutter leads

**Citizens** call `GET /voice/token` → `setTokens(token)`, register as `identity`, connect with **`To: dial_to`**, **`incoming_allow: false`**. **Dispatch staff** log in with a **`staff`/`admin`/`super_admin`** account, call **`POST /staff/heartbeat`** every ~30s with **`twilio_voice_ready: true`** after SDK register, use **`GET /call/availability`** before UX, and use **`GET /voice/token`** with **`incoming_allow: true`** so incoming Client + FCM can work. Twilio dials the same Client identities the JWT uses.
