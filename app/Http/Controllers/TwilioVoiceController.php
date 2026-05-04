<?php

namespace App\Http\Controllers;

use App\Services\StaffPresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        return response()->json([
            'identity' => $identity,
            'token' => $this->makeVoiceAccessToken($identity),
            'dial_to' => config('services.twilio.admin_identity'),
        ]);
    }

    protected function makeVoiceAccessToken(string $identity): string
    {
        $token = new AccessToken(
            config('services.twilio.sid'),
            config('services.twilio.api_key'),
            config('services.twilio.api_secret'),
            3600,
            $identity
        );

        $voiceGrant = new VoiceGrant;
        $voiceGrant->setOutgoingApplicationSid(config('services.twilio.twiml_app_sid'));
        $voiceGrant->setIncomingAllow(true);
        $token->addGrant($voiceGrant);

        return $token->toJWT();
    }

    public function handleVoice(Request $request)
    {
        if (! $this->staffPresence->isCallRoutingAllowed()) {
            $response = new VoiceResponse;
            $response->say(
                'All emergency operators are currently busy. Please try again later, or use text messaging in the application.',
                ['voice' => 'alice']
            );
            $response->hangup();

            return response($response)->header('Content-Type', 'text/xml');
        }

        $response = new VoiceResponse;
        $dial = $response->dial();

        // Get caller info from the request parameters
        $callerInfo = $request->input('callerInfo');

        // Forward to admin with caller info as parameters
        $dial->client(config('services.twilio.admin_identity'), [
            'callerInfo' => $callerInfo,
        ]);

        return response($response)->header('Content-Type', 'text/xml');
    }
}
