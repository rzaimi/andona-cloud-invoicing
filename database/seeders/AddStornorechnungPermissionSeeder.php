<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AddStornorechnungPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the permission
        $permission = Permission::firstOrCreate(
            ['name' => 'create_stornorechnung'],
            ['guard_name' => 'web']
        );

        // Assign to admin and super-admin roles by default
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole && !$adminRole->hasPermissionTo('create_stornorechnung')) {
            $adminRole->givePermissionTo('create_stornorechnung');
        }

        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole && !$superAdminRole->hasPermissionTo('create_stornorechnung')) {
            $superAdminRole->givePermissionTo('create_stornorechnung');
        }

        $this->command->info('Permission "create_stornorechnung" created and assigned to admin roles.');
    }
}
