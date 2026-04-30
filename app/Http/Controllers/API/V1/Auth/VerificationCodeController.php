<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\Code;
use App\Models\User;
use App\Traits\JResponseApiTrait;
use Illuminate\Http\Request;

class VerificationCodeController extends Controller
{
    use JResponseApiTrait;

    public function send(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|email',
        ]);

        if (filter_var($validated['user_id'], FILTER_VALIDATE_EMAIL)) {
            $user = User::whereEmail($validated['user_id'])->first(['id','email']);

            if (!$user) return $this->responseError('Wrong email or phone number.');

            $code = (new ForgotPasswordController())->getCode($user);
            // code to send the verification code here
            return $this->responseOK([], 'A verification code has been sent to your email.');            
        }

        // write code here if user is using phone number
    }

    public function verify(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|email',
            'code' => 'required'
        ]);

        if (filter_var($validated['user_id'], FILTER_VALIDATE_EMAIL)) {
            $userId = User::whereEmail($validated['user_id'])->first(['id','email'])->id;
            if (!$userId) 
                return $this->responseError('Wrong email or user id');

            $latestCode = Code::whereUserId($userId)->latest()->first();
            if ($latestCode->code != $validated['code']) 
                return $this->responseError('Wrong code.');
            if ($latestCode->is_verified) 
                return $this->responseError( 'This code has already been verified.');
            
            $latestCode->update(['is_verified' => true]);
            return $this->responseOK([],'Verified successfully.');
        }

        // add logic here if user is using mobile phone number
    }


}
