<?php

namespace Tests\Feature\Modules;

use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Offer\Models\Offer;
use App\Modules\Product\Models\Product;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardModuleTest extends TestCase
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

    public function test_authenticated_user_can_access_dashboard()
    {
        $this->actingAs($this->user)
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_guest_cannot_access_dashboard()
    {
        $this->get('/dashboard')
            ->assertRedirect('/login');
    }

    public function test_dashboard_shows_correct_statistics()
    {
        // Create test data
        Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'status' => 'active',
            'customer_type' => 'business',
        ]);

        Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => Customer::first()->id,
            'user_id' => $this->user->id,
            'number' => 'RE-2024-0001',
            'status' => 'sent',
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'subtotal' => 100.00,
            'tax_rate' => 0.19,
            'tax_amount' => 19.00,
            'total' => 119.00,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/dashboard');

        $response->assertOk();
        $page = $response->viewData('page');
        $this->assertArrayHasKey('stats', $page['props']);
        $this->assertEquals(1, $page['props']['stats']['customers']['total']);
        $this->assertEquals(1, $page['props']['stats']['invoices']['total']);
    }

    public function test_dashboard_shows_recent_invoices()
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
            'status' => 'sent',
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'subtotal' => 100.00,
            'tax_rate' => 0.19,
            'tax_amount' => 19.00,
            'total' => 119.00,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/dashboard');

        $response->assertOk();
        $page = $response->viewData('page');
        $this->assertArrayHasKey('recent', $page['props']);
        $this->assertArrayHasKey('invoices', $page['props']['recent']);
    }

    public function test_dashboard_shows_overdue_invoices_alerts()
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
            'status' => 'overdue',
            'issue_date' => now()->subDays(30),
            'due_date' => now()->subDays(10),
            'subtotal' => 100.00,
            'tax_rate' => 0.19,
            'tax_amount' => 19.00,
            'total' => 119.00,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/dashboard');

        $response->assertOk();
        $page = $response->viewData('page');
        $this->assertArrayHasKey('alerts', $page['props']);
        $this->assertArrayHasKey('overdue_invoices', $page['props']['alerts']);
    }
}

