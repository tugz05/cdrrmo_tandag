<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Enums\JVerificationCodeStatus;
use App\Enums\JVerificationType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\VerificationCodeService;
use App\Traits\JResponseApiTrait;
use Illuminate\Http\Request;

class NewPasswordController extends Controller
{
    use JResponseApiTrait;

    private $selectedColumns = [
        'id',
        'email',
        // 'phone',
        'email_verified_at',
        // 'phone_verified_at'
    ];

    private $user;

    public function __construct(Request $request)
    {
        $this->user = User::where('email', $request->user_id)
            // ->orWhere('phone', $request->user_id)
            ->first($this->selectedColumns);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|email',
            'password' => 'required',
            'confirm_password' => 'required|same:password',
        ]);

        if (!$this->user) 
            return $this->responseError('User not found.');

        try {
            $this->changePassword($request);
            return $this->responseOK([], 'Password changed successfully.');
        } catch(\Exception $exception) {
            return $this->responseError('An error has occured. Please try again.');
        }


        // $verificationCodeStatus = $this->getVerificationStatus($request);

        // switch($verificationCodeStatus) {
        //     case JVerificationCodeStatus::VERIFIED:
        //         return $this->responseOK([], 'Password changed successfully.');
        //     case JVerificationCodeStatus::INVALID:
        //         return $this->responseError('Invalid Verification Code');
        //     case JVerificationCodeStatus::EXPIRED:
        //         return $this->responseError('Expired Verification Code');
        // }

    }

    private function getVerificationStatus(Request $request)
    {
        return (new VerificationCodeService($this->user))
                ->type(JVerificationType::FORGOT_PASSWORD)
                ->verify($request->code);
    }

    private function changePassword($request)
    {
        $this->user->update([
            'password' => bcrypt($request->password)
        ]);
    }
}        
