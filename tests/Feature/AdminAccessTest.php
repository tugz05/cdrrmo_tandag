<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_guests_cannot_access_admin_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_citizen_users_cannot_access_admin_routes(): void
    {
        $user = User::factory()->create();
        $user->addRole('user');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_admin_users_can_access_admin_dashboard(): void
    {
        $user = User::factory()->create();
        $user->addRole('admin');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }
}
