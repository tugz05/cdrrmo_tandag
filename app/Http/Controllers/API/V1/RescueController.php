<?php

namespace App\Http\Controllers\API\V1;

use App\Events\RescueStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Notifications\RescueArrivedNotification;
use App\Notifications\RescueStartedNotification;
use App\Services\LiveLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

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

        $this->broadcastStatus($report);

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

        $this->broadcastStatus($report);

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

        $this->broadcastStatus($report);

        return response()->json(['message' => 'Rescue completed.']);
    }

    public function rescueStatus(Request $request, Report $report): JsonResponse
    {
        $this->authorize('view', $report);

        return response()->json([
            'report_id'         => $report->id,
            'rescue_status'     => $report->rescue_status,
            'rescuer_user_id'   => $report->rescuer_user_id,
            'rescue_started_at' => $report->rescue_started_at,
            'rescue_arrived_at' => $report->rescue_arrived_at,
        ]);
    }

    /**
     * The rescue change is already saved; a WebSocket outage must not fail the request.
     */
    private function broadcastStatus(Report $report): void
    {
        try {
            RescueStatusUpdated::dispatch(
                $report->id,
                $report->rescue_status,
                ! LiveLocationService::isClosed($report->rescue_status, $report->status)
            );
        } catch (Throwable $exception) {
            Log::warning('Rescue status broadcast failed', [
                'report_id' => $report->id,
                'exception' => $exception::class,
            ]);
        }
    }
}
