<?php

namespace Tests\Feature;

use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Church;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceApiTest extends TestCase
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

    private function makeMember(): User
    {
        $member = $this->makeUser('Member');

        $member->memberProfile()->create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'date_of_birth' => '2000-05-10',
            'phone_number' => '+639171234567',
            'gender' => 'Prefer not to say',
        ]);

        return $member;
    }

    private function makeSession(User $admin): AttendanceSession
    {
        return AttendanceSession::query()->create([
            'title' => 'Sunday Worship',
            'status' => 'open',
            'started_at' => now(),
            'started_by' => $admin->id,
        ]);
    }

    public function test_admin_can_create_attendance_session(): void
    {
        $admin = $this->makeUser('Admin');

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/attendance/sessions', [
                'title' => 'Sunday Worship',
            ])
            ->assertCreated()
            ->assertJsonPath('session.status', 'open')
            ->assertJsonPath('session.title', 'Sunday Worship');

        $this->assertDatabaseHas('attendance_sessions', [
            'id' => $response->json('session.id'),
            'title' => 'Sunday Worship',
            'status' => 'open',
            'started_by' => $admin->id,
            'church_id' => $admin->church_id,
        ]);
    }

    public function test_admin_can_check_in_approved_member(): void
    {
        $admin = $this->makeUser('Admin');
        $member = $this->makeMember();
        $session = $this->makeSession($admin);

        $this->actingAs($admin, 'sanctum')
            ->postJson(
                "/api/v1/attendance/sessions/{$session->id}/check-ins",
                ['user_id' => $member->id]
            )
            ->assertCreated()
            ->assertJsonPath('record.user_id', $member->id)
            ->assertJsonPath('record.check_in_method', 'manual');

        $this->assertDatabaseHas('attendance_records', [
            'attendance_session_id' => $session->id,
            'user_id' => $member->id,
            'church_id' => $admin->church_id,
            'checked_in_by' => $admin->id,
            'check_in_method' => 'manual',
        ]);
    }

    public function test_duplicate_check_in_is_rejected(): void
    {
        $admin = $this->makeUser('Admin');
        $member = $this->makeMember();
        $session = $this->makeSession($admin);

        $url = "/api/v1/attendance/sessions/{$session->id}/check-ins";

        $this->actingAs($admin, 'sanctum')
            ->postJson($url, [
                'user_id' => $member->id,
            ])
            ->assertCreated();

        $this->postJson($url, [
            'user_id' => $member->id,
        ])->assertStatus(409);

        $this->assertSame(
            1,
            AttendanceRecord::query()
                ->where('attendance_session_id', $session->id)
                ->where('user_id', $member->id)
                ->count()
        );
    }

    public function test_admin_can_close_session(): void
    {
        $admin = $this->makeUser('Admin');
        $session = $this->makeSession($admin);

        $this->actingAs($admin, 'sanctum')
            ->patchJson(
                "/api/v1/attendance/sessions/{$session->id}/close"
            )
            ->assertOk()
            ->assertJsonPath('session.status', 'closed');

        $session->refresh();

        $this->assertNotNull($session->ended_at);

        $this->assertSame(
            $admin->id,
            $session->ended_by
        );
    }

    public function test_closed_session_rejects_check_in(): void
    {
        $admin = $this->makeUser('Admin');
        $member = $this->makeMember();
        $session = $this->makeSession($admin);

        $this->actingAs($admin, 'sanctum')
            ->patchJson(
                "/api/v1/attendance/sessions/{$session->id}/close"
            )
            ->assertOk();

        $this->postJson(
            "/api/v1/attendance/sessions/{$session->id}/check-ins",
            ['user_id' => $member->id]
        )->assertStatus(409);

        $this->assertDatabaseMissing('attendance_records', [
            'attendance_session_id' => $session->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_guest_cannot_create_attendance_session(): void
    {
        $guest = $this->makeUser('Guest');

        $this->actingAs($guest, 'sanctum')
            ->postJson('/api/v1/attendance/sessions', [
                'title' => 'Unauthorized Session',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('attendance_sessions', [
            'title' => 'Unauthorized Session',
        ]);
    }

    public function test_guest_cannot_check_in_members(): void
    {
        $admin = $this->makeUser('Admin');
        $guest = $this->makeUser('Guest');
        $member = $this->makeMember();
        $session = $this->makeSession($admin);

        $this->actingAs($guest, 'sanctum')
            ->postJson(
                "/api/v1/attendance/sessions/{$session->id}/check-ins",
                ['user_id' => $member->id]
            )
            ->assertForbidden();

        $this->assertDatabaseMissing('attendance_records', [
            'attendance_session_id' => $session->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_unverified_member_cannot_check_in(): void
    {
        $admin = $this->makeUser('Admin');
        $member = $this->makeMember();
        $session = $this->makeSession($admin);

        $member->forceFill([
            'email_verified_at' => null,
        ])->save();

        $this->actingAs($admin, 'sanctum')
            ->postJson(
                "/api/v1/attendance/sessions/{$session->id}/check-ins",
                ['user_id' => $member->id]
            )
            ->assertUnprocessable();

        $this->assertDatabaseMissing('attendance_records', [
            'attendance_session_id' => $session->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_member_from_another_church_is_rejected(): void
    {
        $admin = $this->makeUser('Admin');
        $member = $this->makeMember();
        $session = $this->makeSession($admin);

        $otherChurch = Church::query()->create([
            'name' => 'Other Church',
            'slug' => 'other-church',
            'is_active' => true,
        ]);

        $member->forceFill([
            'church_id' => $otherChurch->id,
        ])->save();

        $this->actingAs($admin, 'sanctum')
            ->postJson(
                "/api/v1/attendance/sessions/{$session->id}/check-ins",
                ['user_id' => $member->id]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('user_id');

        $this->assertDatabaseMissing('attendance_records', [
            'attendance_session_id' => $session->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_attendance_records_and_count_are_returned(): void
    {
        $admin = $this->makeUser('Admin');
        $member = $this->makeMember();
        $session = $this->makeSession($admin);

        $this->actingAs($admin, 'sanctum')
            ->postJson(
                "/api/v1/attendance/sessions/{$session->id}/check-ins",
                ['user_id' => $member->id]
            )
            ->assertCreated();

        $this->getJson(
            "/api/v1/attendance/sessions/{$session->id}/records"
        )
            ->assertOk()
            ->assertJsonPath('total_checked_in', 1)
            ->assertJsonPath('records.total', 1)
            ->assertJsonPath(
                'records.data.0.user.id',
                $member->id
            );
    }
}