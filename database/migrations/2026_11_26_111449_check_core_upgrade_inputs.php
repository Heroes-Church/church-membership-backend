<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['users', 'member_profiles', 'family_groups'] as $table) {
            if (DB::table($table)->whereNull('church_id')->exists()) {
                throw new RuntimeException("Assign a church to every row in {$table} before migrating.");
            }
        }
        $profiles = DB::table('member_profiles as p')->join('users as u', 'u.id', '=', 'p.user_id')
            ->leftJoin('family_groups as f', 'f.id', '=', 'p.family_group_id');
        if ($profiles->where(function ($query) {
            $query->whereColumn('u.church_id', '<>', 'p.church_id')
                ->orWhereColumn('f.church_id', '<>', 'p.church_id');
        })->exists()) {
            throw new RuntimeException('A member profile links to a user or family in another church.');
        }
        foreach (['first_name', 'middle_name', 'last_name'] as $field) {
            $length = DB::getDriverName() === 'sqlite' ? 'length' : 'char_length';
            if (DB::table('member_profiles')->whereRaw("{$length}({$field}) > 100")->exists()) {
                throw new RuntimeException("A member profile has a {$field} longer than 100 characters.");
            }
        }
        foreach ([
            ['attendance_sessions', 'started_by', 'users'],
            ['attendance_sessions', 'ended_by', 'users'],
            ['attendance_records', 'checked_in_by', 'users'],
            ['attendance_records', 'attendance_session_id', 'attendance_sessions'],
        ] as [$table, $column, $parent]) {
            if (DB::table("{$table} as child")->join("{$parent} as parent", 'parent.id', '=', "child.{$column}")
                ->whereColumn('child.church_id', '<>', 'parent.church_id')->exists()) {
                throw new RuntimeException("A {$table}.{$column} relationship crosses churches.");
            }
        }
        $missing = DB::table('attendance_records as r')
            ->leftJoin('member_profiles as p', function ($join) {
                $join->on('p.user_id', '=', 'r.user_id')->on('p.church_id', '=', 'r.church_id');
            })->whereNull('p.id')->value('r.id');
        if ($missing !== null) {
            throw new RuntimeException("Check-in {$missing} has no matching member profile in its church.");
        }
    }

    public function down(): void
    {
        // Read-only preflight.
    }
};
