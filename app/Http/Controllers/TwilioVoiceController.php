<?php

namespace App\Http\Controllers;

use App\Services\StaffPresenceService;
use Illuminate\Http\Request;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\VoiceGrant;
use Twilio\TwiML\VoiceResponse;

class TwilioVoiceController extends Controller
{
    public function __construct(private StaffPresenceService $staffPresence) {}

    public function generateToken(Request $request)
    {
        $identity = $request->query('identity', 'guest');

        $token = new AccessToken(
            env('TWILIO_ACCOUNT_SID'),
            env('TWILIO_API_KEY'),
            env('TWILIO_API_SECRET'),
            3600,
            $identity
        );

        $voiceGrant = new VoiceGrant;
        $voiceGrant->setOutgoingApplicationSid(env('TWIML_APP_SID'));
        $voiceGrant->setIncomingAllow(true);
        $token->addGrant($voiceGrant);

        return response()->json([
            'identity' => $identity,
            'token' => $token->toJWT(),
        ]);
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
        $dial->client(env('ADMIN_IDENTITY'), [
            'callerInfo' => $callerInfo,
        ]);

        return response($response)->header('Content-Type', 'text/xml');
    }
}
