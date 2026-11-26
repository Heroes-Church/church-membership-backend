<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('core_checkins', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('church_id');
            $table->unsignedBigInteger('attendance_session_id');
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('checked_in_by')->nullable();
            $table->string('check_in_method', 20)->default('qr');
            $table->timestamp('checked_in_at');
            $table->uuid('client_event_uuid')->nullable()->unique();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('church_id')->references('id')->on('churches');
            $table->foreign(['church_id', 'attendance_session_id'])->references(['church_id', 'id'])->on('attendance_sessions');
            $table->foreign(['church_id', 'member_id'])->references(['church_id', 'id'])->on('members');
            $table->foreign(['church_id', 'checked_in_by'])->references(['church_id', 'id'])->on('users');
            $table->unique(['attendance_session_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('core_checkins');
    }
};
