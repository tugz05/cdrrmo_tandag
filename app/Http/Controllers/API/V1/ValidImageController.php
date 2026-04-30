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
}