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
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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

        $residentNotified = $this->notifyResident($report, new RescueStartedNotification($report));

        $this->broadcastStatus($report);

        return response()->json([
            'message'           => 'Rescue started.',
            'rescue_status'     => $report->rescue_status,
            'resident_notified' => $residentNotified,
        ]);
    }

    public function rescueArrived(Request $request, Report $report): JsonResponse
    {
        $this->authorize('update', $report);

        $report->update([
            'rescue_status'     => 'arrived',
            'rescue_arrived_at' => now(),
        ]);

        $residentNotified = $this->notifyResident($report, new RescueArrivedNotification($report));

        $this->broadcastStatus($report);

        return response()->json([
            'message'           => 'Arrival recorded.',
            'rescue_status'     => $report->rescue_status,
            'resident_notified' => $residentNotified,
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
     * The rescue change is already saved; a push failure must not tell the rescuer it failed.
     * The resident can still see the status through the app's rescue status check.
     */
    private function notifyResident(Report $report, Notification $notification): bool
    {
        $resident = $report->user;

        if (! $resident || ! $resident->device_fcm_tokens()->exists()) {
            return false;
        }

        try {
            $resident->notify($notification);

            return true;
        } catch (Throwable $exception) {
            Log::warning('Rescue notification failed', [
                'report_id' => $report->id,
                'exception' => $exception::class,
                'reason' => Str::limit($exception->getMessage(), 200),
            ]);

            return false;
        }
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
