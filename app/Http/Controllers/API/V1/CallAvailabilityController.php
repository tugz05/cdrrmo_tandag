<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\StaffPresenceService;
use Illuminate\Http\JsonResponse;

class CallAvailabilityController extends Controller
{
    public function __construct(private StaffPresenceService $staffPresence) {}

    public function show(): JsonResponse
    {
        $snap = $this->staffPresence->getCachedAvailabilitySnapshot();

        $body = array_merge(
            $snap,
            [
                'code' => $snap['can_connect'] ? 'OK' : 'ALL_OPERATORS_BUSY',
                'message' => $snap['can_connect']
                    ? 'An operator is available to take your call.'
                    : 'All emergency operators are currently busy. Please try again in a few minutes or use a text report if available.',
            ]
        );

        $status = $snap['can_connect'] ? 200 : 503;

        return response()->json($body, $status);
    }
}
