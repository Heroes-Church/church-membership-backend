<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('church_id');
            $table->unsignedBigInteger('user_id')->nullable()->unique();
            $table->unsignedBigInteger('member_status_id');
            $table->unsignedBigInteger('family_group_id')->nullable();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 30)->nullable();
            $table->string('contact_number', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('province')->nullable();
            $table->string('city_municipality')->nullable();
            $table->string('barangay')->nullable();
            $table->string('street_address')->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->string('profile_photo')->nullable();
            $table->dateTime('date_joined')->nullable();
            $table->uuid('qr_uuid')->nullable()->unique();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('church_id')->references('id')->on('churches');
            $table->foreign(['church_id', 'user_id'])->references(['church_id', 'id'])->on('users');
            $table->foreign(['church_id', 'member_status_id'])->references(['church_id', 'id'])->on('member_statuses');
            $table->foreign(['church_id', 'family_group_id'])->references(['church_id', 'id'])->on('family_groups');
            $table->unique(['church_id', 'id']);
            $table->unique(['church_id', 'family_group_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};