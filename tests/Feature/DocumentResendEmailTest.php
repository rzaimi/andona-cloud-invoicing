<?php

namespace Tests\Feature;

use App\Jobs\SendInvoiceEmail;
use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Offer\Models\Offer;
use App\Modules\Offer\Models\OfferItem;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DocumentResendEmailTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $user;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seedRolesAndPermissions();

        $this->company = Company::create([
            'name' => 'Resend GmbH',
            'email' => 'resend@example.com',
            'status' => 'active',
        ]);
        $this->company->setSetting('smtp_host', 'smtp.example.com', 'string');
        $this->company->setSetting('smtp_username', 'mailer@example.com', 'string');
        $this->company->setSetting('smtp_password', 'secret', 'string');
        $this->company->setSetting('smtp_port', 587, 'integer');

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'user',
        ]);
        $this->user->assignRole('user');

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Kunde Resend',
            'email' => 'kunde@example.com',
            'status' => 'active',
            'customer_type' => 'business',
        ]);
    }

    public function test_sent_invoice_can_be_resent_to_another_address(): void
    {
        Queue::fake();
        $this->actingAs($this->user);

        $invoice = $this->makeInvoice('sent');

        $this->post(route('invoices.send', $invoice), [
            'to' => 'buchhaltung@example.com',
            'subject' => 'Rechnung erneut',
            'message' => 'Hier die Kopie.',
        ])->assertRedirect();

        Queue::assertPushed(SendInvoiceEmail::class, function (SendInvoiceEmail $job) use ($invoice) {
            return $job->invoiceId === $invoice->id
                && $job->to === 'buchhaltung@example.com'
                && $job->subject === 'Rechnung erneut';
        });

        $this->assertSame('sent', $invoice->fresh()->status);
    }

    public function test_invoice_can_be_sent_when_customer_has_no_email(): void
    {
        Queue::fake();
        $this->actingAs($this->user);

        $this->customer->update(['email' => '']);
        $invoice = $this->makeInvoice('sent');

        $this->post(route('invoices.send', $invoice), [
            'to' => 'andere@example.com',
            'subject' => 'Rechnung',
        ])->assertRedirect();

        Queue::assertPushed(SendInvoiceEmail::class, fn (SendInvoiceEmail $job) => $job->to === 'andere@example.com');
    }

    public function test_cancelled_invoice_cannot_be_resent(): void
    {
        Queue::fake();
        $this->actingAs($this->user);

        $invoice = $this->makeInvoice('cancelled');

        $this->post(route('invoices.send', $invoice), [
            'to' => 'kunde@example.com',
            'subject' => 'Rechnung',
        ])->assertForbidden();

        Queue::assertNothingPushed();
    }

    public function test_sent_offer_can_be_resent_to_another_address(): void
    {
        Mail::fake();
        $this->actingAs($this->user);

        $offer = $this->makeOffer('sent');

        $this->post(route('offers.send', $offer), [
            'to' => 'einkauf@example.com',
            'subject' => 'Angebot aktualisiert',
            'message' => 'Bitte die neue Fassung beachten.',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('sent', $offer->fresh()->status);
    }

    private function makeInvoice(string $status): Invoice
    {
        return Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'number' => 'RE-2026-RESEND-'.$status,
            'status' => $status,
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'subtotal' => 100.00,
            'tax_rate' => 0.19,
            'tax_amount' => 19.00,
            'total' => 119.00,
        ]);
    }

    private function makeOffer(string $status): Offer
    {
        $offer = Offer::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'number' => 'AN-2026-RESEND-'.$status,
            'status' => $status,
            'issue_date' => now(),
            'valid_until' => now()->addDays(30),
            'subtotal' => 100.00,
            'tax_rate' => 0.19,
            'tax_amount' => 19.00,
            'total' => 119.00,
        ]);

        OfferItem::create([
            'offer_id' => $offer->id,
            'description' => 'Leistung',
            'quantity' => 1,
            'unit_price' => 100.00,
            'tax_rate' => 0.19,
            'total' => 100.00,
            'unit' => 'Stk.',
            'sort_order' => 0,
        ]);

        return $offer;
    }
}
