<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_update_status_methods(): void
    {
        $user = User::factory()->create();
        $result = (new UserService)->verify((string) $user->id);

        $this->assertTrue($result);

        $this->test_destroy_method($user);
        $this->test_restore_method($user);
        $this->test_destroy_method($user);
        $this->test_force_delete($user);
    }

    private function test_destroy_method(User $user): void
    {
        (new UserService)->destroy($user->id);
        $this->assertSoftDeleted($user);
    }

    private function test_restore_method(User $user): void
    {
        (new UserService)->restore($user->id);
        $this->assertNotSoftDeleted($user);
    }

    private function test_force_delete(User $user): void
    {
        $user->forceDelete();
        $this->assertModelMissing($user);
    }
}
