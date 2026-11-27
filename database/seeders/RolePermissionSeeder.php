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

        $permissionNames = [
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
            'attendance.manage',

            // New permissions
            'profile.view-own',
            'profile.update-own',
            'platform.manage',
        ];

        foreach ($permissionNames as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $superadmin = Role::findOrCreate('Superadmin', 'web');
        $admin = Role::findOrCreate('Admin', 'web');
        $leader = Role::findOrCreate('Leader', 'web');
        $member = Role::findOrCreate('Member', 'web');
        $guest = Role::findOrCreate('Guest', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        
        // Superadmin: platform and church administration.
        // Destructive deletion remains disabled.
        $superadmin->syncPermissions(
            array_values(array_diff(
                $permissionNames,
                ['users.delete']
            ))
        );

        // Admin: church administration, not platform control.
        $admin->syncPermissions([
            'users.view',
            'users.create',
            'users.update',
            'roles.manage',
            'members.view',
            'members.update',
            'family-groups.view',
            'family-groups.manage',
            'settings.manage',
            'attendance.manage',
            'profile.view-own',
            'profile.update-own',
        ]);

        // Leader: temporarily limited until assigned-group
        // authorization is implemented and tested.
        $leader->syncPermissions([
            'profile.view-own',
            'profile.update-own',
        ]);

        $member->syncPermissions([
            'profile.view-own',
            'profile.update-own',
        ]);

        $guest->syncPermissions([
            'profile.view-own',
            'profile.update-own',
        ]);

        // Migrate legacy Staff role assignments.
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

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
