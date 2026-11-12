<?php

namespace Tests\Feature;

use App\Jobs\SendInvoiceEmail;
use App\Models\EmailLog;
use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Customer\Models\CustomerContact;
use App\Modules\Customer\Services\CustomerRecipientResolver;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Models\InvoiceItem;
use App\Modules\Mahnung\Services\DunningService;
use App\Modules\Offer\Models\Offer;
use App\Modules\User\Models\User;
use App\Services\InvoiceMailer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CustomerContactsTest extends TestCase
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
            'name' => 'Kontakte GmbH',
            'email' => 'kontakte@example.com',
            'status' => 'active',
        ]);
        $this->company->setSetting('smtp_host', 'smtp.example.com', 'string');
        $this->company->setSetting('smtp_username', 'mailer@example.com', 'string');
        $this->company->setSetting('smtp_password', 'secret', 'string');
        $this->company->setSetting('smtp_port', 587, 'integer');

        $this->user = User::factory()->create(['company_id' => $this->company->id, 'role' => 'admin']);
        $this->user->assignRole('admin');

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Kunde AG',
            'email' => 'info@kunde.example',
            'status' => 'active',
            'customer_type' => 'business',
        ]);
    }

    private function contact(array $attributes = [], ?Customer $customer = null): CustomerContact
    {
        $customer ??= $this->customer;

        return $customer->contacts()->create(array_merge([
            'company_id' => $customer->company_id,
            'name' => 'Anna Müller',
            'email' => 'anna@kunde.example',
        ], $attributes));
    }

    private function makeInvoice(array $overrides = []): Invoice
    {
        $invoice = Invoice::create(array_merge([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'number' => 'RE-2026-CC-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'status' => 'sent',
            'issue_date' => now()->subDays(30),
            'due_date' => now()->subDays(20),
            'subtotal' => 100.00,
            'tax_rate' => 0.19,
            'tax_amount' => 19.00,
            'total' => 119.00,
            'reminder_level' => Invoice::REMINDER_NONE,
        ], $overrides));

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => 'Leistung',
            'quantity' => 1,
            'unit_price' => 100.00,
            'unit' => 'Stk.',
            'tax_rate' => 0.19,
            'total' => 100.00,
            'sort_order' => 0,
        ]);

        return $invoice;
    }

    // ── CRUD + tenancy ───────────────────────────────────────────────────────

    public function test_contact_can_be_created_updated_and_deleted(): void
    {
        $this->actingAs($this->user)
            ->post(route('customers.contacts.store', $this->customer), [
                'name' => 'Anna Müller',
                'email' => 'anna@kunde.example',
                'title' => 'Buchhaltung',
                'phone' => '+49 30 111',
                'receives_invoices' => true,
            ])
            ->assertRedirect();

        $contact = $this->customer->contacts()->firstOrFail();
        $this->assertSame('Buchhaltung', $contact->title);
        $this->assertTrue($contact->receives_invoices);
        $this->assertSame($this->company->id, $contact->company_id);

        $this->actingAs($this->user)
            ->put(route('customers.contacts.update', [$this->customer, $contact]), [
                'name' => 'Anna Schmidt',
                'email' => 'anna@kunde.example',
                'receives_invoices' => false,
                'receives_reminders' => true,
            ])
            ->assertRedirect();

        $contact->refresh();
        $this->assertSame('Anna Schmidt', $contact->name);
        $this->assertFalse($contact->receives_invoices);
        $this->assertTrue($contact->receives_reminders);

        $this->actingAs($this->user)
            ->delete(route('customers.contacts.destroy', [$this->customer, $contact]))
            ->assertRedirect();

        $this->assertSame(0, $this->customer->contacts()->count());
    }

    public function test_contact_requires_name_and_valid_email(): void
    {
        $this->actingAs($this->user)
            ->from(route('customers.show', $this->customer))
            ->post(route('customers.contacts.store', $this->customer), ['name' => '', 'email' => 'kein-email'])
            ->assertSessionHasErrors(['name', 'email']);
    }

    public function test_other_companies_cannot_touch_contacts(): void
    {
        $contact = $this->contact();

        $otherCompany = Company::create(['name' => 'Fremd GmbH', 'email' => 'fremd@example.com', 'status' => 'active']);
        $intruder = User::factory()->create(['company_id' => $otherCompany->id, 'role' => 'admin']);
        $intruder->assignRole('admin');

        $this->actingAs($intruder)
            ->getJson(route('customers.contacts.index', $this->customer))
            ->assertForbidden();
        $this->actingAs($intruder)
            ->post(route('customers.contacts.store', $this->customer), ['name' => 'X', 'email' => 'x@example.com'])
            ->assertForbidden();
        $this->actingAs($intruder)
            ->delete(route('customers.contacts.destroy', [$this->customer, $contact]))
            ->assertForbidden();

        $this->assertNotNull($contact->fresh());
    }

    public function test_contact_must_belong_to_the_customer_in_the_url(): void
    {
        $otherCustomer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Anderer Kunde',
            'email' => 'anderer@example.com',
            'status' => 'active',
            'customer_type' => 'business',
        ]);
        $foreignContact = $this->contact(['name' => 'Fremd'], $otherCustomer);

        $this->actingAs($this->user)
            ->delete(route('customers.contacts.destroy', [$this->customer, $foreignContact]))
            ->assertNotFound();
    }

    // ── Primary contact invariants ───────────────────────────────────────────

    public function test_primary_contact_invariants_and_contact_person_sync(): void
    {
        $first = $this->contact(['name' => 'Erste Person']);
        $this->assertTrue($first->fresh()->is_primary, 'first contact becomes primary automatically');
        $this->assertSame('Erste Person', $this->customer->fresh()->contact_person);

        $second = $this->contact(['name' => 'Zweite Person', 'email' => 'zweite@kunde.example', 'is_primary' => true]);
        $this->assertTrue($second->fresh()->is_primary);
        $this->assertFalse($first->fresh()->is_primary, 'only one primary per customer');
        $this->assertSame('Zweite Person', $this->customer->fresh()->contact_person);

        $second->delete();
        $this->assertTrue($first->fresh()->is_primary, 'deleting the primary promotes the next contact');
        $this->assertSame('Erste Person', $this->customer->fresh()->contact_person);

        $first->delete();
        $this->assertNull($this->customer->fresh()->contact_person);
    }

    public function test_creating_a_customer_with_a_first_contact(): void
    {
        $this->actingAs($this->user)
            ->post(route('customers.store'), [
                'name' => 'Neukunde GmbH',
                'email' => 'info@neukunde.example',
                'customer_type' => 'business',
                'first_contact' => ['name' => 'Ben Beispiel', 'email' => 'ben@neukunde.example', 'title' => 'Einkauf'],
            ])
            ->assertRedirect(route('customers.index'));

        $customer = Customer::where('email', 'info@neukunde.example')->firstOrFail();
        $this->assertSame('Ben Beispiel', $customer->contact_person);
        $this->assertSame('Einkauf', $customer->contacts()->first()->title);
    }

    // ── Recipient resolution ─────────────────────────────────────────────────

    public function test_resolver_prefers_flagged_contacts_and_falls_back_to_customer_email(): void
    {
        $resolver = app(CustomerRecipientResolver::class);

        // No contacts → customer main email
        $this->assertSame(
            ['to' => 'info@kunde.example', 'to_name' => 'Kunde AG', 'cc' => []],
            $resolver->resolve($this->customer->fresh(), CustomerContact::PURPOSE_INVOICE)
        );

        $this->contact(['name' => 'Anna', 'email' => 'anna@kunde.example', 'receives_invoices' => true]);
        $this->contact(['name' => 'Bernd', 'email' => 'bernd@kunde.example', 'receives_invoices' => true]);
        $this->contact(['name' => 'Clara', 'email' => 'clara@kunde.example', 'receives_offers' => true]);

        $invoice = $resolver->resolve($this->customer->fresh(), CustomerContact::PURPOSE_INVOICE);
        $this->assertSame('anna@kunde.example', $invoice['to']);
        $this->assertSame('Anna', $invoice['to_name']);
        $this->assertSame(['bernd@kunde.example'], $invoice['cc']);

        $offer = $resolver->resolve($this->customer->fresh(), CustomerContact::PURPOSE_OFFER);
        $this->assertSame('clara@kunde.example', $offer['to']);

        // Nobody flagged for reminders → still the customer's main email
        $reminder = $resolver->resolve($this->customer->fresh(), CustomerContact::PURPOSE_REMINDER);
        $this->assertSame('info@kunde.example', $reminder['to']);
    }

    public function test_send_dialog_endpoint_returns_contacts_and_defaults(): void
    {
        $this->contact(['name' => 'Anna', 'email' => 'anna@kunde.example', 'title' => 'Buchhaltung', 'receives_invoices' => true]);
        $this->contact(['name' => 'Clara', 'email' => 'clara@kunde.example']);

        $this->actingAs($this->user)
            ->getJson(route('customers.contacts.index', ['customer' => $this->customer->id, 'purpose' => 'invoice']))
            ->assertOk()
            ->assertJsonCount(2, 'contacts')
            ->assertJsonPath('defaults.to', 'anna@kunde.example')
            ->assertJsonPath('defaults.to_name', 'Anna');
    }

    // ── Sending ──────────────────────────────────────────────────────────────

    public function test_invoice_send_accepts_contact_recipient_with_cc_list(): void
    {
        Queue::fake();
        $invoice = $this->makeInvoice(['status' => 'draft']);

        $this->actingAs($this->user)
            ->post(route('invoices.send', $invoice), [
                'to' => 'anna@kunde.example',
                'to_name' => 'Anna Müller',
                'cc' => ['bernd@kunde.example', 'clara@kunde.example'],
                'subject' => 'Rechnung',
                'message' => 'Guten Tag Anna Müller,',
            ])
            ->assertRedirect();

        Queue::assertPushed(SendInvoiceEmail::class, fn (SendInvoiceEmail $job) => $job->to === 'anna@kunde.example'
            && $job->cc === ['bernd@kunde.example', 'clara@kunde.example']
            && $job->recipientName === 'Anna Müller');
    }

    public function test_invoice_send_rejects_an_invalid_cc_address(): void
    {
        Queue::fake();
        $invoice = $this->makeInvoice(['status' => 'draft']);

        $this->actingAs($this->user)
            ->from(route('invoices.show', $invoice))
            ->post(route('invoices.send', $invoice), [
                'to' => 'anna@kunde.example',
                'cc' => ['bernd@kunde.example', 'kaputt'],
                'subject' => 'Rechnung',
            ])
            ->assertSessionHasErrors('cc.1');

        Queue::assertNothingPushed();
    }

    public function test_invoice_send_still_accepts_a_single_cc_string(): void
    {
        Queue::fake();
        $invoice = $this->makeInvoice(['status' => 'draft']);

        $this->actingAs($this->user)
            ->post(route('invoices.send', $invoice), [
                'to' => 'anna@kunde.example',
                'cc' => 'bernd@kunde.example',
                'subject' => 'Rechnung',
            ])
            ->assertRedirect();

        Queue::assertPushed(SendInvoiceEmail::class, fn (SendInvoiceEmail $job) => $job->cc === ['bernd@kunde.example']);
    }

    public function test_mailer_logs_the_contact_name_as_recipient(): void
    {
        Mail::fake();
        $invoice = $this->makeInvoice(['status' => 'draft']);

        $result = app(InvoiceMailer::class)->send(
            invoice: $invoice,
            to: 'anna@kunde.example',
            cc: ['bernd@kunde.example'],
            recipientName: 'Anna Müller',
        );

        $this->assertTrue($result['ok'], $result['error'] ?? '');

        $log = EmailLog::where('related_id', $invoice->id)->firstOrFail();
        $this->assertSame('anna@kunde.example', $log->recipient_email);
        $this->assertSame('Anna Müller', $log->recipient_name);
        $this->assertSame(['bernd@kunde.example'], $log->metadata['cc']);
    }

    public function test_mahnung_goes_to_the_contact_flagged_for_reminders(): void
    {
        Mail::fake();
        $this->contact(['name' => 'Mahn Wesen', 'email' => 'mahnung@kunde.example', 'receives_reminders' => true]);
        $invoice = $this->makeInvoice();

        $result = app(DunningService::class)->sendNext($invoice, respectThresholds: false);

        $this->assertTrue($result['ok'], $result['error'] ?? '');

        $log = EmailLog::where('type', 'mahnung')->where('related_id', $invoice->id)->firstOrFail();
        $this->assertSame('mahnung@kunde.example', $log->recipient_email);
        $this->assertSame('Mahn Wesen', $log->recipient_name);
    }

    public function test_customer_without_main_email_is_dunned_through_a_flagged_contact(): void
    {
        Mail::fake();
        $this->customer->update(['email' => '']);
        $invoice = $this->makeInvoice();
        $dunning = app(DunningService::class);

        // Without any address the Mahnung is refused, as before.
        $refused = $dunning->sendNext($invoice->fresh(), respectThresholds: false);
        $this->assertFalse($refused['ok']);

        $this->contact(['email' => 'mahnung@kunde.example', 'receives_reminders' => true]);

        $sent = $dunning->sendNext($invoice->fresh(), respectThresholds: false);
        $this->assertTrue($sent['ok'], $sent['error'] ?? '');
    }

    public function test_offer_expiry_reminder_targets_the_contact_flagged_for_offers(): void
    {
        $this->contact(['name' => 'Ein Kauf', 'email' => 'einkauf@kunde.example', 'receives_offers' => true]);

        Offer::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'number' => 'AN-2026-CC-1',
            'status' => 'sent',
            'issue_date' => now()->subDays(10),
            'valid_until' => now()->addDay(),
            'subtotal' => 100.00,
            'tax_rate' => 0.19,
            'tax_amount' => 19.00,
            'total' => 119.00,
        ]);

        $this->artisan('reminders:send', ['--company' => $this->company->id, '--dry-run' => true])
            ->expectsOutputToContain('einkauf@kunde.example')
            ->assertSuccessful();
    }
}
