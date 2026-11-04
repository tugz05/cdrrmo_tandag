<?php

namespace App\Http\Controllers;

use App\Models\UserValidId;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ValidIdFileController extends Controller
{
    /**
     * Government ID and selfie images: visible to their owner and dispatch staff only.
     */
    public function show(Request $request, string $filename): StreamedResponse
    {
        // The exact match against stored names also rules out path traversal.
        $image = UserValidId::query()->where('filename', 'img_valid_ids/'.$filename)->firstOrFail();

        $user = $request->user();
        abort_unless((int) $image->user_id === (int) $user->id || $user->isVoiceDispatchOperator(), 403);

        // "public" covers files not yet moved by `php artisan valid-ids:secure`.
        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($image->filename)) {
                return Storage::disk($disk)->response($image->filename, null, [
                    'Cache-Control' => 'private, no-store',
                    'X-Content-Type-Options' => 'nosniff',
                ]);
            }
        }

        abort(404);
    }
}
