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

        return response()->json([
            'ok' => true,
            'now' => now()->toIso8601String(),
            'app_env' => (string) config('app.env'),
            'app_url' => (string) config('app.url'),
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
            $identity = TwilioClientIdentity::sanitize((string) $user->getAuthIdentifier());
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
     * Mobile app (Flutter): Twilio client identity = authenticated user's id (string).
     * Admin inbound UI parses `From` as `client:<id>` to load caller info.
     */
    public function tokenForMobile(Request $request): JsonResponse
    {
        $user = $request->user();
        $identity = TwilioClientIdentity::sanitize((string) $user->getAuthIdentifier());

        if ($configMessage = $this->twilioVoiceConfigurationMessage()) {
            return response()->json([
                'message' => $configMessage,
                'identity' => $identity,
            ], 503);
        }

        $dispatchRing = TwilioClientIdentity::sanitize((string) config('call.dispatch_ring_group_client_name', 'dispatch'));
        $legacyAdmin = TwilioClientIdentity::sanitize((string) config('services.twilio.admin_identity'));

        return response()->json([
            'identity' => $identity,
            'token' => $this->makeVoiceAccessToken($identity),
            /** Pass as `device.connect({ params: { To: dial_to } })` — expanded on `/twilio/voice` to all voice-ready operators. */
            'dial_to' => $dispatchRing,
            'operator_twilio_client_identity' => $dispatchRing,
            'voice_ready_operator_twilio_identities' => $this->staffPresence->voiceReadyOperatorIdentities(),
            'legacy_admin_twilio_client_identity' => $legacyAdmin,
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

        $adminIdentity = (string) config('services.twilio.admin_identity');
        if ($adminIdentity === '') {
            $issues[] = 'ADMIN_IDENTITY is missing — used as a fallback Twilio Client name when no voice-ready operators are found at dial time; set it in .env (often a shared test identity).';
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
        $presenceRequired = (bool) config('call.require_staff_presence_for_voice_twiml', true);
        $failOpen = filter_var((string) config('call.presence_fail_open', app()->environment('local')), FILTER_VALIDATE_BOOL);

        try {
            $routingAllowed = true;
            $availability = null;
            if ($presenceRequired) {
                try {
                    $availability = $this->staffPresence->getCachedAvailabilitySnapshot();
                    $routingAllowed = (bool) ($availability['can_connect'] ?? false);
                } catch (Throwable $e) {
                    Log::error('Twilio handleVoice: staff presence lookup failed', [
                        'message' => $e->getMessage(),
                        'fail_open' => $failOpen,
                    ]);
                    $routingAllowed = $failOpen;
                }
            }

            Log::info('Twilio handleVoice request', [
                'CallSid' => $request->input('CallSid'),
                'From' => $request->input('From'),
                'To' => $request->input('To'),
                'ApplicationSid' => $request->input('ApplicationSid'),
                'staff_presence_required_for_twiml' => $presenceRequired,
                'staff_routing_allowed' => $routingAllowed,
                'staff_snapshot' => $availability,
            ]);

            $adminIdentity = TwilioClientIdentity::sanitize((string) config('services.twilio.admin_identity'));

            if ($adminIdentity === '') {
                Log::warning('Twilio handleVoice: ADMIN_IDENTITY is not configured');

                return $this->twimlResponse(function (VoiceResponse $twiml): void {
                    $twiml->say(
                        'Server configuration error. Please contact the administrator.',
                        ['voice' => 'alice']
                    );
                    $twiml->hangup();
                });
            }

            if ($presenceRequired && ! $routingAllowed) {
                Log::notice('Twilio handleVoice: blocked by staff presence (busy TwiML → gateway hangup on caller)', [
                    'hint' => 'Keep /admin/dashboard open (heartbeat), or set CALL_REQUIRE_STAFF_PRESENCE_FOR_VOICE_TWIML=false for local Client-only tests.',
                ]);

                return $this->twimlResponse(function (VoiceResponse $twiml): void {
                    $twiml->say(
                        'All emergency operators are currently busy. Please try again later, or use text messaging in the application.',
                        ['voice' => 'alice']
                    );
                    $twiml->hangup();
                });
            }

            $callerInfo = $request->input('callerInfo');
            $customParams = [];
            if ($callerInfo !== null && $callerInfo !== '') {
                $callerInfoStr = is_string($callerInfo)
                    ? $callerInfo
                    : json_encode($callerInfo);
                $callerLen = strlen($callerInfoStr);
                if ($callerLen > 512) {
                    Log::notice('Twilio handleVoice: callerInfo truncated (avoid oversized TwiML/custom params)', [
                        'original_length' => $callerLen,
                    ]);
                    $callerInfoStr = substr($callerInfoStr, 0, 512);
                }
                $customParams['callerInfo'] = $callerInfoStr;
            }

            $ringGroupIdentity = TwilioClientIdentity::sanitize((string) config('call.dispatch_ring_group_client_name', 'dispatch'));
            $clientIdentities = $this->resolveOutboundDialClientIdentities($request, $adminIdentity, $ringGroupIdentity);
            $excludeIds = $this->callerClientIdentitiesToExcludeFromDial($request);
            $clientIdentities = $this->excludeClientIdentitiesFromDialTargets($clientIdentities, $excludeIds);
            $clientIdentities = $this->dialTargetsWithCallerFallback($clientIdentities, $excludeIds, $adminIdentity, $request);

            if ($clientIdentities === []) {
                Log::warning('Twilio handleVoice: no Client identities to dial after excluding caller / fallbacks', [
                    'From' => $request->input('From'),
                    'exclude' => $excludeIds,
                ]);

                return $this->twimlResponse(function (VoiceResponse $twiml): void {
                    $twiml->say(
                        'No emergency operator is available to take this call. Please try again later.',
                        ['voice' => 'alice']
                    );
                    $twiml->hangup();
                });
            }

            Log::info('Twilio handleVoice dial', [
                'client_identities' => $clientIdentities,
                'exclude_caller_client_identities' => $excludeIds,
                'from_To_param' => trim((string) $request->input('To', '')) !== '',
                'to_raw' => $request->input('To'),
                'from_raw' => $request->input('From'),
                'admin_identity_config' => (string) config('services.twilio.admin_identity'),
                'admin_identity_sanitized' => $adminIdentity,
                'dispatch_ring_group' => $ringGroupIdentity,
            ]);

            return $this->twimlResponse(function (VoiceResponse $twiml) use ($clientIdentities, $customParams): void {
                $dial = $twiml->dial('', [
                    'timeout' => 60,
                    'answerOnBridge' => true,
                    // Relative so Twilio posts back to the same public host that served /twilio/voice (ignores APP_URL).
                    'action' => '/twilio/voice/dial-status',
                    'method' => 'POST',
                ]);
                $clientAttrs = [
                    'statusCallbackEvent' => 'initiated ringing answered completed',
                    'statusCallback' => '/twilio/voice/client-status',
                    'statusCallbackMethod' => 'POST',
                ];
                foreach ($clientIdentities as $clientIdentity) {
                    $client = $dial->client($clientIdentity, $clientAttrs);
                    foreach ($customParams as $name => $value) {
                        $client->parameter([
                            'name' => (string) $name,
                            'value' => (string) $value,
                        ]);
                    }
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
     * Dial status callback from Twilio.
     * Configured via <Dial action="..."> in handleVoice().
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

        // Twilio expects valid TwiML or empty 200; empty is sufficient.
        return response('', 200);
    }

    /**
     * Per-Client status callback (initiated/ringing/answered/completed) from Twilio.
     * Configured via <Client statusCallback="..."> in handleVoice().
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

        return response('', 200);
    }

    /**
     * Resolve which Twilio Voice Client identities to ring for this parent call.
     *
     * Twilio posts here for (a) Voice JS SDK outbound via a TwiML App — {@code From} is {@code client:IDENTITY},
     * and {@code To} is the connect target; (b) inbound PSTN/SIP to a purchased number — {@code From} is a phone
     * number and {@code To} is your Twilio number. In case (b), {@code To} must never be interpreted as a
     * {@code <Client>} name or Twilio returns an application error.
     *
     * @return list<string>
     */
    protected function resolveOutboundDialClientIdentities(Request $request, string $adminIdentity, string $ringGroupIdentity): array
    {
        $from = trim((string) $request->input('From', ''));
        $fromIsVoiceSdkClient = $from !== '' && str_starts_with(strtolower($from), 'client:');

        if (! $fromIsVoiceSdkClient) {
            return $this->voiceReadyOperatorsOrAdminFallback($adminIdentity);
        }

        $requestedToRaw = trim((string) $request->input('To', ''));
        $requestedTo = $requestedToRaw !== '' ? TwilioClientIdentity::sanitize($requestedToRaw) : '';

        $broadcast = $requestedTo === ''
            || $requestedTo === $ringGroupIdentity
            || ($adminIdentity !== '' && $requestedTo === $adminIdentity);

        if ($broadcast) {
            return $this->voiceReadyOperatorsOrAdminFallback($adminIdentity);
        }

        $ready = $this->staffPresence->voiceReadyOperatorIdentities();
        if (in_array($requestedTo, $ready, true)) {
            return [$requestedTo];
        }

        Log::notice('Twilio handleVoice: To not in voice-ready set; using ring group', [
            'requested_to' => $requestedTo,
        ]);

        return $this->voiceReadyOperatorsOrAdminFallback($adminIdentity);
    }

    /**
     * @return list<string>
     */
    protected function voiceReadyOperatorsOrAdminFallback(string $adminIdentity): array
    {
        $targets = $this->staffPresence->voiceReadyOperatorIdentities();
        if ($targets !== []) {
            return $targets;
        }

        return $adminIdentity !== '' ? [$adminIdentity] : [];
    }

    /**
     * Twilio parent leg From is {@code client:IDENTITY} for SDK calls — never dial that same Client
     * or the call fails (31603 / "application error") because the origin endpoint cannot be the dial target.
     *
     * @return list<string> sanitized identities to omit from {@code <Dial><Client>}
     */
    protected function callerClientIdentitiesToExcludeFromDial(Request $request): array
    {
        $out = [];
        $from = trim((string) $request->input('From', ''));
        if ($from !== '' && str_starts_with(strtolower($from), 'client:')) {
            $rest = trim(substr($from, strlen('client:')));
            if ($rest !== '') {
                $out[] = TwilioClientIdentity::sanitize($rest);
            }
        }

        $raw = $request->input('callerInfo');
        if (is_string($raw) && $raw !== '') {
            try {
                /** @var mixed $decoded */
                $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded) && array_key_exists('userId', $decoded)) {
                    $out[] = TwilioClientIdentity::sanitize((string) $decoded['userId']);
                }
            } catch (Throwable) {
                // ignore malformed callerInfo
            }
        }

        $out = array_values(array_unique(array_filter($out, static fn (string $s): bool => $s !== '')));

        return $out;
    }

    /**
     * @param  list<string>  $targets
     * @param  list<string>  $excludeSanitized
     * @return list<string>
     */
    protected function excludeClientIdentitiesFromDialTargets(array $targets, array $excludeSanitized): array
    {
        if ($excludeSanitized === []) {
            return array_values(array_unique($targets));
        }

        $exc = array_flip($excludeSanitized);
        $out = [];
        foreach ($targets as $t) {
            if (! isset($exc[$t])) {
                $out[] = $t;
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * If every voice-ready operator was the caller's own Client identity, fall back to ADMIN_IDENTITY
     * when it is a different identity so TwiML still has at least one {@code <Client>} noun.
     *
     * @param  list<string>  $targets
     * @param  list<string>  $excludeSanitized
     * @return list<string>
     */
    protected function dialTargetsWithCallerFallback(array $targets, array $excludeSanitized, string $adminIdentity, Request $request): array
    {
        if ($targets !== []) {
            return $targets;
        }

        if ($adminIdentity === '') {
            return [];
        }

        foreach ($excludeSanitized as $ex) {
            if ($ex === $adminIdentity) {
                Log::notice('Twilio handleVoice: cannot route — only dial targets match the caller Client identity and ADMIN_IDENTITY matches the caller', [
                    'From' => $request->input('From'),
                    'admin_identity_sanitized' => $adminIdentity,
                ]);

                return [];
            }
        }

        Log::notice('Twilio handleVoice: all ring-group targets matched caller identity; dialing ADMIN_IDENTITY fallback', [
            'From' => $request->input('From'),
            'admin_identity_sanitized' => $adminIdentity,
        ]);

        return [$adminIdentity];
    }

    /**
     * @param  callable(VoiceResponse):void  $builder
     */
    protected function twimlResponse(callable $builder): Response
    {
        $response = new VoiceResponse;
        $builder($response);

        return response((string) $response)
            ->header('Content-Type', 'text/xml; charset=utf-8');
    }
}
