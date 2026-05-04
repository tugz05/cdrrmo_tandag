<?php

namespace App\Http\Controllers;

use App\Services\StaffPresenceService;
use App\Support\TwilioClientIdentity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\VoiceGrant;
use Twilio\TwiML\VoiceResponse;

class TwilioVoiceController extends Controller
{
    public function __construct(private StaffPresenceService $staffPresence) {}

    /**
     * Lightweight diagnostic endpoint: returns effective Twilio + presence config (no secrets).
     * Useful when config caching / .env mismatch causes voice routing issues (e.g. 31603).
     */
    public function health(Request $request): JsonResponse
    {
        $adminIdentityRaw = (string) config('services.twilio.admin_identity');
        $adminIdentity = TwilioClientIdentity::sanitize($adminIdentityRaw);
        $dispatchRing = TwilioClientIdentity::sanitize((string) config('call.dispatch_ring_group_client_name', 'dispatch'));

        $presenceRequired = (bool) config('call.require_staff_presence_for_voice_twiml', true);
        $requireVoiceReady = (bool) config('call.require_voice_client_ready', true);

        $availability = null;
        $availabilityError = null;
        try {
            $availability = $this->staffPresence->getCachedAvailabilitySnapshot();
        } catch (Throwable $e) {
            $availabilityError = $e->getMessage();
        }

        $webhookOrigin = rtrim(trim((string) config('services.twilio.webhook_public_origin', '')), '/');
        if ($webhookOrigin === '') {
            $appUrl = trim((string) config('app.url', ''));
            if ($appUrl !== '' && str_starts_with(strtolower($appUrl), 'https://')) {
                $parts = parse_url($appUrl);
                if (is_array($parts) && isset($parts['host'])) {
                    $scheme = isset($parts['scheme']) && is_string($parts['scheme']) ? $parts['scheme'] : 'https';
                    $host = $parts['host'];
                    $port = isset($parts['port']) ? ':'.$parts['port'] : '';
                    $webhookOrigin = rtrim($scheme.'://'.$host.$port, '/');
                }
            }
        }
        if ($webhookOrigin === '') {
            $webhookOrigin = rtrim($request->getSchemeAndHttpHost(), '/');
        }

        return response()->json([
            'ok' => true,
            'now' => now()->toIso8601String(),
            'app_env' => (string) config('app.env'),
            'app_url' => (string) config('app.url'),
            'twilio_webhook_origin_effective' => $webhookOrigin,
            'twilio' => [
                'account_sid_starts_with_AC' => str_starts_with((string) config('services.twilio.sid'), 'AC'),
                'api_key_starts_with_SK' => str_starts_with((string) config('services.twilio.api_key'), 'SK'),
                'twiml_app_sid' => (string) config('services.twilio.twiml_app_sid'),
                'admin_identity_raw' => $adminIdentityRaw,
                'admin_identity_sanitized' => $adminIdentity,
                'dispatch_ring_group_client_name' => $dispatchRing,
                'voice_home_region' => (string) config('services.twilio.voice_home_region'),
                'voice_sdk_edge' => (string) config('services.twilio.voice_sdk_edge'),
            ],
            'presence' => [
                'require_staff_presence_for_voice_twiml' => $presenceRequired,
                'require_voice_client_ready' => $requireVoiceReady,
                'availability' => $availability,
                'availability_error' => $availabilityError,
            ],
        ]);
    }

    /**
     * Public token URL (admin web + test pages). Identity is supplied in the query string.
     */
    public function generateToken(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user && $user->hasRole(['admin', 'super_admin'])) {
            $adminRaw = trim((string) config('services.twilio.admin_identity'));
            $identity = $adminRaw !== ''
                ? TwilioClientIdentity::sanitize($adminRaw)
                : TwilioClientIdentity::sanitize((string) $user->getAuthIdentifier());
        } else {
            $identity = TwilioClientIdentity::sanitize((string) $request->query('identity', 'guest'));
        }

