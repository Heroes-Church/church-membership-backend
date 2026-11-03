<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChurchSettingsController;
use App\Http\Controllers\Api\FamilyGroupController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('church-settings', [ChurchSettingsController::class, 'show']);

    Route::prefix('auth')->group(function (): void {
        Route::post('register', [AuthController::class, 'register'])
            ->middleware('throttle:auth-register');

        Route::post('register/member', [AuthController::class, 'registerMember'])
            ->middleware('throttle:auth-register');

        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:auth-login');

        Route::post('verify-otp', [AuthController::class, 'verifyOtp'])
            ->middleware('throttle:auth-verify-otp');

        Route::post('resend-otp', [AuthController::class, 'resendOtp'])
            ->middleware('throttle:auth-resend-otp');
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::patch('church-settings', [ChurchSettingsController::class, 'update'])
            ->middleware('permission:settings.manage');

        Route::prefix('auth')->group(function (): void {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });

        Route::middleware('permission:members.view')->group(function (): void {
            Route::get('members', [MemberController::class, 'index']);
            Route::get('members/{user}', [MemberController::class, 'show']);
        });

        Route::patch('members/{user}', [MemberController::class, 'update'])
            ->middleware('permission:members.update');

        Route::middleware('permission:family-groups.view')->group(function (): void {
            Route::get('family-groups', [FamilyGroupController::class, 'index']);
            Route::get('family-groups/{familyGroup}', [FamilyGroupController::class, 'show']);
        });

        Route::middleware('permission:family-groups.manage')->group(function (): void {
            Route::post('family-groups', [FamilyGroupController::class, 'store']);
            Route::put('family-groups/{familyGroup}', [FamilyGroupController::class, 'update']);
            Route::delete('family-groups/{familyGroup}', [FamilyGroupController::class, 'destroy']);

            Route::post(
                'family-groups/{familyGroup}/members',
                [FamilyGroupController::class, 'attachMember']
            );

            Route::delete(
                'family-groups/{familyGroup}/members/{memberProfile}',
                [FamilyGroupController::class, 'detachMember']
            );
        });

        Route::middleware('permission:users.view')->group(function (): void {
            Route::get('users/all', [UserController::class, 'all']);
            Route::get('users', [UserController::class, 'index']);
            Route::get('users/{user}', [UserController::class, 'show']);
        });

        Route::post('users', [UserController::class, 'store'])
            ->middleware('permission:users.create');

        Route::match(['put', 'patch'], 'users/{user}', [UserController::class, 'update'])
            ->middleware('permission:users.update');

        Route::delete('users/{user}', [UserController::class, 'destroy'])
            ->middleware('permission:users.delete');

        Route::patch('users/{user}/role', [UserController::class, 'updateRole'])
            ->middleware('permission:roles.manage');

        Route::get('users/{user}/member-profile', [UserController::class, 'memberProfile'])
            ->middleware('permission:roles.manage');
    });
});
