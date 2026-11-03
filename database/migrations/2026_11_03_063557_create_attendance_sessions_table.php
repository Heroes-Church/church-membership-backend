<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sessions', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('church_id')
                ->constrained('churches')
                ->cascadeOnDelete();

            $table->string('title', 150);
            $table->string('status', 20)->default('open');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();

            $table->foreignId('started_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('ended_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['church_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_sessions');
    }
};