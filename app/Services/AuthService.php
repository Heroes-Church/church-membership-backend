<?php

namespace App\Services;

use App\Mail\VerificationCodeMail;
use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthService
{
    private const OTP_TTL_MINUTES = 10;

    private const OTP_MAX_ATTEMPTS = 5;

    /**
     * @param  array{name:string,email:string,password:string}  $data
     */
    public function register(array $data): User
    {
        $user = User::query()->create($data);
        $user->assignRole('Guest');

        $this->sendOtp($user);

        return $user;
    }

    /**
     * @param array{
     *     email:string,
     *     password:string,
     *     first_name:string,
     *     middle_name?:string|null,
     *     last_name:string,
     *     date_of_birth:string,
     *     phone_number:string,
     *     gender:string,
     *     province?:string|null,
     *     city_municipality?:string|null,
     *     barangay?:string|null,
     *     street_address?:string|null,
     *     zip_code?:string|null
     * } $data
     */
    public function registerMember(array $data): User
    {
        $user = DB::transaction(function () use ($data): User {
            $user = User::query()->create([
                'name' => implode(' ', array_filter([
                    $data['first_name'],
                    $data['middle_name'] ?? null,
                    $data['last_name'],
                ])),
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $user->assignRole('Guest');

            MemberProfile::query()->create([
                'user_id' => $user->id,
                'first_name' => $data['first_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'last_name' => $data['last_name'],
                'date_of_birth' => $data['date_of_birth'],
                'phone_number' => $data['phone_number'],
                'gender' => $data['gender'],
                'province' => $data['province'] ?? null,
                'city_municipality' => $data['city_municipality'] ?? null,
                'barangay' => $data['barangay'] ?? null,
                'street_address' => $data['street_address'] ?? null,
                'zip_code' => $data['zip_code'] ?? null,
            ]);

            return $user;
        });

        $this->sendOtp($user);

        return $user;
    }

    /**
     * @param  array{email:string,password:string}  $credentials
     * @return array{user:User,token:string}
     */
    public function login(array $credentials): array
    {
        $user = User::query()
            ->where('email', $credentials['email'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->email_verified_at) {
            throw ValidationException::withMessages([
                'email' => ['Please verify your email address before signing in.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * @param  array{email:string,otp:string}  $data
     * @return array{user:User,token:string}
     */
    public function verifyOtp(array $data): array
    {
        $user = User::query()
            ->where('email', $data['email'])
            ->first();

        $otpKey = $this->otpCacheKey($data['email']);
        $attemptsKey = $this->otpAttemptsCacheKey($data['email']);
        $hashedOtp = Cache::get($otpKey);

        if (! $user || ! is_string($hashedOtp)) {
            $this->throwInvalidOtp();
        }

        Cache::add(
            $attemptsKey,
            0,
            now()->addMinutes(self::OTP_TTL_MINUTES)
        );

        $attempts = Cache::increment($attemptsKey);

        if (Hash::check($data['otp'], $hashedOtp)) {
            Cache::forget($otpKey);
            Cache::forget($attemptsKey);

            if (! $user->email_verified_at) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $token = $user->createToken('auth-token')->plainTextToken;

            return [
                'user' => $user,
                'token' => $token,
            ];
        }

        if ($attempts >= self::OTP_MAX_ATTEMPTS) {
            Cache::forget($otpKey);
            Cache::forget($attemptsKey);
        }

        $this->throwInvalidOtp();
    }

    public function resendOtp(string $email): void
    {
        $user = User::query()
            ->where('email', $email)
            ->first();

        if (! $user || $user->email_verified_at) {
            return;
        }

        $this->sendOtp($user);
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    private function sendOtp(User $user): void
    {
        $otp = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(self::OTP_TTL_MINUTES);

        Cache::put(
            $this->otpCacheKey($user->email),
            Hash::make($otp),
            $expiresAt
        );

        Cache::put(
            $this->otpAttemptsCacheKey($user->email),
            0,
            $expiresAt
        );

        Mail::to($user->email)->send(new VerificationCodeMail($otp));
    }

    private function throwInvalidOtp(): never
    {
        throw ValidationException::withMessages([
            'otp' => ['The verification code is invalid or expired.'],
        ]);
    }

    private function otpCacheKey(string $email): string
    {
        return 'auth_otp:'.strtolower($email);
    }

    private function otpAttemptsCacheKey(string $email): string
    {
        return 'auth_otp_attempts:'.strtolower($email);
    }
}
