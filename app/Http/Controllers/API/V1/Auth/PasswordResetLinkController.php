<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Enums\JStatusCode;
use App\Enums\JVerificationType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\VerificationCodeService;
use App\Traits\JResponseApiTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    use JResponseApiTrait;
    
    // JTODO: refactor this section
    public function store(Request $request)
    {
        $request->validate(['user_id' => 'required']);
        
        $user = User::where('email', $request->user_id)
            ->orWhere('phone', $request->user_id)
            ->first(['id','email','phone']);

        if (!$user) 
            return $this->responseError('These credentials do not match our records..', [], JStatusCode::ACCEPTED);            

        try {
            if (filter_var($request->user_id, FILTER_VALIDATE_EMAIL)) {
                (new VerificationCodeService($user))
                    ->type(JVerificationType::EMAIL)
                    ->send();
                    
                return $this->responseOK([], 'A verification code was sent to your email.');
            } else {
                (new VerificationCodeService($user))
                    ->type(JVerificationType::PHONE)
                    ->send();

                return $this->responseOK([], 'A verification code was sent to your phone number.');
            }
        } catch (\Exception $exception) {
            return $this->responseError('An error has occured. Please try again later.');
        }

    }
}
