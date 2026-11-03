<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('church_id')
                ->constrained('churches')
                ->cascadeOnDelete();

            $table->foreignId('attendance_session_id')
                ->constrained('attendance_sessions')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('checked_in_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('check_in_method', 20)->default('qr');
            $table->timestamp('checked_in_at');

            $table->timestamps();

            $table->unique(
                ['attendance_session_id', 'user_id'],
                'attendance_session_user_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};