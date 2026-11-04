<?php

namespace Tests\Feature\Api;

use App\Helpers\JHelper;
use App\Models\Report;
use App\Models\User;
use App\Models\UserValidId;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SensitiveRoutesAccessTest extends TestCase
{
    use RefreshDatabase;

    private const ID_FILE = 'abc123.jpg';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
        Storage::fake('public');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create(['confirmed_at' => null]);
        $user->addRole($role);

        return $user;
    }

    private function storedIdFor(User $user, string $disk = 'local'): void
    {
        Storage::disk($disk)->put('img_valid_ids/'.self::ID_FILE, 'image-bytes');
        UserValidId::create(['user_id' => $user->id, 'filename' => 'img_valid_ids/'.self::ID_FILE]);
    }

    private function imageFiles(): array
    {
        return [
            'img_valid_id' => UploadedFile::fake()->create('id.jpg', 10, 'image/jpeg'),
            'img_selfie' => UploadedFile::fake()->create('selfie.jpg', 10, 'image/jpeg'),
        ];
    }

    private function reportFor(User $user): Report
    {
        return Report::create([
            'user_id' => $user->id,
            'latitude' => '9.0195',
            'longitude' => '126.2331',
            'type' => 'Call',
            'status' => 'Pending',
        ]);
    }

    public function test_valid_id_image_needs_login(): void
    {
        $this->storedIdFor($this->userWithRole('user'));

        $this->getJson('/api/v1/valid_ids/'.self::ID_FILE)->assertUnauthorized();
    }

    public function test_other_citizen_cannot_view_a_valid_id_image(): void
    {
        $this->storedIdFor($this->userWithRole('user'));
        Sanctum::actingAs($this->userWithRole('user'));

        $this->getJson('/api/v1/valid_ids/'.self::ID_FILE)->assertForbidden();
    }

    public function test_owner_and_staff_can_view_a_valid_id_image(): void
    {
        $owner = $this->userWithRole('user');
        $this->storedIdFor($owner);

        Sanctum::actingAs($owner);
        $this->get('/api/v1/valid_ids/'.self::ID_FILE)
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        Sanctum::actingAs($this->userWithRole('staff'));
        $this->get('/api/v1/valid_ids/'.self::ID_FILE)->assertOk();
    }

    public function test_admin_dashboard_links_to_the_protected_route_and_it_works(): void
    {
        $owner = $this->userWithRole('user');
        $this->storedIdFor($owner, 'public');

        [$url] = JHelper::getValidImages($owner->id);
        $this->assertStringContainsString('/admin/valid-ids/'.self::ID_FILE, $url);

        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs($this->userWithRole('admin'))->get($url)->assertOk();
    }

    public function test_new_uploads_go_to_private_storage(): void
    {
        $user = $this->userWithRole('user');

        $this->post('/api/v1/valid-images/'.$user->id, $this->imageFiles(), ['Accept' => 'application/json'])->assertOk();

        $stored = UserValidId::whereUserId($user->id)->pluck('filename');
        $this->assertCount(2, $stored);
        foreach ($stored as $path) {
            Storage::disk('local')->assertExists($path);
            Storage::disk('public')->assertMissing($path);
        }
    }

    public function test_guest_cannot_upload_for_account_with_existing_id_or_already_confirmed(): void
    {
        $hasId = $this->userWithRole('user');
        $this->storedIdFor($hasId);
        $this->post('/api/v1/valid-images/'.$hasId->id, $this->imageFiles(), ['Accept' => 'application/json'])
            ->assertUnauthorized();

        $confirmed = $this->userWithRole('user');
        $confirmed->forceFill(['confirmed_at' => now()])->save();
        $this->post('/api/v1/valid-images/'.$confirmed->id, $this->imageFiles(), ['Accept' => 'application/json'])
            ->assertUnauthorized();

        $operator = $this->userWithRole('admin');
        $this->post('/api/v1/valid-images/'.$operator->id, $this->imageFiles(), ['Accept' => 'application/json'])
            ->assertUnauthorized();
    }

    public function test_signed_in_user_cannot_upload_for_someone_else(): void
    {
        $target = $this->userWithRole('user');
        Sanctum::actingAs($this->userWithRole('user'));

        $this->post('/api/v1/valid-images/'.$target->id, $this->imageFiles(), ['Accept' => 'application/json'])
            ->assertForbidden();
    }

    public function test_call_status_routes_need_login_and_ownership(): void
    {
        $owner = $this->userWithRole('user');
        $report = $this->reportFor($owner);

        $this->postJson('/api/v1/call/started', ['report_id' => $report->id])->assertUnauthorized();
        $this->postJson('/api/v1/call/ended', ['report_id' => $report->id])->assertUnauthorized();

        Sanctum::actingAs($this->userWithRole('user'));
        $this->postJson('/api/v1/call/started', ['report_id' => $report->id])->assertForbidden();
        $this->assertSame('Pending', $report->fresh()->status);

        Sanctum::actingAs($owner);
        $this->postJson('/api/v1/call/started', ['report_id' => $report->id])->assertOk();
    }

    public function test_admin_records_calls_through_the_session_routes(): void
    {
        $report = $this->reportFor($this->userWithRole('user'));

        $this->actingAs($this->userWithRole('admin'))
            ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->postJson(route('admin.calls.started'), ['report_id' => $report->id])
            ->assertOk();

        $this->assertNotNull($report->fresh()->call_started_at);
    }

    public function test_set_location_rejects_guests(): void
    {
        $victim = $this->userWithRole('user');
        $payload = ['user_id' => $victim->id, 'latitude' => 9.0195, 'longitude' => 126.2331];

        $this->postJson('/api/v1/caller-details/set-location', $payload)->assertUnauthorized();
        $this->assertSame(0, Report::count());
    }

    public function test_set_location_can_be_opened_temporarily_by_config(): void
    {
        config(['call.set_location_require_auth' => false]);
        $victim = $this->userWithRole('user');
        $payload = ['user_id' => $victim->id, 'latitude' => 9.0195, 'longitude' => 126.2331];

        // Past the login gate: with nobody online the next check answers "busy".
        $this->postJson('/api/v1/caller-details/set-location', $payload)
            ->assertStatus(503)
            ->assertJsonPath('code', 'ALL_OPERATORS_BUSY');
    }

    public function test_twilio_health_requires_an_admin(): void
    {
        $this->getJson('/twilio/health')->assertUnauthorized();
        $this->actingAs($this->userWithRole('user'))->getJson('/twilio/health')->assertForbidden();
        $this->actingAs($this->userWithRole('admin'))->getJson('/twilio/health')->assertOk();
    }

    public function test_open_upload_route_is_gone(): void
    {
        $this->postJson('/api/v1/upload', [])->assertNotFound();
    }

    public function test_secure_command_moves_public_ids_to_private_storage(): void
    {
        Storage::disk('public')->put('img_valid_ids/'.self::ID_FILE, 'image-bytes');

        $this->artisan('valid-ids:secure')->assertSuccessful();

        Storage::disk('public')->assertMissing('img_valid_ids/'.self::ID_FILE);
        $this->assertSame('image-bytes', Storage::disk('local')->get('img_valid_ids/'.self::ID_FILE));
    }
}
