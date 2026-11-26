<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_ministries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('church_id');
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('ministry_id');
            $table->date('joined_at')->nullable();
            $table->string('role', 100)->nullable();
            $table->date('left_at')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('church_id')->references('id')->on('churches');
            $table->foreign(['church_id', 'member_id'])->references(['church_id', 'id'])->on('members');
            $table->foreign(['church_id', 'ministry_id'])->references(['church_id', 'id'])->on('ministries');
            $table->unique(['member_id', 'ministry_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_ministries');
    }
};
