<?php

namespace App\Services\Auth;

use App\Exceptions\GoogleIdTokenVerificationException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use stdClass;

class GoogleIdTokenVerifier
{
    private const JWKS_URL = 'https://www.googleapis.com/oauth2/v3/certs';

    private const JWKS_CACHE_KEY = 'google_oauth2_jwks';

    private const JWKS_TTL_SECONDS = 3600;

    private const ALLOWED_ISS = [
        'https://accounts.google.com',
        'accounts.google.com',
    ];

    /**
     * Verify a Google ID token JWT (mobile / backend flow).
     *
     * @throws GoogleIdTokenVerificationException
     */
    public function verify(string $idToken): stdClass
    {
        $expectedAud = (string) config('services.google.server_client_id', '');

        if ($expectedAud === '') {
            throw new GoogleIdTokenVerificationException(
                'Google sign-in is not configured on the server.',
                503
            );
        }

        JWT::$leeway = 60;

        try {
            $jwks = Cache::remember(self::JWKS_CACHE_KEY, self::JWKS_TTL_SECONDS, function (): array {
                $response = Http::timeout(10)->get(self::JWKS_URL);

                if (! $response->successful()) {
                    throw new GoogleIdTokenVerificationException(
                        'Unable to load Google signing keys.',
                        503
                    );
                }

                $decoded = json_decode($response->body(), true);

                if (! is_array($decoded)) {
                    throw new GoogleIdTokenVerificationException(
                        'Invalid Google signing keys response.',
                        503
                    );
                }

                return $decoded;
            });

            $keys = JWK::parseKeySet($jwks);
        } catch (GoogleIdTokenVerificationException $e) {
            throw $e;
        } catch (\Throwable) {
            throw new GoogleIdTokenVerificationException(
                'Unable to parse Google signing keys.',
                503
            );
        }

        try {
            $payload = JWT::decode($idToken, $keys);
        } catch (ExpiredException) {
            throw new GoogleIdTokenVerificationException('The Google sign-in session has expired.', 401);
        } catch (SignatureInvalidException) {
            throw new GoogleIdTokenVerificationException('Invalid Google ID token.', 401);
        } catch (\Throwable) {
            throw new GoogleIdTokenVerificationException('Invalid Google ID token.', 401);
        }

        if (! isset($payload->iss) || ! in_array($payload->iss, self::ALLOWED_ISS, true)) {
            throw new GoogleIdTokenVerificationException('Invalid Google ID token.', 401);
        }

        if (! isset($payload->aud) || ! $this->audienceMatches($payload->aud, $expectedAud)) {
            throw new GoogleIdTokenVerificationException('Invalid Google ID token.', 401);
        }

        if (! isset($payload->sub) || $payload->sub === '') {
            throw new GoogleIdTokenVerificationException('Invalid Google ID token.', 401);
        }

        return $payload;
    }

    private function audienceMatches(mixed $aud, string $expected): bool
    {
        if (is_array($aud)) {
            return in_array($expected, $aud, true);
        }

        return is_string($aud) && $aud === $expected;
    }
}
