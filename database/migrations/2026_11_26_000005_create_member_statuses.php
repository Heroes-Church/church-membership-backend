<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_statuses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('church_id');
            $table->string('name', 100);
            $table->string('code', 50);
            $table->string('description')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('church_id')->references('id')->on('churches');
            $table->unique(['church_id', 'id']);
            $table->unique(['church_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_statuses');
    }
};
