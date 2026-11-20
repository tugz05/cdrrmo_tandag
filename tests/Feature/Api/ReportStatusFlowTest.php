<?php

namespace Tests\Feature\Api;

use App\Models\Report;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportStatusFlowTest extends TestCase
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

    private function report(string $status = 'Pending'): Report
    {
        return Report::create([
            'user_id' => $this->userWithRole('user')->id,
            'latitude' => '9.0195',
            'longitude' => '126.2331',
            'type' => 'Call',
            'status' => $status,
            'rescue_status' => 'pending',
        ]);
    }

    private function asStaff(): void
    {
        Sanctum::actingAs($this->userWithRole('staff'));
    }

    public function test_answered_call_moves_the_report_to_in_progress(): void
    {
        $report = $this->report();
        $this->asStaff();

        $this->postJson('/api/v1/call/started', ['report_id' => $report->id])->assertOk();

        $report->refresh();
        $this->assertSame('In Progress', $report->status);
        $this->assertNotNull($report->call_started_at);
    }

    public function test_answered_call_does_not_reopen_a_rescued_report(): void
    {
        $report = $this->report('Rescued');
        $this->asStaff();

        $this->postJson('/api/v1/call/started', ['report_id' => $report->id])->assertOk();

        $this->assertSame('Rescued', $report->fresh()->status);
    }

    public function test_starting_a_rescue_moves_the_report_to_in_progress(): void
    {
        $report = $this->report();
        $this->asStaff();

        $this->postJson("/api/v1/reports/{$report->id}/start-rescue")->assertOk();

        $report->refresh();
        $this->assertSame('In Progress', $report->status);
        $this->assertSame('en_route', $report->rescue_status);
    }

    public function test_arrival_keeps_in_progress_and_records_on_scene(): void
    {
        $report = $this->report('in_progress');
        $this->asStaff();

        $this->postJson("/api/v1/reports/{$report->id}/rescue-arrived")->assertOk();

        $report->refresh();
        $this->assertSame('In Progress', $report->status);
        $this->assertSame('arrived', $report->rescue_status);
        $this->assertNotNull($report->rescue_arrived_at);
    }

    public function test_completing_the_rescue_marks_it_rescued(): void
    {
        $report = $this->report('In Progress');
        $this->asStaff();

        $this->postJson("/api/v1/reports/{$report->id}/rescue-complete")->assertOk();

        $report->refresh();
        $this->assertSame('Rescued', $report->status);
        $this->assertSame('completed', $report->rescue_status);

        // Live location for the report is closed: the resident's share is accepted but stops the session.
        Sanctum::actingAs($report->user);
        $this->postJson('/api/v1/live-location', [
            'report_id' => $report->id,
            'mode' => 'active_report',
            'samples' => [['lat' => 9.0195, 'lng' => 126.2331, 'recorded_at' => now()->toIso8601String()]],
        ])->assertOk()->assertJsonPath('data.session_active', false);
    }

    public function test_full_flow_counts_on_the_reports_page(): void
    {
        $report = $this->report();
        $this->asStaff();

        $this->postJson('/api/v1/call/started', ['report_id' => $report->id])->assertOk();
        $this->postJson("/api/v1/reports/{$report->id}/start-rescue")->assertOk();
        $this->postJson("/api/v1/reports/{$report->id}/rescue-arrived")->assertOk();

        $this->assertSame(1, Report::query()->whereStatus('In Progress')->count());

        $this->postJson("/api/v1/reports/{$report->id}/rescue-complete")->assertOk();

        $this->assertSame(1, Report::query()->whereStatus('Rescued')->count());
    }

    public function test_second_migration_catches_the_remaining_spellings(): void
    {
        $reports = collect(['in_progress', 'IN PROGRESS', 'in progress', 'completed', 'Resolved', 'rescued', 'Pending', 'In Progress', 'Rescued'])
            ->mapWithKeys(fn (string $status) => [$status => $this->report($status)]);

        (require database_path('migrations/2026_11_20_130000_normalize_remaining_report_statuses.php'))->up();

        $expected = [
            'in_progress' => 'In Progress', 'IN PROGRESS' => 'In Progress', 'in progress' => 'In Progress',
            'completed' => 'Rescued', 'Resolved' => 'Rescued', 'rescued' => 'Rescued',
            'Pending' => 'Pending', 'In Progress' => 'In Progress', 'Rescued' => 'Rescued',
        ];
        foreach ($expected as $from => $to) {
            $this->assertSame($to, DB::table('reports')->where('id', $reports[$from]->id)->value('status'), "from {$from}");
        }
    }

    public function test_admin_cannot_set_a_status_outside_the_three(): void
    {
        $report = $this->report();

        $this->actingAs($this->userWithRole('admin'))
            ->putJson('/admin/reports/update-status', ['id' => $report->id, 'status' => 'in_progress'])
            ->assertUnprocessable();

        $this->assertSame('Pending', $report->fresh()->status);
    }

    public function test_app_reports_start_pending(): void
    {
        Sanctum::actingAs($this->userWithRole('user'));

        $this->postJson('/api/v1/report', ['latitude' => '9.0195', 'longitude' => '126.2331', 'type' => 'Message'])->assertOk();

        $this->assertSame('Pending', Report::query()->sole()->status);
    }

    public function test_migration_normalizes_old_values(): void
    {
        $inProgress = $this->report('in_progress');
        $completed = $this->report('completed');
        $pending = $this->report('Pending');
        $rescued = $this->report('Rescued');

        (require database_path('migrations/2026_11_20_120000_normalize_report_statuses.php'))->up();

        $status = fn (Report $report) => DB::table('reports')->where('id', $report->id)->value('status');
        $this->assertSame('In Progress', $status($inProgress));
        $this->assertSame('Rescued', $status($completed));
        $this->assertSame('Pending', $status($pending));
        $this->assertSame('Rescued', $status($rescued));
    }
}
