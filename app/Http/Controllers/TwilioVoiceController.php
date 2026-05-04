<?php

namespace App\Http\Controllers;

use App\Services\StaffPresenceService;
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
     * Public token URL (admin web + test pages). Identity is supplied in the query string.
     */
    public function generateToken(Request $request): JsonResponse
    {
        $identity = $request->query('identity', 'guest');

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
        $identity = (string) $user->getAuthIdentifier();

        if ($configMessage = $this->twilioVoiceConfigurationMessage()) {
            return response()->json([
                'message' => $configMessage,
                'identity' => $identity,
            ], 503);
        }

        $operatorIdentity = (string) config('services.twilio.admin_identity');

        return response()->json([
            'identity' => $identity,
            'token' => $this->makeVoiceAccessToken($identity),
            /** TwiML `<Dial><Client>` target — dispatch must register Twilio with this exact Client name or calls get 31603. */
            'dial_to' => $operatorIdentity,
            'operator_twilio_client_identity' => $operatorIdentity,
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
            $issues[] = 'ADMIN_IDENTITY is missing — pick a Twilio Client name for operators (must match the identity used when admins load /twilio/token).';
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

        $identity = $request->query('identity', '5');

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
        $routingAllowed = $this->staffPresence->isCallRoutingAllowed();

        Log::info('Twilio handleVoice request', [
            'CallSid' => $request->input('CallSid'),
            'From' => $request->input('From'),
            'To' => $request->input('To'),
            'ApplicationSid' => $request->input('ApplicationSid'),
            'staff_presence_required_for_twiml' => $presenceRequired,
            'staff_routing_allowed' => $routingAllowed,
        ]);

        try {
            $adminIdentity = (string) config('services.twilio.admin_identity');

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

            $requestedTo = trim((string) $request->input('To', ''));
            $clientIdentity = $requestedTo !== '' ? $requestedTo : $adminIdentity;

            Log::info('Twilio handleVoice dial', [
                'client_identity' => $clientIdentity,
                'from_To_param' => $requestedTo !== '',
            ]);

            return $this->twimlResponse(function (VoiceResponse $twiml) use ($clientIdentity, $customParams): void {
                $dial = $twiml->dial('', [
                    'timeout' => 60,
                    'answerOnBridge' => true,
                ]);
                // Custom data must be <Parameter> children per Twilio Voice docs — not XML attributes on <Client>,
                // or Twilio may fail the call with a generic "application error" prompt.
                $client = $dial->client($clientIdentity, []);
                foreach ($customParams as $name => $value) {
                    $client->parameter([
                        'name' => (string) $name,
                        'value' => (string) $value,
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
