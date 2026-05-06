<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Enums\JStatusCode;
use App\Http\Requests\API\V1\Auth\LoginPostRequest;
use App\Support\MobileLoginPayload;
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

        if (! Auth::attempt($request->validated())) {
            RateLimiter::hit($this->throttleKey());

            return $this->responseError(
                'These credentials do not match our records..',
                [],
                JStatusCode::ACCEPTED
            );
        }

        $user = Auth::user();

        if (! $user->canAccessMobileApp()) {
            Auth::logout();

            return $this->responseError(
                'This account is not enabled for the mobile app. Use an account with a valid role.',
                [],
                403
            );
        }

        $user->syncAppRoleFromRoles();
        $user->refresh();

        if (! $this->isEmailVerified($user, $request)) {
            $data['success'] = true;
            $data['message'] = 'Verify your account first.';
            $data['is_email_confirmed'] = ! is_null($user->email_verified_at);

            return response()->json($data);
        }

        // $this->userIsActiveAt($user);
        RateLimiter::clear($this->throttleKey());

        return response()->json(
            MobileLoginPayload::mobileAuthJson($user, $user->createToken('user-token')->plainTextToken)
        );
    }

    private function isEmailVerified($user, $request)
    {
        return true;

        return filter_var($request->user_id, FILTER_VALIDATE_EMAIL)
            ? ! is_null($user->email_verified_at)
            : ! is_null($user->phone_verified_at);
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
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
        return Str::transliterate(Str::lower($this->input('user_id')).'|'.$this->ip());
    }
}
