<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Enums\JAuthorizationEnum;
use App\Enums\JStatusCode;
use App\Enums\JVerificationCodeStatus;
use App\Enums\JVerificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Auth\LoginPostRequest;
use App\Http\Requests\API\V1\Auth\SendVerificationRequest;
use App\Http\Requests\API\V1\Auth\VerifyVerificationRequest;
use App\Models\AlumniSkill;
use App\Models\Company;
use App\Models\User;
use App\Services\VerificationCodeService;
use App\Traits\JActiveUser;
use App\Traits\JResponseApiTrait;
use App\Traits\JTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerificationController extends Controller
{
    use JResponseApiTrait, JTrait;


    private $user;

    public function __construct(Request $request)
    {
        $selectedColumns = ['id', 'fname', 'name', 'email', 'phone', 'email_verified_at'];
        $this->user = User::where('email', $request->user_id)
            ->orWhere('phone', $request->user_id)
            ->first($selectedColumns);
    }

    public function store(Request $request)
    {
        if (!$this->user)
            return $this->responseError('These credentials do not match our records..', [], JStatusCode::UNPROCESSABLE_CONTENT);

        return filter_var($request->user_id, FILTER_VALIDATE_EMAIL)
            ? $this->sendVerificationCodeTo(JVerificationType::EMAIL)
            : $this->sendVerificationCodeTo(JVerificationType::PHONE);
    }

    public function verify(VerifyVerificationRequest $request)
    {
        $validatedData = $request->validated();

        if (!$this->user)
            return $this->responseError('Invalid Code..', [], JStatusCode::UNPROCESSABLE_CONTENT);

        $codeStatus = $this->getVerificationCodeStatus($request, $validatedData['code']);

        switch ($codeStatus) {
            case JVerificationCodeStatus::VERIFIED:
                Auth::login($this->user);

                // $this->userIsActiveAt($this->user);

                return $this->user->hasRole(JAuthorizationEnum::EMPLOYER)
                    // employer should return this
                    // id
                    // role
                    // token
                    // name
                    ? $this->responseOK([
                        'id' => $this->user->id,
                        'name' => $this->user->name,
                        'company_name' => $this->user->company->company_name,
                        'employees_no' => $this->user->company->employees_no,
                        'company_address' => $this->user->company->company_address,
                        'about' => $this->user->company->about,
                        'email' => $this->user->email,
                        'phone' => $this->user->phone,
                        'role' => JAuthorizationEnum::EMPLOYER,
                        'user_id' => $this->user->email,
                        'token' => $this->user->createToken('graduate-tracer-app-user')->plainTextToken
                    ], 'Verified successfully.')
                    // alumni should return this
                    // id
                    // role
                    // token
                    // fname
                    // fullname
                    : $this->responseOK([
                        'id' => $this->user->id,
                        'fname' => $this->user->fname,
                        'fullname' => $this->user->fullname(),
                        'role' => null,
                        'user_id' => $this->user->email,
                        'skills' => AlumniSkill::whereUserId($this->user->id)?->first()->skills ?? '',
                        'token' => $this->user->createToken('graduate-tracer-app-user')->plainTextToken,
                        'questionnaire_notification' => $this->user->notification_settings->questionnaire_notification,
                        'job_hiring_notification' => $this->user->notification_settings->job_hiring_notification,
                        'trainings_notification' => $this->user->notification_settings->trainings_notification,            
                    ], 'Verified successfully.');

            case JVerificationCodeStatus::EXPIRED:
                return $this->responseError('Expired Code.', ['title' => 'Expired Code'], JStatusCode::UNPROCESSABLE_CONTENT);
            case JVerificationCodeStatus::INVALID:
                return $this->responseError('Invalid Code.', ['title' => 'Invalid Code'], JStatusCode::UNPROCESSABLE_CONTENT);
            default:
                return $this->responseError('An error has occured. Please try again later.');
        }
    }

    private function getVerificationCodeStatus($request, $code)
    {
        return filter_var($request->user_id, FILTER_VALIDATE_EMAIL)
            ? (new VerificationCodeService($this->user))
                ->type(JVerificationType::EMAIL)
                ->verify($code)
            : (new VerificationCodeService($this->user))
                ->type(JVerificationType::PHONE)
                ->verify($code);
    }

    private function sendVerificationCodeTo($type)
    {
        (new VerificationCodeService($this->user))
            ->type($type)
            ->send();

        return $this->responseOK([], 'Verification code has been sent.');
    }
}
