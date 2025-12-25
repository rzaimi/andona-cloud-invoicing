<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * RefreshDatabase uses database transactions by default in SQLite
     * This ensures all changes are automatically rolled back after each test
     */

    protected function setUp(): void
    {
        parent::setUp();
        
        // Set the correct user model for authentication
        $this->app['config']->set('auth.providers.users.model', \App\Modules\User\Models\User::class);
    }

    /**
     * Seed roles and permissions for tests
     * This is a helper method that can be called in test setUp() methods
     */
    protected function seedRolesAndPermissions(): void
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
