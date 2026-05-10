# Twilio Programmable Voice — verification checklist

Use this after configuring `.env` / Twilio Console. Public base URL for webhooks must match **`TWILIO_WEBHOOK_PUBLIC_ORIGIN`** (or **`APP_URL`** when HTTPS) so `X-Twilio-Signature` validation uses the same string Twilio signs.

## Console

1. **TwiML App** (Voice) → Request URL: `https://YOUR_DOMAIN/twilio/voice` (HTTP GET or POST, as deployed).
2. **API Keys** → create key; set `TWILIO_API_KEY` + `TWILIO_API_SECRET` (not the Auth Token for JWT).
3. **Auth Token** → set `TWILIO_AUTH_TOKEN` (required when webhook signature validation is enabled).
4. **FCM (Android staff incoming)** → Twilio Console Push Credentials; staff devices use tokens with `incoming_allow: true` from `GET /api/v1/voice/token`.

## Laravel env (minimal)

- `TWILIO_ACCOUNT_SID`, `TWILIO_API_KEY`, `TWILIO_API_SECRET`, `TWIML_APP_SID`, `ADMIN_IDENTITY`
- `TWILIO_WEBHOOK_PUBLIC_ORIGIN=https://YOUR_DOMAIN` (no trailing slash)
- Production/staging: `TWILIO_VALIDATE_WEBHOOK_SIGNATURE=true` (default is off in `local` / `testing` only)

## End-to-end

1. **Staff online** — Log in as `staff`, `admin`, or `super_admin` on the Flutter dispatch app. Every ~30s call `POST /api/v1/staff/heartbeat` with JSON `twilio_voice_ready: true` after the Voice SDK registers.
2. **Availability** — `GET /api/v1/call/availability` → `can_connect` true when at least one operator has a heartbeat inside **`CALL_STAFF_HEARTBEAT_TTL`** (default 90s) and, if `CALL_REQUIRE_VOICE_CLIENT_READY=true`, a fresh `voice_client_ready_at`.
3. **Citizen token** — Citizen Bearer token → `GET /api/v1/voice/token` → JSON includes `token`, `dial_to`, `incoming_allow: false`. SDK `From` identity = sanitized `users.id` (same as JWT `sub` / grant identity).
4. **Place call** — Citizen app `Voice.connect` / `place` with `To` = `dial_to` from step 3.
5. **Webhook** — Twilio POSTs to `/twilio/voice`; Laravel returns TwiML `<Dial><Client>…</Client></Dial>` for each voice-ready operator identity (matching staff JWT identities). If nobody is in TTL, TwiML plays busy / hangs up (and `can_connect` should have been false).
6. **Staff rings** — A staff device with matching Client identity and recent heartbeat receives the incoming Client call (push + SDK when FCM is configured in Twilio).

## Optional callbacks

- `/twilio/voice/dial-status` — Dial `action` (logging / retry).
- `/twilio/voice/client-status` — per-`Client` leg logging.

Both validate `X-Twilio-Signature` when enabled, same as `/twilio/voice`.
