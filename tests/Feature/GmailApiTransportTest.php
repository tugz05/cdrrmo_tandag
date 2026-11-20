<?php

namespace Tests\Feature;

use App\Mail\OtpCodeMail;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class GmailApiTransportTest extends TestCase
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const SEND_URL = 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Config::set('mail.from', ['address' => 'eresponse@gmail.com', 'name' => 'E-Response']);
        Config::set('services.gmail', [
            'client_id' => 'client-id.apps.googleusercontent.com',
            'client_secret' => 'client-secret',
            'refresh_token' => 'refresh-token',
        ]);
    }

    private function fakeGoogle(array $send = ['id' => 'm1'], int $sendStatus = 200): void
    {
        Http::fake([
            self::TOKEN_URL => Http::response(['access_token' => 'access-1', 'expires_in' => 3599, 'token_type' => 'Bearer']),
            self::SEND_URL => Http::response($send, $sendStatus),
        ]);
    }

    private function decodedRaw(Request $request): string
    {
        return base64_decode(strtr($request['raw'], '-_', '+/'));
    }

    public function test_sends_the_email_through_the_gmail_api(): void
    {
        $this->fakeGoogle();

        Mail::mailer('gmail')->to('juan@example.com')->send(new OtpCodeMail('482913'));

        Http::assertSent(function (Request $request) {
            if ($request->url() !== self::SEND_URL) {
                return false;
            }
            $raw = $this->decodedRaw($request);

            return $request->hasHeader('Authorization', 'Bearer access-1')
                && ! str_contains($request['raw'], '+') && ! str_contains($request['raw'], '/')
                && str_contains($raw, 'To: juan@example.com')
                && str_contains($raw, 'From: E-Response <eresponse@gmail.com>')
                && str_contains($raw, 'Subject: Your E-Response code')
                && str_contains($raw, '482913');
        });
    }

    public function test_refreshes_with_the_configured_refresh_token(): void
    {
        $this->fakeGoogle();

        Mail::mailer('gmail')->raw('Hello', fn ($m) => $m->to('juan@example.com')->subject('Test'));

        Http::assertSent(fn (Request $request) => $request->url() === self::TOKEN_URL
            && $request['grant_type'] === 'refresh_token'
            && $request['refresh_token'] === 'refresh-token'
            && $request['client_id'] === 'client-id.apps.googleusercontent.com');
    }

    public function test_reuses_the_access_token_between_emails(): void
    {
        $this->fakeGoogle();

        Mail::mailer('gmail')->raw('One', fn ($m) => $m->to('a@example.com')->subject('1'));
        Mail::mailer('gmail')->raw('Two', fn ($m) => $m->to('b@example.com')->subject('2'));

        Http::assertSentCount(3);
    }

    public function test_failed_send_throws_without_leaking_the_message(): void
    {
        $this->fakeGoogle(['error' => ['code' => 403, 'status' => 'PERMISSION_DENIED', 'message' => 'Insufficient Permission']], 403);

        try {
            Mail::mailer('gmail')->to('juan@example.com')->send(new OtpCodeMail('482913'));
            $this->fail('Expected a TransportException.');
        } catch (TransportException $e) {
            $this->assertStringContainsString('HTTP 403', $e->getMessage());
            $this->assertStringContainsString('Insufficient Permission', $e->getMessage());
            $this->assertStringNotContainsString('482913', $e->getMessage());
        }
    }

    public function test_rejected_access_token_is_dropped_for_the_next_try(): void
    {
        $this->fakeGoogle(['error' => ['status' => 'UNAUTHENTICATED', 'message' => 'Invalid Credentials']], 401);

        try {
            Mail::mailer('gmail')->raw('Hello', fn ($m) => $m->to('juan@example.com')->subject('Test'));
        } catch (TransportException) {
        }

        $this->assertNull(Cache::get('mail:gmail-api:access-token'));
    }

    public function test_revoked_refresh_token_gives_a_clear_error(): void
    {
        Http::fake([self::TOKEN_URL => Http::response(['error' => 'invalid_grant', 'error_description' => 'Token has been expired or revoked.'], 400)]);

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('invalid_grant');

        Mail::mailer('gmail')->raw('Hello', fn ($m) => $m->to('juan@example.com')->subject('Test'));
    }

    public function test_missing_configuration_says_what_to_set(): void
    {
        Config::set('services.gmail.refresh_token', '');
        Http::fake();

        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('GMAIL_REFRESH_TOKEN');

        Mail::mailer('gmail')->raw('Hello', fn ($m) => $m->to('juan@example.com')->subject('Test'));
    }
}
