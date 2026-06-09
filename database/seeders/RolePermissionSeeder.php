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
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Network
            'network.view', 'network.create', 'network.edit', 'network.delete',
            'path-tracing.view', 'path-tracing.execute',
            'gis-map.view', 'gis-map.edit',
            'cable.manage', 'core.manage', 'tube.manage',
            'customer.view', 'customer.manage',
            'fault.view', 'fault.manage',
            'maintenance.view', 'maintenance.manage',
            'audit.view',
            'report.view', 'report.export',
            // CMS
            'cms.settings.manage', 'cms.menus.manage', 'cms.modules.manage', 'cms.pages.manage',
            // Admin
            'users.manage', 'roles.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::query()->firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $rolePermissions = [
            'super-admin' => $permissions,
            'noc' => [
                'network.view', 'path-tracing.view', 'path-tracing.execute',
                'gis-map.view', 'cable.manage', 'core.manage', 'tube.manage',
                'customer.view', 'fault.view', 'fault.manage', 'maintenance.view', 'report.view', 'report.export', 'audit.view',
            ],
            'teknisi' => [
                'network.view', 'network.create', 'network.edit',
                'path-tracing.view', 'path-tracing.execute',
                'gis-map.view', 'cable.manage', 'core.manage',
                'customer.view', 'maintenance.view', 'maintenance.manage',
            ],
            'customer-service' => [
                'network.view', 'path-tracing.view', 'customer.view', 'fault.view', 'report.view',
            ],
            'manager' => [
                'network.view', 'path-tracing.view', 'gis-map.view',
                'customer.view', 'fault.view', 'maintenance.view', 'report.view', 'report.export', 'audit.view',
            ],
        ];

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::query()->firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
        }
    }
}
