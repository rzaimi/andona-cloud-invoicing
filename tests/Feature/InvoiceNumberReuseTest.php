<?php

namespace Tests\Feature;

use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceNumberReuseTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Customer $customer;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Number Reuse Company',
            'email' => 'reuse@example.com',
            'status' => 'active',
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Reuse Customer',
            'email' => 'reuse-customer@example.com',
            'status' => 'active',
            'customer_type' => 'business',
        ]);
    }

    private function makeInvoice(string $number, string $status = 'draft'): Invoice
    {
        return Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'number' => $number,
            'status' => $status,
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'subtotal' => 100.00,
            'tax_rate' => 0.19,
            'tax_amount' => 19.00,
            'total' => 119.00,
        ]);
    }

    public function test_delete_frees_the_number_for_reuse()
    {
        $year = now()->format('Y');
        $invoice = $this->makeInvoice("RE-{$year}-0001");

        $invoice->delete();

        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);

        // The next generated number reuses the freed one...
        $probe = $this->makeInvoice('TMP-PLACEHOLDER');
        $this->assertEquals("RE-{$year}-0001", $probe->generateNumber());

        // ...and inserting it does not collide.
        $reissued = $this->makeInvoice("RE-{$year}-0001");
        $this->assertDatabaseHas('invoices', ['id' => $reissued->id, 'number' => "RE-{$year}-0001"]);
    }

    public function test_lowering_the_counter_works_after_deleting_invoices()
    {
        $year = now()->format('Y');
        $high = $this->makeInvoice("RE-{$year}-0042");
        $high->delete();

        // With the high number gone, generation falls back to the counter
        // floor instead of continuing at 0043.
        $probe = $this->makeInvoice('TMP-PLACEHOLDER');
        $this->assertEquals("RE-{$year}-0001", $probe->generateNumber());
    }
}
