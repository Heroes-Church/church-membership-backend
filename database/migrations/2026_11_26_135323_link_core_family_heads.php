<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('family_groups', function (Blueprint $table): void {
            $table->unsignedBigInteger('head_member_id')->nullable();
            $table->foreign(['church_id', 'id', 'head_member_id'], 'family_head_membership_fk')
                ->references(['church_id', 'family_group_id', 'id'])->on('members');
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Restore the pre-upgrade database backup to reverse the member identity migration.');
    }
};
