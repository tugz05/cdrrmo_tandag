<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_profile_update_requires_authentication(): void
    {
        $this->putJson('/api/v1/profile', [
            'email' => 'x@example.com',
        ])->assertUnauthorized();
    }

    public function test_authenticated_user_updates_own_profile_only(): void
    {
        $user = User::factory()->create(['email' => 'a@example.com']);
        $user->addRole('user');

        $response = $this->actingAs($user, 'sanctum')
            ->putJson('/api/v1/profile', [
                'email' => 'b@example.com',
            ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertSame('b@example.com', $user->fresh()->email);
    }
}
