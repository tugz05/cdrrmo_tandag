<?php

namespace Tests\Feature\Api;

use App\Events\MessageReportReceived;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MessageReportBroadcastTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'latitude' => '9.0785',
            'longitude' => '126.1986',
            'details' => 'Flooding near the bridge',
            'type' => 'Message',
        ], $overrides);
    }

    public function test_new_message_report_broadcasts_alert(): void
    {
        Event::fake([MessageReportReceived::class]);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/report', $this->payload())->assertOk();

        Event::assertDispatched(MessageReportReceived::class, function (MessageReportReceived $event) {
            return $event->report->details === 'Flooding near the bridge'
                && $event->broadcastAs() === 'message-report.received';
        });
    }

    public function test_call_report_does_not_broadcast_message_alert(): void
    {
        Event::fake([MessageReportReceived::class]);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/report', $this->payload(['type' => 'Call']))->assertOk();

        Event::assertNotDispatched(MessageReportReceived::class);
    }

    public function test_updating_existing_message_report_does_not_broadcast_again(): void
    {
        $user = User::factory()->create();
        $report = Report::create($this->payload(['user_id' => $user->id, 'status' => 'Pending']));

        Event::fake([MessageReportReceived::class]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/report', $this->payload(['id' => $report->id, 'details' => 'Update']))->assertOk();

        Event::assertNotDispatched(MessageReportReceived::class);
    }

    public function test_only_admins_may_join_admin_reports_channel(): void
    {
        config(['broadcasting.default' => 'reverb']);

        $citizen = User::factory()->create();
        $citizen->addRole('user');
        $admin = User::factory()->create();
        $admin->addRole('admin');

        $params = ['socket_id' => '1234.5678', 'channel_name' => 'private-admin.reports'];

        $this->actingAs($citizen)->post('/broadcasting/auth', $params)->assertForbidden();
        $this->actingAs($admin)->post('/broadcasting/auth', $params)->assertOk();
    }
}
