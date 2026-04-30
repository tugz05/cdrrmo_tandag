<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\JActiveUser;
use App\Traits\JResponseApiTrait;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    use JResponseApiTrait;
    use JActiveUser;

    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            $existingUser = User::where('email', $googleUser->getEmail())->first();

            if ($existingUser) {
                $user = Auth::login($existingUser);
                $this->userIsActiveAt($user);
                return $this->responseOK($this->userResponseData($user), 'Login Successfully');
            }

            $user = User::create([
                'google_id' => $googleUser->getId(),
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
            ]);

            Auth::login($user);

            return $this->responseOK($this->userResponseData($user), 'Registered successfully.');
        } catch (\Exception $exception) {
            return redirect()->route('login')->withErrors(['google' => 'Google sign-in failed.']);
        }
    }

    private function userResponseData($user): array
    {
        // $data['id'] = $user->id;
        // $data['fullname'] = $user->fullname();
        // $data['email'] = $user->email;
        // $data['token'] = $user->createToken('graduate-tracer-app-user')->plainTextToken;

        $data['success'] = true;
        $data['message'] = 'Login Successfully.';
        $data['email_confirmed'] = !is_null($user->email_verified_at);
        $data['token'] = $user->createToken('graduate-tracer-app-user')->plainTextToken;

        return $data;
    }
}