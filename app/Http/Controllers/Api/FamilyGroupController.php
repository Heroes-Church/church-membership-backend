<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FamilyGroup\AttachFamilyMemberRequest;
use App\Http\Requests\FamilyGroup\StoreFamilyGroupRequest;
use App\Http\Requests\FamilyGroup\UpdateFamilyGroupRequest;
use App\Http\Resources\FamilyGroupResource;
use App\Models\FamilyGroup;
use App\Models\MemberProfile;
use App\Support\CurrentChurch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class FamilyGroupController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $familyGroups = FamilyGroup::query()
            ->with('members')
            ->latest('id')
            ->paginate(15);

        return FamilyGroupResource::collection($familyGroups);
    }

    public function store(
        StoreFamilyGroupRequest $request
    ): JsonResponse {
        $familyGroup = FamilyGroup::query()
            ->create($request->validated());

        return (new FamilyGroupResource(
            $familyGroup->load('members')
        ))->response()->setStatusCode(201);
    }

    public function show(
        FamilyGroup $familyGroup
    ): FamilyGroupResource {
        return new FamilyGroupResource(
            $familyGroup->load('members')
        );
    }

    public function update(
        UpdateFamilyGroupRequest $request,
        FamilyGroup $familyGroup
    ): FamilyGroupResource {
        $familyGroup->update($request->validated());

        return new FamilyGroupResource(
            $familyGroup->load('members')
        );
    }

    public function destroy(
        FamilyGroup $familyGroup
    ): JsonResponse {
        DB::transaction(function () use ($familyGroup): void {
            $group = FamilyGroup::query()->lockForUpdate()->findOrFail($familyGroup->id);
            $group->head_member_id = null;
            $group->save();
            $group->members()->update(['family_group_id' => null]);
            $group->delete();
        });

        return response()->json([
            'message' =>
                'Family group deleted. Member profiles were kept.',
        ]);
    }

    public function attachMember(
        AttachFamilyMemberRequest $request,
        FamilyGroup $familyGroup
    ): FamilyGroupResource {
        $churchId = app(CurrentChurch::class)->id();
        $profileId = $request->integer('member_profile_id');

        DB::transaction(function () use (
            $familyGroup,
            $churchId,
            $profileId
        ): void {
            // Lock the member profile during assignment.
            $profile = MemberProfile::query()
                ->where('church_id', $churchId)
                ->whereKey($profileId)
                ->lockForUpdate()
                ->firstOrFail();

            // Explicitly verify the destination family.
            abort_unless(
                (int) $familyGroup->church_id === $churchId,
                404,
                'Family group not found.'
            );

            // A profile must belong to a user in this church.
            $user = $profile->user;

            abort_unless(
                $user !== null
                    && (int) $user->church_id === $churchId,
                422,
                'Member account does not belong to this church.'
            );

            abort_unless(
                $user->hasRole('Member'),
                422,
                'Only users with the Member role can be added '
                    . 'to a family group.'
            );

            // Reject reassignment from an existing family.
            abort_if(
                $profile->family_group_id !== null
                    && (int) $profile->family_group_id
                        !== (int) $familyGroup->id,
                422,
                'This member already belongs to another '
                    . 'family group.'
            );

            $profile->family_group_id = $familyGroup->id;
            $profile->save();
        });

        return new FamilyGroupResource(
            $familyGroup->load('members')
        );
    }

    public function detachMember(
        FamilyGroup $familyGroup,
        MemberProfile $memberProfile
    ): FamilyGroupResource {
        $churchId = app(CurrentChurch::class)->id();

        DB::transaction(function () use (
            $familyGroup,
            $memberProfile,
            $churchId
        ): void {
            $profile = MemberProfile::query()
                ->where('church_id', $churchId)
                ->whereKey($memberProfile->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                (int) $familyGroup->church_id === $churchId,
                404,
                'Family group not found.'
            );

            abort_unless(
                (int) $profile->family_group_id
                    === (int) $familyGroup->id,
                404,
                'This profile does not belong '
                    . 'to this family group.'
            );

            FamilyGroup::query()->whereKey($familyGroup->id)
                ->where('head_member_id', $profile->id)->update(['head_member_id' => null]);
            $profile->family_group_id = null;
            $profile->save();
        });

        return new FamilyGroupResource(
            $familyGroup->load('members')
        );
    }
}
