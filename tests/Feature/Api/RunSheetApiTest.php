<?php

namespace Tests\Feature\Api;

use App\Models\SituationalIncidentReport;
use App\Models\User;
use App\Services\IncidentNumberBackfill;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class RunSheetApiTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/situational-incident-reports';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Carbon::setTestNow('2026-11-06 01:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        SituationalIncidentReport::flushEventListeners();
        parent::tearDown();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->addRole($role);

        return $user;
    }

    private function runSheet(array $overrides = []): array
    {
        return array_merge([
            'form_version' => 2,
            'incident_date' => '2026-11-06',
            'response_team' => 'BRAVO',
            'time_informed' => '08:15',
            'incident_types' => ['vehicular'],
            'airway' => 'partly_obstructed',
            'skin' => ['pale', 'sweaty'],
            'victim_name' => 'Example Victim',
            'victim_sex' => 'female',
            'consciousness' => 'conscious',
            'pupils' => 'reactive',
            'pupil_left' => true,
            'transport_vehicle' => 'ambulance',
            'patient_disposition' => 'treated_released',
            'injury_natures' => [1, 13],
            'actions_taken' => ['bandaging', 'oxygen_administration'],
            'sample_allergies' => 'None known',
            'equipment_used' => ['gauze_pad', 'c_collar'],
            'responders' => ['REY S. RELOVA', 'ROMEO LOZADA'],
            'patient_belongings' => 'One phone, returned to family',
        ], $overrides);
    }

    public function test_staff_can_create_a_run_sheet(): void
    {
        Sanctum::actingAs($this->userWithRole('staff'));

        $this->postJson(self::URL, $this->runSheet())->assertOk();

        $report = SituationalIncidentReport::query()->sole();
        $this->assertTrue($report->isRunSheet());
        $this->assertSame(['pale', 'sweaty'], $report->skin);
        $this->assertSame([1, 13], $report->injury_natures);
        $this->assertSame(['REY S. RELOVA', 'ROMEO LOZADA'], $report->responders);
        $this->assertTrue($report->pupil_left);
        $this->assertFalse($report->pupil_right);
    }

    public function test_released_apps_that_omit_the_version_keep_the_old_form(): void
    {
        Sanctum::actingAs($this->userWithRole('staff'));

        $this->postJson(self::URL, [
            'incident_type' => 'Vehicular',
            'location' => 'Brgy. Poblacion',
            'has_abrasion' => true,
        ])->assertOk();

        $report = SituationalIncidentReport::query()->sole();
        $this->assertSame(1, $report->fresh()->form_version);
        $this->assertFalse($report->isRunSheet());
    }

    public function test_unknown_choices_are_rejected(): void
    {
        Sanctum::actingAs($this->userWithRole('staff'));

        foreach ([
            ['airway' => 'blocked'],
            ['response_team' => 'ECHO'],
            ['skin' => ['purple']],
            ['injury_natures' => [16]],
            ['equipment_used' => ['helicopter']],
            ['victim_sex' => 'other'],
            ['form_version' => 3],
        ] as $invalid) {
            $this->postJson(self::URL, $this->runSheet($invalid))->assertUnprocessable();
        }
    }

    public function test_marker_nature_is_validated(): void
    {
        Sanctum::actingAs($this->userWithRole('staff'));

        $this->postJson(self::URL, $this->runSheet([
            'injury_markers' => [['x' => 0.5, 'y' => 0.5, 'label' => 'Left arm', 'nature' => 13]],
        ]))->assertOk();

        $this->postJson(self::URL, $this->runSheet([
            'injury_markers' => [['x' => 0.5, 'y' => 0.5, 'nature' => 99]],
        ]))->assertUnprocessable();
    }

    public function test_options_list_all_four_teams(): void
    {
        Sanctum::actingAs($this->userWithRole('staff'));

        $response = $this->getJson(self::URL.'/run-sheet-options')->assertOk();

        $this->assertSame(['ALPHA', 'BRAVO', 'CHARLIE', 'DELTA'], array_keys($response->json('data.teams')));
        $this->assertSame('REY S. RELOVA', $response->json('data.teams.BRAVO.team_leader'));
    }

    public function test_options_require_a_login(): void
    {
        $this->getJson(self::URL.'/run-sheet-options')->assertUnauthorized();
    }

    public function test_run_sheet_prints_on_the_new_form_with_its_values(): void
    {
        $staff = $this->userWithRole('staff');
        $report = SituationalIncidentReport::query()->create($this->runSheet(['user_id' => $staff->id]));
        $report->forceFill(['incident_no' => 'INC-2026-0014'])->save();

        $this->actingAs($this->userWithRole('admin'))
            ->get("/admin/situational-incident-reports/{$report->id}/print")
            ->assertOk()
            ->assertViewIs('print.run-sheet')
            ->assertSee('EMERGENCY MEDICAL RESPONSE')
            ->assertSee('INC-2026-0014')
            ->assertSee('Example Victim')
            ->assertSee('JEANN T. EMBOC');
    }

    public function test_printed_values_are_escaped(): void
    {
        $staff = $this->userWithRole('staff');
        $report = SituationalIncidentReport::query()->create($this->runSheet([
            'user_id' => $staff->id,
            'victim_name' => '<script>alert(1)</script>',
        ]));

        $this->actingAs($this->userWithRole('admin'))
            ->get("/admin/situational-incident-reports/{$report->id}/print")
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_older_reports_keep_printing_on_the_old_form(): void
    {
        $staff = $this->userWithRole('staff');
        $report = SituationalIncidentReport::query()->create(['user_id' => $staff->id, 'incident_type' => 'Vehicular']);

        $this->actingAs($this->userWithRole('admin'))
            ->get("/admin/situational-incident-reports/{$report->id}/print")
            ->assertOk()
            ->assertViewIs('print.situational-incident-report');
    }

    public function test_print_stays_admin_only(): void
    {
        $staff = $this->userWithRole('staff');
        $report = SituationalIncidentReport::query()->create($this->runSheet(['user_id' => $staff->id]));

        $this->actingAs($this->userWithRole('user'))
            ->get("/admin/situational-incident-reports/{$report->id}/print")
            ->assertForbidden();
    }

    private function createAs(User $user, array $payload = ['form_version' => 2])
    {
        Sanctum::actingAs($user);

        return $this->postJson(self::URL, $payload);
    }

    private function legacyRow(array $attributes): int
    {
        return DB::table('situational_incident_reports')->insertGetId(array_merge([
            'user_id' => User::factory()->create()->id,
            'form_version' => 2,
            'created_at' => '2026-06-01 00:00:00',
            'updated_at' => '2026-06-01 00:00:00',
        ], $attributes));
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_11_06_100000_add_automatic_incident_numbers.php');
    }

    private function numberOf(int $id): ?string
    {
        return DB::table('situational_incident_reports')->where('id', $id)->value('incident_no');
    }

    public function test_run_sheets_are_numbered_in_order_and_the_reply_includes_the_number(): void
    {
        $staff = $this->userWithRole('staff');

        $this->createAs($staff)->assertOk()->assertJsonPath('data.incident_no', 'INC-2026-0001');
        $this->createAs($staff)->assertOk()->assertJsonPath('data.incident_no', 'INC-2026-0002');
    }

    public function test_different_accounts_share_one_counter(): void
    {
        $this->createAs($this->userWithRole('staff'))->assertJsonPath('data.incident_no', 'INC-2026-0001');
        $this->createAs($this->userWithRole('admin'))->assertJsonPath('data.incident_no', 'INC-2026-0002');
    }

    public function test_year_comes_from_manila_time(): void
    {
        Carbon::setTestNow('2026-12-31 16:30:00');

        $this->createAs($this->userWithRole('staff'))->assertJsonPath('data.incident_no', 'INC-2027-0001');
    }

    public function test_counter_restarts_in_a_new_year(): void
    {
        $staff = $this->userWithRole('staff');
        $this->createAs($staff);
        $this->createAs($staff)->assertJsonPath('data.incident_no', 'INC-2026-0002');

        Carbon::setTestNow('2027-02-01 01:00:00');

        $this->createAs($staff)->assertJsonPath('data.incident_no', 'INC-2027-0001');
    }

    public function test_numbers_continue_past_four_digits(): void
    {
        DB::table('incident_number_sequences')->insert(['year' => 2026, 'last_number' => 9999]);

        $this->createAs($this->userWithRole('staff'))->assertJsonPath('data.incident_no', 'INC-2026-10000');
    }

    public function test_client_sent_number_is_ignored_on_create(): void
    {
        $this->createAs($this->userWithRole('staff'), ['form_version' => 2, 'incident_no' => 'MY-OWN-1'])
            ->assertOk()
            ->assertJsonPath('data.incident_no', 'INC-2026-0001');

        // Older apps may send anything here; it must not cause a validation error.
        $this->createAs($this->userWithRole('staff'), ['form_version' => 2, 'incident_no' => str_repeat('x', 500)])
            ->assertOk()
            ->assertJsonPath('data.incident_no', 'INC-2026-0002');
    }

    public function test_update_cannot_change_the_number(): void
    {
        $staff = $this->userWithRole('staff');
        $id = $this->createAs($staff)->json('data.id');

        $this->putJson(self::URL."/{$id}", ['form_version' => 2, 'incident_no' => 'CHANGED', 'victim_name' => 'Updated'])
            ->assertOk()
            ->assertJsonPath('data.incident_no', 'INC-2026-0001')
            ->assertJsonPath('data.victim_name', 'Updated');

        $this->assertSame('INC-2026-0001', $this->numberOf($id));
    }

    public function test_old_form_reports_get_no_number_and_leave_the_counter_alone(): void
    {
        $staff = $this->userWithRole('staff');

        $this->createAs($staff, ['incident_type' => 'Vehicular'])->assertOk()->assertJsonPath('data.incident_no', null);
        $this->createAs($staff, ['form_version' => 1])->assertOk()->assertJsonPath('data.incident_no', null);

        $this->assertSame(0, DB::table('incident_number_sequences')->count());
        $this->createAs($staff)->assertJsonPath('data.incident_no', 'INC-2026-0001');
    }

    public function test_deleted_report_number_is_not_reused(): void
    {
        $staff = $this->userWithRole('staff');
        $id = $this->createAs($staff)->json('data.id');

        $this->deleteJson(self::URL."/{$id}")->assertOk();

        $this->createAs($staff)->assertJsonPath('data.incident_no', 'INC-2026-0002');
    }

    public function test_failed_save_does_not_use_up_a_number(): void
    {
        $staff = $this->userWithRole('staff');
        $fail = true;
        SituationalIncidentReport::creating(function () use (&$fail) {
            if ($fail) {
                $fail = false;
                throw new RuntimeException('Simulated failed save');
            }
        });

        $this->createAs($staff)->assertServerError();
        $this->assertSame(0, SituationalIncidentReport::query()->count());

        $this->createAs($staff)->assertOk()->assertJsonPath('data.incident_no', 'INC-2026-0001');
    }

    public function test_version_alone_still_saves_and_gets_a_number(): void
    {
        $this->createAs($this->userWithRole('staff'), ['form_version' => 2])
            ->assertOk()
            ->assertJsonPath('data.form_version', 2)
            ->assertJsonPath('data.incident_no', 'INC-2026-0001');
    }

    public function test_options_include_victim_statuses(): void
    {
        Sanctum::actingAs($this->userWithRole('staff'));

        $this->getJson(self::URL.'/run-sheet-options')
            ->assertOk()
            ->assertJsonPath('data.victim_statuses', ['Single', 'Married', 'Widowed', 'Separated']);
    }

    public function test_status_outside_the_list_still_saves(): void
    {
        $this->createAs($this->userWithRole('staff'), ['form_version' => 2, 'victim_status' => 'Live-in partner'])
            ->assertOk()
            ->assertJsonPath('data.victim_status', 'Live-in partner');
    }

    public function test_backfill_numbers_blanks_in_order_keeps_typed_and_starts_above_the_highest(): void
    {
        $first = $this->legacyRow([]);
        $typed = $this->legacyRow(['incident_no' => 'INC-2026-0040']);
        $otherFormat = $this->legacyRow(['incident_no' => 'TAGO-77']);
        $threeDigits = $this->legacyRow(['incident_no' => 'INC-2026-900']);
        $deleted = $this->legacyRow(['deleted_at' => '2026-06-02 00:00:00']);
        $emptyString = $this->legacyRow(['incident_no' => '']);
        $oldForm = $this->legacyRow(['form_version' => 1]);

        (new IncidentNumberBackfill)->run();

        $this->assertSame('INC-2026-0041', $this->numberOf($first));
        $this->assertSame('INC-2026-0040', $this->numberOf($typed));
        $this->assertSame('TAGO-77', $this->numberOf($otherFormat));
        $this->assertSame('INC-2026-900', $this->numberOf($threeDigits));
        $this->assertSame('INC-2026-0042', $this->numberOf($deleted));
        $this->assertSame('INC-2026-0043', $this->numberOf($emptyString));
        $this->assertNull($this->numberOf($oldForm));

        $this->createAs($this->userWithRole('staff'))->assertJsonPath('data.incident_no', 'INC-2026-0044');
    }

    public function test_backfill_uses_the_manila_year_of_created_at(): void
    {
        $newYearInManila = $this->legacyRow(['created_at' => '2025-12-31 16:30:00']);
        $stillOldYear = $this->legacyRow(['created_at' => '2025-12-31 15:30:00']);

        (new IncidentNumberBackfill)->run();

        $this->assertSame('INC-2026-0001', $this->numberOf($newYearInManila));
        $this->assertSame('INC-2025-0001', $this->numberOf($stillOldYear));
    }

    public function test_typed_number_from_a_year_with_no_blanks_still_raises_that_counter(): void
    {
        $this->legacyRow(['incident_no' => 'INC-2024-0007']);

        (new IncidentNumberBackfill)->run();

        $this->assertSame(7, DB::table('incident_number_sequences')->where('year', 2024)->value('last_number'));
    }

    public function test_unique_index_allows_many_old_reports_without_numbers(): void
    {
        $this->assertTrue(Schema::hasIndex('situational_incident_reports', 'sir_incident_no_unique'));

        $first = $this->legacyRow(['form_version' => 1]);
        $second = $this->legacyRow(['form_version' => 1]);

        $this->assertNull($this->numberOf($first));
        $this->assertNull($this->numberOf($second));
    }

    public function test_migration_skips_the_index_and_keeps_values_when_duplicates_exist(): void
    {
        $this->migration()->down();
        $first = $this->legacyRow(['incident_no' => 'DUP-1']);
        $second = $this->legacyRow(['incident_no' => 'DUP-1']);

        ob_start();
        $this->migration()->up();
        ob_end_clean();

        $this->assertFalse(Schema::hasIndex('situational_incident_reports', 'sir_incident_no_unique'));
        $this->assertSame('DUP-1', $this->numberOf($first));
        $this->assertSame('DUP-1', $this->numberOf($second));
        $this->assertTrue(Schema::hasTable('incident_number_sequences'));
    }

    public function test_migration_down_keeps_assigned_numbers(): void
    {
        $id = $this->createAs($this->userWithRole('staff'))->json('data.id');

        $this->migration()->down();

        $this->assertFalse(Schema::hasTable('incident_number_sequences'));
        $this->assertFalse(Schema::hasIndex('situational_incident_reports', 'sir_incident_no_unique'));
        $this->assertSame('INC-2026-0001', $this->numberOf($id));
    }
}
