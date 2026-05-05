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
            /*
             * Uncached snapshot so twilio_dial_identity, can_connect, and counts stay aligned with
             * GET /api/v1/voice/token (dial_to) and TwiML at /twilio/voice (no availability JSON cache skew).
             */
            $snap = $this->staffPresence->getAvailabilitySnapshot();
        } catch (Throwable $e) {
            $failOpen = (bool) config('call.presence_fail_open', false);
            Log::error('Call availability: staff presence lookup failed', [
                'message' => $e->getMessage(),
                'fail_open' => $failOpen,
            ]);

            if ($failOpen) {
                $adminRaw = trim((string) config('services.twilio.admin_identity'));
                $adminId = TwilioClientIdentity::sanitize($adminRaw !== '' ? $adminRaw : (string) config('services.twilio.admin_identity'));
                $dispatchRing = TwilioClientIdentity::sanitize((string) config('call.dispatch_ring_group_client_name', 'dispatch'));
                $ttl = max(15, (int) config('call.staff_heartbeat_ttl', 90));
                $snap = [
                    'can_connect' => true,
                    'available_operators' => 1,
                    'available_operators_strict' => 0,
                    'available_operators_for_voice' => 1,
                    'total_operators' => 1,
                    'block_reason' => null,
                    'heartbeat_ttl_seconds' => $ttl,
                    'operator_twilio_client_identity' => $dispatchRing,
                    'dispatch_twilio_client_identity' => $dispatchRing,
                    'voice_ready_operator_twilio_identities' => [],
                    'twiml_dial_operator_identities' => [],
                    'twiml_dial_operator_count' => 0,
                    'outbound_twilio_client_leg_count' => 1,
                    'voice_outbound_dial_mode' => strtolower(trim((string) config('call.voice_outbound_dial_mode', 'single'))),
                    'voice_outbound_single_pick_strategy' => strtolower(trim((string) config('call.voice_outbound_single_pick_strategy', 'round_robin'))),
                    'legacy_admin_twilio_client_identity' => $adminId,
                    'require_voice_client_ready' => (bool) config('call.require_voice_client_ready', true),
                    'strict_presence_ttl_seconds' => $ttl,
                    'twiml_presence_ttl_seconds' => $ttl,
                    'resolution_hint' => 'Presence check failed; proceeding because CALL_PRESENCE_FAIL_OPEN=true.',
                ];
            } else {
                $note = $this->staffPresence->twilioDialContractNote();
                $msg = 'Temporary error checking operator availability. Please try again in a moment.';
                $data = [
                    'success' => false,
                    'can_connect' => false,
                    'code' => 'PRESENCE_CHECK_FAILED',
                    'message' => $msg,
                    'twilio_note' => $note,
                ];

                return response()->json(array_merge($data, ['data' => $data]), 503);
            }
        }

        $payload = array_merge(
            $snap,
            [
                'code' => $snap['can_connect'] ? 'OK' : 'ALL_OPERATORS_BUSY',
                'message' => $snap['can_connect']
                    ? 'An operator is available to take your call.'
                    : 'All emergency operators are currently busy. Please try again in a few minutes or use a text report if available.',
                'twilio_dial_identity' => (string) ($snap['operator_twilio_client_identity'] ?? ''),
                'twilio_note' => $this->staffPresence->twilioDialContractNote(),
            ]
        );

        $status = $snap['can_connect'] ? 200 : 503;

        $inner = array_merge($payload, [
            'success' => (bool) $snap['can_connect'],
        ]);

        return response()->json(array_merge($inner, [
            'data' => $inner,
        ]), $status);
    }
}
