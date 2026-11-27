<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $now = now();
            foreach (DB::table('churches')->orderBy('id')->cursor() as $church) {
                $status = DB::table('member_statuses')->insertGetId([
                    'church_id' => $church->id, 'code' => 'unclassified', 'name' => 'Unclassified',
                    'created_at' => $now, 'updated_at' => $now,
                ]);
                $service = DB::table('services')->insertGetId([
                    'church_id' => $church->id, 'name' => 'General service',
                    'created_at' => $now, 'updated_at' => $now,
                ]);
                DB::table('attendance_sessions')->where('church_id', $church->id)->update(['service_id' => $service]);
                DB::table('member_profiles')->where('church_id', $church->id)->chunkById(250, function ($rows) use ($status, $now) {
                    $members = [];
                    foreach ($rows as $profile) {
                        $row = (array) $profile;
                        $row['contact_number'] = $row['phone_number'];
                        unset($row['phone_number']);
                        $row['member_status_id'] = $status;
                        $row['created_at'] ??= $now;
                        $row['updated_at'] ??= $row['created_at'];
                        $members[] = $row;
                    }
                    DB::table('members')->insert($members);
                });
            }
            $source = DB::table('attendance_records as r')->join('members as m', function ($join) {
                $join->on('m.user_id', '=', 'r.user_id')->on('m.church_id', '=', 'r.church_id');
            })->select('r.id', 'r.church_id', 'r.attendance_session_id', 'm.id as member_id',
                'r.checked_in_by', 'r.check_in_method', 'r.checked_in_at')
                ->selectRaw('COALESCE(r.created_at, r.checked_in_at), COALESCE(r.updated_at, r.created_at, r.checked_in_at)');
            DB::table('core_checkins')->insertUsing([
                'id', 'church_id', 'attendance_session_id', 'member_id', 'checked_in_by',
                'check_in_method', 'checked_in_at', 'created_at', 'updated_at',
            ], $source);
            if (DB::table('core_checkins')->count() !== DB::table('attendance_records')->count()
                || DB::table('members')->count() !== DB::table('member_profiles')->count()) {
                throw new RuntimeException('Member or attendance counts differ. The copy was rolled back.');
            }
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Restore the pre-upgrade database backup to reverse the member identity migration.');
    }
};
