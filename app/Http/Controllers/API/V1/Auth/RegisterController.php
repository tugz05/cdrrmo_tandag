<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Enums\JStatusCode;
use App\Enums\JVerificationType;
use App\Helpers\JHelper;
use App\Http\Requests\API\V1\Auth\RegisterPostRequest;
use App\Models\User;
use App\Services\VerificationCodeService;
use App\Traits\JResponseApiTrait;
use App\Traits\JTrait;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\JsonResponse;

class RegisterController
{
    use JResponseApiTrait, JTrait;

    public function store(RegisterPostRequest $request): JsonResponse
    {
        $validatedData = $request->validated();

        // JTODO: also make the column "phone" a unique field like the email

        $user = User::whereEmail($validatedData['email'])
            ->first(['id', 'email', 'email_verified_at']);

        if ($user) {
            return $this->responseError(
                'Validation Error',
                ['The email has already been taken.'],
                JStatusCode::ACCEPTED
            );
        }

        try {
            $validatedData['name'] = "{$validatedData['fname']} {$validatedData['mname']} {$validatedData['lname']}";
            $user = User::create($validatedData);
            $user->addRole('user');

            // JHelper::storeValidIds($request, $user);

            (new VerificationCodeService($user))
                ->type(JVerificationType::EMAIL) // email is default
                ->send();

            return $this->responseOK(
                $user,
                'New user has been successfully registered. A verification code has been sent to your Email.'
            );
        } catch (QueryException|ModelNotFoundException|\Exception $exception) {
            $message = $exception->errorInfo[2]
                ?? 'An error occured. Please try again.';

            return $this->responseError($message);
        }
    }
}
