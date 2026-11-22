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
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

class UserController extends Controller
{
    private function authorizeAccountUpdate(
        Request $request,
        User $target
    ): void{
        $actor = $request->user();
        
        abort_unless($actor !== null &&  $actor-> hasAnyRole(['Admin', 'Superadmin']),
            403, 'Only authorized administrators can manage accounts.'
        );

        abort_unless((int) $actor->church_id === (int) $target->church_id,
            404,
            'User not found.'
        );

        abort_if($target->hasRole('Superadmin'),
            403, 'Superadmin accounts cannot be updated here.'
        );

        abort_if($target->hasRole('Admin') && ! $actor->hasRole('Superadmin'),
            403, 'Only a Superadmin can update Admin accounts.'
        );
    }

    private function authorizeAccountView(
        Request $request,
        User $target
    ): void {
        $actor = $request->user();

        abort_unless($actor !== null, 401);

        abort_unless((int) $actor->church_id === (int) $target->church_id,
            404,
            'User not found.'    
        );

        if($target->hasRole('Superadmin')){
            abort_unless($actor->hasRole('Superadmin'),
                403
            );
        }
        if($target->hasRole('Admin')){
            abort_unless(
                $actor->hasAnyRole(['Admin', 'Superadmin']),
                403
            );
        }
    }

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
        $actor = $request->user();
        
        abort_unless($actor !== null && $actor->hasAnyRole(['Admin', 'Superadmin']),
            403, 'Only administrators can create accounts.'
        );

        $user = $this->userService->create($request->validated());

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, User $user): UserResource
    {
        $this->authorizeAccountView($request, $user);

        return new UserResource($user);
    }

    public function memberProfile(Request $request, User $user): MemberProfileResource
    {
        $this->authorizeAccountView($request, $user);
        
        return new MemberProfileResource($user->memberProfile()->firstOrFail());
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $this->authorizeAccountUpdate($request, $user);

        $updatedUser = $this->userService->update($user, $request->validated());

        return new UserResource($updatedUser);
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user): JsonResponse 
    {
        $actor = $request->user(); 
        $newRole = $request->validated('role');

        // RULE 1: Users cannot change their own role.
        abort_if(
            $actor->is($user),
            403,
            'You cannot change your own role.'
        );

        // RULE 2: Prevent cross-church role changes.
        abort_unless(
            $actor->church_id === $user->church_id,
            404
        );

        // RULE 3: Superadmin accounts are protected.
        // Provisioning and recovery must use a separate
        // trusted administrative process.
        abort_if(
            $user->hasRole('Superadmin'),
            403,
            'Superadmin accounts cannot be modified here.'
        );

        // RULE 4: Only Superadmin can assign Admin
        // or change an existing Admin account's role.
        if (
            $newRole === 'Admin' ||
            $user->hasRole('Admin')
        ) {
            abort_unless(
                $actor->hasRole('Superadmin'),
                403,
                'Only Superadmin can manage Admin roles.'
            );
        }

        // RULE 5: Member/Leader accounts must
        // have a verified email and member profile.
        if (in_array($newRole, ['Member', 'Leader'], true)) {
            abort_unless(
                $user->email_verified_at !== null,
                422,
                'Email verification is required.'
            );

            abort_unless(
                $user->memberProfile()->exists(),
                422,
                'A member profile is required.'
            );
        }

        // RULE 6: Save role changes atomically.
        DB::transaction(function () use ($user, $newRole): void {
            // Lock the target user to serialize role changes.
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // Recheck the role under the lock.
            abort_if(
                $lockedUser->hasRole('Superadmin'),
                403,
                'Superadmin accounts cannot be modified here.'
            );

            // Prevent removing an Admin role if it is
            // the final Admin for this church.
            if (
                $lockedUser->hasRole('Admin') &&
                $newRole !== 'Admin'
            ) {
                $adminCount = User::role('Admin')->count();

                abort_if(
                    $adminCount <= 1,
                    422,
                    'Cannot remove the last church Admin.'
                );
            }

            $lockedUser->syncRoles([$newRole]);

            // Revoke existing sessions after a role change.
            $lockedUser->tokens()->delete();
        });

        return response()->json([
            'message' => 'User role updated successfully.',
            'user' => new UserResource($user->refresh()),
        ]);
    }

    public function destroy(Request $request, User $user): Response
    {
        $this->authorizeAccountUpdate($request , $user);

        $this->userService->delete($user);  

        return response()->noContent();
    }
}
