<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TwilioTokenAndCallerAccessTest extends TestCase
{
    use RefreshDatabase;

    private const API_SECRET = 'test_api_secret_with_enough_length_for_hs256';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        config(['services.twilio' => array_merge((array) config('services.twilio'), [
            'sid' => 'AC00000000000000000000000000000000',
            'api_key' => 'SK00000000000000000000000000000000',
            'api_secret' => self::API_SECRET,
            'twiml_app_sid' => 'AP00000000000000000000000000000000',
        ])]);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->addRole($role);

        return $user;
    }

    private function voiceGrant(string $jwt): array
    {
        $claims = JWT::decode($jwt, new Key(self::API_SECRET, 'HS256'));

        return json_decode(json_encode($claims->grants), true);
    }

    public function test_guest_cannot_get_voice_token_even_for_a_named_operator(): void
    {
        $operator = $this->userWithRole('admin');

        $this->getJson('/twilio/token?identity='.$operator->id)->assertUnauthorized();
    }

    public function test_admin_token_uses_own_identity_and_ignores_query(): void
    {
        $admin = $this->userWithRole('admin');
        $other = $this->userWithRole('admin');

        $response = $this->actingAs($admin)->getJson('/twilio/token?identity='.$other->id)->assertOk();

        $this->assertSame((string) $admin->id, $response->json('identity'));
        $grants = $this->voiceGrant($response->json('token'));
        $this->assertSame((string) $admin->id, $grants['identity']);
        $this->assertTrue($grants['voice']['incoming']['allow']);
    }

    public function test_citizen_bearer_token_cannot_impersonate_an_operator_or_receive_calls(): void
    {
        $citizen = $this->userWithRole('user');
        $operator = $this->userWithRole('admin');
        Sanctum::actingAs($citizen);

        $response = $this->getJson('/twilio/token?identity='.$operator->id)->assertOk();

        $this->assertSame((string) $citizen->id, $response->json('identity'));
        $grants = $this->voiceGrant($response->json('token'));
        $this->assertSame((string) $citizen->id, $grants['identity']);
        $this->assertArrayNotHasKey('incoming', $grants['voice']);
    }

    public function test_guest_cannot_read_caller_details(): void
    {
        $citizen = $this->userWithRole('user');

        $this->getJson(route('caller-info.index', $citizen->id))->assertUnauthorized();
    }

    public function test_citizen_cannot_read_caller_details(): void
    {
        $citizen = $this->userWithRole('user');
        $other = $this->userWithRole('user');

        $this->actingAs($citizen)->getJson(route('caller-info.index', $other->id))->assertForbidden();
    }

    public function test_admin_gets_only_the_fields_the_call_alert_needs(): void
    {
        $admin = $this->userWithRole('admin');
        $citizen = $this->userWithRole('user');
        $report = Report::create([
            'user_id' => $citizen->id,
            'latitude' => '9.0195',
            'longitude' => '126.2331',
            'details' => 'Private report text',
            'type' => 'Call',
            'status' => 'Pending',
        ]);

        $response = $this->actingAs($admin)->getJson(route('caller-info.index', $citizen->id))->assertOk();

        $response->assertJsonPath('id', $citizen->id)
            ->assertJsonPath('latest_call.0.id', $report->id)
            ->assertJsonPath('latest_call.0.latitude', '9.0195')
            ->assertJsonMissingPath('email')
            ->assertJsonMissingPath('address')
            ->assertJsonMissingPath('latitude')
            ->assertJsonMissingPath('longitude')
            ->assertJsonMissingPath('google_id')
            ->assertJsonMissingPath('latest_call.0.details');
    }

    public function test_voice_test_pages_require_an_admin(): void
    {
        $this->get('/caller')->assertRedirect(route('login'));
        $this->get('/receiver')->assertRedirect(route('login'));

        $citizen = $this->userWithRole('user');
        $this->actingAs($citizen)->get('/caller')->assertForbidden();
    }
}
