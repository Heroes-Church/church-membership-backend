<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('churches', function (Blueprint $table): void {
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 32)->nullable();
            $table->text('address')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('primary_color', 7)->nullable();
            $table->string('secondary_color', 7)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('churches', function (Blueprint $table): void {
            $table->dropColumn([
                'contact_email',
                'contact_phone',
                'address',
                'logo_path',
                'primary_color',
                'secondary_color',
            ]);
        });
    }
};
