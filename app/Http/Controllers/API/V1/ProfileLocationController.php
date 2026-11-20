<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileLocationController extends Controller
{
    /**
     * The app sends this after the resident moves 300 m or every 6 hours.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'address' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $user->forceFill($validated + ['address_updated_at' => now()])->save();

        return response()->json([
            'success' => true,
            'data' => [
                'address' => $user->address,
                'address_updated_at' => $user->address_updated_at->toIso8601String(),
            ],
        ]);
    }
}
