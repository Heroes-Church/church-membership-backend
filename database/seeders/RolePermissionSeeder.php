<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect([
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'roles.manage',
            'members.view',
            'members.update',
            'family-groups.view',
            'family-groups.manage',
            'settings.manage',
            'attendance.manage'
        ])->map(
            fn (string $name): Permission => Permission::findOrCreate($name, 'web')
        );

        $admin = Role::findOrCreate('Admin', 'web');
        $leader = Role::findOrCreate('Leader', 'web');
        $member = Role::findOrCreate('Member', 'web');
        $guest = Role::findOrCreate('Guest', 'web');

        $oldStaffRole = Role::query()
            ->where('name', 'Staff')
            ->where('guard_name', 'web')
            ->first();

        if ($oldStaffRole) {
            foreach ($oldStaffRole->users()->get() as $user) {
                $user->assignRole($leader);
            }

            $oldStaffRole->delete();
        }

        $admin->syncPermissions($permissions);
        $leader->syncPermissions([
            'users.view',
            'users.create',
            'users.update',
            'members.view',
            'members.update',
            'family-groups.view',
            'family-groups.manage',
            'attendance.manage',
        ]);
        $member->syncPermissions([]);
        $guest->syncPermissions([]);
    }
}
