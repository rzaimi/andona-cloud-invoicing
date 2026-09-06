<?php

namespace Tests\Feature\Modules;

use App\Jobs\SendInvoiceReminder;
use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Models\InvoiceItem;
use App\Modules\Mahnung\Services\DunningService;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MahnungModuleTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $user;

    protected Customer $customer;

    protected DunningService $dunning;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seedRolesAndPermissions();

        $this->company = Company::create([
            'name' => 'Mahnung Test GmbH',
            'email' => 'office@mahnung.test',
            'status' => 'active',
        ]);

        $this->configureSmtp();
        $this->company->setSetting('reminder_mahnung1_fee', 8.50, 'decimal');
        $this->company->setSetting('reminder_mahnung2_fee', 12.00, 'decimal');
        $this->company->setSetting('reminder_interest_rate', 9.00, 'decimal');
        $this->company->setSetting('reminder_inkasso_fee', 50.00, 'decimal');
        $this->company->setSetting('reminder_auto_send', '1', 'boolean');

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->user->assignRole('admin');

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Schuldner AG',
            'email' => 'buchhaltung@schuldner.test',
            'status' => 'active',
            'customer_type' => 'business',
        ]);

        $this->dunning = app(DunningService::class);
    }

    public function test_next_level_and_fee_come_from_settings(): void
    {
        $invoice = $this->makeInvoice([
            'due_date' => now()->subDays(20),
            'reminder_level' => Invoice::REMINDER_FRIENDLY,
        ]);

        $due = $this->dunning->nextDueLevel($invoice, $this->company);

        $this->assertNotNull($due);
        $this->assertSame(Invoice::REMINDER_MAHNUNG_1, $due['level']);
        $this->assertEquals(8.50, $due['fee']);
        $this->assertEquals(8.50, $this->dunning->feeForLevel($this->company, Invoice::REMINDER_MAHNUNG_1));
        $this->assertEquals(9.00, $this->dunning->settings($this->company)['reminder_interest_rate']);
        $this->assertEquals(50.00, $this->dunning->settings($this->company)['reminder_inkasso_fee']);
    }

    public function test_auto_send_skips_when_disabled_smtp_missing_or_no_customer_email(): void
    {
        $this->makeInvoice(['due_date' => now()->subDays(10)]);

        $this->company->setSetting('reminder_auto_send', '0', 'boolean');
        $this->assertCount(0, $this->dunning->invoicesDueForEscalation($this->company->fresh()));

        $this->company->setSetting('reminder_auto_send', '1', 'boolean');
        $this->company->setSetting('smtp_host', '', 'string');
        $this->company->setSetting('smtp_username', '', 'string');
        $this->assertCount(0, $this->dunning->invoicesDueForEscalation($this->company->fresh()));

        $this->configureSmtp();
        $this->customer->update(['email' => '']);
        $this->assertCount(0, $this->dunning->invoicesDueForEscalation($this->company->fresh()));
    }

    public function test_manual_send_advances_history_without_skipping_levels(): void
    {
        Mail::fake();

        $invoice = $this->makeInvoice([
            'due_date' => now()->addDays(3),
        ]);

        $this->actingAs($this->user)
            ->from('/mahnungen')
            ->post(route('mahnungen.store', $invoice))
            ->assertRedirect('/mahnungen');

        $invoice->refresh();
        $this->assertSame(Invoice::REMINDER_FRIENDLY, (int) $invoice->reminder_level);
        $this->assertCount(1, $invoice->reminder_history);
        $this->assertSame(Invoice::REMINDER_FRIENDLY, $invoice->reminder_history[0]['level']);
        $this->assertEquals(0, (float) $invoice->reminder_fee);

        $this->actingAs($this->user)
            ->from('/mahnungen')
            ->post(route('invoices.send-reminder', $invoice))
            ->assertRedirect('/mahnungen');

        $invoice->refresh();
        $this->assertSame(Invoice::REMINDER_MAHNUNG_1, (int) $invoice->reminder_level);
        $this->assertCount(2, $invoice->reminder_history);
        $this->assertSame(Invoice::REMINDER_MAHNUNG_1, $invoice->reminder_history[1]['level']);
        $this->assertEquals(8.50, (float) $invoice->reminder_fee);
        $this->assertEquals(127.50, (float) $invoice->total);
        $this->assertEquals((float) $invoice->total, $invoice->total_with_fees);
        $this->assertNotEquals((float) $invoice->total + (float) $invoice->reminder_fee, $invoice->total_with_fees);
        $this->assertTrue($invoice->items()->where('description', 'like', 'Mahngebühr%')->exists());
        $this->assertDatabaseCount('email_logs', 2);
        $this->assertDatabaseHas('email_logs', [
            'related_id' => $invoice->id,
            'type' => 'mahnung',
        ]);
    }

    public function test_paid_and_cancelled_invoices_cannot_escalate(): void
    {
        Mail::fake();

        foreach (['paid', 'cancelled'] as $status) {
            $invoice = $this->makeInvoice([
                'status' => $status,
                'due_date' => now()->subDays(20),
                'number' => $status === 'paid' ? 'RE-2026-PAID' : 'RE-2026-CANC',
            ]);

            $this->actingAs($this->user)
                ->from('/mahnungen')
                ->post(route('mahnungen.store', $invoice))
                ->assertRedirect('/mahnungen')
                ->assertSessionHas('error');

            $invoice->refresh();
            $this->assertSame(Invoice::REMINDER_NONE, (int) $invoice->reminder_level);
            $this->assertEmpty($invoice->reminder_history ?? []);
        }

        $this->assertDatabaseCount('email_logs', 0);
    }

    public function test_daily_command_dry_run_queues_nothing(): void
    {
        Queue::fake();
        $this->makeInvoice(['due_date' => now()->subDays(10)]);

        $this->artisan('mahnungen:send', ['--dry-run' => true])
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_auto_send_waits_for_interval_while_manual_does_not(): void
    {
        Mail::fake();

        $invoice = $this->makeInvoice([
            'due_date' => now()->subDays(3),
        ]);

        $this->assertNull($this->dunning->nextDueLevel($invoice, $this->company));

        Queue::fake();
        $this->artisan('mahnungen:send')->assertSuccessful();
        Queue::assertNothingPushed();

        $result = $this->dunning->sendNext($invoice, respectThresholds: false);
        $this->assertTrue($result['ok']);
        $this->assertSame(Invoice::REMINDER_FRIENDLY, $result['level']);
    }

    public function test_auto_send_escalates_at_most_once_per_day(): void
    {
        Mail::fake();

        // 20 days overdue at level NONE: friendly (7d) AND Mahnung 1 (14d)
        // thresholds are both already met — the classic double-escalation setup.
        $invoice = $this->makeInvoice(['due_date' => now()->subDays(20)]);

        $first = $this->dunning->sendNext($invoice, respectThresholds: true);
        $this->assertTrue($first['ok']);
        $this->assertSame(Invoice::REMINDER_FRIENDLY, $first['level']);

        // A second auto run on the same day must not escalate again.
        $invoice->refresh();
        $this->assertNull($this->dunning->nextDueLevel($invoice, $this->company));
        $second = $this->dunning->sendNext($invoice, respectThresholds: true);
        $this->assertFalse($second['ok']);

        // The next day the following level is due again.
        $this->travel(1)->days();
        $due = $this->dunning->nextDueLevel($invoice->fresh(), $this->company);
        $this->assertNotNull($due);
        $this->assertSame(Invoice::REMINDER_MAHNUNG_1, $due['level']);
    }

    public function test_duplicate_reminder_jobs_are_not_queued(): void
    {
        Queue::fake();

        $invoice = $this->makeInvoice(['due_date' => now()->subDays(10)]);

        // Simulates the daily command running twice before the queue drained.
        SendInvoiceReminder::dispatch($invoice->id);
        SendInvoiceReminder::dispatch($invoice->id);

        Queue::assertPushed(SendInvoiceReminder::class, 1);
    }

    public function test_workspace_lists_open_dunning_invoices(): void
    {
        $this->makeInvoice(['due_date' => now()->subDays(10)]);

        $this->actingAs($this->user)
            ->get(route('mahnungen.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('mahnungen/index')
                ->has('invoices.data', 1)
            );
    }

    public function test_reminders_send_wraps_mahnungen_command(): void
    {
        Queue::fake();
        $this->makeInvoice(['due_date' => now()->subDays(10)]);

        $this->artisan('reminders:send', ['--dry-run' => true])->assertSuccessful();

        Queue::assertNothingPushed();
    }

    private function configureSmtp(): void
    {
        $this->company->setSetting('smtp_host', 'smtp.mahnung.test', 'string');
        $this->company->setSetting('smtp_username', 'mailer@mahnung.test', 'string');
        $this->company->setSetting('smtp_from_address', 'mailer@mahnung.test', 'string');
        $this->company->setSetting('smtp_from_name', 'Mahnung Test GmbH', 'string');
    }

    private function makeInvoice(array $overrides = []): Invoice
    {
        $invoice = Invoice::create(array_merge([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'number' => 'RE-2026-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
            'status' => 'sent',
            'issue_date' => now()->subDays(20),
            'due_date' => now()->subDays(14),
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
}
