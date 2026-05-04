<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\StaffPresenceService;
use App\Support\TwilioClientIdentity;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class CallAvailabilityController extends Controller
{
    public function __construct(private StaffPresenceService $staffPresence) {}

    public function show(): JsonResponse
    {
        try {
            $snap = $this->staffPresence->getCachedAvailabilitySnapshot();
        } catch (Throwable $e) {
            $failOpen = (bool) config('call.presence_fail_open', false);
            Log::error('Call availability: staff presence lookup failed', [
                'message' => $e->getMessage(),
                'fail_open' => $failOpen,
            ]);

            if ($failOpen) {
                $adminId = TwilioClientIdentity::sanitize((string) config('services.twilio.admin_identity'));
                $snap = [
                    'can_connect' => true,
                    'available_operators' => 1,
                    'total_operators' => 1,
                    'block_reason' => null,
                    'heartbeat_ttl_seconds' => (int) config('call.staff_heartbeat_ttl', 90),
                    'operator_twilio_client_identity' => $adminId,
                    'resolution_hint' => 'Presence check failed; proceeding because CALL_PRESENCE_FAIL_OPEN=true.',
                ];
            } else {
                return response()->json([
                    'can_connect' => false,
                    'code' => 'PRESENCE_CHECK_FAILED',
                    'message' => 'Temporary error checking operator availability. Please try again in a moment.',
                ], 503);
            }
        }

        $body = array_merge(
            $snap,
            [
                'code' => $snap['can_connect'] ? 'OK' : 'ALL_OPERATORS_BUSY',
                'message' => $snap['can_connect']
                    ? 'An operator is available to take your call.'
                    : 'All emergency operators are currently busy. Please try again in a few minutes or use a text report if available.',
                'twilio_dial_identity' => $snap['operator_twilio_client_identity'] ?? '',
                'twilio_note' => 'Mobile outbound calls TwiML-dial this Client name; dispatch must register Twilio.Device with the same identity.',
            ]
        );

        $status = $snap['can_connect'] ? 200 : 503;

        return response()->json($body, $status);
    }
}
