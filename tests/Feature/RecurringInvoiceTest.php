<?php

namespace Tests\Feature;

use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\RecurringInvoice\Models\RecurringInvoiceItem;
use App\Modules\RecurringInvoice\Models\RecurringInvoiceProfile;
use App\Modules\User\Models\User;
use App\Services\RecurringInvoiceService;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class RecurringInvoiceTest extends TestCase
{
    protected Company $company;
    protected User $user;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seedRolesAndPermissions();

        $this->company = Company::create([
            'name'   => 'Acme GmbH',
            'email'  => 'billing@acme.example',
            'status' => 'active',
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'role'       => 'user',
        ]);
        $this->user->assignRole('user');

        $this->customer = Customer::create([
            'company_id'    => $this->company->id,
            'name'          => 'Kunde 1',
            'email'         => 'k1@example.com',
            'status'        => 'active',
            'customer_type' => 'business',
        ]);
    }

    private function makeProfile(array $overrides = []): RecurringInvoiceProfile
    {
        $profile = RecurringInvoiceProfile::create(array_merge([
            'company_id'          => $this->company->id,
            'customer_id'         => $this->customer->id,
            'user_id'             => $this->user->id,
            'name'                => 'Monatliche Wartung',
            'vat_regime'          => 'standard',
            'tax_rate'            => 0.19,
            'due_days_after_issue'=> 14,
            'interval_unit'       => 'month',
            'interval_count'      => 1,
            'day_of_month'        => null,
            'start_date'          => '2026-01-01',
            'next_run_date'       => '2026-01-01',
            'status'              => 'active',
            'auto_send'           => false,
        ], $overrides));

        RecurringInvoiceItem::create([
            'recurring_profile_id' => $profile->id,
            'description'          => 'Wartungspauschale',
            'quantity'             => 1,
            'unit_price'           => 100.00,
            'unit'                 => 'Monat',
            'tax_rate'             => 0.19,
            'sort_order'           => 0,
        ]);

        return $profile;
    }

    public function test_generates_invoice_when_due_and_advances_schedule(): void
    {
        $profile = $this->makeProfile();

        $service = app(RecurringInvoiceService::class);
        $results = $service->generateDue(CarbonImmutable::parse('2026-01-01'));

        $this->assertCount(1, $results);
        $this->assertSame('generated', $results[0]['status']);

        $profile->refresh();
        $this->assertSame(1, (int) $profile->occurrences_count);
        $this->assertSame('2026-01-01', $profile->last_run_date->toDateString());
        $this->assertSame('2026-02-01', $profile->next_run_date->toDateString());

        $invoice = Invoice::where('recurring_profile_id', $profile->id)->first();
        $this->assertNotNull($invoice);
        $this->assertSame('draft', $invoice->status);
        $this->assertSame($this->company->id, $invoice->company_id);
        $this->assertSame($this->customer->id, $invoice->customer_id);
        $this->assertEquals(100.00, (float) $invoice->subtotal);
        $this->assertEquals(119.00, (float) $invoice->total);
        $this->assertSame('2026-01-15', $invoice->due_date->toDateString());
        $this->assertCount(1, $invoice->items);
    }

    public function test_skips_profiles_that_are_not_due_yet(): void
    {
        $profile = $this->makeProfile([
            'start_date'    => '2026-06-01',
            'next_run_date' => '2026-06-01',
        ]);

        $results = app(RecurringInvoiceService::class)
            ->generateDue(CarbonImmutable::parse('2026-01-01'));

        $this->assertCount(0, $results);
        $this->assertDatabaseMissing('invoices', ['recurring_profile_id' => $profile->id]);
    }

    public function test_running_twice_on_the_same_day_does_not_double_generate(): void
    {
        $profile = $this->makeProfile();

        $service = app(RecurringInvoiceService::class);
        $service->generateDue(CarbonImmutable::parse('2026-01-01'));
        $service->generateDue(CarbonImmutable::parse('2026-01-01'));

        $this->assertSame(1, Invoice::where('recurring_profile_id', $profile->id)->count());
    }

    public function test_respects_max_occurrences_and_marks_completed(): void
    {
        $profile = $this->makeProfile([
            'max_occurrences'   => 2,
            'interval_unit'     => 'day',
            'interval_count'    => 1,
            'next_run_date'     => '2026-01-01',
        ]);

        $service = app(RecurringInvoiceService::class);
        $service->generateDue(CarbonImmutable::parse('2026-01-01'));
        $service->generateDue(CarbonImmutable::parse('2026-01-02'));
        // Third run must be a no-op because the profile is now completed.
        $service->generateDue(CarbonImmutable::parse('2026-01-03'));

        $profile->refresh();
        $this->assertSame(2, (int) $profile->occurrences_count);
        $this->assertSame('completed', $profile->status);
        $this->assertSame(2, Invoice::where('recurring_profile_id', $profile->id)->count());
    }

    public function test_respects_end_date_and_stops_at_boundary(): void
    {
        $profile = $this->makeProfile([
            'interval_unit'  => 'day',
            'interval_count' => 1,
            'next_run_date'  => '2026-01-01',
            'end_date'       => '2026-01-02',
        ]);

        $service = app(RecurringInvoiceService::class);
        $service->generateDue(CarbonImmutable::parse('2026-01-01'));
        $service->generateDue(CarbonImmutable::parse('2026-01-02'));
        $service->generateDue(CarbonImmutable::parse('2026-01-03'));

        $profile->refresh();
        $this->assertSame(2, Invoice::where('recurring_profile_id', $profile->id)->count());
        $this->assertSame('completed', $profile->status);
    }

    public function test_paused_profile_is_not_generated(): void
    {
        $profile = $this->makeProfile(['status' => 'paused']);

        app(RecurringInvoiceService::class)->generateDue(CarbonImmutable::parse('2026-01-01'));

        $this->assertDatabaseMissing('invoices', ['recurring_profile_id' => $profile->id]);
    }

    public function test_clamps_day_of_month_to_last_day_of_shorter_months(): void
    {
        // "31st of every month" starting 2026-01-31 should land on
        // 2026-02-28 (non-leap) rather than rolling into March.
        $profile = $this->makeProfile([
            'day_of_month'  => 31,
            'start_date'    => '2026-01-31',
            'next_run_date' => '2026-01-31',
        ]);

        $service = app(RecurringInvoiceService::class);
        $service->generateDue(CarbonImmutable::parse('2026-01-31'));

        $profile->refresh();
        $this->assertSame('2026-02-28', $profile->next_run_date->toDateString());
    }

    public function test_only_scans_the_requested_company(): void
    {
        $otherCompany = Company::create([
            'name' => 'Other',
            'email'=> 'o@o.example',
            'status'=> 'active',
        ]);
        $otherCustomer = Customer::create([
            'company_id'    => $otherCompany->id,
            'name'          => 'Other customer',
            'email'         => 'oc@example.com',
            'status'        => 'active',
            'customer_type' => 'business',
        ]);

        $profileA = $this->makeProfile();
        $profileB = RecurringInvoiceProfile::create([
            'company_id'           => $otherCompany->id,
            'customer_id'          => $otherCustomer->id,
            'name'                 => 'Other',
            'vat_regime'           => 'standard',
            'tax_rate'             => 0.19,
            'due_days_after_issue' => 14,
            'interval_unit'        => 'month',
            'interval_count'       => 1,
            'start_date'           => '2026-01-01',
            'next_run_date'        => '2026-01-01',
            'status'               => 'active',
        ]);
        RecurringInvoiceItem::create([
            'recurring_profile_id' => $profileB->id,
            'description'          => 'Foo',
            'quantity'             => 1,
            'unit_price'           => 50,
            'unit'                 => 'Stk.',
            'tax_rate'             => 0.19,
            'sort_order'           => 0,
        ]);

        app(RecurringInvoiceService::class)
            ->generateDue(CarbonImmutable::parse('2026-01-01'), $this->company->id);

        $this->assertSame(1, Invoice::where('recurring_profile_id', $profileA->id)->count());
        $this->assertSame(0, Invoice::where('recurring_profile_id', $profileB->id)->count());
    }

    public function test_run_once_catches_up_one_period_at_a_time_when_overdue(): void
    {
        // Daily profile due since Jan 1 that we generate on Jan 5. The current
        // design advances one period per call so the scheduler catches up
        // gradually rather than spamming five invoices in one shot.
        $profile = $this->makeProfile([
            'interval_unit'  => 'day',
            'interval_count' => 1,
            'start_date'     => '2026-01-01',
            'next_run_date'  => '2026-01-01',
        ]);

        app(RecurringInvoiceService::class)
            ->generateDue(CarbonImmutable::parse('2026-01-05'));

        $profile->refresh();
        $this->assertSame(1, (int) $profile->occurrences_count);
        $this->assertSame('2026-01-02', $profile->next_run_date->toDateString());
        $this->assertSame(1, Invoice::where('recurring_profile_id', $profile->id)->count());
    }

    public function test_controller_creates_profile(): void
    {
        $this->actingAs($this->user);

        $response = $this->post('/recurring-invoices', [
            'customer_id'    => $this->customer->id,
            'name'           => 'Website-Hosting',
            'interval_unit'  => 'month',
            'interval_count' => 1,
            'start_date'     => '2026-02-01',
            'items'          => [[
                'description' => 'Hosting-Gebühr',
                'quantity'    => 1,
                'unit_price'  => 25,
                'unit'        => 'Monat',
                'tax_rate'    => 0.19,
            ]],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('recurring_invoice_profiles', [
            'company_id' => $this->company->id,
            'name'       => 'Website-Hosting',
            'status'     => 'active',
        ]);
    }

    public function test_pause_and_resume_toggles_status(): void
    {
        $profile = $this->makeProfile();
        $this->actingAs($this->user);

        $this->post("/recurring-invoices/{$profile->id}/pause")->assertRedirect();
        $this->assertSame('paused', $profile->fresh()->status);

        $this->post("/recurring-invoices/{$profile->id}/resume")->assertRedirect();
        $this->assertSame('active', $profile->fresh()->status);
    }

    public function test_cross_tenant_access_is_forbidden(): void
    {
        $profile = $this->makeProfile();

        $otherCompany = Company::create([
            'name'  => 'Stranger',
            'email' => 's@s.example',
            'status'=> 'active',
        ]);
        $stranger = User::factory()->create([
            'company_id' => $otherCompany->id,
            'role'       => 'user',
        ]);
        $stranger->assignRole('user');

        $this->actingAs($stranger);

        $this->get("/recurring-invoices/{$profile->id}")->assertForbidden();
        $this->put("/recurring-invoices/{$profile->id}", [])->assertForbidden();
        $this->delete("/recurring-invoices/{$profile->id}")->assertForbidden();
    }
}
