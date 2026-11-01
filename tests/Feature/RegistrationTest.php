<?php

namespace Tests\Feature;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Mail::fake();
    }

    public function test_guest_registration_starts_as_guest_and_can_verify_otp(): void
    {
        $email = 'guest@example.com';

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Guest Person',
            'email' => $email,
            'password' => 'Password123!',
        ])->assertCreated();

        $user = User::query()->where('email', $email)->firstOrFail();
        $this->assertTrue($user->hasRole('Guest'));

        $verificationMail = Mail::sent(VerificationCodeMail::class)->first();
        $this->assertNotNull($verificationMail);

        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => $email,
            'otp' => $verificationMail->otp,
        ])->assertOk()->assertJsonStructure(['token', 'user']);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_member_registration_saves_profile_but_starts_as_guest(): void
    {
        $email = 'member@example.com';

        $this->postJson('/api/v1/auth/register/member', [
            'email' => $email,
            'password' => 'Password123!',
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'last_name' => 'Dela Cruz',
            'date_of_birth' => '1990-05-10',
            'phone_number' => '+639171234567',
            'gender' => 'Prefer not to say',
            'province' => 'Metro Manila',
            'city_municipality' => 'Taguig',
            'barangay' => 'Fort Bonifacio',
            'street_address' => 'Unit 1',
            'zip_code' => '1630',
            'terms_accepted' => true,
        ])->assertCreated();

        $user = User::query()->where('email', $email)->firstOrFail();

        $this->assertSame('Juan Santos Dela Cruz', $user->name);
        $this->assertTrue($user->hasRole('Guest'));
        $this->assertDatabaseHas('member_profiles', [
            'user_id' => $user->id,
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'last_name' => 'Dela Cruz',
            'phone_number' => '+639171234567',
            'zip_code' => '1630',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/users/{$user->id}/member-profile")
            ->assertOk()
            ->assertJsonPath('data.date_of_birth', '1990-05-10');
    }

    public function test_admin_can_change_guest_role_to_member(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $guest = User::factory()->create();
        $guest->assignRole('Guest');

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/users/{$guest->id}/role", ['role' => 'Member', 'membership_class_attended' => true])
            ->assertOk()
            ->assertJsonPath('user.roles.0', 'Member');

        $this->assertTrue($guest->fresh()->hasRole('Member'));
    }
}
