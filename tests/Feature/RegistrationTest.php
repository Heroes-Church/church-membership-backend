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
            'terms_accepted' => true,
        ])->assertAccepted();

        $this->assertDatabaseMissing('users', ['email' => $email]);

        $verificationMail = Mail::sent(VerificationCodeMail::class)->first();
        $this->assertNotNull($verificationMail);

        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => $email,
            'otp' => $verificationMail->otp,
        ])->assertOk()->assertJsonStructure(['token', 'user']);

        $user = User::query()->where('email', $email)->firstOrFail();
        $this->assertTrue($user->hasRole('Guest'));
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_member_registration_creates_account_and_profile_after_otp_verification(): void
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
        ])->assertAccepted();

        $this->assertDatabaseMissing('users', ['email' => $email]);
        $this->assertDatabaseMissing('member_profiles', [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
        ]);

        $verificationMail = Mail::sent(VerificationCodeMail::class)->first();
        $this->assertNotNull($verificationMail);

        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => $email,
            'otp' => $verificationMail->otp,
        ])->assertOk()->assertJsonStructure(['token', 'user']);

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
        $this->assertNotNull($user->email_verified_at);

        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/users/{$user->id}/member-profile")
            ->assertOk()
            ->assertJsonPath('data.date_of_birth', '1990-05-10');
    }

    public function test_registration_can_replace_a_legacy_unverified_account_after_otp(): void
    {
        $email = 'pending@example.com';
        $existingUser = User::factory()->unverified()->create([
            'name' => 'Old Signup',
            'email' => $email,
        ]);
        $existingUser->assignRole('Guest');

        $this->postJson('/api/v1/auth/register/member', [
            'email' => $email,
            'password' => 'NewPassword123!',
            'first_name' => 'New',
            'last_name' => 'Signup',
            'date_of_birth' => '1990-05-10',
            'phone_number' => '+639171234567',
            'gender' => 'Female',
            'terms_accepted' => true,
        ])->assertAccepted();

        $this->assertSame('Old Signup', $existingUser->fresh()->name);

        $verificationMail = Mail::sent(VerificationCodeMail::class)->first();
        $this->assertNotNull($verificationMail);

        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => $email,
            'otp' => $verificationMail->otp,
        ])->assertOk();

        $this->assertSame(
            'New Signup',
            $existingUser->fresh()->name
        );
        $this->assertNotNull($existingUser->fresh()->email_verified_at);
        $this->assertDatabaseHas('member_profiles', [
            'user_id' => $existingUser->id,
            'first_name' => 'New',
            'last_name' => 'Signup',
        ]);
    }

    public function test_admin_can_change_guest_role_to_member(): void
    {
        // Arrange: Create an Admin.
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        // Arrange: Create a verified Guest.
        $guest = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $guest->assignRole('Guest');

        // Arrange: Provide the required member profile.
        $guest->memberProfile()->create([
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'last_name' => 'Dela Cruz',
            'date_of_birth' => '2000-05-10',
            'phone_number' => '+639171234567',
            'gender' => 'Male',
            'province' => 'Metro Manila',
            'city_municipality' => 'Taguig',
            'barangay' => 'Fort Bonifacio',
            'street_address' => 'Sample Street',
            'zip_code' => '1630',
        ]);

        // Act: Admin approves Guest promotion.
        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/users/{$guest->id}/role", [
                'role' => 'Member',
                'membership_class_attended' => true,
            ])
            ->assertOk()
            ->assertJsonPath('user.roles.0', 'Member');

        // Assert: User now has Member role.
        $this->assertTrue(
            $guest->fresh()->hasRole('Member')
        );
    }

}
