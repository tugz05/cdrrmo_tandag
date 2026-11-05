<?php

namespace Tests\Feature\Api;

use App\Events\LiveLocationUpdated;
use App\Events\RescueStatusUpdated;
use App\Models\Report;
use App\Models\ReportLocationPoint;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class LiveLocationTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/live-location';

    private const LAT = 9.017912;

    private const LNG = 126.233611;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['broadcasting.default' => 'null']);
        Carbon::setTestNow('2026-11-05 08:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->addRole($role);

        return $user;
    }

    private function reportFor(?User $owner, array $attributes = []): Report
    {
        return Report::create(array_merge([
            'user_id' => $owner?->id,
            'latitude' => '9.0195',
            'longitude' => '126.2331',
            'type' => 'Message',
            'status' => 'Pending',
            'rescue_status' => 'pending',
        ], $attributes));
    }

    private function sample(array $overrides = []): array
    {
        return array_merge([
            'lat' => self::LAT,
            'lng' => self::LNG,
            'accuracy' => 8,
            'speed' => 11.2,
            'heading' => 205,
            'recorded_at' => now()->toIso8601String(),
        ], $overrides);
    }

    private function send(?Report $report, array $samples = [], string $mode = 'navigating')
    {
        return $this->postJson(self::URL, [
            'report_id' => $report?->id,
            'mode' => $mode,
            'samples' => $samples,
        ]);
    }

    private function shareAs(User $user, ?Report $report, array $sample = [], string $mode = 'navigating')
    {
        Sanctum::actingAs($user);

        return $this->send($report, [$this->sample($sample)], $mode)->assertOk();
    }

    public function test_requires_a_token(): void
    {
        $this->postJson(self::URL, ['mode' => 'navigating', 'samples' => []])->assertUnauthorized();
    }

    public function test_missing_token_gets_401_even_without_a_json_accept_header(): void
    {
        $this->post(self::URL, ['mode' => 'navigating', 'samples' => []])->assertUnauthorized();
    }

    public function test_resident_may_post_for_their_own_open_report(): void
    {
        $resident = $this->userWithRole('user');
        Sanctum::actingAs($resident);

        $this->send($this->reportFor($resident), [$this->sample()], 'active_report')
            ->assertOk()
            ->assertJsonPath('data.session_active', true);
    }

    public function test_resident_cannot_post_for_someone_elses_report(): void
    {
        $report = $this->reportFor($this->userWithRole('user'));
        Sanctum::actingAs($this->userWithRole('user'));

        $this->send($report, [$this->sample()])->assertForbidden();
        $this->send($report)->assertForbidden();
        $this->assertSame(0, ReportLocationPoint::count());
    }

    public function test_resident_cannot_post_without_a_report(): void
    {
        Sanctum::actingAs($this->userWithRole('user'));

        $this->send(null, [$this->sample()], 'on_duty')->assertForbidden();
    }

    public function test_every_dispatch_role_may_post_for_any_open_report(): void
    {
        $report = $this->reportFor($this->userWithRole('user'));

        foreach (['staff', 'admin', 'super_admin'] as $role) {
            Sanctum::actingAs($this->userWithRole($role));
            $this->send($report, [$this->sample()])->assertOk();
        }
    }

    public function test_staff_may_share_on_duty_and_get_no_peer(): void
    {
        foreach (['staff', 'admin', 'super_admin'] as $role) {
            Sanctum::actingAs($this->userWithRole($role));
            $this->send(null, [$this->sample()], 'on_duty')
                ->assertOk()
                ->assertJsonPath('data.peer', null)
                ->assertJsonPath('data.rescue_status', null)
                ->assertJsonPath('data.session_active', true);
        }

        $this->assertSame(0, ReportLocationPoint::count());
    }

    public function test_unknown_report_gets_404(): void
    {
        Sanctum::actingAs($this->userWithRole('staff'));

        $this->postJson(self::URL, ['report_id' => 999999, 'mode' => 'navigating', 'samples' => []])->assertNotFound();
    }

    public function test_rejects_out_of_range_coordinates(): void
    {
        $report = $this->reportFor(null);
        Sanctum::actingAs($this->userWithRole('staff'));

        $this->send($report, [$this->sample(['lat' => 90.1])])->assertUnprocessable();
        $this->send($report, [$this->sample(['lng' => -180.1])])->assertUnprocessable();
    }

    public function test_rejects_more_than_50_samples(): void
    {
        $report = $this->reportFor(null);
        Sanctum::actingAs($this->userWithRole('staff'));

        $this->send($report, array_fill(0, 51, $this->sample()))->assertUnprocessable();
        $this->send($report, array_fill(0, 50, $this->sample()))->assertOk();
    }

    public function test_rejects_missing_samples_key_and_bad_mode(): void
    {
        Sanctum::actingAs($this->userWithRole('staff'));

        $this->postJson(self::URL, ['mode' => 'navigating'])->assertUnprocessable();
        $this->postJson(self::URL, ['mode' => 'teleporting', 'samples' => []])->assertUnprocessable();
    }

    public function test_optional_sample_fields_may_be_absent(): void
    {
        $report = $this->reportFor($resident = $this->userWithRole('user'));
        Sanctum::actingAs($resident);

        $this->send($report, [['lat' => self::LAT, 'lng' => self::LNG, 'recorded_at' => now()->toIso8601String()]])
            ->assertOk();

        $this->assertNull(ReportLocationPoint::first()->speed);
    }

    public function test_reply_shape_with_no_peer_yet(): void
    {
        $resident = $this->userWithRole('user');
        Sanctum::actingAs($resident);

        $this->send($this->reportFor($resident))
            ->assertOk()
            ->assertExactJson(['data' => [
                'peer' => null,
                'rescue_status' => 'pending',
                'next_interval_seconds' => 4,
                'session_active' => true,
            ]]);
    }

    public function test_resident_gets_assigned_rescuer_with_position_fields_only(): void
    {
        $resident = $this->userWithRole('user');
        $rescuer = $this->userWithRole('staff');
        $report = $this->reportFor($resident, ['rescuer_user_id' => $rescuer->id, 'rescue_status' => 'en_route']);

        $this->shareAs($rescuer, $report, ['recorded_at' => now()->subSeconds(3)->toIso8601String()]);

        Sanctum::actingAs($resident);
        $response = $this->send($report)->assertOk()->assertJsonPath('data.rescue_status', 'en_route');

        $this->assertEqualsCanonicalizing(
            ['lat', 'lng', 'accuracy', 'speed', 'heading', 'age_seconds'],
            array_keys($response->json('data.peer'))
        );
        $response->assertJsonPath('data.peer.age_seconds', 3)
            ->assertJsonPath('data.peer.lat', self::LAT);
    }

    public function test_resident_never_sees_staff_who_are_not_the_assigned_rescuer(): void
    {
        $resident = $this->userWithRole('user');
        $rescuer = $this->userWithRole('staff');
        $otherStaff = $this->userWithRole('staff');
        $report = $this->reportFor($resident);

        $this->shareAs($otherStaff, $report);
        Sanctum::actingAs($resident);
        $this->send($report)->assertOk()->assertJsonPath('data.peer', null);

        $report->update(['rescuer_user_id' => $rescuer->id]);
        $this->send($report)->assertOk()->assertJsonPath('data.peer', null);

        $this->shareAs($rescuer, $report, ['lat' => 9.5]);
        Sanctum::actingAs($resident);
        $this->send($report)->assertOk()->assertJsonPath('data.peer.lat', 9.5);
    }

    public function test_staff_member_who_is_not_the_rescuer_is_not_broadcast_to_the_report_channel(): void
    {
        Event::fake([LiveLocationUpdated::class]);
        $resident = $this->userWithRole('user');
        $rescuer = $this->userWithRole('staff');
        $report = $this->reportFor($resident, ['rescuer_user_id' => $rescuer->id]);

        $this->shareAs($this->userWithRole('staff'), $report);
        Event::assertNotDispatched(LiveLocationUpdated::class);

        $this->shareAs($rescuer, $report);
        Event::assertDispatched(LiveLocationUpdated::class, fn ($event) => $event->role === 'rescuer');
    }

    public function test_staff_get_the_residents_position(): void
    {
        $resident = $this->userWithRole('user');
        $report = $this->reportFor($resident);
        $this->shareAs($resident, $report, ['lat' => 9.25], 'active_report');

        Sanctum::actingAs($this->userWithRole('staff'));
        $this->send($report)->assertOk()->assertJsonPath('data.peer.lat', 9.25);
    }

    public function test_age_seconds_is_never_negative_for_a_future_timestamp(): void
    {
        $resident = $this->userWithRole('user');
        $report = $this->reportFor($resident);
        $this->shareAs($resident, $report, ['recorded_at' => now()->addHour()->toIso8601String()]);

        Sanctum::actingAs($this->userWithRole('staff'));
        $this->send($report)->assertOk()->assertJsonPath('data.peer.age_seconds', 0);

        $this->assertTrue(ReportLocationPoint::first()->recorded_at->equalTo(now()));
    }

    #[DataProvider('closedReports')]
    public function test_closed_report_stops_the_session_and_stores_nothing(array $closed): void
    {
        Event::fake([LiveLocationUpdated::class]);
        $resident = $this->userWithRole('user');
        $rescuer = $this->userWithRole('staff');
        $this->shareAs($rescuer, null, ['lat' => 9.9], 'on_duty');
        Event::fake([LiveLocationUpdated::class]);
        $report = $this->reportFor($resident, ['rescuer_user_id' => $rescuer->id] + $closed);

        Sanctum::actingAs($resident);
        $this->send($report, [$this->sample()], 'active_report')
            ->assertOk()
            ->assertJsonPath('data.session_active', false)
            ->assertJsonPath('data.peer', null);

        $this->assertSame(0, ReportLocationPoint::count());
        $this->assertNull(Cache::get("live_location:user:{$resident->id}"));
        Event::assertNotDispatched(LiveLocationUpdated::class);
    }

    public static function closedReports(): array
    {
        return [
            'rescue completed' => [['rescue_status' => 'completed']],
            'status completed' => [['status' => 'completed']],
            'status resolved' => [['status' => 'resolved']],
            'status marked Rescued on the dashboard' => [['status' => 'Rescued']],
        ];
    }

    public function test_marking_a_report_rescued_applies_immediately_despite_the_cache(): void
    {
        $resident = $this->userWithRole('user');
        $report = $this->reportFor($resident);
        Sanctum::actingAs($resident);
        $this->send($report)->assertJsonPath('data.session_active', true);

        $report->update(['status' => 'Rescued']);

        $this->send($report)->assertJsonPath('data.session_active', false);
    }

    public function test_ending_a_call_keeps_the_report_status_and_the_session_open(): void
    {
        $resident = $this->userWithRole('user');
        $report = $this->reportFor($resident, ['type' => 'Call', 'status' => 'Pending']);
        Sanctum::actingAs($resident);

        $this->postJson('/api/v1/call/ended', ['report_id' => $report->id])->assertOk();

        $report->refresh();
        $this->assertSame('Pending', $report->status);
        $this->assertNotNull($report->call_ended_at);
        $this->send($report)->assertJsonPath('data.session_active', true);
    }

    public function test_empty_samples_returns_peer_without_writes_or_broadcast(): void
    {
        $resident = $this->userWithRole('user');
        $report = $this->reportFor($resident);
        $this->shareAs($resident, $report, ['lat' => 9.25], 'active_report');
        $this->assertSame(1, ReportLocationPoint::count());

        $staff = $this->userWithRole('staff');
        Event::fake([LiveLocationUpdated::class]);
        Sanctum::actingAs($staff);

        $this->send($report)->assertOk()->assertJsonPath('data.peer.lat', 9.25);

        $this->assertSame(1, ReportLocationPoint::count());
        $this->assertNull(Cache::get("live_location:user:{$staff->id}"));
        Event::assertNotDispatched(LiveLocationUpdated::class);
    }

    public function test_polling_reads_no_report_location_rows(): void
    {
        $resident = $this->userWithRole('user');
        $report = $this->reportFor($resident);
        Sanctum::actingAs($resident);
        $this->send($report)->assertOk();

        \DB::enableQueryLog();
        $this->send($report)->assertOk();
        $queries = collect(\DB::getQueryLog())->pluck('query');
        \DB::disableQueryLog();

        $this->assertEmpty($queries->filter(fn ($sql) => str_contains($sql, 'report_location_points')));
        $this->assertEmpty($queries->filter(fn ($sql) => str_contains($sql, '"reports"')));
    }

    public function test_close_samples_create_one_row_and_a_far_sample_another(): void
    {
        $resident = $this->userWithRole('user');
        $report = $this->reportFor($resident);
        Sanctum::actingAs($resident);

        $burst = [];
        for ($i = 0; $i < 10; $i++) {
            $burst[] = $this->sample([
                'lat' => self::LAT + ($i * 0.00001),
                'recorded_at' => now()->addSeconds($i - 10)->toIso8601String(),
            ]);
        }
        $this->send($report, $burst)->assertOk();
        $this->assertSame(1, ReportLocationPoint::count());

        // About 110 m away, only one second later.
        $this->send($report, [$this->sample(['lat' => self::LAT + 0.001])])->assertOk();
        $this->assertSame(2, ReportLocationPoint::count());
    }

    public function test_a_sample_stored_after_30_seconds_even_without_moving(): void
    {
        $resident = $this->userWithRole('user');
        $report = $this->reportFor($resident);
        $this->shareAs($resident, $report);

        Carbon::setTestNow(now()->addSeconds(30));
        $this->shareAs($resident, $report);

        $this->assertSame(2, ReportLocationPoint::count());
    }

    public function test_thinning_survives_a_cache_flush_using_the_last_row(): void
    {
        $resident = $this->userWithRole('user');
        $report = $this->reportFor($resident);
        $this->shareAs($resident, $report);

        Cache::flush();
        $this->shareAs($resident, $report);

        $this->assertSame(1, ReportLocationPoint::count());
    }

    public function test_on_duty_samples_are_cached_but_not_stored(): void
    {
        $staff = $this->userWithRole('staff');
        $this->shareAs($staff, $this->reportFor(null), [], 'on_duty');
        $this->shareAs($staff, null, [], 'on_duty');

        $this->assertSame(0, ReportLocationPoint::count());
        $this->assertNotNull(Cache::get("live_location:user:{$staff->id}"));
    }

    public function test_cached_position_has_a_ten_minute_lifetime(): void
    {
        $resident = $this->userWithRole('user');
        $this->shareAs($resident, $this->reportFor($resident));

        Carbon::setTestNow(now()->addMinutes(11));
        $this->assertNull(Cache::get("live_location:user:{$resident->id}"));
    }

    public function test_late_older_sample_does_not_overwrite_the_newest_position(): void
    {
        $resident = $this->userWithRole('user');
        $report = $this->reportFor($resident);
        $this->shareAs($resident, $report, ['lat' => 9.5]);

        $this->shareAs($resident, $report, [
            'lat' => 9.1,
            'recorded_at' => now()->subMinutes(5)->toIso8601String(),
        ]);

        $this->assertSame(9.5, Cache::get("live_location:user:{$resident->id}")['lat']);
    }

    public function test_late_sample_is_still_kept_in_the_stored_path_but_not_broadcast(): void
    {
        $resident = $this->userWithRole('user');
        $report = $this->reportFor($resident);
        $this->shareAs($resident, $report);

        Carbon::setTestNow(now()->addSeconds(3));
        Event::fake([LiveLocationUpdated::class]);
        $this->shareAs($resident, $report, ['recorded_at' => now()->subMinutes(5)->toIso8601String()]);

        $this->assertSame(2, ReportLocationPoint::count());
        Event::assertNotDispatched(LiveLocationUpdated::class);
    }

    public function test_samples_older_than_an_hour_are_dropped(): void
    {
        $resident = $this->userWithRole('user');
        $this->shareAs($resident, $this->reportFor($resident), [
            'recorded_at' => now()->subMinutes(61)->toIso8601String(),
        ]);

        $this->assertSame(0, ReportLocationPoint::count());
        $this->assertNull(Cache::get("live_location:user:{$resident->id}"));
    }

    public function test_broadcasts_at_most_once_per_two_seconds_per_user_and_report(): void
    {
        Event::fake([LiveLocationUpdated::class]);
        $resident = $this->userWithRole('user');
        $report = $this->reportFor($resident);
        $this->shareAs($resident, $report);

        Carbon::setTestNow(now()->addSecond());
        $this->shareAs($resident, $report);
        Event::assertDispatchedTimes(LiveLocationUpdated::class, 1);

        Carbon::setTestNow(now()->addSeconds(2));
        $this->shareAs($resident, $report);
        Event::assertDispatchedTimes(LiveLocationUpdated::class, 2);
    }

    public function test_broadcast_payload_and_channel(): void
    {
        Event::fake([LiveLocationUpdated::class]);
        $resident = $this->userWithRole('user');
        $report = $this->reportFor($resident);
        $this->shareAs($resident, $report);

        Event::assertDispatched(LiveLocationUpdated::class, function (LiveLocationUpdated $event) use ($resident, $report) {
            $this->assertSame('live-location.updated', $event->broadcastAs());
            $this->assertSame("private-report.{$report->id}.tracking", $event->broadcastOn()[0]->name);
            $this->assertEqualsCanonicalizing(
                ['report_id', 'user_id', 'role', 'lat', 'lng', 'accuracy', 'speed', 'heading', 'recorded_at'],
                array_keys($event->broadcastWith())
            );
            $this->assertSame($resident->id, $event->broadcastWith()['user_id']);

            return $event->broadcastWith()['role'] === 'resident';
        });
    }

    public function test_on_duty_broadcasts_to_the_staff_channel(): void
    {
        Event::fake([LiveLocationUpdated::class]);
        $this->shareAs($this->userWithRole('staff'), null, [], 'on_duty');

        Event::assertDispatched(LiveLocationUpdated::class, function (LiveLocationUpdated $event) {
            return $event->broadcastOn()[0]->name === 'private-staff.locations';
        });
    }

    private function useFailingBroadcaster(): void
    {
        Broadcast::extend('failing', fn () => new class extends Broadcaster
        {
            public function auth($request) {}

            public function validAuthenticationResponse($request, $result) {}

            public function broadcast(array $channels, $event, array $payload = [])
            {
                throw new RuntimeException('Reverb is down');
            }
        });
        config([
            'broadcasting.connections.failing' => ['driver' => 'failing'],
            'broadcasting.default' => 'failing',
        ]);
    }

    public function test_failing_broadcast_still_returns_200_and_logs_no_coordinates(): void
    {
        $this->useFailingBroadcaster();
        Log::spy();
        $resident = $this->userWithRole('user');
        $report = $this->reportFor($resident);

        $this->shareAs($resident, $report);

        $this->assertSame(1, ReportLocationPoint::count());
        Log::shouldHaveReceived('warning')->once()->withArgs(function ($message, $context) {
            return ! str_contains(json_encode([$message, $context]), (string) self::LAT);
        });
    }

    public function test_server_failure_returns_503_with_retry_after_and_logs_no_coordinates(): void
    {
        Log::spy();
        $resident = $this->userWithRole('user');
        $report = $this->reportFor($resident);
        Sanctum::actingAs($resident);
        \Schema::drop('report_location_points');

        $this->send($report, [$this->sample()])
            ->assertStatus(503)
            ->assertHeader('Retry-After', '30');

        Log::shouldHaveReceived('error')->once()->withArgs(function ($message, $context) {
            return ! str_contains(json_encode([$message, $context]), (string) self::LAT);
        });
    }

    public function test_rate_limit_is_per_user_and_sends_retry_after(): void
    {
        config(['live_location.rate_limit_per_minute' => 2]);
        $report = $this->reportFor(null);
        Sanctum::actingAs($this->userWithRole('staff'));

        $this->send($report)->assertOk();
        $this->send($report)->assertOk();
        $this->send($report)->assertStatus(429)->assertHeader('Retry-After');

        Sanctum::actingAs($this->userWithRole('staff'));
        $this->send($report)->assertOk();
    }

    public function test_interval_rises_with_load_and_navigating_stays_fastest(): void
    {
        $report = $this->reportFor(null);
        Sanctum::actingAs($this->userWithRole('staff'));
        $key = 'live_location:load:'.now()->utc()->format('YmdHi');

        Cache::put($key, 700, 180);
        $this->send($report, [], 'navigating')->assertJsonPath('data.next_interval_seconds', 6);
        $this->send($report, [], 'watching')->assertJsonPath('data.next_interval_seconds', 15);

        Cache::put($key, 100000, 180);
        $this->send($report, [], 'navigating')->assertJsonPath('data.next_interval_seconds', 10);
        $this->send($report, [], 'watching')->assertJsonPath('data.next_interval_seconds', 60);
    }

    public function test_rescue_status_is_limited_to_owner_and_dispatch_staff(): void
    {
        $owner = $this->userWithRole('user');
        $report = $this->reportFor($owner);
        $url = "/api/v1/reports/{$report->id}/rescue-status";

        $this->getJson($url)->assertUnauthorized();

        Sanctum::actingAs($this->userWithRole('user'));
        $this->getJson($url)->assertForbidden();

        Sanctum::actingAs($owner);
        $this->getJson($url)->assertOk();

        Sanctum::actingAs($this->userWithRole('staff'));
        $this->getJson($url)->assertOk();
    }

    public function test_rescue_endpoints_broadcast_status(): void
    {
        Event::fake([RescueStatusUpdated::class]);
        $report = $this->reportFor(null);
        Sanctum::actingAs($this->userWithRole('staff'));

        $this->postJson("/api/v1/reports/{$report->id}/rescue-arrived")->assertOk();
        Event::assertDispatched(RescueStatusUpdated::class, function (RescueStatusUpdated $event) {
            return $event->broadcastAs() === 'rescue-status.updated'
                && $event->rescueStatus === 'arrived'
                && $event->sessionActive === true;
        });

        $this->postJson("/api/v1/reports/{$report->id}/rescue-complete")->assertOk();
        Event::assertDispatched(RescueStatusUpdated::class, fn ($event) => $event->sessionActive === false);
    }

    public function test_rescue_endpoints_survive_a_broadcast_failure(): void
    {
        $this->useFailingBroadcaster();
        $report = $this->reportFor(null);
        Sanctum::actingAs($this->userWithRole('staff'));

        $this->postJson("/api/v1/reports/{$report->id}/rescue-arrived")->assertOk();

        $this->assertSame('arrived', $report->fresh()->rescue_status);
    }

    private function channelAuth(User $user, string $channel)
    {
        config(['broadcasting.default' => 'reverb']);

        return $this->actingAs($user)->post('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => $channel,
        ]);
    }

    public function test_report_channel_authorization(): void
    {
        $owner = $this->userWithRole('user');
        $rescuer = $this->userWithRole('user');
        $report = $this->reportFor($owner, ['rescuer_user_id' => $rescuer->id]);
        $channel = "private-report.{$report->id}.tracking";

        $this->channelAuth($owner, $channel)->assertOk();
        $this->channelAuth($rescuer, $channel)->assertOk();
        $this->channelAuth($this->userWithRole('staff'), $channel)->assertOk();
        $this->channelAuth($this->userWithRole('user'), $channel)->assertForbidden();
    }

    public function test_staff_locations_channel_is_admin_only(): void
    {
        $channel = 'private-staff.locations';

        $this->channelAuth($this->userWithRole('admin'), $channel)->assertOk();
        $this->channelAuth($this->userWithRole('super_admin'), $channel)->assertOk();
        $this->channelAuth($this->userWithRole('staff'), $channel)->assertForbidden();
        $this->channelAuth($this->userWithRole('user'), $channel)->assertForbidden();
    }

    public function test_prune_deletes_only_expired_rows(): void
    {
        $resident = $this->userWithRole('user');
        $report = $this->reportFor($resident);
        $point = fn ($recordedAt) => ReportLocationPoint::create([
            'report_id' => $report->id,
            'user_id' => $resident->id,
            'lat' => self::LAT,
            'lng' => self::LNG,
            'recorded_at' => $recordedAt,
        ]);
        $point(now()->subDays(31));
        $point(now()->subDays(32));
        $kept = $point(now()->subDays(29));

        $this->artisan('live-location:prune')->assertSuccessful();

        $this->assertSame([$kept->id], ReportLocationPoint::pluck('id')->all());
    }

    public function test_prune_handles_more_rows_than_one_chunk(): void
    {
        $resident = $this->userWithRole('user');
        $report = $this->reportFor($resident);
        ReportLocationPoint::insert(array_fill(0, 1001, [
            'report_id' => $report->id,
            'user_id' => $resident->id,
            'lat' => self::LAT,
            'lng' => self::LNG,
            'recorded_at' => now()->subDays(40)->format('Y-m-d H:i:s'),
        ]));

        $this->artisan('live-location:prune')->assertSuccessful();

        $this->assertSame(0, ReportLocationPoint::count());
    }

    public function test_deleting_a_report_cascades_its_points(): void
    {
        $resident = $this->userWithRole('user');
        $report = $this->reportFor($resident);
        $this->shareAs($resident, $report);

        $report->forceDelete();

        $this->assertSame(0, ReportLocationPoint::count());
    }

    public function test_live_map_is_for_dispatch_staff_only(): void
    {
        $owner = $this->userWithRole('user');
        $report = $this->reportFor($owner);

        $this->get(route('reports.live', $report))->assertRedirect(route('login'));
        $this->getJson(route('reports.live.state', $report))->assertUnauthorized();
        $this->actingAs($owner)->get(route('reports.live', $report))->assertForbidden();
        $this->actingAs($owner)->getJson(route('reports.live.state', $report))->assertForbidden();

        $this->actingAs($this->userWithRole('staff'))
            ->getJson(route('reports.live.state', $report))
            ->assertOk();
    }

    public function test_map_shows_only_the_resident_and_rescuer_and_falls_back_to_stored_points(): void
    {
        $resident = $this->userWithRole('user');
        $rescuer = $this->userWithRole('staff');
        $report = $this->reportFor($resident, ['rescuer_user_id' => $rescuer->id]);
        $this->shareAs($resident, $report, ['lat' => 9.1], 'active_report');
        $this->shareAs($this->userWithRole('staff'), $report, ['lat' => 9.7]);

        Cache::forget("live_location:user:{$resident->id}");

        $admin = $this->userWithRole('admin');
        $this->withoutVite()->actingAs($admin)
            ->get(route('reports.live', $report))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Reports/LiveMap', false)
                ->where('initial_state.resident.lat', 9.1)
                ->where('initial_state.rescuer', null)
                ->has('paths.resident', 1)
                ->where('paths.resident.0.0', 9.1)
                ->has('paths.rescuer', 0)
            );
    }

    public function test_recorded_at_is_stored_in_the_app_timezone(): void
    {
        $resident = $this->userWithRole('user');
        $this->shareAs($resident, $this->reportFor($resident), [
            'recorded_at' => '2026-11-05T15:59:00+08:00',
        ]);

        $this->assertSame(
            '2026-11-05 07:59:00',
            ReportLocationPoint::first()->recorded_at->setTimezone('UTC')->format('Y-m-d H:i:s')
        );
    }
}
