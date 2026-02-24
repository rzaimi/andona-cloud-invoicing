<?php

namespace Tests\Feature\Settings;

use App\Modules\Company\Models\Company;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanySettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seedRolesAndPermissions();
    }

    public function test_company_settings_page_is_displayed()
    {
        $company = Company::create([
            'name' => 'Test Company',
            'email' => 'test@company.com',
            'status' => 'active',
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);
        $user->assignRole('user');
        $user->assignRole('user');

        $response = $this
            ->actingAs($user)
            ->get('/settings');

        $response->assertOk();
    }

    public function test_company_settings_page_requires_authentication()
    {
        $response = $this->get('/settings');

        $response->assertRedirect('/login');
    }

    public function test_company_settings_requires_company_association()
    {
        $user = User::factory()->create([
            'company_id' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/settings');

        $response->assertStatus(404);
    }

    public function test_company_settings_can_be_updated()
    {
        $company = Company::create([
            'name' => 'Test Company',
            'email' => 'test@company.com',
            'status' => 'active',
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'admin',
        ]);

        $response = $this
            ->actingAs($user)
            ->put('/settings', [
                'currency' => 'EUR',
                'tax_rate' => 0.19,
                'reduced_tax_rate' => 0.07,
                'invoice_number_format'  => 'RE-{YYYY}-{####}',
                'offer_number_format'    => 'AN-{YYYY}-{####}',
                'customer_number_format' => 'KU-{YYYY}-{####}',
                'date_format' => 'd.m.Y',
                'payment_terms' => 14,
                'decimal_separator' => '.',
                'thousands_separator' => '.',
                'offer_validity_days' => 30,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings');
    }

    public function test_company_settings_update_validates_required_fields()
    {
        $company = Company::create([
            'name' => 'Test Company',
            'email' => 'test@company.com',
            'status' => 'active',
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'admin',
        ]);

        $response = $this
            ->actingAs($user)
            ->put('/settings', []);

        $response->assertSessionHasErrors([
            'currency',
            'tax_rate',
            'invoice_number_format',
            'offer_number_format',
            'date_format',
            'payment_terms',
            'decimal_separator',
            'thousands_separator',
            'offer_validity_days',
        ]);
    }

    public function test_company_settings_renders_inertia_component()
    {
        $company = Company::create([
            'name' => 'Test Company',
            'email' => 'test@company.com',
            'status' => 'active',
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
        ]);
        $user->assignRole('user');

        $response = $this
            ->actingAs($user)
            ->get('/settings');

        $response->assertInertia(fn ($page) => 
            $page->component('settings/index')
                ->has('company')
                ->has('settings')
        );
    }
}

