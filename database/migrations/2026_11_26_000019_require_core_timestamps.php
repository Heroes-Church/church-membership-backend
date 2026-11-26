<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['churches', 'users', 'family_groups', 'attendance_sessions', 'roles'] as $name) {
            DB::table($name)->whereNull('created_at')->update(['created_at' => now()]);
            if ($name !== 'attendance_sessions') {
                DB::table($name)->whereNull('updated_at')->update(['updated_at' => DB::raw('created_at')]);
            }
            Schema::table($name, function (Blueprint $table) use ($name): void {
                $table->timestamp('created_at')->change();
                if ($name !== 'attendance_sessions') {
                    $table->timestamp('updated_at')->change();
                }
            });
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Restore the pre-upgrade database backup to reverse the member identity migration.');
    }
};
