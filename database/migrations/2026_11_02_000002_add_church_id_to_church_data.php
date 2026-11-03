<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $churchId = DB::table('churches')
            ->where('slug', 'heroes-church-bgc')
            ->value('id');

        if (! $churchId) {
            throw new \RuntimeException(
                'Heroes Church BGC must exist before assigning church-owned records.'
            );
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('church_id')
                ->nullable()
                ->constrained('churches')
                ->cascadeOnDelete();
        });

        Schema::table('member_profiles', function (Blueprint $table): void {
            $table->foreignId('church_id')
                ->nullable()
                ->constrained('churches')
                ->cascadeOnDelete();
        });

        Schema::table('family_groups', function (Blueprint $table): void {
            $table->foreignId('church_id')
                ->nullable()
                ->constrained('churches')
                ->cascadeOnDelete();
        });

        DB::table('users')
            ->whereNull('church_id')
            ->update(['church_id' => $churchId]);

        DB::table('member_profiles')
            ->whereNull('church_id')
            ->update(['church_id' => $churchId]);

        DB::table('family_groups')
            ->whereNull('church_id')
            ->update(['church_id' => $churchId]);

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_email_unique');
            $table->unique(
                ['church_id', 'email'],
                'users_church_email_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_church_email_unique');
            $table->unique('email', 'users_email_unique');
            $table->dropConstrainedForeignId('church_id');
        });

        Schema::table('member_profiles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('church_id');
        });

        Schema::table('family_groups', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('church_id');
        });
    }
};