<?php

namespace Tests\Feature;

use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Offer\Models\Offer;
use App\Modules\User\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ModuleDependencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_invoices_cannot_be_enabled_without_customers_and_payments(): void
    {
        [$company, $superAdmin] = $this->companyWithSuperAdmin();

        $this->actingAs($superAdmin)
            ->from(route('companies.edit', $company))
            ->put(route('companies.update', $company), $this->updatePayload($company, [
                'enabled_modules' => ['invoices'],
            ]))
            ->assertSessionHasErrors([
                'enabled_modules' => '„Rechnungen“ benötigt „Kunden“ und „Zahlungen“.',
            ]);

        $this->assertNull($company->fresh()->enabled_modules);
    }

    public function test_dunning_cannot_be_enabled_without_invoices_and_payments(): void
    {
        [$company, $superAdmin] = $this->companyWithSuperAdmin();

        $this->actingAs($superAdmin)
            ->put(route('companies.update', $company), $this->updatePayload($company, [
                'enabled_modules' => ['dunning', 'customers'],
            ]))
            ->assertSessionHasErrors('enabled_modules');
    }

    public function test_datev_needs_at_least_one_bookkeeping_module(): void
    {
        [$company, $superAdmin] = $this->companyWithSuperAdmin();

        $this->actingAs($superAdmin)
            ->put(route('companies.update', $company), $this->updatePayload($company, [
                'enabled_modules' => ['datev'],
            ]))
            ->assertSessionHasErrors([
                'enabled_modules' => '„DATEV“ benötigt mindestens eines von „Rechnungen“ oder „Ausgaben“.',
            ]);

        // With expenses alongside, DATEV is fine.
        $this->actingAs($superAdmin)
            ->put(route('companies.update', $company), $this->updatePayload($company, [
                'enabled_modules' => ['datev', 'expenses'],
            ]))
            ->assertSessionDoesntHaveErrors('enabled_modules');
    }

    public function test_consistent_bundle_saves_cleanly(): void
    {
        [$company, $superAdmin] = $this->companyWithSuperAdmin();

        $this->actingAs($superAdmin)
            ->put(route('companies.update', $company), $this->updatePayload($company, [
                'enabled_modules' => ['invoices', 'customers', 'payments'],
            ]))
            ->assertRedirect();

        $this->assertSame(['invoices', 'customers', 'payments'], $company->fresh()->enabled_modules);
    }

    public function test_stored_lists_are_healed_with_hard_requirements(): void
    {
        // Data written before dependency enforcement (or edited directly in
        // the DB) must not split the invoices/customers/payments bundle.
        $company = Company::create([
            'name' => 'Alt GmbH',
            'email' => 'alt@example.com',
            'status' => 'active',
            'enabled_modules' => ['invoices'],
        ]);

        $this->assertEqualsCanonicalizing(
            ['invoices', 'customers', 'payments'],
            $company->enabledModules()
        );

        $admin = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $admin->assignRole('admin');

        $this->actingAs($admin)->get('/customers')->assertOk();
        $this->actingAs($admin)->get('/payments')->assertOk();
    }

    public function test_offer_conversion_is_blocked_without_the_invoices_module(): void
    {
        $company = Company::create([
            'name' => 'Angebote GmbH',
            'email' => 'angebote@example.com',
            'status' => 'active',
            'enabled_modules' => ['offers', 'customers'],
        ]);

        $admin = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $admin->assignRole('admin');

        $customer = Customer::create([
            'company_id' => $company->id,
            'name' => 'Kunde Konvertierung',
            'email' => 'kunde@example.com',
            'status' => 'active',
            'customer_type' => 'business',
        ]);

        $offer = Offer::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'user_id' => $admin->id,
            'number' => 'AN-2026-CONV-1',
            'status' => 'accepted',
            'issue_date' => now(),
            'valid_until' => now()->addDays(30),
            'subtotal' => 100.00,
            'tax_rate' => 0.19,
            'tax_amount' => 19.00,
            'total' => 119.00,
        ]);

        // Converting would create an invoice the company can never see.
        $this->actingAs($admin)
            ->post(route('offers.convert-to-invoice', $offer))
            ->assertForbidden();

        $this->assertNull($offer->fresh()->converted_to_invoice_id);
    }

    public function test_offer_expiry_reminders_skip_companies_without_the_offers_module(): void
    {
        Queue::fake();

        $company = Company::create([
            'name' => 'Nur Ausgaben GmbH',
            'email' => 'nur-ausgaben@example.com',
            'status' => 'active',
            'enabled_modules' => ['expenses'],
        ]);
        $company->setSetting('smtp_host', 'smtp.example.test', 'string');
        $company->setSetting('smtp_username', 'mailer@example.test', 'string');

        $admin = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $admin->assignRole('admin');

        $customer = Customer::create([
            'company_id' => $company->id,
            'name' => 'Kunde Ablauf',
            'email' => 'ablauf@example.com',
            'status' => 'active',
            'customer_type' => 'business',
        ]);

        // Legacy offer that expires tomorrow — would normally get a reminder.
        Offer::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'user_id' => $admin->id,
            'number' => 'AN-2026-EXP-1',
            'status' => 'sent',
            'issue_date' => now()->subDays(10),
            'valid_until' => now()->addDay(),
            'subtotal' => 100.00,
            'tax_rate' => 0.19,
            'tax_amount' => 19.00,
            'total' => 119.00,
        ]);

        $this->artisan('reminders:send', ['--company' => $company->id])
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_wizard_creates_a_company_with_a_restricted_module_package(): void
    {
        [, $superAdmin] = $this->companyWithSuperAdmin();

        $this->actingAs($superAdmin)
            ->post(route('companies.wizard.complete'), [
                'company_info' => ['name' => 'Wizard GmbH', 'email' => 'wizard@example.com'],
                'modules' => ['expenses'],
            ])
            ->assertSessionDoesntHaveErrors();

        $company = Company::where('email', 'wizard@example.com')->firstOrFail();
        $this->assertSame(['expenses'], $company->enabled_modules);
    }

    public function test_wizard_rejects_an_inconsistent_module_package(): void
    {
        [, $superAdmin] = $this->companyWithSuperAdmin();

        $this->actingAs($superAdmin)
            ->post(route('companies.wizard.complete'), [
                'company_info' => ['name' => 'Wizard Kaputt GmbH', 'email' => 'wizard-kaputt@example.com'],
                'modules' => ['invoices'],
            ])
            ->assertSessionHasErrors('modules');

        $this->assertNull(Company::where('email', 'wizard-kaputt@example.com')->first());
    }

    public function test_wizard_without_module_selection_grants_the_full_product(): void
    {
        [, $superAdmin] = $this->companyWithSuperAdmin();

        $this->actingAs($superAdmin)
            ->post(route('companies.wizard.complete'), [
                'company_info' => ['name' => 'Voll GmbH', 'email' => 'voll@example.com'],
            ])
            ->assertSessionDoesntHaveErrors();

        $company = Company::where('email', 'voll@example.com')->firstOrFail();
        // Stored as null = full product, so future modules default to on.
        $this->assertNull($company->enabled_modules);
        $this->assertSame(config('modules.all'), $company->enabledModules());
    }

    /**
     * @return array{0: Company, 1: User}
     */
    private function companyWithSuperAdmin(): array
    {
        $company = Company::create([
            'name' => 'Module GmbH',
            'email' => 'module@example.com',
            'status' => 'active',
        ]);

        $superAdmin = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
        $superAdmin->assignRole('super_admin');

        return [$company, $superAdmin];
    }

    private function updatePayload(Company $company, array $overrides = []): array
    {
        return array_merge([
            'name' => $company->name,
            'email' => $company->email,
            'country' => 'Deutschland',
            'status' => 'active',
        ], $overrides);
    }
}
