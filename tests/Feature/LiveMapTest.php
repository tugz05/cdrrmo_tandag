<?php

namespace Tests\Feature;

use App\Events\LiveLocationUpdated;
use App\Events\RescueStatusUpdated;
use App\Models\Report;
use App\Models\ReportLocationPoint;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LiveMapTest extends TestCase
{
    use RefreshDatabase;

    private const STATE_URL = '/admin/live-map/state';

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

    private function cachePosition(User $user, float $lat = 9.1, float $lng = 126.1): void
    {
        Cache::put("live_location:user:{$user->id}", [
            'lat' => $lat,
            'lng' => $lng,
            'accuracy' => 5.0,
            'speed' => null,
            'heading' => null,
            'recorded_at' => now()->toIso8601String(),
            'recorded_ms' => now()->getTimestampMs(),
        ], 600);
    }

    private function state(): array
    {
        return $this->actingAs($this->userWithRole('admin'))->getJson(self::STATE_URL)->assertOk()->json();
    }

    public function test_only_admins_can_open_the_map(): void
    {
        foreach (['admin', 'super_admin'] as $role) {
            $this->actingAs($this->userWithRole($role))->getJson(self::STATE_URL)->assertOk();
        }
        foreach (['staff', 'user'] as $role) {
            $this->actingAs($this->userWithRole($role))->getJson(self::STATE_URL)->assertForbidden();
        }
    }

    public function test_guests_cannot_open_the_map(): void
    {
        $this->getJson(self::STATE_URL)->assertUnauthorized();
    }

    public function test_page_renders(): void
    {
        $this->actingAs($this->userWithRole('admin'))
            ->get('/admin/live-map')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('LiveMap/Index')->has('initial_state.rescuers'));
    }

    public function test_rescuer_status_comes_from_their_active_rescue(): void
    {
        $enRoute = $this->userWithRole('staff');
        $arrived = $this->userWithRole('staff');
        $available = $this->userWithRole('staff');
        $offline = $this->userWithRole('staff');
        $resident = $this->userWithRole('user');

        $first = $this->reportFor($resident, ['rescue_status' => 'en_route', 'rescuer_user_id' => $enRoute->id]);
        $second = $this->reportFor($resident, ['rescue_status' => 'arrived', 'rescuer_user_id' => $arrived->id]);
        $this->cachePosition($enRoute);
        $this->cachePosition($available);

        $rescuers = collect($this->state()['rescuers'])->keyBy('id');

        $this->assertSame(['report_id' => $first->id, 'rescue_status' => 'en_route'], $rescuers[$enRoute->id]['assignment']);
        $this->assertSame(['report_id' => $second->id, 'rescue_status' => 'arrived'], $rescuers[$arrived->id]['assignment']);
        $this->assertNull($rescuers[$available->id]['assignment']);
        $this->assertSame(9.1, $rescuers[$available->id]['position']['lat']);
        $this->assertNull($rescuers[$offline->id]['position']);
    }

    public function test_desk_admins_only_appear_when_sharing(): void
    {
        $desk = $this->userWithRole('admin');
        $sharing = $this->userWithRole('admin');
        $this->cachePosition($sharing);

        $ids = collect($this->state()['rescuers'])->pluck('id');

        $this->assertTrue($ids->contains($sharing->id));
        $this->assertFalse($ids->contains($desk->id));
    }

    public function test_residents_are_not_listed_as_rescuers(): void
    {
        $resident = $this->userWithRole('user');
        $this->cachePosition($resident);

        $this->assertFalse(collect($this->state()['rescuers'])->pluck('id')->contains($resident->id));
    }

    public function test_assigned_rescuer_falls_back_to_their_last_stored_point(): void
    {
        $rescuer = $this->userWithRole('staff');
        $report = $this->reportFor(null, ['rescue_status' => 'en_route', 'rescuer_user_id' => $rescuer->id]);
        foreach ([[10, 9.2], [2, 9.3], [20, 9.4]] as [$minutes, $lat]) {
            ReportLocationPoint::create([
                'report_id' => $report->id,
                'user_id' => $rescuer->id,
                'lat' => $lat,
                'lng' => 126.2,
                'recorded_at' => now()->subMinutes($minutes),
            ]);
        }

        $found = collect($this->state()['rescuers'])->firstWhere('id', $rescuer->id);

        $this->assertSame(9.3, (float) $found['position']['lat']);
    }

    public function test_closed_and_old_reports_are_left_out(): void
    {
        $open = $this->reportFor(null);
        $this->reportFor(null, ['status' => 'Rescued']);
        $this->reportFor(null, ['status' => 'completed', 'rescue_status' => 'completed']);
        $this->reportFor(null, ['rescue_status' => 'completed']);

        $old = $this->reportFor(null);
        $old->forceFill(['created_at' => now()->subDays(8)])->saveQuietly();
        $oldActive = $this->reportFor(null, ['rescue_status' => 'en_route']);
        $oldActive->forceFill(['created_at' => now()->subDays(30)])->saveQuietly();

        $state = $this->state();

        $this->assertEqualsCanonicalizing([$open->id, $oldActive->id], collect($state['incidents'])->pluck('id')->all());
        $this->assertSame(2, $state['incidents_total']);
    }

    public function test_limit_keeps_active_rescues_and_reports_the_true_total(): void
    {
        config(['live_location.overview_report_limit' => 2]);
        $active = $this->reportFor(null, ['rescue_status' => 'en_route']);
        $this->reportFor(null);
        $newest = $this->reportFor(null);

        $state = $this->state();

        $this->assertEqualsCanonicalizing([$active->id, $newest->id], collect($state['incidents'])->pluck('id')->all());
        $this->assertSame(3, $state['incidents_total']);
    }

    public function test_resident_live_position_only_marks_their_newest_report(): void
    {
        $resident = $this->userWithRole('user');
        $older = $this->reportFor($resident);
        $newer = $this->reportFor($resident);
        $this->cachePosition($resident);

        $incidents = collect($this->state()['incidents'])->keyBy('id');

        $this->assertSame(9.1, $incidents[$newer->id]['resident']['lat']);
        $this->assertNull($incidents[$older->id]['resident']);
    }

    public function test_unusable_report_coordinates_stay_listed_without_a_location(): void
    {
        $blank = $this->reportFor(null, ['latitude' => '', 'longitude' => '']);
        $zero = $this->reportFor(null, ['latitude' => '0', 'longitude' => '0']);

        $incidents = collect($this->state()['incidents'])->keyBy('id');

        $this->assertNull($incidents[$blank->id]['reported_location']);
        $this->assertNull($incidents[$zero->id]['reported_location']);
    }

    public function test_positions_do_not_expose_internal_fields(): void
    {
        $rescuer = $this->userWithRole('staff');
        $this->cachePosition($rescuer);

        $found = collect($this->state()['rescuers'])->firstWhere('id', $rescuer->id);

        $this->assertArrayNotHasKey('recorded_ms', $found['position']);
    }

    public function test_events_also_reach_the_admin_channel(): void
    {
        $position = ['lat' => 9.1, 'lng' => 126.1, 'accuracy' => null, 'speed' => null, 'heading' => null, 'recorded_at' => now()->toIso8601String()];
        $names = fn ($event) => array_map(fn ($channel) => $channel->name, $event->broadcastOn());

        $this->assertSame(
            ['private-report.5.tracking', 'private-admin.live-map'],
            $names(new LiveLocationUpdated(5, 1, 'resident', $position))
        );
        $this->assertSame(
            ['private-staff.locations', 'private-admin.live-map'],
            $names(new LiveLocationUpdated(null, 1, 'rescuer', $position))
        );
        $this->assertSame(
            ['private-report.5.tracking', 'private-admin.live-map'],
            $names(new RescueStatusUpdated(5, 'en_route', true))
        );
    }

    public function test_admin_channel_is_admin_only(): void
    {
        config(['broadcasting.default' => 'reverb', 'broadcasting.connections.reverb.key' => 'key', 'broadcasting.connections.reverb.secret' => 'secret', 'broadcasting.connections.reverb.app_id' => 'app']);
        $auth = fn (User $user) => $this->actingAs($user)->post('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-admin.live-map',
        ]);

        $auth($this->userWithRole('admin'))->assertOk();
        $auth($this->userWithRole('super_admin'))->assertOk();
        $auth($this->userWithRole('staff'))->assertForbidden();
        $auth($this->userWithRole('user'))->assertForbidden();
    }
}
