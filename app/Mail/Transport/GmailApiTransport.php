<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

/**
 * Sends mail through the Gmail API over HTTPS, because the hosting provider blocks outgoing SMTP.
 * The finished MIME message goes to Gmail unchanged, so mail looks the same as it would over SMTP.
 * Never log the tokens or the message: it holds sign-in codes.
 */
class GmailApiTransport extends AbstractTransport
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const SEND_URL = 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send';

    private const TOKEN_CACHE_KEY = 'mail:gmail-api:access-token';

    public function __construct(
        private string $clientId,
        private string $clientSecret,
        private string $refreshToken,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $raw = rtrim(strtr(base64_encode($message->toString()), '+/', '-_'), '=');

        $response = Http::withToken($this->accessToken())
            ->timeout(20)
            ->post(self::SEND_URL, ['raw' => $raw]);

        // An access token revoked early: drop it so the next try fetches a fresh one.
        if ($response->status() === 401) {
            Cache::forget(self::TOKEN_CACHE_KEY);
        }

        if ($response->failed()) {
            throw new TransportException('Gmail API send failed with HTTP '.$response->status().': '.$this->googleError($response->json()));
        }
    }

    public function __toString(): string
    {
        return 'gmail';
    }

    private function accessToken(): string
    {
        if ($this->clientId === '' || $this->clientSecret === '' || $this->refreshToken === '') {
            throw new TransportException('Gmail API is not configured: set GMAIL_CLIENT_ID, GMAIL_CLIENT_SECRET and GMAIL_REFRESH_TOKEN.');
        }

        $cached = Cache::get(self::TOKEN_CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $response = Http::asForm()->timeout(20)->post(self::TOKEN_URL, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'refresh_token' => $this->refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        $token = $response->json('access_token');

        if ($response->failed() || ! is_string($token) || $token === '') {
            throw new TransportException('Gmail API token refresh failed with HTTP '.$response->status().': '.$this->googleError($response->json()));
        }

        // A minute short of Google's lifetime, so a token never expires mid-send.
        Cache::put(self::TOKEN_CACHE_KEY, $token, max(60, (int) $response->json('expires_in', 3600) - 60));

        return $token;
    }

    /**
     * Google's error code and description only, never the request.
     */
    private function googleError(mixed $body): string
    {
        if (! is_array($body)) {
            return 'no details';
        }

        $error = $body['error'] ?? null;

        if (is_array($error)) {
            return trim(($error['status'] ?? '').' '.($error['message'] ?? ''));
        }

        return trim(($error ?? '').' '.($body['error_description'] ?? '')) ?: 'no details';
    }
}
