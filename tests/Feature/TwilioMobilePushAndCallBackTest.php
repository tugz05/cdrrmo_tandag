<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class TwilioMobilePushAndCallBackTest extends TestCase
{
    use RefreshDatabase;

    private const ANDROID_SID = 'CR00000000000000000000000000000a';

    private const IOS_SID = 'CR00000000000000000000000000000b';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        Config::set('call.validate_twilio_webhook_signature', false);
        Config::set('services.twilio', array_merge(config('services.twilio'), [
            'sid' => 'AC000000000000000000000000000001',
            'api_key' => 'SK000000000000000000000000000001',
            'api_secret' => 'twilio_api_secret_test_only',
            'twiml_app_sid' => 'AP000000000000000000000000000001',
            'admin_identity' => '99',
            'webhook_public_origin' => 'http://localhost',
            'push_credential_sid_android' => self::ANDROID_SID,
            'push_credential_sid_ios' => self::IOS_SID,
        ]));
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->addRole($role);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function voiceGrant(TestResponse $response): array
    {
        $parts = explode('.', (string) $response->json('token'));
        $this->assertCount(3, $parts);
        $b64 = $parts[1].str_repeat('=', (4 - strlen($parts[1]) % 4) % 4);

        return json_decode(base64_decode(strtr($b64, '-_', '+/'), true), true, 512, JSON_THROW_ON_ERROR)['grants']['voice'];
    }

    private function tokenFor(User $user, string $query = ''): TestResponse
    {
        return $this->actingAs($user, 'sanctum')->getJson('/api/v1/voice/token'.$query)->assertOk();
    }

    private function reportBy(User $resident): Report
    {
        return Report::create([
            'user_id' => $resident->id,
            'latitude' => '9.0195',
            'longitude' => '126.2331',
            'type' => 'Call',
            'status' => 'Pending',
        ]);
    }

    private function voiceWebhook(array $fields): TestResponse
    {
        return $this->post('/twilio/voice', array_merge(['CallSid' => 'CA0000000000000000000000000000001'], $fields))->assertOk();
    }

    private function staffCallBack(User $caller, string $to, ?string $reportId): TestResponse
    {
        return $this->voiceWebhook(array_filter([
            'From' => 'client:'.$caller->id,
            'To' => $to,
            'call_type' => 'staff_to_resident',
            'report_id' => $reportId,
        ], fn ($value) => $value !== null));
    }

    private function assertRefused(TestResponse $response): void
    {
        $response->assertSee('This call is not allowed.', false);
        $this->assertStringNotContainsString('<Dial', $response->getContent());
    }

    public function test_android_token_carries_the_android_push_credential(): void
    {
        $response = $this->tokenFor($this->userWithRole('staff'), '?platform=android')
            ->assertJsonPath('push_enabled', true);

        $this->assertSame(self::ANDROID_SID, $this->voiceGrant($response)['push_credential_sid']);
    }

    public function test_ios_token_carries_the_ios_push_credential(): void
    {
        $response = $this->tokenFor($this->userWithRole('staff'), '?platform=ios');

        $this->assertSame(self::IOS_SID, $this->voiceGrant($response)['push_credential_sid']);
    }

    public function test_missing_or_unknown_platform_defaults_to_android(): void
    {
        $staff = $this->userWithRole('staff');

        $this->assertSame(self::ANDROID_SID, $this->voiceGrant($this->tokenFor($staff))['push_credential_sid']);
        $this->assertSame(self::ANDROID_SID, $this->voiceGrant($this->tokenFor($staff, '?platform=windows'))['push_credential_sid']);
    }

    public function test_empty_push_credential_leaves_the_claim_out(): void
    {
        Config::set('services.twilio.push_credential_sid_android', '');

        $response = $this->tokenFor($this->userWithRole('staff'), '?platform=android')
            ->assertJsonPath('push_enabled', false);

        $this->assertArrayNotHasKey('push_credential_sid', $this->voiceGrant($response));
    }

    public function test_citizen_token_can_receive_calls(): void
    {
        $response = $this->tokenFor($this->userWithRole('user'), '?platform=android')
            ->assertJsonPath('incoming_allow', true);

        $this->assertTrue($this->voiceGrant($response)['incoming']['allow']);
    }

    public function test_browser_token_has_no_push_credential(): void
    {
        $response = $this->actingAs($this->userWithRole('admin'))->getJson('/twilio/token')->assertOk();

        $this->assertArrayNotHasKey('push_credential_sid', $this->voiceGrant($response));
    }

    public function test_staff_can_call_the_resident_who_filed_the_report(): void
    {
        $resident = $this->userWithRole('user');
        $report = $this->reportBy($resident);

        $content = $this->staffCallBack($this->userWithRole('staff'), "resident:{$resident->id}", (string) $report->id)->getContent();

        $this->assertStringContainsString('<Dial timeout="30" answerOnBridge="true">', $content);
        $this->assertStringContainsString("<Client>{$resident->id}<Parameter name=\"report_id\" value=\"{$report->id}\"/>", $content);
        $this->assertStringContainsString('<Parameter name="call_type" value="staff_to_resident"/>', $content);
    }

    public function test_call_back_works_even_when_no_operator_is_ready(): void
    {
        Config::set('call.require_staff_presence_for_voice_twiml', true);
        $resident = $this->userWithRole('user');
        $report = $this->reportBy($resident);

        $this->staffCallBack($this->userWithRole('admin'), "resident:{$resident->id}", (string) $report->id)
            ->assertSee("<Client>{$resident->id}", false);
    }

    public function test_citizen_cannot_call_another_resident(): void
    {
        $resident = $this->userWithRole('user');
        $report = $this->reportBy($resident);

        $this->assertRefused($this->staffCallBack($this->userWithRole('user'), "resident:{$resident->id}", (string) $report->id));
    }

    public function test_report_owned_by_someone_else_is_refused(): void
    {
        $resident = $this->userWithRole('user');
        $otherReport = $this->reportBy($this->userWithRole('user'));

        $this->assertRefused($this->staffCallBack($this->userWithRole('staff'), "resident:{$resident->id}", (string) $otherReport->id));
    }

    public function test_missing_report_is_refused(): void
    {
        $resident = $this->userWithRole('user');
        $staff = $this->userWithRole('staff');

        $this->assertRefused($this->staffCallBack($staff, "resident:{$resident->id}", null));
        $this->assertRefused($this->staffCallBack($staff, "resident:{$resident->id}", '987654'));
    }

    public function test_non_numeric_resident_id_is_refused(): void
    {
        $resident = $this->userWithRole('user');
        $report = $this->reportBy($resident);
        $staff = $this->userWithRole('staff');

        foreach (['resident:abc', 'resident:', 'resident:0', 'resident:-5', "resident:{$resident->id}x"] as $to) {
            $this->assertRefused($this->staffCallBack($staff, $to, (string) $report->id));
        }
    }

    public function test_deleted_report_is_refused(): void
    {
        $resident = $this->userWithRole('user');
        $report = $this->reportBy($resident);
        $report->delete();

        $this->assertRefused($this->staffCallBack($this->userWithRole('staff'), "resident:{$resident->id}", (string) $report->id));
    }

    public function test_refusal_is_logged_with_call_details(): void
    {
        Log::spy();
        $resident = $this->userWithRole('user');

        $this->staffCallBack($this->userWithRole('user'), "resident:{$resident->id}", '5');

        Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context) use ($resident) {
            return $message === 'Twilio staff-to-resident call refused'
                && $context['To'] === "resident:{$resident->id}"
                && $context['report_id'] === '5'
                && $context['CallSid'] === 'CA0000000000000000000000000000001'
                && $context['reason'] === 'caller_not_dispatch_staff';
        })->once();
    }

    public function test_citizen_call_to_dispatch_is_unchanged(): void
    {
        Config::set('call.require_staff_presence_for_voice_twiml', false);
        $citizen = $this->userWithRole('user');

        $content = $this->voiceWebhook(['From' => 'client:'.$citizen->id, 'To' => 'dispatch'])->getContent();

        $this->assertStringNotContainsString('not allowed', $content);
        $this->assertStringContainsString('<Dial', $content);
        $this->assertStringContainsString('<Client', $content);
        $this->assertStringContainsString('/twilio/voice/dial-status', $content);
    }
}
