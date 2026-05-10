<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\StaffPresenceService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Twilio\Security\RequestValidator;

class TwilioProgrammableVoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->configureTwilioForVoiceJwt();
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJwtPayload(string $jwt): array
    {
        $parts = explode('.', $jwt);
        $this->assertCount(3, $parts);
        $b64 = $parts[1];
        $b64 .= str_repeat('=', (4 - strlen($b64) % 4) % 4);
        $decoded = base64_decode(strtr($b64, '-_', '+/'), true);
        $this->assertNotFalse($decoded);

        return json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
    }

    private function voiceGrantIncomingAllowed(array $payload): bool
    {
        $voice = $payload['grants']['voice'] ?? [];

        return isset($voice['incoming']['allow']) && (bool) $voice['incoming']['allow'];
    }

    private function configureTwilioForVoiceJwt(): void
    {
        Config::set('services.twilio', array_merge(config('services.twilio'), [
            'sid' => 'AC000000000000000000000000000001',
            'api_key' => 'SK000000000000000000000000000001',
            'api_secret' => 'twilio_api_secret_test_only',
            'twiml_app_sid' => 'AP000000000000000000000000000001',
            'admin_identity' => '99',
            'auth_token' => 'twilio_auth_token_test_only',
            'webhook_public_origin' => 'http://localhost',
        ]));
    }

    public function test_mobile_voice_token_citizen_has_outgoing_only_grant(): void
    {
        $user = User::factory()->create();
        $user->addRole('user');

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/voice/token');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('incoming_allow', false);

        $this->assertIsString($response->json('dial_to'));
        $jwt = $response->json('token');
        $this->assertIsString($jwt);
        $payload = $this->decodeJwtPayload($jwt);
        $this->assertFalse($this->voiceGrantIncomingAllowed($payload));
        $this->assertSame(
            config('services.twilio.twiml_app_sid'),
            $payload['grants']['voice']['outgoing']['application_sid'] ?? null
        );
    }

    public function test_mobile_voice_token_staff_has_incoming_grant(): void
    {
        $user = User::factory()->create();
        $user->addRole('staff');

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/voice/token');

        $response->assertOk()
            ->assertJsonPath('incoming_allow', true);

        $jwt = $response->json('token');
        $this->assertIsString($jwt);
        $payload = $this->decodeJwtPayload($jwt);
        $this->assertTrue($this->voiceGrantIncomingAllowed($payload));
    }

    public function test_api_staff_heartbeat_forbidden_for_citizen(): void
    {
        $user = User::factory()->create();
        $user->addRole('user');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/staff/heartbeat', ['twilio_voice_ready' => true])
            ->assertForbidden()
            ->assertJsonPath('code', 'NOT_VOICE_DISPATCH_OPERATOR');
    }

    public function test_api_staff_heartbeat_ok_for_staff(): void
    {
        $user = User::factory()->create();
        $user->addRole('staff');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/staff/heartbeat', ['twilio_voice_ready' => true])
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }

    public function test_twilio_client_status_rejects_invalid_signature_when_validation_enabled(): void
    {
        Config::set('call.validate_twilio_webhook_signature', true);

        $this->post('/twilio/voice/client-status', [
            'CallSid' => 'CAtest123',
            'CallStatus' => 'ringing',
        ])->assertForbidden();
    }

    public function test_twilio_client_status_accepts_valid_signature(): void
    {
        Config::set('call.validate_twilio_webhook_signature', true);

        $url = 'http://localhost/twilio/voice/client-status';
        $params = [
            'CallSid' => 'CAtest123',
            'CallStatus' => 'ringing',
        ];
        $signature = (new RequestValidator((string) config('services.twilio.auth_token')))
            ->computeSignature($url, $params);

        $this->post('/twilio/voice/client-status', $params, [
            'HTTP_X_TWILIO_SIGNATURE' => $signature,
        ])
            ->assertOk()
            ->assertHeader('Content-Type', 'text/xml; charset=utf-8');
    }

    public function test_voice_dispatch_operator_pool_includes_staff_role(): void
    {
        $staff = User::factory()->create();
        $staff->addRole('staff');
        $admin = User::factory()->create();
        $admin->addRole('admin');

        $ids = app(StaffPresenceService::class)->operatorUserIds();
        sort($ids);

        $expected = [$staff->id, $admin->id];
        sort($expected);

        $this->assertSame($expected, $ids);
    }
}
