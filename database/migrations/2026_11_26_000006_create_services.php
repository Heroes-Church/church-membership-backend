<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('church_id');
            $table->string('name', 150);
            $table->string('description', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->foreign('church_id')->references('id')->on('churches');
            $table->unique(['church_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
