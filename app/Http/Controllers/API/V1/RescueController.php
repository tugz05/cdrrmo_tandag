<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Notifications\RescueArrivedNotification;
use App\Notifications\RescueStartedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RescueController extends Controller
{
    public function startRescue(Request $request, Report $report): JsonResponse
    {
        $this->authorize('update', $report);

        $report->update([
            'rescue_status'     => 'en_route',
            'rescuer_user_id'   => $request->user()->id,
            'rescue_started_at' => now(),
        ]);

        if ($report->user) {
            $report->user->notify(new RescueStartedNotification($report));
        }

        return response()->json([
            'message'       => 'Rescue started.',
            'rescue_status' => $report->rescue_status,
        ]);
    }

    public function rescueArrived(Request $request, Report $report): JsonResponse
    {
        $this->authorize('update', $report);

        $report->update([
            'rescue_status'     => 'arrived',
            'rescue_arrived_at' => now(),
        ]);

        if ($report->user) {
            $report->user->notify(new RescueArrivedNotification($report));
        }

        return response()->json([
            'message'       => 'Arrival recorded.',
            'rescue_status' => $report->rescue_status,
        ]);
    }

    public function rescueComplete(Request $request, Report $report): JsonResponse
    {
        $this->authorize('update', $report);

        $report->update([
            'rescue_status' => 'completed',
            'status'        => 'completed',
        ]);

        return response()->json(['message' => 'Rescue completed.']);
    }

    public function rescueStatus(Request $request, Report $report): JsonResponse
    {
        return response()->json([
            'report_id'         => $report->id,
            'rescue_status'     => $report->rescue_status,
            'rescuer_user_id'   => $report->rescuer_user_id,
            'rescue_started_at' => $report->rescue_started_at,
            'rescue_arrived_at' => $report->rescue_arrived_at,
        ]);
    }
}
