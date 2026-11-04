<?php

namespace App\Http\Controllers\API\V1;

use App\Helpers\JHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserValidId;
use App\Traits\JResponseApiTrait;
use Illuminate\Http\Request;

class ValidImageController extends Controller
{       
    use JResponseApiTrait;

    public function store(Request $request, User $user) 
    {   
        if (!$user) return $this->responseError('User ID is required.');

        $requester = $request->user('sanctum');
        if ($requester && (int) $requester->id !== (int) $user->id) {
            return $this->responseError('You may only upload your own valid ID.', [], 403);
        }
        // Registration has no token yet, so allow only a first upload for an unconfirmed citizen.
        if (! $requester && ! $this->mayUploadWithoutToken($user)) {
            return $this->responseError('Sign in to upload a valid ID.', [], 401);
        }

        $request->validate([
            'img_valid_id'      => 'required|image|mimes:jpg,png,jpeg|max:10240',
            'img_selfie'        => 'required|image|mimes:jpg,png,jpeg|max:10240',
        ]);

        try {
            JHelper::storeValidIds($request, $user);
            return $this->responseOk('Valid images has been uploaded successfully.');
        } catch (\Exception $e) {
            return $this->responseError('An error has occured. Please try again.');
        }
    }

    private function mayUploadWithoutToken(User $user): bool
    {
        return is_null($user->confirmed_at)
            && ! $user->isVoiceDispatchOperator()
            && ! $user->user_valid_ids()->exists();
    }
}