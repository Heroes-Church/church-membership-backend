<?php

namespace Tests\Feature;

use App\Models\Church;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChurchSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_church_settings_are_publicly_readable(): void
    {
        $this->getJson('/api/v1/church-settings')
            ->assertOk()
            ->assertJsonPath('data.name', 'Heroes Church BGC')
            ->assertJsonPath('data.logo_url', null);
    }

    public function test_admin_can_update_church_settings(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/church-settings', [
                'name' => 'Heroes Church BGC Updated',
                'contact_email' => 'hello@heroeschurch.example',
                'primary_color' => '#123ABC',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Heroes Church BGC Updated')
            ->assertJsonPath('data.contact_email', 'hello@heroeschurch.example')
            ->assertJsonPath('data.primary_color', '#123ABC');

        $this->assertDatabaseHas('churches', [
            'id' => app(\App\Support\CurrentChurch::class)->id(),
            'name' => 'Heroes Church BGC Updated',
            'primary_color' => '#123ABC',
        ]);
    }

    public function test_non_admin_cannot_update_church_settings(): void
    {
        $member = User::factory()->create();
        $member->assignRole('Member');

        $this->actingAs($member, 'sanctum')
            ->patchJson('/api/v1/church-settings', ['name' => 'Unauthorized Change'])
            ->assertForbidden();

        $this->assertDatabaseHas('churches', [
            'slug' => 'heroes-church-bgc',
            'name' => 'Heroes Church BGC',
        ]);
    }

    public function test_admin_can_upload_and_remove_church_logo(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin, 'sanctum')
            ->patch('/api/v1/church-settings', [
                'logo' => UploadedFile::fake()->createWithContent(
                    'logo.png',
                    base64_decode(
                        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j8ioAAAAASUVORK5CYII='
                    )
                ),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonStructure(['data' => ['logo_url']]);

        $this->assertIsString(data_get($response->json(), 'data.logo_url'));

        $church = Church::query()->where('slug', 'heroes-church-bgc')->firstOrFail();
        $logoPath = $church->logo_path;

        $this->assertNotNull($logoPath);
        Storage::disk('public')->assertExists($logoPath);

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/church-settings', ['remove_logo' => true])
            ->assertOk()
            ->assertJsonPath('data.logo_url', null);

        Storage::disk('public')->assertMissing($logoPath);
    }

    public function test_invalid_color_is_rejected(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/church-settings', ['primary_color' => 'red'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('primary_color');
    }
}
