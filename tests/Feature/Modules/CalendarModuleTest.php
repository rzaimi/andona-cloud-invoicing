<?php

namespace Tests\Feature\Modules;

use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Offer\Models\Offer;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarModuleTest extends TestCase
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

    public function test_authenticated_user_can_access_calendar()
    {
        $this->actingAs($this->user)
            ->get('/calendar')
            ->assertOk();
    }

    public function test_guest_cannot_access_calendar()
    {
        $this->get('/calendar')
            ->assertRedirect('/login');
    }

    public function test_calendar_shows_invoice_due_dates()
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
            ->get('/calendar');

        $response->assertOk();
        $page = $response->viewData('page');
        $this->assertArrayHasKey('events', $page['props']);
        
        $events = $page['props']['events'];
        $invoiceEvents = collect($events)->where('type', 'invoice_due');
        $this->assertGreaterThan(0, $invoiceEvents->count());
    }

    public function test_calendar_shows_overdue_invoices()
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
            ->get('/calendar');

        $response->assertOk();
        $page = $response->viewData('page');
        $events = $page['props']['events'];
        $overdueEvents = collect($events)->where('status', 'overdue');
        $this->assertGreaterThan(0, $overdueEvents->count());
    }

    public function test_calendar_shows_offer_expiry_dates()
    {
        $customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'status' => 'active',
            'customer_type' => 'business',
        ]);

        Offer::create([
            'company_id' => $this->company->id,
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'number' => 'AN-2024-0001',
            'status' => 'sent',
            'issue_date' => now(),
            'valid_until' => now()->addDays(30),
            'subtotal' => 100.00,
            'tax_rate' => 0.19,
            'tax_amount' => 19.00,
            'total' => 119.00,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/calendar');

        $response->assertOk();
        $page = $response->viewData('page');
        $events = $page['props']['events'];
        $offerEvents = collect($events)->where('type', 'offer_expiry');
        $this->assertGreaterThan(0, $offerEvents->count());
    }
}

