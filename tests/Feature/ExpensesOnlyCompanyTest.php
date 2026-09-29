<?php

namespace Tests\Feature;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpensesOnlyCompanyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_expenses_only_company_can_use_expenses_and_not_invoicing(): void
    {
        [$company, $admin] = $this->companyWithAdmin(['expenses']);

        $this->actingAs($admin)->get('/expenses')->assertOk();
        $this->actingAs($admin)->get('/expenses/create')->assertOk();
        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('dashboard')
                ->has('expenseDashboard')
                ->missing('stats')
            );

        $this->actingAs($admin)->get('/invoices')->assertForbidden();
        $this->actingAs($admin)->get('/offers')->assertForbidden();
        $this->actingAs($admin)->get('/customers')->assertForbidden();
        $this->actingAs($admin)->get('/payments')->assertForbidden();
    }

    public function test_super_admin_can_grant_the_invoicing_bundle_without_the_other_modules(): void
    {
        // Invoices are inseparable from customers and payments (hard
        // dependencies) — but everything else stays off.
        [$company, $admin] = $this->companyWithAdmin(['invoices', 'customers', 'payments']);

        $this->actingAs($admin)->get('/invoices')->assertOk();
        $this->actingAs($admin)->get('/customers')->assertOk();
        $this->actingAs($admin)->get('/payments')->assertOk();
        $this->actingAs($admin)->get('/offers')->assertForbidden();
        $this->actingAs($admin)->get('/dunning')->assertForbidden();
        $this->actingAs($admin)->get('/expenses')->assertForbidden();
        $this->actingAs($admin)->get('/calendar')->assertForbidden();
        $this->actingAs($admin)->get('/datev')->assertForbidden();
    }

    public function test_company_without_module_list_keeps_full_access(): void
    {
        [$company, $admin] = $this->companyWithAdmin(null);

        $this->actingAs($admin)->get('/invoices')->assertOk();
        $this->actingAs($admin)->get('/expenses')->assertOk();
    }

    public function test_accountant_can_store_an_expense_and_a_plain_user_cannot(): void
    {
        $company = $this->makeCompany(['expenses']);

        $accountant = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'user',
        ]);
        $accountant->assignRole('accountant');

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'user',
        ]);
        $user->assignRole('user');

        $payload = [
            'title' => 'Taxi',
            'amount' => 12.50,
            'expense_date' => '2026-09-01',
        ];

        $this->actingAs($accountant)
            ->post(route('expenses.store'), $payload)
            ->assertRedirect(route('expenses.index'));

        $this->assertDatabaseHas('expenses', [
            'company_id' => $company->id,
            'title' => 'Taxi',
        ]);

        $this->actingAs($user)
            ->post(route('expenses.store'), [
                'title' => 'Kaffee',
                'amount' => 3.50,
                'expense_date' => '2026-09-02',
            ])
            ->assertForbidden();
    }

    public function test_expenses_only_company_is_redirected_off_reminder_settings(): void
    {
        [$company, $admin] = $this->companyWithAdmin(['expenses']);

        // A stale bookmark to a disabled tab lands on the default tab, not a 403.
        $this->actingAs($admin)
            ->get(route('settings.index', ['tab' => 'reminders']))
            ->assertRedirect(route('settings.index'));
    }

    public function test_module_gate_covers_import_and_export_routes(): void
    {
        [$company, $admin] = $this->companyWithAdmin(['expenses']);

        $this->actingAs($admin)->get(route('export.customers'))->assertForbidden();
        $this->actingAs($admin)->get(route('export.invoices'))->assertForbidden();
    }

    public function test_super_admin_can_limit_modules_without_wiping_other_settings(): void
    {
        $company = $this->makeCompany(null);
        $company->update(['settings' => ['custom_key' => 'keep']]);

        $superAdmin = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'admin',
        ]);
        $superAdmin->assignRole('super_admin');

        $this->actingAs($superAdmin)
            ->put(route('companies.update', $company), [
                'name' => $company->name,
                'email' => $company->email,
                'country' => 'Deutschland',
                'status' => 'active',
                'enabled_modules' => ['expenses'],
            ])
            ->assertRedirect();

        $fresh = $company->fresh();
        $this->assertSame(['expenses'], $fresh->enabled_modules);
        $this->assertSame(['custom_key' => 'keep'], $fresh->settings);
        $this->assertSame(['expenses'], $fresh->enabledModules());
    }

    public function test_unchecking_every_module_fails_validation_with_a_message(): void
    {
        $company = $this->makeCompany(['expenses']);

        $superAdmin = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'admin',
        ]);
        $superAdmin->assignRole('super_admin');

        // The edit form sends [""] when nothing is checked (FormData cannot
        // carry an empty array) — this must surface the min:1 error.
        $this->actingAs($superAdmin)
            ->from(route('companies.edit', $company))
            ->put(route('companies.update', $company), [
                'name' => $company->name,
                'email' => $company->email,
                'country' => 'Deutschland',
                'status' => 'active',
                'enabled_modules' => [''],
            ])
            ->assertSessionHasErrors(['enabled_modules' => 'Mindestens ein Modul muss aktiv sein.']);

        $this->assertSame(['expenses'], $company->fresh()->enabled_modules);
    }

    public function test_unknown_module_keys_fail_closed(): void
    {
        // A restricted list whose keys no longer exist must NOT unlock the
        // full product.
        [$company, $admin] = $this->companyWithAdmin(['no_longer_a_module']);

        $this->assertSame([], $company->enabledModules());
        $this->actingAs($admin)->get('/invoices')->assertForbidden();
        $this->actingAs($admin)->get('/expenses')->assertForbidden();
    }

    /**
     * @param  list<string>|null  $modules
     * @return array{0: Company, 1: User}
     */
    private function companyWithAdmin(?array $modules): array
    {
        $company = $this->makeCompany($modules);
        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'admin',
        ]);
        $admin->assignRole('admin');

        return [$company, $admin];
    }

    /**
     * @param  list<string>|null  $modules
     */
    private function makeCompany(?array $modules): Company
    {
        return Company::create([
            'name' => 'Ausgaben GmbH',
            'email' => 'ausgaben@example.com',
            'status' => 'active',
            'enabled_modules' => $modules,
        ]);
    }
}
