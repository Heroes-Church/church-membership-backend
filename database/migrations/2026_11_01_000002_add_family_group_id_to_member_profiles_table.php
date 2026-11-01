<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_profiles', function (Blueprint $table): void {
            $table->foreignId('family_group_id')
                ->nullable()
                ->constrained('family_groups')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('member_profiles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('family_group_id');
        });
    }
};