        if ($configMessage = $this->twilioVoiceConfigurationMessage()) {
            return response()->json([
                'message' => $configMessage,
                'identity' => $identity,
            ], 503);
        }

        return response()->json([
            'identity' => $identity,
            'token' => $this->makeVoiceAccessToken($identity),
        ]);
    }

    /**
     * Mobile app (Flutter): JWT identity matches {@code ADMIN_IDENTITY} so inbound VoIP matches legacy
     * {@code handleVoice} {@code <Dial><Client>}. Use {@code dial_to} as {@code device.connect} {@code To}.
     */
    public function tokenForMobile(Request $request): JsonResponse
    {
        $user = $request->user();
        $adminRaw = trim((string) config('services.twilio.admin_identity'));
        $identity = $adminRaw !== ''
            ? TwilioClientIdentity::sanitize($adminRaw)
            : TwilioClientIdentity::sanitize((string) $user->getAuthIdentifier());

        if ($configMessage = $this->twilioVoiceConfigurationMessage()) {
            return response()->json([
                'message' => $configMessage,
                'identity' => $identity,
            ], 503);
        }

        return response()->json([
            'identity' => $identity,
            'token' => $this->makeVoiceAccessToken($identity),
            /** Same as JWT identity — pass as `device.connect({ params: { To: dial_to } })` for VoIP to `/twilio/voice`. */
            'dial_to' => $identity,
        ]);
    }

    /**
     * Human-readable reason why Voice JWT cannot be built (empty .env, wrong key type, etc.).
     * Missing or invalid values cause Twilio error 31000/53000 at device.register() in the browser.
     */
    protected function twilioVoiceConfigurationMessage(): ?string
    {
        $sid = (string) config('services.twilio.sid');
        $apiKey = (string) config('services.twilio.api_key');
        $apiSecret = (string) config('services.twilio.api_secret');
        $twimlAppSid = (string) config('services.twilio.twiml_app_sid');

        $issues = [];

        if ($sid === '') {
            $issues[] = 'TWILIO_ACCOUNT_SID is missing.';
        } elseif (! str_starts_with($sid, 'AC')) {
            $issues[] = 'TWILIO_ACCOUNT_SID must start with AC (Account SID).';
        }

        if ($apiKey === '') {
            $issues[] = 'TWILIO_API_KEY is missing — create an API Key under Twilio Console → Account → API keys & tokens (not the Auth Token).';
        } elseif (! str_starts_with($apiKey, 'SK')) {
            $issues[] = 'TWILIO_API_KEY must start with SK (API Key SID). If you pasted the Auth Token (starts with letters other than SK), create an API Key instead.';
        }

        if ($apiSecret === '') {
            $issues[] = 'TWILIO_API_SECRET is missing (the secret shown once when you create the API Key).';
        }

        if ($twimlAppSid === '') {
            $issues[] = 'TWIML_APP_SID is missing — Twilio Console → Voice → TwiML Apps → Create → copy Application SID (starts with AP).';
        } elseif (! str_starts_with($twimlAppSid, 'AP')) {
            $issues[] = 'TWIML_APP_SID must start with AP (TwiML Application SID).';
        }

        $adminIdentity = trim((string) config('services.twilio.admin_identity'));
        if ($adminIdentity === '') {
            $issues[] = 'ADMIN_IDENTITY is missing — required for VoIP `<Client>` routing and operator tokens; set it in .env.';
        }

        if ($issues === []) {
            return null;
        }

        return implode(' ', $issues);
    }

    protected function makeVoiceAccessToken(string $identity): string
    {
        $region = config('services.twilio.voice_home_region');
        $region = is_string($region) && $region !== '' ? $region : null;

        $token = new AccessToken(
            config('services.twilio.sid'),
            config('services.twilio.api_key'),
            config('services.twilio.api_secret'),
            3600, // 1h; refresh in browser via tokenWillExpire (Twilio max JWT TTL 24h)
            $identity,
            $region
        );

        $voiceGrant = new VoiceGrant;
        $voiceGrant->setOutgoingApplicationSid(config('services.twilio.twiml_app_sid'));
        $voiceGrant->setIncomingAllow(true);
        $token->addGrant($voiceGrant);

        return $token->toJWT();
    }

    /**
     * Local environment only: inspect Voice JWT shape (no secrets returned).
     * GET /twilio/token-debug?identity=5 — use when debugging Twilio 53000 / 31000.
     */
    public function tokenDebug(Request $request): JsonResponse
    {
        abort_unless(app()->environment('local'), 404);

        $identity = TwilioClientIdentity::sanitize((string) $request->query('identity', '5'));

        if ($msg = $this->twilioVoiceConfigurationMessage()) {
            return response()->json([
                'ok' => false,
                'message' => $msg,
            ], 503);
        }

        try {
            $jwt = $this->makeVoiceAccessToken((string) $identity);
            $parts = explode('.', $jwt);
            if (count($parts) < 2) {
                return response()->json(['ok' => false, 'error' => 'jwt_malformed'], 500);
            }

            $b64 = $parts[1];
            $b64 .= str_repeat('=', (4 - strlen($b64) % 4) % 4);
            $payload = json_decode(base64_decode(strtr($b64, '-_', '+/')), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => 'jwt_decode_failed',
                'detail' => $e->getMessage(),
            ], 500);
        }

        $voice = $payload['grants']['voice'] ?? null;

        return response()->json([
            'ok' => true,
            'identity' => $identity,
            'voice_home_region_env' => config('services.twilio.voice_home_region'),
            'jwt_iss_starts_with_SK' => isset($payload['iss']) && str_starts_with((string) $payload['iss'], 'SK'),
            'jwt_sub_starts_with_AC' => isset($payload['sub']) && str_starts_with((string) $payload['sub'], 'AC'),
            'voice_grant' => $voice,
            'outgoing_application_sid' => is_array($voice)
                ? ($voice['outgoing']['application_sid'] ?? null)
                : null,
        ]);
    }

    public function handleVoice(Request $request)
    {
        try {
            Log::info('Twilio handleVoice request', [
                'CallSid' => $request->input('CallSid'),
                'From' => $request->input('From'),
                'To' => $request->input('To'),
                'ApplicationSid' => $request->input('ApplicationSid'),
            ]);

            if (! $this->staffPresence->isCallRoutingAllowed()) {
                return $this->twimlResponse(function (VoiceResponse $twiml): void {
                    $twiml->say(
                        'All emergency operators are currently busy. Please try again later, or use text messaging in the application.',
                        ['voice' => 'alice']
                    );
                    $twiml->hangup();
                });
            }

            $adminIdentityRaw = trim((string) config('services.twilio.admin_identity'));
            if ($adminIdentityRaw === '') {
                Log::warning('Twilio handleVoice: ADMIN_IDENTITY is empty in .env');

                return $this->twimlResponse(function (VoiceResponse $twiml): void {
                    $twiml->say(
                        'Server configuration error. Please contact the administrator.',
                        ['voice' => 'alice']
                    );
                    $twiml->hangup();
                });
            }

            $adminIdentity = TwilioClientIdentity::sanitize($adminIdentityRaw);

            $callerInfo = $request->input('callerInfo');
            $callerInfoValue = null;
            if ($callerInfo !== null && $callerInfo !== '') {
                $callerInfoStr = is_string($callerInfo) ? $callerInfo : json_encode($callerInfo);
                $callerLen = strlen($callerInfoStr);
                if ($callerLen > 512) {
                    Log::notice('Twilio handleVoice: callerInfo truncated', [
                        'original_length' => $callerLen,
                    ]);
                    $callerInfoStr = substr($callerInfoStr, 0, 512);
                }
                $callerInfoValue = $callerInfoStr;
            }

            Log::info('Twilio handleVoice dial', [
                'admin_identity_sanitized' => $adminIdentity,
                'from_raw' => $request->input('From'),
                'to_raw' => $request->input('To'),
            ]);

            /*
             * Custom data must be <Parameter> children on <Client>, not arbitrary XML attributes.
             * Passing ['callerInfo' => ...] as the second arg to client() emits callerInfo="..." on <Client>,
             * which Twilio rejects with 12100 Document parse failure.
             */
            return $this->twimlResponse(function (VoiceResponse $twiml) use ($adminIdentity, $callerInfoValue): void {
                $dial = $twiml->dial();
                $client = $dial->client($adminIdentity);
                if ($callerInfoValue !== null && $callerInfoValue !== '') {
                    $client->parameter([
                        'name' => 'callerInfo',
                        'value' => $callerInfoValue,
                    ]);
                }
            });
        } catch (Throwable $e) {
            Log::error('Twilio handleVoice exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->twimlResponse(function (VoiceResponse $twiml): void {
                $twiml->say(
                    'A temporary error occurred. Please try again in a moment.',
                    ['voice' => 'alice']
                );
                $twiml->hangup();
            });
        }
    }

    /**
     * Dial status callback from Twilio (optional; legacy handleVoice uses a plain {@code <Dial>} with no action).
     */
    public function dialStatus(Request $request): Response
    {
        $dialStatus = (string) $request->input('DialCallStatus', '');
        $sip = $request->input('DialSipResponseCode') ?? $request->input('DialCallSIPResponseCode');

        $payload = [
            'CallSid' => $request->input('CallSid'),
            'DialCallSid' => $request->input('DialCallSid'),
            'DialCallStatus' => $dialStatus,
            'DialCallDuration' => $request->input('DialCallDuration'),
            'DialSipResponseCode' => $sip,
            'DialCallSIPResponseCode' => $request->input('DialCallSIPResponseCode'),
            'To' => $request->input('To'),
            'From' => $request->input('From'),
        ];

        // 603 / busy / no-answer on Client leg → browser often shows 31603 Decline.
        $dialStatusLower = strtolower($dialStatus);
        $failedLeg = in_array($dialStatusLower, ['busy', 'no-answer', 'failed', 'canceled'], true);
        if ($failedLeg || (string) $sip === '603') {
            Log::warning('Twilio dial status: Client leg did not complete (check 31603 / unregistered Client)', $payload);
        } else {
            Log::info('Twilio dial status callback', $payload);
        }

        return $this->emptyTwiMLResponse();
    }

    /**
     * Per-Client status callback (optional; legacy handleVoice does not set statusCallback on {@code <Client>}).
     */
    public function clientStatus(Request $request): Response
    {
        Log::info('Twilio client status callback', [
            'CallSid' => $request->input('CallSid'),
            'ParentCallSid' => $request->input('ParentCallSid'),
            'CallStatus' => $request->input('CallStatus'),
            'CallDuration' => $request->input('CallDuration'),
            'SipResponseCode' => $request->input('SipResponseCode') ?? $request->input('CallSipResponseCode'),
            'To' => $request->input('To'),
            'From' => $request->input('From'),
        ]);

        return $this->emptyTwiMLResponse();
    }

    /**
     * Twilio may treat non-TwiML or missing Content-Type on Voice webhooks as delivery failures (error 11200).
     *
     * @param  callable(VoiceResponse):void  $builder
     */
    protected function twimlResponse(callable $builder): Response
    {
        $response = new VoiceResponse;
        $builder($response);

        return response((string) $response)
            ->header('Content-Type', 'text/xml; charset=utf-8');
    }

    /**
     * Minimal valid TwiML for status callbacks (Dial action / Client status) so Content-Type is always correct.
     */
    protected function emptyTwiMLResponse(): Response
    {
        return $this->twimlResponse(static function (VoiceResponse $twiml): void {
            // Empty <Response> — acknowledges webhook; see Twilio Dial `action` docs.
        });
    }
}
