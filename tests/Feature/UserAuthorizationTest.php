<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Override;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        Role::findOrCreate('Superadmin', 'web');
    }

    private function makeUser(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;

    }

    public function test_admin_can_update_member_account() : void 
    {
        $admin = $this->makeUser('Admin');
        $member = $this->makeUser('Member');
        
        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/users/{$member->id}",
            [
                'name' => 'Updated Member',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Member');
        
        $this->assertSame(
            'Updated Member',
            $member->fresh()->name
        );
    }

    public function test_admin_cannot_update_another_admin(): void
    {
        $admin = $this->makeUser('Admin');
        $target = $this->makeUser('Admin');

        $originalName = $target->name;

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/users/{$target->id}",
            [   'name' => 'Unauthorized Change'],
            )
            ->assertForbidden();
        
        $this->assertSame(
            $originalName,
            $target->fresh()->name
        );
    }

    public function test_superadmin_can_update_admin_account(): void
    {
        $superadmin = $this->makeUser('Superadmin');
        $admin = $this->makeUser('Admin');

        $this->actingAs($superadmin, 'sanctum')
            ->patchJson("/api/v1/users/{$admin->id}", [
                'name' => 'Updated Administrator',
            ])
            ->assertOk();

        $this->assertSame(
            'Updated Administrator',
            $admin->fresh()->name
        );
    }

    public function test_leader_cannot_update_user_accounts(): void
    {
        $leader = $this->makeUser('Leader');
        $member = $this->makeUser('Member');

        $this->actingAs($leader, 'sanctum')
            ->patchJson("/api/v1/users/{$member->id}", [
                'name' => 'Unauthorized Change',
            ])
            ->assertForbidden();
    }

    public function test_admin_creates_guest_account_by_default(): void
    {
        $admin = $this->makeUser('Admin');

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/users', [
                'name' => 'New Guest',
                'email' => 'newguest@example.com',
                'password' => 'Password123!',
            ])
            ->assertCreated();

        $createdUser = User::query()
            ->findOrFail($response->json('data.id'));

        $this->assertTrue($createdUser->hasRole('Guest'));
        $this->assertFalse($createdUser->hasRole('Admin'));
    }

    public function test_admin_cannot_view_superadmin_account(): void
    {
        $admin = $this->makeUser('Admin');
        $superadmin = $this->makeUser('Superadmin');

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/users/{$superadmin->id}")
            ->assertForbidden();
    }

    public function test_permanent_user_deletion_is_blocked(): void
    {
        $superadmin = $this->makeUser('Superadmin');
        $member = $this->makeUser('Member');

        // Explicitly grant access to reach the controller.
        // This verifies the service's deletion safeguard.
        $superadmin->givePermissionTo('users.delete');

        $this->actingAs($superadmin, 'sanctum')
            ->deleteJson("/api/v1/users/{$member->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('users', [
            'id' => $member->id,
        ]);
    }

}


?>