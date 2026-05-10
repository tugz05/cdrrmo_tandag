<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\StoreDeviceFcmTokenRequest;
use App\Models\UserDeviceFcmToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class DeviceFcmTokenController extends Controller
{
    /**
     * Register or replace the FCM token for the authenticated user and platform.
     * Removes the same token from any other user (or other platform for the same user) so pushes do not mis-route.
     */
    public function store(StoreDeviceFcmTokenRequest $request): JsonResponse
    {
        $user = $request->user();
        $fcmToken = $request->validated('fcm_token');
        $platform = $request->validated('platform');

        $device = DB::transaction(function () use ($user, $fcmToken, $platform) {
            UserDeviceFcmToken::query()
                ->where('fcm_token', $fcmToken)
                ->where(function ($q) use ($user, $platform) {
                    $q->where('user_id', '!=', $user->id)
                        ->orWhere('platform', '!=', $platform);
                })
                ->delete();

            return UserDeviceFcmToken::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'platform' => $platform,
                ],
                [
                    'fcm_token' => $fcmToken,
                ]
            );
        });

        $status = $device->wasRecentlyCreated ? Response::HTTP_CREATED : Response::HTTP_OK;

        return response()->json([
            'success' => true,
            'message' => 'FCM token registered.',
            'data' => [
                'id' => $device->id,
                'user_id' => $device->user_id,
                'platform' => $device->platform,
                'updated_at' => $device->updated_at?->toIso8601String(),
            ],
        ], $status);
    }
}
