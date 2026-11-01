<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('auth-register', function (Request $request) {
            return Limit::perHour(5)
                ->by('auth-register-ip:'.$request->ip());
        });

        RateLimiter::for('auth-login', function (Request $request) {
            return [
                Limit::perMinute(10)
                    ->by('auth-login-ip:'.$request->ip()),
                Limit::perMinute(5)
                    ->by('auth-login-email:'.strtolower((string) $request->input('email'))),
            ];
        });

        RateLimiter::for('auth-verify-otp', function (Request $request) {
            return [
                Limit::perMinute(10)
                    ->by('auth-verify-ip:'.$request->ip()),
                Limit::perMinute(5)
                    ->by('auth-verify-email:'.strtolower((string) $request->input('email'))),
            ];
        });

        RateLimiter::for('auth-resend-otp', function (Request $request) {
            $email = strtolower((string) $request->input('email'));

            return [
                Limit::perMinute(1)
                    ->by('auth-resend-ip:'.$request->ip()),
                Limit::perDay(5)
                    ->by('auth-resend-email:'.$email),
            ];
        });
    }
}
