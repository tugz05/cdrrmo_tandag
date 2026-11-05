<?php

namespace Tests\Feature\Api;

use App\Models\SituationalIncidentReport;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RunSheetApiTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/api/v1/situational-incident-reports';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
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
            'incident_no' => 'INC-2026-014',
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

        $this->actingAs($this->userWithRole('admin'))
            ->get("/admin/situational-incident-reports/{$report->id}/print")
            ->assertOk()
            ->assertViewIs('print.run-sheet')
            ->assertSee('EMERGENCY MEDICAL RESPONSE')
            ->assertSee('INC-2026-014')
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
}
