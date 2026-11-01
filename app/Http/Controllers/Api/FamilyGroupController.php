<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FamilyGroup\AttachFamilyMemberRequest;
use App\Http\Requests\FamilyGroup\StoreFamilyGroupRequest;
use App\Http\Requests\FamilyGroup\UpdateFamilyGroupRequest;
use App\Http\Resources\FamilyGroupResource;
use App\Models\FamilyGroup;
use App\Models\MemberProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

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

    public function store(StoreFamilyGroupRequest $request): JsonResponse
    {
        $familyGroup = FamilyGroup::query()->create($request->validated());

        return (new FamilyGroupResource($familyGroup->load('members')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(FamilyGroup $familyGroup): FamilyGroupResource
    {
        return new FamilyGroupResource($familyGroup->load('members'));
    }

    public function update(
        UpdateFamilyGroupRequest $request,
        FamilyGroup $familyGroup
    ): FamilyGroupResource {
        $familyGroup->update($request->validated());

        return new FamilyGroupResource($familyGroup->load('members'));
    }

    public function destroy(FamilyGroup $familyGroup): JsonResponse
    {
        $familyGroup->delete();

        return response()->json([
            'message' => 'Family group deleted. Member profiles were kept.',
        ]);
    }

    public function attachMember(
        AttachFamilyMemberRequest $request,
        FamilyGroup $familyGroup
    ): FamilyGroupResource {
        $profile = MemberProfile::query()
            ->with('user')
            ->findOrFail($request->validated('member_profile_id'));

        abort_unless(
            $profile->user?->hasRole('Member'),
            422,
            'Only users with the Member role can be added to a family group.'
        );

        abort_if(
            $profile->family_group_id !== null
                && $profile->family_group_id !== $familyGroup->id,
            422,
            'This member already belongs to another family group.'
        );

        $profile->family_group_id = $familyGroup->id;
        $profile->save();

        return new FamilyGroupResource($familyGroup->load('members'));
    }

    public function detachMember(
        FamilyGroup $familyGroup,
        MemberProfile $memberProfile
    ): FamilyGroupResource {
        abort_unless(
            $memberProfile->family_group_id === $familyGroup->id,
            404,
            'This profile does not belong to this family group.'
        );

        $memberProfile->family_group_id = null;
        $memberProfile->save();

        return new FamilyGroupResource($familyGroup->load('members'));
    }
}
