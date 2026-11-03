<?php

namespace Tests\Feature;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use App\Support\CurrentChurch;

class AuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        Mail::fake();
    }

    public function test_registration_sends_hashed_otp_and_verification_returns_token(): void
    {
        $email = 'guest@example.com';

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Guest Person',
            'email' => $email,
            'password' => 'Password123!',
        ])->assertCreated();

        $this->assertDatabaseHas('users', ['email' => $email]);

        $user = User::query()->where('email', $email)->firstOrFail();
        $this->assertTrue($user->hasRole('Guest'));

        $mail = Mail::sent(VerificationCodeMail::class)->first();
        $this->assertNotNull($mail);

        $hashedOtp = Cache::get('auth_otp:'.app(CurrentChurch::class)->id().':'.$email);
        $this->assertIsString($hashedOtp);
        $this->assertTrue(Hash::check($mail->otp, $hashedOtp));

        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => $email,
            'otp' => $mail->otp,
        ])->assertOk()
            ->assertJsonStructure(['token', 'user']);

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertNull(Cache::get('auth_otp:'.app(CurrentChurch::class)->id().':'.$email));
    }

    public function test_unverified_user_cannot_login(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Unverified User',
            'email' => 'unverified@example.com',
            'password' => 'Password123!',
        ])->assertCreated();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'unverified@example.com',
            'password' => 'Password123!',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_otp_is_removed_after_five_incorrect_attempts(): void
    {
        $email = 'otp-test@example.com';

        $this->postJson('/api/v1/auth/register', [
            'name' => 'OTP Test',
            'email' => $email,
            'password' => 'Password123!',
        ])->assertCreated();

        $mail = Mail::sent(VerificationCodeMail::class)->first();
        $this->assertNotNull($mail);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/verify-otp', [
                'email' => $email,
                'otp' => '000000',
            ])->assertUnprocessable()
                ->assertJsonValidationErrors('otp');
        }

        $this->assertNull(Cache::get('auth_otp:'.app(CurrentChurch::class)->id().':'.$email));

        $this->postJson('/api/v1/auth/verify-otp', [
            'email' => $email,
            'otp' => $mail->otp,
        ])->assertTooManyRequests();
    }

    public function test_resend_otp_returns_same_public_response_for_unknown_email(): void
    {
        $response = $this->postJson('/api/v1/auth/resend-otp', [
            'email' => 'unknown@example.com',
        ])->assertAccepted();

        $response->assertExactJson([
            'message' => 'If the account exists and is not verified, a new code has been sent.',
        ]);

        Mail::assertNothingSent();
    }
}
