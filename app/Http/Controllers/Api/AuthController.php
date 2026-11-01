<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\MemberRegisterRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());

        return response()->json([
            'message' => 'Registration successful. OTP sent.',
            'user' => new UserResource($user),
        ], 201);
    }

    public function registerMember(MemberRegisterRequest $request): JsonResponse
    {
        $user = $this->authService->registerMember($request->validated());

        return response()->json([
            'message' => 'Registration successful. OTP sent.',
            'user' => new UserResource($user),
        ], 201);
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $data = $this->authService->verifyOtp($request->validated());

        return response()->json([
            'user' => new UserResource($data['user']),
            'token' => $data['token'],
            'token_type' => 'Bearer',
            'message' => 'OTP verified successfully. You are now logged in.',
        ]);
    }

    public function resendOtp(ResendOtpRequest $request): JsonResponse
    {
        $this->authService->resendOtp($request->validated()['email']);

        return response()->json([
            'message' => 'If the account exists and is not verified, a new code has been sent.',
        ], 202);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $this->authService->login($request->validated());

        return response()->json([
            'user' => new UserResource($data['user']),
            'token' => $data['token'],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        if ($request->user()) {
            $this->authService->logout($request->user());
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($request->user()),
        ]);
    }
}
