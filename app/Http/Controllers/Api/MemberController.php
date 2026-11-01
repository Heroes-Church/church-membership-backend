<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\UpdateMemberProfileRequest;
use App\Http\Resources\MemberResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $members = User::query()
            ->role('Member')
            ->whereHas('memberProfile')
            ->with('memberProfile')
            ->latest('id')
            ->paginate(15);

        return MemberResource::collection($members);
    }

    public function show(User $user): MemberResource
    {
        abort_unless(
            $user->hasRole('Member') && $user->memberProfile()->exists(),
            404
        );

        return new MemberResource($user->load('memberProfile'));
    }

    public function update(
        UpdateMemberProfileRequest $request,
        User $user
    ): MemberResource {
        abort_unless(
            $user->hasRole('Member') && $user->memberProfile()->exists(),
            404
        );

        $profile = $user->memberProfile;
        $data = $request->validated();

        DB::transaction(function () use ($profile, $user, $data): void {
            $profile->fill($data)->save();

            $user->forceFill([
                'name' => implode(' ', array_filter([
                    $profile->first_name,
                    $profile->middle_name,
                    $profile->last_name,
                ])),
            ])->save();
        });

        return new MemberResource($user->load('memberProfile'));
    }
}
