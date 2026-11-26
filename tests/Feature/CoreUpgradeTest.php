<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CoreUpgradeTest extends TestCase
{
    public function test_upgrade_preserves_distinct_account_member_and_checkin_ids(): void
    {
        config(['database.connections.upgrade' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true,
        ]]);
        $original = DB::getDefaultConnection();
        DB::setDefaultConnection('upgrade');

        try {
            $migrations = glob(database_path('migrations/*.php'));
            $core = [];
            foreach ($migrations as $path) {
                if (str_starts_with(basename($path), '2026_11_26_')) {
                    $core[] = $path;
                } else {
                    (require $path)->up();
                }
            }
            $church = DB::table('churches')->value('id');
            DB::table('users')->insert([
                'id' => 41, 'church_id' => $church, 'name' => 'Test Member',
                'email' => 'member@example.test', 'password' => 'test-fixture',
            ]);
            DB::table('member_profiles')->insert([
                'id' => 7, 'user_id' => 41, 'church_id' => $church,
                'first_name' => 'Test', 'last_name' => 'Member', 'gender' => 'Female',
                'date_of_birth' => '2000-01-01', 'phone_number' => '012345',
            ]);
            DB::table('attendance_sessions')->insert([
                'id' => 3, 'church_id' => $church, 'title' => 'Sunday', 'started_at' => now(),
            ]);
            DB::table('attendance_records')->insert([
                'id' => 18, 'church_id' => $church, 'attendance_session_id' => 3,
                'user_id' => 41, 'checked_in_at' => now(),
            ]);

            foreach ($core as $path) {
                (require $path)->up();
            }

            $this->assertDatabaseHas('members', ['id' => 7, 'user_id' => 41, 'contact_number' => '012345']);
            $this->assertDatabaseHas('attendance_records', ['id' => 18, 'member_id' => 7, 'attendance_session_id' => 3]);
            $this->assertFalse(Schema::hasColumn('attendance_records', 'user_id'));
            $this->assertSame([], DB::select('PRAGMA foreign_key_check'));
            $this->assertCount(1, DB::table('members')->get());
        } finally {
            DB::setDefaultConnection($original);
            DB::purge('upgrade');
        }
    }
}
