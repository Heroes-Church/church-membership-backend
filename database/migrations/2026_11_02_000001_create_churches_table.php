<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('churches', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 100)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('churches')->insert([
            'name' => 'Heroes Church BGC',
            'slug' => 'heroes-church-bgc',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('churches');
    }
};