<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * @param array{name:string,email:string,password:string} $data
     */
    public function register(array $data): User
    {
        $user = User::query()->create($data);

        $otp = (string) random_int(100000, 999999);
        Cache::put($this->otpCacheKey($user->email), $otp, now()->addMinutes(10));

        return $user;
    }

    /**
     * @param array{email:string,password:string} $credentials
     * @return array{user:User,token:string}
     */
    public function login(array $credentials): array
    {
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * @param array{email:string,otp:string} $data
     * @return array{user:User,token:string}
     */
    public function verifyOtp(array $data): array
    {
        $user = User::query()->where('email', $data['email'])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => ['No user found with the provided email.'],
            ]);
        }

        $cachedOtp = Cache::get($this->otpCacheKey($user->email));

        if (! is_string($cachedOtp) || $cachedOtp !== $data['otp']) {
            throw ValidationException::withMessages([
                'otp' => ['The OTP is invalid or expired.'],
            ]);
        }

        Cache::forget($this->otpCacheKey($user->email));

        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $user->tokens()->delete();
    }

    private function otpCacheKey(string $email): string
    {
        return 'auth_otp:'.strtolower($email);
    }
}
