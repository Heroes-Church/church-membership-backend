<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table): void {
            $table->unsignedBigInteger('service_id')->change();
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Restore the pre-upgrade database backup to reverse the member identity migration.');
    }
};
