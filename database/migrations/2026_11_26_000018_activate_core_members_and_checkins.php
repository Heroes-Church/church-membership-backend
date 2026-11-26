<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('core_checkins')) {
            if (Schema::hasTable('attendance_records')) {
                Schema::rename('attendance_records', 'legacy_checkins');
            }
            Schema::rename('core_checkins', 'attendance_records');
        }
        Schema::dropIfExists('legacy_checkins');
        Schema::dropIfExists('member_profiles');
    }

    public function down(): void
    {
        throw new RuntimeException('Restore the pre-upgrade database backup to reverse the member identity migration.');
    }
};
