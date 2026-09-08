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
            'create_stornorechnung',       // Permission to create correction invoices (GoBD compliance)
            'manage_employee_documents',   // Upload / manage documents for employees
            'view_own_documents',          // Employee self-service: view own documents
            'approve_expenses',            // Expense inbox: approve/reject expenses
            'export_expenses',             // Expense inbox: export approved expenses
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => $guard]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => $guard]);
        $admin = Role::firstOrCreate(['name' => 'admin',       'guard_name' => $guard]);
        $user = Role::firstOrCreate(['name' => 'user',        'guard_name' => $guard]);
        $employee = Role::firstOrCreate(['name' => 'employee',    'guard_name' => $guard]);
        $accountant = Role::firstOrCreate(['name' => 'accountant',  'guard_name' => $guard]);
        $approver = Role::firstOrCreate(['name' => 'approver',    'guard_name' => $guard]);
        $auditor = Role::firstOrCreate(['name' => 'auditor',     'guard_name' => $guard]);

        $superAdmin->syncPermissions(Permission::all());

        $adminPermissions = [
            'manage_users',
            'manage_settings',
            'manage_invoices',
            'manage_offers',
            'manage_products',
            'view_reports',
            'create_stornorechnung',
            'manage_employee_documents',
            'approve_expenses',
            'export_expenses',
        ];
        $admin->syncPermissions(Permission::whereIn('name', $adminPermissions)->get());

        $accountant->syncPermissions(Permission::whereIn('name', ['view_reports', 'export_expenses'])->get());
        $approver->syncPermissions(Permission::whereIn('name', ['view_reports', 'approve_expenses'])->get());
        $auditor->syncPermissions(Permission::whereIn('name', ['view_reports'])->get());

        $userPermissions = [
            'manage_invoices',
            'manage_offers',
            'manage_products',
            'view_reports',
        ];
        $user->syncPermissions(Permission::whereIn('name', $userPermissions)->get());

        $employeePermissions = [
            'view_own_documents',
        ];
        $employee->syncPermissions(Permission::whereIn('name', $employeePermissions)->get());
    }
}
