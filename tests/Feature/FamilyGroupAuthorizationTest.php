<?php

namespace Tests\Feature;

use App\Models\Church;
use App\Models\FamilyGroup;
use App\Models\MemberProfile;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyGroupAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    private function makeUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function makeProfile(User $user): MemberProfile
    {
        return $user->memberProfile()->create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'date_of_birth' => '2000-05-10',
            'phone_number' => '+639171234567',
            'gender' => 'Prefer not to say',
        ]);
    }

    private function makeFamily(): FamilyGroup
    {
        return FamilyGroup::query()->create([
            'name' => 'Sample Family',
        ]);
    }

    public function test_admin_can_attach_member_to_family(): void
    {
        $admin = $this->makeUser('Admin');
        $member = $this->makeUser('Member');

        $profile = $this->makeProfile($member);
        $family = $this->makeFamily();

        $this->actingAs($admin, 'sanctum')
            ->postJson(
                "/api/v1/family-groups/{$family->id}/members",
                ['member_profile_id' => $profile->id]
            )
            ->assertOk();

        $this->assertSame(
            $family->id,
            $profile->fresh()->family_group_id
        );
    }

    public function test_cannot_attach_member_from_another_church(): void
    {
        $admin = $this->makeUser('Admin');
        $family = $this->makeFamily();

        $otherChurch = Church::query()->create([
            'name' => 'Another Church',
            'slug' => 'another-church',
            'is_active' => true,
        ]);

        $originalSlug = config('church.default_slug');
        config(['church.default_slug' => $otherChurch->slug]);

        try {
            $member = $this->makeUser('Member');
            $profile = $this->makeProfile($member);
        } finally {
            config(['church.default_slug' => $originalSlug]);
        }

        $this->actingAs($admin, 'sanctum')
            ->postJson(
                "/api/v1/family-groups/{$family->id}/members",
                ['member_profile_id' => $profile->id]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('member_profile_id');

        $this->assertDatabaseHas('members', [
            'id' => $profile->id,
            'family_group_id' => null,
        ]);
    }

    public function test_cannot_attach_member_to_another_family(): void
    {
        $admin = $this->makeUser('Admin');
        $member = $this->makeUser('Member');

        $profile = $this->makeProfile($member);
        $firstFamily = $this->makeFamily();
        $secondFamily = $this->makeFamily();

        $profile->family_group_id = $firstFamily->id;
        $profile->save();

        $this->actingAs($admin, 'sanctum')
            ->postJson(
                "/api/v1/family-groups/{$secondFamily->id}/members",
                ['member_profile_id' => $profile->id]
            )
            ->assertUnprocessable();

        $this->assertSame(
            $firstFamily->id,
            $profile->fresh()->family_group_id
        );
    }

    public function test_guest_cannot_manage_family_groups(): void
    {
        $guest = $this->makeUser('Guest');
        $family = $this->makeFamily();

        $this->actingAs($guest, 'sanctum')
            ->deleteJson("/api/v1/family-groups/{$family->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('family_groups', [
            'id' => $family->id,
        ]);
    }

    public function test_admin_can_detach_member(): void
    {
        $admin = $this->makeUser('Admin');
        $member = $this->makeUser('Member');

        $profile = $this->makeProfile($member);
        $family = $this->makeFamily();

        $profile->family_group_id = $family->id;
        $profile->save();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson(
                "/api/v1/family-groups/{$family->id}"
                . "/members/{$profile->id}"
            )
            ->assertOk();

        $this->assertNull(
            $profile->fresh()->family_group_id
        );
    }

    public function test_other_church_family_cannot_be_modified(): void
    {
        $admin = $this->makeUser('Admin');
        $family = $this->makeFamily();

        $otherChurch = Church::query()->create([
            'name' => 'Another Church',
            'slug' => 'another-church',
            'is_active' => true,
        ]);

        $family->forceFill([
            'church_id' => $otherChurch->id,
        ])->save();

        // Scoped route model binding must not expose it.
       $this->actingAs($admin, 'sanctum')
            ->putJson(
                "/api/v1/family-groups/{$family->id}",
                ['name' => 'Unauthorized Update']
            )
            ->assertNotFound();

        $this->assertDatabaseHas('family_groups', [
            'id' => $family->id,
            'name' => 'Sample Family',
            'church_id' => $otherChurch->id,
        ]);
    }
}
