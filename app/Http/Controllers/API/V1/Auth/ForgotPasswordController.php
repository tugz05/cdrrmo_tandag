<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Enums\JVerificationType;
use App\Helpers\JHelper;
use App\Http\Controllers\Controller;
use App\Models\Code;
use App\Models\User;
use App\Services\VerificationCodeService;
use App\Traits\JResponseApiTrait;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    use JResponseApiTrait;

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|email'
        ]);
        
        if (filter_var($validated['user_id'], FILTER_VALIDATE_EMAIL)) {
            $user = User::whereEmail($validated['user_id'])->first();
            // $code = $this->getCode($user);
            // some code here to send the verification code to email 
            (new VerificationCodeService($user))
                ->type(JVerificationType::EMAIL) // email is default
                ->send();
                
            return $this->responseOK([], 'A verification code has been sent to your email.');            
        } 

        // find phone
        // generate code and store
        // send to phone
        // return a message
    }

    public function getCode($user)
    {
        $code = Code::create([
            'user_id' => $user->id,
            'code' => JHelper::generateCode(),
            'is_verified' => false,
        ]);

        return $code->code;
    }
}
