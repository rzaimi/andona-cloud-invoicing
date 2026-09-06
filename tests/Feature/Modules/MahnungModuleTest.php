<?php

namespace Tests\Feature\Modules;

use App\Jobs\SendInvoiceReminder;
use App\Models\EmailLog;
use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Models\InvoiceItem;
use App\Modules\Mahnung\Services\DunningService;
use App\Modules\Payment\Models\Payment;
use App\Modules\User\Models\User;
use App\Services\GirocodeService;
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
            ->from('/dunning')
            ->post(route('dunning.store', $invoice))
            ->assertRedirect('/dunning');

        $invoice->refresh();
        $this->assertSame(Invoice::REMINDER_FRIENDLY, (int) $invoice->reminder_level);
        $this->assertCount(1, $invoice->reminder_history);
        $this->assertSame(Invoice::REMINDER_FRIENDLY, $invoice->reminder_history[0]['level']);
        $this->assertEquals(0, (float) $invoice->reminder_fee);

        $this->actingAs($this->user)
            ->from('/dunning')
            ->post(route('invoices.send-reminder', $invoice))
            ->assertRedirect('/dunning');

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
                ->from('/dunning')
                ->post(route('dunning.store', $invoice))
                ->assertRedirect('/dunning')
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

        $this->artisan('dunning:send', ['--dry-run' => true])
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
        $this->artisan('dunning:send')->assertSuccessful();
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

    public function test_pause_blocks_auto_escalation_until_resumed(): void
    {
        $invoice = $this->makeInvoice(['due_date' => now()->subDays(20)]);

        // Due before the pause…
        $this->assertNotNull($this->dunning->nextDueLevel($invoice, $this->company));

        $this->actingAs($this->user)
            ->from('/dunning')
            ->post(route('dunning.pause', $invoice), ['until' => now()->addDays(7)->toDateString()])
            ->assertRedirect('/dunning')
            ->assertSessionHas('success');

        $invoice->refresh();
        $this->assertTrue($this->dunning->isPaused($invoice));
        $this->assertNull($this->dunning->nextDueLevel($invoice, $this->company));
        $this->assertCount(0, $this->dunning->invoicesDueForEscalation($this->company));

        // GoBD: the Mahnsperre itself must be in the audit trail.
        $this->assertDatabaseHas('invoice_audit_logs', [
            'invoice_id' => $invoice->id,
            'action' => 'dunning_paused',
        ]);

        // Manual send stays possible while paused.
        $this->assertTrue($this->dunning->canSendManually($invoice));

        $this->actingAs($this->user)
            ->post(route('dunning.resume', $invoice))
            ->assertRedirect();

        $invoice->refresh();
        $this->assertFalse($this->dunning->isPaused($invoice));
        $this->assertNotNull($this->dunning->nextDueLevel($invoice, $this->company));
        $this->assertDatabaseHas('invoice_audit_logs', [
            'invoice_id' => $invoice->id,
            'action' => 'dunning_resumed',
        ]);
    }

    public function test_failed_filter_works_at_query_level(): void
    {
        $failed = $this->makeInvoice(['due_date' => now()->subDays(10), 'number' => 'RE-2026-FAIL']);
        $healthy = $this->makeInvoice(['due_date' => now()->subDays(10), 'number' => 'RE-2026-OKAY']);

        EmailLog::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'recipient_email' => $this->customer->email,
            'subject' => 'Mahnung',
            'type' => 'mahnung',
            'related_type' => 'Invoice',
            'related_id' => $failed->id,
            'status' => 'failed',
            'error_message' => 'SMTP down',
            'sent_at' => now(),
        ]);

        $this->actingAs($this->user)
            ->get(route('dunning.index', ['filter' => 'failed']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('dunning/index')
                ->has('invoices.data', 1)
                ->where('invoices.data.0.number', 'RE-2026-FAIL')
                ->where('invoices.data.0.last_send_failed', true)
            );
    }

    public function test_due_counter_matches_what_bulk_send_would_queue(): void
    {
        // Due invoice whose customer has no email: the bulk action skips it,
        // so the counter must not include it either.
        $this->makeInvoice(['due_date' => now()->subDays(10)]);
        $this->customer->update(['email' => '']);

        $this->assertSame(0, $this->dunning->countDueForNextStep($this->company->id));

        $this->customer->update(['email' => 'buchhaltung@schuldner.test']);
        $this->assertSame(1, $this->dunning->countDueForNextStep($this->company->id));

        // Auto-send off: the button IS the approval, counter still counts.
        $this->company->setSetting('reminder_auto_send', '0', 'boolean');
        $this->dunning->flushSettingsCache();
        $this->assertSame(1, $this->dunning->countDueForNextStep($this->company->id));
    }

    public function test_legacy_mahnungen_urls_redirect_to_dunning(): void
    {
        $invoice = $this->makeInvoice(['due_date' => now()->subDays(10)]);

        $this->actingAs($this->user)->get('/mahnungen')->assertRedirect('/dunning');
        $this->actingAs($this->user)
            ->get('/mahnungen/'.$invoice->id)
            ->assertRedirect(route('dunning.show', $invoice->id));
    }

    public function test_pause_requires_a_future_date(): void
    {
        $invoice = $this->makeInvoice(['due_date' => now()->subDays(20)]);

        $this->actingAs($this->user)
            ->from('/dunning')
            ->post(route('dunning.pause', $invoice), ['until' => now()->subDay()->toDateString()])
            ->assertSessionHasErrors('until');
    }

    public function test_send_due_queues_all_due_reminders_even_with_auto_send_disabled(): void
    {
        Queue::fake();

        // Approval workflow: auto-send off, the button IS the approval.
        $this->company->setSetting('reminder_auto_send', '0', 'boolean');

        $dueA = $this->makeInvoice(['due_date' => now()->subDays(20), 'number' => 'RE-2026-DUEA']);
        $dueB = $this->makeInvoice(['due_date' => now()->subDays(20), 'number' => 'RE-2026-DUEB']);
        $paused = $this->makeInvoice([
            'due_date' => now()->subDays(20),
            'number' => 'RE-2026-PAUS',
            'dunning_paused_until' => now()->addDays(7)->toDateString(),
        ]);

        $this->actingAs($this->user)
            ->from('/dunning')
            ->post(route('dunning.send-due'))
            ->assertRedirect('/dunning')
            ->assertSessionHas('success');

        Queue::assertPushed(SendInvoiceReminder::class, 2);
        Queue::assertPushed(SendInvoiceReminder::class, fn ($job) => $job->invoiceId === $dueA->id);
        Queue::assertPushed(SendInvoiceReminder::class, fn ($job) => $job->invoiceId === $dueB->id);
        Queue::assertNotPushed(SendInvoiceReminder::class, fn ($job) => $job->invoiceId === $paused->id);
    }

    public function test_failed_send_is_recorded_in_email_log(): void
    {
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('SMTP down'));

        $invoice = $this->makeInvoice(['due_date' => now()->subDays(10)]);

        $result = $this->dunning->sendNext($invoice, respectThresholds: false);

        $this->assertFalse($result['ok']);
        $this->assertDatabaseHas('email_logs', [
            'related_id' => $invoice->id,
            'type' => 'mahnung',
            'status' => 'failed',
            'error_message' => 'SMTP down',
        ]);

        // The escalation state must NOT advance on a failed send.
        $invoice->refresh();
        $this->assertSame(Invoice::REMINDER_NONE, (int) $invoice->reminder_level);
    }

    public function test_mahnung_duns_the_open_balance_after_partial_payment(): void
    {
        Mail::fake();

        $invoice = $this->makeInvoice(['due_date' => now()->subDays(10)]);

        Payment::create([
            'company_id' => $this->company->id,
            'invoice_id' => $invoice->id,
            'amount' => 50.00,
            'payment_date' => now(),
            'payment_method' => 'bank_transfer',
            'status' => 'completed',
            'created_by' => $this->user->id,
        ]);

        $result = $this->dunning->sendNext($invoice, respectThresholds: false);
        $this->assertTrue($result['ok']);

        $log = EmailLog::where('related_id', $invoice->id)->where('type', 'mahnung')->firstOrFail();
        $this->assertEquals(50.00, $log->metadata['paid_amount']);
        $this->assertEquals(69.00, $log->metadata['open_amount']); // 119.00 total − 50.00 paid
    }

    public function test_girocode_payload_follows_the_epc_spec(): void
    {
        $service = app(GirocodeService::class);

        $payload = $service->payload('Mahnung Test GmbH', 'DE89 3704 0044 0532 0130 00', 123.45, 'Rechnung RE-2026-0001');

        $this->assertSame([
            'BCD',
            '002',
            '1',
            'SCT',
            '',
            'Mahnung Test GmbH',
            'DE89370400440532013000',
            'EUR123.45',
            '',
            '',
            'Rechnung RE-2026-0001',
        ], explode("\n", $payload));

        $this->assertNull($service->payload('Mahnung Test GmbH', '', 123.45, 'x'));
        $this->assertNull($service->payload('Mahnung Test GmbH', 'DE89370400440532013000', 0.0, 'x'));
    }

    public function test_dossier_download_returns_a_zip(): void
    {
        $invoice = $this->makeInvoice(['due_date' => now()->subDays(30)]);

        $response = $this->actingAs($this->user)
            ->get(route('dunning.dossier', $invoice));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/zip');
        $this->assertStringContainsString(
            'Inkasso-Dossier_'.$invoice->number.'.zip',
            $response->headers->get('content-disposition'),
        );
    }

    public function test_mark_overdue_flips_past_due_sent_invoices_with_audit_log(): void
    {
        $pastDue = $this->makeInvoice(['due_date' => now()->subDays(5), 'number' => 'RE-2026-PAST']);
        $notYetDue = $this->makeInvoice(['due_date' => now()->addDays(5), 'number' => 'RE-2026-FUTR']);
        $draft = $this->makeInvoice(['due_date' => now()->subDays(5), 'status' => 'draft', 'number' => 'RE-2026-DRFT']);

        $this->artisan('invoices:mark-overdue')->assertSuccessful();

        $this->assertSame('overdue', $pastDue->fresh()->status);
        $this->assertSame('sent', $notYetDue->fresh()->status);
        $this->assertSame('draft', $draft->fresh()->status);

        $this->assertDatabaseHas('invoice_audit_logs', [
            'invoice_id' => $pastDue->id,
            'action' => 'status_changed',
            'old_status' => 'sent',
            'new_status' => 'overdue',
        ]);
    }

    public function test_mark_overdue_reconciles_fully_paid_invoices_as_paid(): void
    {
        $paidButSent = $this->makeInvoice(['due_date' => now()->subDays(5), 'number' => 'RE-2026-PDSENT']);
        $this->payInFull($paidButSent);

        $paidButOverdue = $this->makeInvoice(['due_date' => now()->subDays(5), 'status' => 'overdue', 'number' => 'RE-2026-PDOVER']);
        $this->payInFull($paidButOverdue);

        $this->artisan('invoices:mark-overdue')->assertSuccessful();

        $this->assertSame('paid', $paidButSent->fresh()->status);
        $this->assertSame('paid', $paidButOverdue->fresh()->status);
        $this->assertDatabaseHas('invoice_audit_logs', [
            'invoice_id' => $paidButOverdue->id,
            'old_status' => 'overdue',
            'new_status' => 'paid',
        ]);
    }

    public function test_settled_invoices_are_never_dunned_even_with_stale_status(): void
    {
        Mail::fake();

        // Status says "sent", but payments cover the full amount.
        $invoice = $this->makeInvoice(['due_date' => now()->subDays(20)]);
        $this->payInFull($invoice);

        // Excluded from the automatic escalation, the due counter,
        // AND the workspace pool itself…
        $this->assertCount(0, $this->dunning->invoicesDueForEscalation($this->company));
        $this->assertSame(0, $this->dunning->countDueForNextStep($this->company->id));
        $this->assertSame(0, $this->dunning->workspaceQuery($this->company->id)->count());
        $this->assertFalse($this->dunning->canSendManually($invoice));

        // …and a manual/queued send refuses too.
        $result = $this->dunning->sendNext($invoice, respectThresholds: false);
        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('bezahlt', $result['error']);
        Mail::assertNothingSent();
    }

    private function payInFull(Invoice $invoice): void
    {
        Payment::create([
            'company_id' => $this->company->id,
            'invoice_id' => $invoice->id,
            'amount' => (float) $invoice->total,
            'payment_date' => now(),
            'payment_method' => 'bank_transfer',
            'status' => 'completed',
            'created_by' => $this->user->id,
        ]);
    }

    public function test_workspace_lists_open_dunning_invoices(): void
    {
        $this->makeInvoice(['due_date' => now()->subDays(10)]);

        $this->actingAs($this->user)
            ->get(route('dunning.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('dunning/index')
                ->has('invoices.data', 1)
            );
    }

    public function test_reminders_send_wraps_dunning_command(): void
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
