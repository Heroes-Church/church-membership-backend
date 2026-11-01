<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Requests\User\UpdateUserRoleRequest;
use App\Http\Resources\MemberProfileResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    public function all(): AnonymousResourceCollection
    {
        return UserResource::collection($this->userService->all());
    }

    public function index(): AnonymousResourceCollection
    {
        return UserResource::collection($this->userService->paginate());
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }

    public function show(User $user): UserResource
    {
        return new UserResource($user);
    }

    public function memberProfile(User $user): MemberProfileResource
    {
        return new MemberProfileResource($user->memberProfile()->firstOrFail());
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $user = $this->userService->update($user, $request->validated());

        return new UserResource($user);
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user): JsonResponse
    {
        $user->syncRoles([$request->validated('role')]);

        return response()->json([
            'message' => 'User role updated successfully.',
            'user' => new UserResource($user),
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->userService->delete($user);

        return response()->noContent();
    }
}
