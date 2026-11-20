<?php

namespace Tests\Feature\Api;

use App\Mail\OtpCodeMail;
use App\Models\OtpCode;
use App\Models\Report;
use App\Models\User;
use App\Models\UserValidId;
use App\Services\Otp\OtpCheck;
use App\Services\Otp\OtpSender;
use App\Services\Otp\OtpService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OtpAuthTest extends TestCase
{
    use RefreshDatabase;

    private FakeOtpSender $sms;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Mail::fake();

        // SMS goes through a fake so Twilio is never called. Email uses the real code store.
        $this->sms = new FakeOtpSender;
        $this->app->instance(OtpSender::class, new class($this->sms) implements OtpSender
        {
            public function __construct(private FakeOtpSender $sms) {}

            public function send(string $channel, string $destination): void
            {
                $channel === 'sms' ? $this->sms->send($channel, $destination) : app(OtpService::class)->send($channel, $destination);
            }

            public function check(string $channel, string $destination, string $code): OtpCheck
            {
                return $channel === 'sms' ? $this->sms->check($channel, $destination, $code) : app(OtpService::class)->check($channel, $destination, $code);
            }
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->addRole($role);

        return $user;
    }

    /** An account whose unverified phone was there before code sign-in. */
    private function legacyPhoneUser(string $phone): User
    {
        $user = $this->userWithRole('user', ['phone' => $phone]);
        $user->forceFill(['legacy_phone_sign_in' => true])->save();

        return $user;
    }

    private function send(string $channel, string $destination): TestResponse
    {
        return $this->postJson('/api/v1/auth/otp/send', compact('channel', 'destination'));
    }

    private function verify(string $channel, string $destination, string $code): TestResponse
    {
        return $this->postJson('/api/v1/auth/otp/verify', compact('channel', 'destination', 'code'));
    }

    private function sentEmailCode(): string
    {
        $code = null;
        Mail::assertQueued(OtpCodeMail::class, function (OtpCodeMail $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        return $code;
    }

    private function registrationToken(string $phone = '+639171234567'): string
    {
        $this->send('sms', $phone)->assertOk();

        return $this->verify('sms', $phone, FakeOtpSender::GOOD_CODE)
            ->assertOk()
            ->assertJsonPath('is_new_user', true)
            ->json('data.registration_token');
    }

    private function signUp(string $token, array $overrides = []): TestResponse
    {
        return $this->postJson('/api/v1/auth/otp/register', array_merge([
            'registration_token' => $token,
            'fname' => 'Juan',
            'lname' => 'Dela Cruz',
            'birthdate' => '1990-04-15',
            'address' => 'Brgy. Poblacion, Tago, Surigao del Sur',
            'latitude' => 9.0195,
            'longitude' => 126.2331,
        ], $overrides));
    }

    // send

    public function test_send_normalizes_a_local_number(): void
    {
        $this->send('sms', '0917 123 4567')
            ->assertOk()
            ->assertJsonPath('data.destination', '+639171234567')
            ->assertJsonPath('data.channel', 'sms')
            ->assertJsonPath('data.expires_in', 600)
            ->assertJsonPath('data.resend_in', 60)
            ->assertJsonPath('message', 'We sent a code to +63 917 ••• 4567.');

        $this->assertSame(['+639171234567'], $this->sms->sent);
    }

    public function test_send_accepts_every_mobile_format(): void
    {
        foreach (['09181234567', '9191234567', '639201234567', '+63 921-123-4567'] as $i => $input) {
            $this->send('sms', $input)->assertOk();
        }

        $this->assertSame(['+639181234567', '+639191234567', '+639201234567', '+639211234567'], $this->sms->sent);
    }

    public function test_send_rejects_a_landline(): void
    {
        foreach (['02 8123 4567', '+63 2 8123 4567', '0917123456', 'hello'] as $input) {
            $this->send('sms', $input)->assertUnprocessable()->assertJsonValidationErrors('destination');
        }

        $this->assertSame([], $this->sms->sent);
    }

    public function test_send_gives_the_same_reply_for_known_and_unknown_numbers(): void
    {
        $this->userWithRole('user', ['phone' => '+639171234567', 'phone_verified_at' => now()]);

        $known = $this->send('sms', '+639171234567')->assertOk()->json();
        $unknown = $this->send('sms', '+639181234567')->assertOk()->json();

        $this->assertSame(
            str_replace('917', 'X', json_encode($known)),
            str_replace(['918', '+639181234567', '+639171234567'], ['X', '+639171234567', '+639171234567'], str_replace('917', 'X', json_encode($unknown))),
        );
        $this->assertSame(array_keys($known), array_keys($unknown));
        $this->assertSame(array_keys($known['data']), array_keys($unknown['data']));
    }

    public function test_second_send_within_a_minute_is_refused(): void
    {
        $this->send('sms', '09171234567')->assertOk();

        $this->send('sms', '+639171234567')
            ->assertStatus(429)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Wait before asking for another code.');

        $wait = $this->send('sms', '+639171234567')->json('data.resend_in');
        $this->assertIsInt($wait);
        $this->assertGreaterThan(0, $wait);
        $this->assertLessThanOrEqual(60, $wait);
    }

    public function test_send_failure_returns_502_without_the_code(): void
    {
        $this->sms->failSending = true;

        $this->send('sms', '09171234567')
            ->assertStatus(502)
            ->assertJsonPath('message', "We couldn't send the code. Try again in a minute.");
    }

    // email codes

    public function test_email_code_is_stored_only_as_a_hash(): void
    {
        $this->send('email', 'Juan@Example.com')->assertOk()->assertJsonPath('data.destination', 'juan@example.com');

        $code = $this->sentEmailCode();
        $row = OtpCode::query()->sole();

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertNotSame($code, $row->code_hash);
        $this->assertStringNotContainsString($code, json_encode($row->getAttributes()));
        $this->assertTrue(password_verify($code, $row->code_hash));
    }

    public function test_five_wrong_email_codes_lock_it(): void
    {
        $this->send('email', 'juan@example.com')->assertOk();
        $code = $this->sentEmailCode();
        $wrong = $code === '000000' ? '111111' : '000000';

        foreach ([4, 3, 2, 1] as $left) {
            $this->verify('email', 'juan@example.com', $wrong)->assertUnprocessable()->assertJsonPath('data.attempts_left', $left);
        }

        $this->verify('email', 'juan@example.com', $wrong)->assertStatus(429);
        $this->verify('email', 'juan@example.com', $code)->assertStatus(410);
    }

    public function test_expired_email_code_gives_410(): void
    {
        $this->send('email', 'juan@example.com')->assertOk();
        $code = $this->sentEmailCode();

        Carbon::setTestNow(now()->addMinutes(11));

        $this->verify('email', 'juan@example.com', $code)
            ->assertStatus(410)
            ->assertJsonPath('message', 'This code has expired. Send a new one.');
    }

    public function test_used_email_code_cannot_be_reused(): void
    {
        $this->send('email', 'juan@example.com')->assertOk();
        $code = $this->sentEmailCode();

        $this->verify('email', 'juan@example.com', $code)->assertOk();
        $this->verify('email', 'juan@example.com', $code)->assertStatus(410);
    }

    public function test_new_email_code_replaces_the_old_one(): void
    {
        $this->send('email', 'juan@example.com')->assertOk();
        $first = $this->sentEmailCode();
        $this->travel(61)->seconds();
        Mail::fake();
        $this->send('email', 'juan@example.com')->assertOk();

        if ($first !== $this->sentEmailCode()) {
            $this->verify('email', 'juan@example.com', $first)->assertUnprocessable();
        }
        $this->assertSame(1, OtpCode::query()->whereNull('consumed_at')->count());
    }

    // verify

    public function test_existing_user_signs_in(): void
    {
        $user = $this->userWithRole('user', ['phone' => '+639171234567', 'phone_verified_at' => now()]);

        $response = $this->verify('sms', '09171234567', FakeOtpSender::GOOD_CODE)
            ->assertOk()
            ->assertJsonPath('is_new_user', false)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $user->id);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_legacy_phone_format_is_matched_and_rewritten(): void
    {
        $user = $this->legacyPhoneUser('0917-123-4567');

        $this->verify('sms', '+639171234567', FakeOtpSender::GOOD_CODE)->assertOk()->assertJsonPath('data.id', $user->id);

        $this->assertSame('+639171234567', $user->fresh()->phone);
        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    public function test_existing_email_matches_regardless_of_case(): void
    {
        $user = $this->userWithRole('user', ['email' => 'Juan@Example.com', 'email_verified_at' => null]);
        $user->forceFill(['legacy_email_sign_in' => true])->save();
        $this->send('email', 'juan@example.com')->assertOk();

        $this->verify('email', 'JUAN@example.com', $this->sentEmailCode())->assertOk()->assertJsonPath('data.id', $user->id);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_account_without_a_mobile_role_gets_403(): void
    {
        User::factory()->create(['phone' => '+639171234567', 'phone_verified_at' => now()]);

        $this->verify('sms', '+639171234567', FakeOtpSender::GOOD_CODE)
            ->assertForbidden()
            ->assertJsonPath('message', 'This account is not enabled for the mobile app. Use an account with a valid role.');
    }

    public function test_deleted_account_is_refused_not_duplicated(): void
    {
        $this->userWithRole('user', ['phone' => '+639171234567', 'phone_verified_at' => now()])->delete();

        $this->verify('sms', '+639171234567', FakeOtpSender::GOOD_CODE)->assertForbidden();
    }

    public function test_verified_phone_wins_when_a_number_is_on_several_accounts(): void
    {
        $this->legacyPhoneUser('09171234567');
        $verified = $this->userWithRole('user', ['phone' => '+639171234567', 'phone_verified_at' => now()]);

        $this->verify('sms', '+639171234567', FakeOtpSender::GOOD_CODE)->assertJsonPath('data.id', $verified->id);
    }

    public function test_wrong_sms_code_gives_422_with_attempts_left(): void
    {
        $this->verify('sms', '+639171234567', '000000')
            ->assertUnprocessable()
            ->assertJsonPath('message', "That code isn't right. Check it and try again.")
            ->assertJsonPath('data.attempts_left', 4);
    }

    public function test_unknown_number_gets_a_registration_token(): void
    {
        $this->verify('sms', '+639171234567', FakeOtpSender::GOOD_CODE)
            ->assertOk()
            ->assertJsonPath('is_new_user', true)
            ->assertJsonPath('message', 'Code confirmed.')
            ->assertJsonPath('data.expires_in', 1800)
            ->assertJsonPath('data.channel', 'sms')
            ->assertJsonPath('data.destination', '+639171234567');

        $this->assertSame(0, User::query()->count());
    }

    // register

    public function test_register_creates_an_unverified_citizen(): void
    {
        $response = $this->signUp($this->registrationToken())
            ->assertCreated()
            ->assertJsonPath('is_new_user', false)
            ->assertJsonPath('message', 'Account created.')
            ->assertJsonPath('app_role', 'citizen')
            ->assertJsonPath('data.status', 'for_verification')
            ->assertJsonPath('data.fname', 'Juan')
            ->assertJsonPath('data.birthdate', '1990-04-15');

        $this->assertContains('valid_id', $response->json('data.missing_requirements'));
        $this->assertContains('selfie', $response->json('data.missing_requirements'));
        $this->assertContains('email', $response->json('data.missing_requirements'));
        $this->assertNotContains('phone', $response->json('data.missing_requirements'));

        $user = User::query()->sole();
        $this->assertSame('Juan Dela Cruz', $user->name);
        $this->assertSame('+639171234567', $user->phone);
        $this->assertNotNull($user->phone_verified_at);
        $this->assertNull($user->email);
        $this->assertSame('1990-04-15', $user->birthdate->format('Y-m-d'));
        $this->assertSame(9.0195, (float) $user->latitude);
        $this->assertNotNull($user->address_updated_at);
        $this->assertTrue($user->hasRole('user'));
    }

    public function test_register_by_email_stores_the_verified_email(): void
    {
        $this->send('email', 'juan@example.com')->assertOk();
        $token = $this->verify('email', 'juan@example.com', $this->sentEmailCode())->json('data.registration_token');

        $this->signUp($token)->assertCreated();

        $user = User::query()->sole();
        $this->assertSame('juan@example.com', $user->email);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->phone);
    }

    public function test_registration_token_works_once(): void
    {
        $token = $this->registrationToken();

        $this->signUp($token)->assertCreated();
        $this->signUp($token)->assertStatus(410);

        $this->assertSame(1, User::query()->count());
    }

    public function test_validation_error_keeps_the_token_usable(): void
    {
        $token = $this->registrationToken();

        $this->signUp($token, ['fname' => ''])->assertUnprocessable();
        $this->signUp($token)->assertCreated();
    }

    public function test_expired_registration_token_gives_410(): void
    {
        $token = $this->registrationToken();
        $this->travel(31)->minutes();

        $this->signUp($token)
            ->assertStatus(410)
            ->assertJsonPath('message', 'Your sign-up took too long. Request a new code.');
    }

    public function test_future_birthdate_is_rejected(): void
    {
        $this->signUp($this->registrationToken(), ['birthdate' => now()->addDay()->format('Y-m-d')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('birthdate');
    }

    public function test_account_created_meanwhile_is_signed_in_instead_of_duplicated(): void
    {
        $token = $this->registrationToken();
        $existing = $this->userWithRole('user', ['phone' => '09171234567', 'phone_verified_at' => now()]);

        $this->signUp($token)->assertOk()->assertJsonPath('data.id', $existing->id);

        $this->assertSame(1, User::query()->count());
    }

    public function test_verified_account_has_no_missing_requirements(): void
    {
        $user = $this->userWithRole('user', ['phone' => '+639171234567', 'phone_verified_at' => now(), 'confirmed_at' => now()]);

        $this->verify('sms', '+639171234567', FakeOtpSender::GOOD_CODE)
            ->assertJsonPath('data.status', 'verified')
            ->assertJsonPath('data.missing_requirements', []);
    }

    public function test_uploaded_id_and_selfie_leave_the_requirements(): void
    {
        $user = $this->userWithRole('user', ['phone' => '+639171234567', 'phone_verified_at' => now(), 'confirmed_at' => null]);
        UserValidId::query()->create(['user_id' => $user->id, 'filename' => 'img_valid_ids/id.jpg']);
        UserValidId::query()->create(['user_id' => $user->id, 'filename' => 'img_valid_ids/selfie.jpg']);

        $missing = $this->verify('sms', '+639171234567', FakeOtpSender::GOOD_CODE)
            ->assertJsonPath('data.status', 'pending_verification')
            ->json('data.missing_requirements');

        $this->assertNotContains('valid_id', $missing);
        $this->assertNotContains('selfie', $missing);
    }

    public function test_password_login_still_returns_the_old_keys(): void
    {
        $this->userWithRole('user', ['email' => 'juan@example.com', 'password' => bcrypt('secret-pass')]);

        $this->postJson('/api/v1/auth/login', ['user_id' => 'juan@example.com', 'password' => 'secret-pass'])
            ->assertOk()
            ->assertJsonStructure(['success', 'message', 'app_role', 'is_staff', 'is_citizen', 'data' => [
                'app_role', 'id', 'name', 'email', 'phone', 'address', 'email_verified_at', 'confirmed_at', 'status', 'token',
                'fname', 'lname', 'mname', 'suffix', 'birthdate', 'missing_requirements',
            ]]);
    }

    // profile/location

    public function test_location_update_saves_for_the_signed_in_user(): void
    {
        $user = $this->userWithRole('user');
        $other = $this->userWithRole('user', ['address' => 'Unchanged']);
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/profile/location', ['latitude' => 9.02, 'longitude' => 126.24, 'address' => 'Brgy. Gamut, Tago'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.address', 'Brgy. Gamut, Tago');

        $user->refresh();
        $this->assertSame('Brgy. Gamut, Tago', $user->address);
        $this->assertNotNull($user->address_updated_at);
        $this->assertSame('Unchanged', $other->fresh()->address);
    }

    public function test_location_update_needs_a_token(): void
    {
        $this->patchJson('/api/v1/profile/location', ['latitude' => 9.02, 'longitude' => 126.24, 'address' => 'Tago'])
            ->assertUnauthorized();
    }

    public function test_location_update_validates_coordinates(): void
    {
        Sanctum::actingAs($this->userWithRole('user'));

        $this->patchJson('/api/v1/profile/location', ['latitude' => 95, 'longitude' => 126.24, 'address' => 'Tago'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('latitude');
    }

    // unverified citizens keep working

    public function test_unverified_citizen_can_get_a_voice_token(): void
    {
        Config::set('services.twilio', array_merge(config('services.twilio'), [
            'sid' => 'AC000000000000000000000000000001',
            'api_key' => 'SK000000000000000000000000000001',
            'api_secret' => 'twilio_api_secret_test_only',
            'twiml_app_sid' => 'AP000000000000000000000000000001',
            'admin_identity' => '99',
        ]));
        $citizen = $this->userWithRole('user', ['confirmed_at' => null, 'email_verified_at' => null]);
        Sanctum::actingAs($citizen);

        $this->getJson('/api/v1/voice/token')->assertOk()->assertJsonPath('success', true);
    }

    public function test_unverified_citizen_can_send_a_report(): void
    {
        $citizen = $this->userWithRole('user', ['confirmed_at' => null, 'email_verified_at' => null]);
        Sanctum::actingAs($citizen);

        $this->postJson('/api/v1/report', [
            'latitude' => '9.0195',
            'longitude' => '126.2331',
            'type' => 'Message',
            'details' => 'Flood water rising',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertSame(1, Report::query()->where('user_id', $citizen->id)->count());
    }

    // phone trust

    public function test_unverified_phone_on_a_new_account_does_not_sign_in(): void
    {
        $intruder = $this->userWithRole('user', ['phone' => '+639171234567']);

        $this->verify('sms', '+639171234567', FakeOtpSender::GOOD_CODE)
            ->assertOk()
            ->assertJsonPath('is_new_user', true);

        $this->assertNull($intruder->fresh()->phone_verified_at);
    }

    public function test_changing_the_phone_removes_its_trust(): void
    {
        $user = $this->legacyPhoneUser('09171234567');
        $user->forceFill(['phone_verified_at' => now()])->save();
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile', ['phone' => '09181234567'])->assertOk();

        $user->refresh();
        $this->assertNull($user->phone_verified_at);
        $this->assertFalse($user->legacy_phone_sign_in);
        $this->verify('sms', '+639181234567', FakeOtpSender::GOOD_CODE)->assertJsonPath('is_new_user', true);
    }

    public function test_rewriting_the_same_number_keeps_its_trust(): void
    {
        $user = $this->userWithRole('user', ['phone' => '+639171234567', 'phone_verified_at' => now()]);
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile', ['phone' => '0917 123 4567'])->assertOk();

        $this->assertNotNull($user->fresh()->phone_verified_at);
    }

    public function test_profile_update_without_a_phone_keeps_its_trust(): void
    {
        $user = $this->legacyPhoneUser('09171234567');
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile', ['address' => 'Brgy. Gamut, Tago'])->assertOk();

        $this->assertTrue($user->fresh()->legacy_phone_sign_in);
    }

    public function test_migration_trusts_only_values_that_already_exist(): void
    {
        $migration = require database_path('migrations/2026_11_20_100200_add_legacy_sign_in_flags_to_users_table.php');
        $migration->down();
        $withPhone = $this->userWithRole('user', ['phone' => '09171234567']);
        $blankPhone = $this->userWithRole('user', ['phone' => ' ']);
        $noPhone = $this->userWithRole('user', ['phone' => null]);
        $noEmail = $this->userWithRole('user', ['email' => null]);

        $migration->up();

        $this->assertTrue($withPhone->fresh()->legacy_email_sign_in);
        $this->assertFalse($noEmail->fresh()->legacy_email_sign_in);

        $this->assertTrue($withPhone->fresh()->legacy_phone_sign_in);
        $this->assertFalse($blankPhone->fresh()->legacy_phone_sign_in);
        $this->assertFalse($noPhone->fresh()->legacy_phone_sign_in);
    }

    public function test_unverified_email_on_a_new_account_does_not_sign_in(): void
    {
        $this->userWithRole('user', ['email' => 'juan@example.com', 'email_verified_at' => null]);
        $this->send('email', 'juan@example.com')->assertOk();

        $this->verify('email', 'juan@example.com', $this->sentEmailCode())
            ->assertOk()
            ->assertJsonPath('is_new_user', true);
    }

    public function test_changing_the_email_removes_its_trust(): void
    {
        $user = $this->userWithRole('user', ['email' => 'juan@example.com']);
        $user->forceFill(['legacy_email_sign_in' => true])->save();
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile', ['email' => 'maria@example.com'])->assertOk();

        $user->refresh();
        $this->assertNull($user->email_verified_at);
        $this->assertFalse($user->legacy_email_sign_in);
    }

    public function test_changing_only_the_letter_case_keeps_email_trust(): void
    {
        $user = $this->userWithRole('user', ['email' => 'juan@example.com']);
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile', ['email' => 'Juan@Example.com'])->assertOk();

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_admin_edits_also_remove_trust(): void
    {
        $user = $this->userWithRole('user', ['email' => 'juan@example.com', 'phone' => '+639171234567', 'phone_verified_at' => now()]);

        $user->update(['email' => 'other@example.com', 'phone' => '+639181234567']);

        $user->refresh();
        $this->assertNull($user->email_verified_at);
        $this->assertNull($user->phone_verified_at);
    }

    public function test_code_sign_in_can_still_verify_a_new_value(): void
    {
        $user = $this->legacyPhoneUser('0917-123-4567');

        $this->verify('sms', '+639171234567', FakeOtpSender::GOOD_CODE)->assertOk();

        $this->assertNotNull($user->fresh()->phone_verified_at);
        $this->assertSame('+639171234567', $user->fresh()->phone);
    }
}


class FakeOtpSender implements OtpSender
{
    public const GOOD_CODE = '123456';

    /** @var list<string> */
    public array $sent = [];

    public bool $failSending = false;

    private array $wrongAttempts = [];

    public function send(string $channel, string $destination): void
    {
        if ($this->failSending) {
            throw new \RuntimeException('Twilio unavailable');
        }
        $this->sent[] = $destination;
    }

    public function check(string $channel, string $destination, string $code): OtpCheck
    {
        if ($code === self::GOOD_CODE) {
            return new OtpCheck(OtpCheck::APPROVED);
        }

        $this->wrongAttempts[$destination] = ($this->wrongAttempts[$destination] ?? 0) + 1;

        return new OtpCheck(OtpCheck::WRONG, self::MAX_ATTEMPTS - $this->wrongAttempts[$destination]);
    }
}
