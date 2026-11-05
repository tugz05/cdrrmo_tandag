<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LiveLocationStoreRequest;
use App\Models\User;
use App\Services\LiveLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class LiveLocationController extends Controller
{
    public function __construct(private LiveLocationService $liveLocation) {}

    public function store(LiveLocationStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $mode = $validated['mode'];
        $reportId = isset($validated['report_id']) ? (int) $validated['report_id'] : null;

        try {
            $context = $reportId === null ? null : $this->liveLocation->reportContext($reportId);
            abort_if($reportId !== null && $context === null, 404);

            $isOwner = $context !== null && $context['user_id'] === (int) $user->id;
            abort_unless($isOwner || $user->isVoiceDispatchOperator(), 403);

            $this->liveLocation->recordRequest();

            if ($context !== null && $context['closed']) {
                return $this->reply(null, $context, $mode, false);
            }

            if ($validated['samples'] !== []) {
                $this->liveLocation->ingest($user, $reportId, $context, $mode, $validated['samples']);
            }

            return $this->reply($this->peerFor($isOwner, $context), $context, $mode, true);
        } catch (HttpExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            // Class only: messages from failed inserts contain the coordinates.
            Log::error('Live location request failed', [
                'report_id' => $reportId,
                'exception' => $exception::class,
            ]);

            $retryAfter = (int) config('live_location.retry_after_seconds');

            return response()->json(['message' => 'Live sharing is temporarily unavailable.'], 503)
                ->header('Retry-After', (string) $retryAfter);
        }
    }

    /**
     * Each side only sees the other: a resident gets their assigned rescuer and
     * nobody else, staff get the report's resident, and on-duty staff get no one.
     */
    private function peerFor(bool $isOwner, ?array $context): ?array
    {
        if ($context === null) {
            return null;
        }

        $peerId = $isOwner ? $context['rescuer_user_id'] : $context['user_id'];
        $position = $peerId === null ? null : $this->liveLocation->latestPosition($peerId);

        if ($position === null) {
            return null;
        }

        return [
            'lat' => $position['lat'],
            'lng' => $position['lng'],
            'accuracy' => $position['accuracy'],
            'speed' => $position['speed'],
            'heading' => $position['heading'],
            'age_seconds' => LiveLocationService::ageSeconds($position),
        ];
    }

    private function reply(?array $peer, ?array $context, string $mode, bool $sessionActive): JsonResponse
    {
        return response()->json([
            'data' => [
                'peer' => $peer,
                'rescue_status' => $context['rescue_status'] ?? null,
                'next_interval_seconds' => $this->liveLocation->nextIntervalSeconds($mode),
                'session_active' => $sessionActive,
            ],
        ]);
    }
}
