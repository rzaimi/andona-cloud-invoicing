<?php

namespace App\Console\Commands;

use App\Modules\Company\Models\Company;
use App\Modules\Invoice\Models\InvoiceLayout;
use App\Modules\Offer\Models\OfferLayout;
use App\Modules\User\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SetupProduction extends Command
{
    protected $signature   = 'app:setup-production';
    protected $description = 'Create the production company (Andona GmbH) and super admin user. Safe to run multiple times.';

    public function handle(): int
    {
        $this->info('');
        $this->info('=== Andona Production Setup ===');
        $this->info('');

        // ── 1. Roles & Permissions ────────────────────────────────────────────
        $this->line('→ Syncing roles & permissions...');
        $this->setupRolesAndPermissions();
        $this->info('  ✓ Roles and permissions ready.');

        // ── 2. Company ────────────────────────────────────────────────────────
        $this->line('→ Setting up company...');
        $company = $this->setupCompany();
        $this->info("  ✓ Company: {$company->name} (ID: {$company->id})");

        // ── 3. Super Admin ────────────────────────────────────────────────────
        $this->line('→ Setting up super admin...');
        $email = 'admin@andona.de';

        $existing = User::where('email', $email)->first();
        if ($existing) {
            $this->warn("  ! User {$email} already exists — skipping creation.");
            $this->ensureSuperAdminRole($existing);
            $this->info("  ✓ Super admin role verified for {$email}.");
        } else {
            $password = $this->askForPassword();
            $user = User::create([
                'name'              => 'Super Admin',
                'email'             => $email,
                'password'          => Hash::make($password),
                'company_id'        => $company->id,
                'role'              => 'admin',
                'status'            => 'active',
                'email_verified_at' => now(),
            ]);
            $this->ensureSuperAdminRole($user);
            $this->info("  ✓ Super admin created: {$email}");
            $this->warn("  ! Password set — store it securely and change it after first login.");
        }

        $this->info('');
        $this->info('Setup complete.');
        $this->info('');

        return self::SUCCESS;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function setupRolesAndPermissions(): void
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
            'create_stornorechnung',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => $guard]);
        }

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => $guard]);
        $admin      = Role::firstOrCreate(['name' => 'admin',       'guard_name' => $guard]);
        $user       = Role::firstOrCreate(['name' => 'user',        'guard_name' => $guard]);

        $superAdmin->syncPermissions(Permission::all());

        $admin->syncPermissions(Permission::whereIn('name', [
            'manage_users', 'manage_settings', 'manage_invoices',
            'manage_offers', 'manage_products', 'view_reports', 'create_stornorechnung',
        ])->get());

        $user->syncPermissions(Permission::whereIn('name', [
            'manage_invoices', 'manage_offers', 'manage_products', 'view_reports',
        ])->get());

        // Clear cached permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    private function setupCompany(): Company
    {
        $company = Company::firstOrCreate(
            ['email' => 'info@andona.de'],
            [
                'name'               => 'Andona GmbH',
                'phone'              => '+49 (0) 6051 – 53 83 658',
                'address'            => 'Bahnhofstraße 16',
                'postal_code'        => '63571',
                'city'               => 'Gelnhausen',
                'country'            => 'Deutschland',
                'tax_number'         => '019 228 35202',
                'vat_number'         => 'DE369264419',
                'commercial_register'=> 'HRB 100017',
                'managing_director'  => 'Lirim Ziberi',
                'website'            => 'https://andona.de',
                'status'             => 'active',
                'is_default'         => true,
            ]
        );

        // Set bank details (safe to call multiple times)
        $company->setBankSettings([
            'bank_name' => 'Raiffeisenbank eG, Rodenbach',
            'bank_iban' => 'DE88506636990001121200',
            'bank_bic'  => 'GENODEF1RDB',
        ]);

        // Create default invoice layouts if none exist for this company
        if (!InvoiceLayout::where('company_id', $company->id)->exists()) {
            foreach ([
                ['name' => 'Standard Rechnung',   'template' => 'modern',       'is_default' => true],
                ['name' => 'Klassische Rechnung',  'template' => 'classic',      'is_default' => false],
                ['name' => 'Minimale Rechnung',    'template' => 'minimal',      'is_default' => false],
                ['name' => 'Professionell',        'template' => 'professional', 'is_default' => false],
                ['name' => 'Elegant',              'template' => 'elegant',      'is_default' => false],
            ] as $layout) {
                InvoiceLayout::create(['company_id' => $company->id, ...$layout]);
            }
            $this->line('  ✓ Default invoice layouts created.');
        }

        // Create default offer layouts if none exist for this company
        if (!OfferLayout::where('company_id', $company->id)->exists()) {
            foreach ([
                ['name' => 'Standard Angebot',       'template' => 'modern',       'is_default' => true],
                ['name' => 'Klassisches Angebot',    'template' => 'classic',      'is_default' => false],
                ['name' => 'Minimales Angebot',      'template' => 'minimal',      'is_default' => false],
                ['name' => 'Professionelles Angebot','template' => 'professional', 'is_default' => false],
                ['name' => 'Elegantes Angebot',      'template' => 'elegant',      'is_default' => false],
            ] as $layout) {
                OfferLayout::create(['company_id' => $company->id, ...$layout]);
            }
            $this->line('  ✓ Default offer layouts created.');
        }

        return $company;
    }

    private function askForPassword(): string
    {
        while (true) {
            $password = $this->secret('Enter password for admin@andona.de (min 12 chars)');

            if (strlen($password) < 12) {
                $this->error('  Password must be at least 12 characters.');
                continue;
            }

            $confirm = $this->secret('Confirm password');

            if ($password !== $confirm) {
                $this->error('  Passwords do not match. Try again.');
                continue;
            }

            return $password;
        }
    }

    private function ensureSuperAdminRole(User $user): void
    {
        $superRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        if (!$user->hasRole('super_admin')) {
            $user->assignRole($superRole);
        }
    }
}
