<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Enums\JStatusCode;
use App\Helpers\JHelper;
use App\Http\Requests\API\V1\Auth\LoginPostRequest;
use App\Models\User;
use App\Traits\JResponseApiTrait;
use App\Traits\JTrait;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginController extends FormRequest
{
    use JResponseApiTrait;
    use JTrait;

    public function store(LoginPostRequest $request)
    {
        $this->ensureIsNotRateLimited();

        if (! $this->isValidLoginCredentials($request)) {
            RateLimiter::hit($this->throttleKey());
            return $this->responseError(
                'These credentials do not match our records..',
                [],
                JStatusCode::ACCEPTED
            );
        }

        $user = Auth::user();

        if (!$this->isEmailVerified($user, $request)) {
            $data['success'] = true;
            $data['message'] = 'Verify your account first.';
            $data['is_email_confirmed'] = !is_null($user->email_verified_at);
            return response()->json($data);
        }

        // $this->userIsActiveAt($user);
        RateLimiter::clear($this->throttleKey());

        return $this->responseOK([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'email_verified_at' => $user->email_verified_at,
            'confirmed_at' => $user->confirmed_at,
            'status' => $this->accountStatus($user), // for_verification, verified, pending_verification
            'token' => $user->createToken('user-token')->plainTextToken
        ], 'Login successfully.');
    }

    private function accountStatus($user): string
    {
        if (!is_null($user->confirmed_at))
            return 'verified';

        if (count(JHelper::getValidImages($user->id)) > 0) 
            return 'pending_verification';

        return 'for_verification';
    }

    private function isValidLoginCredentials($request)
    {
        return Auth::attempt($request->validated()) &&
            !Auth::user()->hasRole(['admin','super_admin']);
    }

    private function isEmailVerified($user, $request)
    {
        return true;
        return filter_var($request->user_id, FILTER_VALIDATE_EMAIL)
            ? !is_null($user->email_verified_at)
            : !is_null($user->phone_verified_at);
    }


    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('user_id')) . '|' . $this->ip());
    }
}
