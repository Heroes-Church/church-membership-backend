<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('baptisms', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('church_id');
            $table->unsignedBigInteger('member_id');
            $table->string('status', 50);
            $table->date('baptism_date')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('church_id')->references('id')->on('churches');
            $table->foreign(['church_id', 'member_id'])->references(['church_id', 'id'])->on('members');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('baptisms');
    }
};
