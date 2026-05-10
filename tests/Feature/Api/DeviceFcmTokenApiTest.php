<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\UserDeviceFcmToken;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceFcmTokenApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_fcm_token_requires_authentication(): void
    {
        $this->postJson('/api/v1/device/fcm-token', [
            'fcm_token' => 'fake-fcm-token-string',
            'platform' => 'android',
        ])->assertUnauthorized();
    }

    public function test_fcm_token_validates_platform(): void
    {
        $user = User::factory()->create();
        $user->addRole('user');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/device/fcm-token', [
                'fcm_token' => 'token-a',
                'platform' => 'windows',
            ])
            ->assertUnprocessable();
    }

    public function test_fcm_token_upserts_per_user_and_platform(): void
    {
        $user = User::factory()->create();
        $user->addRole('user');

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/device/fcm-token', [
                'fcm_token' => 'first-token',
                'platform' => 'android',
            ])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.platform', 'android');

        $this->assertDatabaseHas('user_device_fcm_tokens', [
            'user_id' => $user->id,
            'platform' => 'android',
            'fcm_token' => 'first-token',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/device/fcm-token', [
                'fcm_token' => 'second-token',
                'platform' => 'android',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $row = UserDeviceFcmToken::query()->where('user_id', $user->id)->where('platform', 'android')->first();
        $this->assertSame('second-token', $row?->fcm_token);
    }

    public function test_same_fcm_token_moves_from_other_user(): void
    {
        $alice = User::factory()->create();
        $alice->addRole('user');
        $bob = User::factory()->create();
        $bob->addRole('user');

        UserDeviceFcmToken::query()->create([
            'user_id' => $alice->id,
            'platform' => 'android',
            'fcm_token' => 'shared-once',
        ]);

        $this->actingAs($bob, 'sanctum')
            ->postJson('/api/v1/device/fcm-token', [
                'fcm_token' => 'shared-once',
                'platform' => 'android',
            ])
            ->assertSuccessful();

        $this->assertDatabaseMissing('user_device_fcm_tokens', [
            'user_id' => $alice->id,
            'fcm_token' => 'shared-once',
        ]);
        $this->assertDatabaseHas('user_device_fcm_tokens', [
            'user_id' => $bob->id,
            'fcm_token' => 'shared-once',
        ]);
    }
}
