<?php

namespace App\Http\Controllers;

use App\Services\StaffPresenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffPresenceController extends Controller
{
    public function __construct(private StaffPresenceService $staffPresence) {}

    public function heartbeat(Request $request): JsonResponse
    {
        if (! $request->user()->isVoiceDispatchOperator()) {
            return response()->json([
                'success' => false,
                'code' => 'NOT_VOICE_DISPATCH_OPERATOR',
                'message' => 'Only dispatch staff may send voice presence heartbeats.',
            ], 403);
        }

        $voiceReady = $request->has('twilio_voice_ready')
            ? $request->boolean('twilio_voice_ready')
            : null;

        $this->staffPresence->touchHeartbeat($request->user(), $voiceReady);

        return response()->json(['status' => 'ok']);
    }

    public function callAnswered(Request $request): JsonResponse
    {
        $this->staffPresence->markBusy($request->user());

        return response()->json(['status' => 'ok']);
    }

    public function callFinished(Request $request): JsonResponse
    {
        $this->staffPresence->markAvailable($request->user());

        return response()->json(['status' => 'ok']);
    }
}
