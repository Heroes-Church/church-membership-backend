<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\CurrentChurch;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $churchId = app(CurrentChurch::class)->id();
        User::firstOrCreate([
            'church_id' => $churchId,
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'email_verified_at' => now(),
            'password' => 'password',
        ]);

        if (! app()->environment('local')) {
            return;
        }

        DB::transaction(function () use ($churchId): void {
            $admin = User::firstOrCreate([
                'church_id' => $churchId,
                'email' => 'admin@heroescims.test',
            ], [
                'name' => 'HeroesCIMS Local Admin',
                'email_verified_at' => now(),
                'password' => 'localheroescims',
            ]);

            // Only grant access to an account created by this seeder.
            if ($admin->wasRecentlyCreated) {
                $admin->assignRole('Admin');
            } elseif (! $admin->hasRole('Admin')) {
                throw new \RuntimeException('The local admin email is already used by a non-admin account.');
            }
        });
    }
}
