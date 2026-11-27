<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('church_id');
            $table->unsignedBigInteger('created_by');
            $table->string('title', 200);
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->dateTime('published_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('church_id')->references('id')->on('churches');
            $table->foreign(['church_id', 'created_by'])->references(['church_id', 'id'])->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
