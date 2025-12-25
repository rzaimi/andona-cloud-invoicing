<?php

namespace Tests\Feature\Modules;

use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Offer\Models\Offer;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsModuleTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seedRolesAndPermissions();
        
        $this->company = Company::create([
            'name' => 'Test Company',
            'email' => 'test@company.com',
            'status' => 'active',
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'user',
        ]);
        $this->user->assignRole('user');
    }

    public function test_authenticated_user_can_access_reports()
    {
        $this->actingAs($this->user)
            ->get('/reports')
            ->assertOk();
    }

    public function test_guest_cannot_access_reports()
    {
        $this->get('/reports')
            ->assertRedirect('/login');
    }

    public function test_reports_index_shows_overview_statistics()
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'status' => 'active',
            'customer_type' => 'business',
        ]);

        Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'number' => 'RE-2024-0001',
            'status' => 'paid',
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'subtotal' => 100.00,
            'tax_rate' => 0.19,
            'tax_amount' => 19.00,
            'total' => 119.00,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/reports');

        $response->assertOk();
        $page = $response->viewData('page');
        $this->assertArrayHasKey('stats', $page['props']);
    }

    public function test_revenue_report_shows_revenue_data()
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'status' => 'active',
            'customer_type' => 'business',
        ]);

        Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'number' => 'RE-2024-0001',
            'status' => 'paid',
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'subtotal' => 100.00,
            'tax_rate' => 0.19,
            'tax_amount' => 19.00,
            'total' => 119.00,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/reports/revenue?period=month');

        $response->assertOk();
        $page = $response->viewData('page');
        $this->assertArrayHasKey('revenueData', $page['props']);
    }

    public function test_customer_report_shows_customer_statistics()
    {
        Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'status' => 'active',
            'customer_type' => 'business',
        ]);

        $response = $this->actingAs($this->user)
            ->get('/reports/customers');

        $response->assertOk();
        $page = $response->viewData('page');
        $this->assertArrayHasKey('customerStats', $page['props']);
    }

    public function test_tax_report_shows_tax_data()
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'status' => 'active',
            'customer_type' => 'business',
        ]);

        Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'number' => 'RE-2024-0001',
            'status' => 'paid',
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'subtotal' => 100.00,
            'tax_rate' => 0.19,
            'tax_amount' => 19.00,
            'total' => 119.00,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/reports/tax?period=month');

        $response->assertOk();
        $page = $response->viewData('page');
        $this->assertArrayHasKey('taxData', $page['props']);
    }
}

