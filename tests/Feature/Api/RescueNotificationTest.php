<?php

namespace Tests\Feature\Api;

use App\Models\Report;
use App\Models\User;
use App\Models\UserDeviceFcmToken;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Laravel\Sanctum\Sanctum;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class RescueNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['broadcasting.default' => 'null']);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->addRole($role);

        return $user;
    }

    private function reportWithResident(bool $withDevice = true): Report
    {
        $resident = $this->userWithRole('user');

        if ($withDevice) {
            UserDeviceFcmToken::query()->create([
                'user_id' => $resident->id,
                'platform' => 'android',
                'fcm_token' => 'example-token',
            ]);
        }

        return Report::create([
            'user_id' => $resident->id,
            'latitude' => '9.0195',
            'longitude' => '126.2331',
            'type' => 'Message',
            'status' => 'Pending',
            'rescue_status' => 'pending',
        ]);
    }

    private function failingFirebase(): void
    {
        $this->app->bind(Messaging::class, function () {
            throw new RuntimeException('Unable to determine the Firebase Project ID');
        });
    }

    public function test_start_rescue_succeeds_and_is_saved_when_firebase_is_unavailable(): void
    {
        $this->failingFirebase();
        $report = $this->reportWithResident();
        $rescuer = $this->userWithRole('staff');
        Sanctum::actingAs($rescuer);

        $this->postJson("/api/v1/reports/{$report->id}/start-rescue")
            ->assertOk()
            ->assertJsonPath('rescue_status', 'en_route')
            ->assertJsonPath('resident_notified', false);

        $report->refresh();
        $this->assertSame('en_route', $report->rescue_status);
        $this->assertSame($rescuer->id, (int) $report->rescuer_user_id);
    }

    public function test_arrival_succeeds_when_firebase_is_unavailable(): void
    {
        $this->failingFirebase();
        $report = $this->reportWithResident();
        Sanctum::actingAs($this->userWithRole('staff'));

        $this->postJson("/api/v1/reports/{$report->id}/rescue-arrived")
            ->assertOk()
            ->assertJsonPath('resident_notified', false);

        $this->assertSame('arrived', $report->fresh()->rescue_status);
    }

    public function test_failed_notification_is_logged_without_device_tokens(): void
    {
        $this->failingFirebase();
        $report = $this->reportWithResident();
        Sanctum::actingAs($this->userWithRole('staff'));
        Log::spy();

        $this->postJson("/api/v1/reports/{$report->id}/start-rescue")->assertOk();

        Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context) use ($report) {
            return $message === 'Rescue notification failed'
                && $context['report_id'] === $report->id
                && ! str_contains(json_encode($context), 'example-token');
        })->once();
    }

    public function test_resident_is_notified_when_firebase_works(): void
    {
        $messaging = Mockery::mock(Messaging::class);
        $messaging->shouldReceive('sendMulticast')
            ->once()
            ->withArgs(fn ($message, $tokens) => $tokens === ['example-token'])
            ->andReturn(MulticastSendReport::withItems([]));
        $this->app->instance(Messaging::class, $messaging);

        $report = $this->reportWithResident();
        Sanctum::actingAs($this->userWithRole('staff'));

        $this->postJson("/api/v1/reports/{$report->id}/start-rescue")
            ->assertOk()
            ->assertJsonPath('resident_notified', true);
    }

    public function test_resident_with_no_registered_device_is_reported_as_not_notified(): void
    {
        $this->failingFirebase();
        $report = $this->reportWithResident(withDevice: false);
        Sanctum::actingAs($this->userWithRole('staff'));

        $this->postJson("/api/v1/reports/{$report->id}/start-rescue")
            ->assertOk()
            ->assertJsonPath('resident_notified', false);
    }

    public function test_report_without_an_account_reports_no_notification(): void
    {
        $report = Report::create([
            'latitude' => '9.0195',
            'longitude' => '126.2331',
            'type' => 'Call',
            'status' => 'Pending',
            'rescue_status' => 'pending',
        ]);
        Sanctum::actingAs($this->userWithRole('staff'));

        $this->postJson("/api/v1/reports/{$report->id}/start-rescue")
            ->assertOk()
            ->assertJsonPath('resident_notified', false);
    }

    public function test_residents_still_cannot_start_a_rescue(): void
    {
        $this->failingFirebase();
        $report = $this->reportWithResident();
        Sanctum::actingAs($report->user);

        $this->postJson("/api/v1/reports/{$report->id}/start-rescue")->assertForbidden();

        $this->assertSame('pending', $report->fresh()->rescue_status);
    }
}
