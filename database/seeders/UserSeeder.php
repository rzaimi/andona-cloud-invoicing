<?php

namespace Database\Seeders;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure roles exist
        $superRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // Create a global super admin on the first company
        $firstCompany = Company::query()->first();
        if ($firstCompany) {
            $super = User::firstOrCreate(
                ['email' => 'superadmin@example.com'],
                [
                    'name' => 'Super Admin',
                    'password' => Hash::make('password'),
                    'company_id' => $firstCompany->id,
                    // DB role column only supports 'admin'|'user', use 'admin' and grant Spatie role 'super_admin'
                    'role' => 'admin',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );
            $super->assignRole($superRole);
        }

        $companies = Company::all();

        foreach ($companies as $company) {
            // Create admin user for each company
            $adminUser = User::firstOrCreate([
                'email' => 'admin@' . strtolower(str_replace(' ', '', $company->name)) . '.com',
            ], [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'company_id' => $company->id,
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
            $adminUser->assignRole($adminRole);

            // Create regular users
            $users = [
                [
                    'name' => 'John Smith',
                    'email' => 'john@' . strtolower(str_replace(' ', '', $company->name)) . '.com',
                    'role' => 'user',
                ],
                [
                    'name' => 'Sarah Johnson',
                    'email' => 'sarah@' . strtolower(str_replace(' ', '', $company->name)) . '.com',
                    'role' => 'user',
                ],
            ];

            foreach ($users as $userData) {
                $u = User::firstOrCreate([
                    'email' => $userData['email'],
                ], [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'company_id' => $company->id,
                    'role' => $userData['role'],
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]);
                $u->assignRole($userRole);
            }
        }
    }
}
