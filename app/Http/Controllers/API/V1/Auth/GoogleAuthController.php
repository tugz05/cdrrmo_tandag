<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Enums\JStatusCode;
use App\Exceptions\GoogleIdTokenVerificationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Auth\GoogleIdTokenRequest;
use App\Models\User;
use App\Services\Auth\GoogleIdTokenVerifier;
use App\Support\MobileLoginPayload;
use App\Traits\JResponseApiTrait;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\JsonResponse;

class GoogleAuthController extends Controller
{
    use JResponseApiTrait;

    public function __construct(
        private readonly GoogleIdTokenVerifier $googleIdTokenVerifier
    ) {}

    public function store(GoogleIdTokenRequest $request): JsonResponse
    {
        try {
            $payload = $this->googleIdTokenVerifier->verify($request->validated('id_token'));
        } catch (GoogleIdTokenVerificationException $e) {
            return $this->responseError(
                $e->getMessage(),
                [],
                $e->getCode() > 0 ? $e->getCode() : JsonResponse::HTTP_UNAUTHORIZED
            );
        }

        $email = isset($payload->email) && is_string($payload->email) ? $payload->email : '';
        if ($email === '') {
            return $this->responseError('Your Google account did not return an email address.', [], 401);
        }

        $emailVerified = isset($payload->email_verified) && $payload->email_verified === true;
        if (! $emailVerified) {
            return $this->responseError('Please verify your email address with Google before signing in.', [], 401);
        }

        $sub = (string) $payload->sub;
        $displayName = $this->displayNameFromPayload($payload);

        $conflict = User::query()
            ->where('email', $email)
            ->whereNotNull('google_id')
            ->where('google_id', '!=', $sub)
            ->exists();

        if ($conflict) {
            return $this->responseError(
                'This email is already linked to a different Google account.',
                [],
                401
            );
        }

        try {
            $user = DB::transaction(function () use ($sub, $email, $displayName, $emailVerified) {
                $user = User::query()->where('google_id', $sub)->first();

                if ($user) {
                    $this->syncEmailVerificationFromGoogle($user, $emailVerified);

                    return $user;
                }

                $user = User::query()->where('email', $email)->first();

                if ($user) {
                    $user->forceFill([
                        'google_id' => $sub,
                        'name' => $user->name ?: $displayName,
                    ]);
                    $this->syncEmailVerificationFromGoogle($user, $emailVerified);
                    $user->save();

                    return $user;
                }

                $nameParts = $this->splitDisplayName($displayName);

                $user = User::query()->create([
                    'name' => $displayName,
                    'fname' => $nameParts['fname'],
                    'lname' => $nameParts['lname'],
                    'mname' => null,
                    'email' => $email,
                    'google_id' => $sub,
                    'password' => Str::password(32),
                    'email_verified_at' => $emailVerified ? now() : null,
                ]);

                $user->addRole('user');

                return $user;
            });
        } catch (QueryException) {
            return $this->responseError('Could not create or update your account. Please try again.', [], 422);
        }

        $user->refresh();

        if ($user->hasRole(['admin', 'super_admin'])) {
            return $this->responseError(
                'These credentials do not match our records..',
                [],
                JStatusCode::ACCEPTED
            );
        }

        return $this->responseOK(
            MobileLoginPayload::data($user, $user->createToken('user-token')->plainTextToken),
            'Login successfully.'
        );
    }

    private function syncEmailVerificationFromGoogle(User $user, bool $emailVerified): void
    {
        if ($emailVerified && $user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()]);
            $user->save();
        }
    }

    private function displayNameFromPayload(object $payload): string
    {
        if (isset($payload->name) && is_string($payload->name) && trim($payload->name) !== '') {
            return trim($payload->name);
        }
        $given = isset($payload->given_name) && is_string($payload->given_name) ? trim($payload->given_name) : '';
        $family = isset($payload->family_name) && is_string($payload->family_name) ? trim($payload->family_name) : '';
        $composed = trim($given.' '.$family);
        if ($composed !== '') {
            return $composed;
        }
        $email = isset($payload->email) && is_string($payload->email) ? $payload->email : 'user';

        return Str::before($email, '@') ?: 'User';
    }

    /**
     * @return array{fname: string|null, lname: string|null}
     */
    private function splitDisplayName(string $displayName): array
    {
        $parts = preg_split('/\s+/', trim($displayName), -1, PREG_SPLIT_NO_EMPTY);
        if ($parts === false || $parts === []) {
            return ['fname' => 'User', 'lname' => null];
        }
        if (count($parts) === 1) {
            return ['fname' => $parts[0], 'lname' => null];
        }

        return [
            'fname' => array_shift($parts),
            'lname' => implode(' ', $parts),
        ];
    }
}
