<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $guard = 'web';

        $permissions = [
            'manage_users',
            'manage_companies',
            'manage_settings',
            'manage_invoices',
            'manage_offers',
            'manage_products',
            'view_reports',
            'create_stornorechnung', // Permission to create correction invoices (GoBD compliance)
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => $guard]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => $guard]);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => $guard]);
        $user = Role::firstOrCreate(['name' => 'user', 'guard_name' => $guard]);

        $superAdmin->syncPermissions(Permission::all());

        $adminPermissions = [
            'manage_users',
            'manage_settings',
            'manage_invoices',
            'manage_offers',
            'manage_products',
            'view_reports',
            'create_stornorechnung', // Admins can create correction invoices
        ];
        $admin->syncPermissions(Permission::whereIn('name', $adminPermissions)->get());

        $userPermissions = [
            'manage_invoices',
            'manage_offers',
            'manage_products',
            'view_reports',
        ];
        $user->syncPermissions(Permission::whereIn('name', $userPermissions)->get());
    }
}


