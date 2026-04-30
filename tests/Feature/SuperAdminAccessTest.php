<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function makeSuperAdmin(): User
    {
        $user = User::factory()->create();
        $user->syncRoles(['super_admin']);

        return $user;
    }

    private function makeAdmin(): User
    {
        $user = User::factory()->create();
        $user->addRole('admin');

        return $user;
    }

    public function test_admin_cannot_visit_staff_accounts_page(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('administrators.index'))
            ->assertForbidden();
    }

    public function test_super_admin_can_visit_staff_accounts_page(): void
    {
        $super = $this->makeSuperAdmin();

        $this->actingAs($super)
            ->get(route('administrators.index'))
            ->assertOk();
    }

    public function test_admin_cannot_create_resident_via_users_store(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Test Resident',
                'email' => 'resident@test.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertForbidden();
    }

    public function test_super_admin_can_create_resident_via_users_store(): void
    {
        $super = $this->makeSuperAdmin();

        $this->actingAs($super)
            ->post(route('users.store'), [
                'name' => 'Test Resident',
                'email' => 'resident@test.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'resident@test.com']);
        $resident = User::where('email', 'resident@test.com')->first();
        $this->assertTrue($resident->hasRole('user'));
    }
}
