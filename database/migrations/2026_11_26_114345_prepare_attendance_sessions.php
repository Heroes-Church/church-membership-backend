<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table): void {
            $table->unique(['church_id', 'id']);
            $table->foreign(['church_id', 'started_by'])->references(['church_id', 'id'])->on('users');
            $table->foreign(['church_id', 'ended_by'])->references(['church_id', 'id'])->on('users');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Restore the pre-upgrade database backup to reverse the member identity migration.');
    }
};
