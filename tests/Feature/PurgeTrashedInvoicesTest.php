<?php

namespace Tests\Feature;

use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\User\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PurgeTrashedInvoicesTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected Customer $customer;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'name' => 'Purge Test Company',
            'email' => 'purge@example.com',
            'status' => 'active',
        ]);

        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Purge Customer',
            'email' => 'purge-customer@example.com',
            'status' => 'active',
            'customer_type' => 'business',
        ]);
    }

    /**
     * Re-create the old-schema deleted_at column, as found on a database
     * that has not yet run the remove-soft-deletes migration.
     */
    private function addLegacyDeletedAtColumn(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    private function makeInvoice(string $number, ?string $deletedAt = null): Invoice
    {
        $invoice = Invoice::create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'number' => $number,
            'status' => 'draft',
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'subtotal' => 100.00,
            'tax_rate' => 0.19,
            'tax_amount' => 19.00,
            'total' => 119.00,
        ]);

        if ($deletedAt !== null) {
            DB::table('invoices')->where('id', $invoice->id)->update(['deleted_at' => $deletedAt]);
        }

        return $invoice;
    }

    public function test_exits_gracefully_when_column_is_already_gone()
    {
        $this->artisan('invoices:purge-trashed')
            ->expectsOutputToContain('nothing to purge')
            ->assertExitCode(0);
    }

    public function test_purges_trashed_invoices_and_leaves_active_ones()
    {
        $this->addLegacyDeletedAtColumn();

        $trashed = $this->makeInvoice('RE-2024-T1', now()->toDateTimeString());
        $active = $this->makeInvoice('RE-2024-A1');

        $this->artisan('invoices:purge-trashed', ['--force' => true])
            ->assertExitCode(0);

        $this->assertDatabaseMissing('invoices', ['id' => $trashed->id]);
        $this->assertDatabaseHas('invoices', ['id' => $active->id]);
    }

    public function test_dry_run_deletes_nothing()
    {
        $this->addLegacyDeletedAtColumn();

        $trashed = $this->makeInvoice('RE-2024-T2', now()->toDateTimeString());

        $this->artisan('invoices:purge-trashed', ['--dry-run' => true])
            ->assertExitCode(0);

        $this->assertDatabaseHas('invoices', ['id' => $trashed->id]);
    }

    public function test_days_option_only_purges_old_enough_invoices()
    {
        $this->addLegacyDeletedAtColumn();

        $old = $this->makeInvoice('RE-2024-T3', now()->subDays(40)->toDateTimeString());
        $recent = $this->makeInvoice('RE-2024-T4', now()->toDateTimeString());

        $this->artisan('invoices:purge-trashed', ['--days' => 30, '--force' => true])
            ->assertExitCode(0);

        $this->assertDatabaseMissing('invoices', ['id' => $old->id]);
        $this->assertDatabaseHas('invoices', ['id' => $recent->id]);
    }

    public function test_company_option_scopes_the_purge()
    {
        $this->addLegacyDeletedAtColumn();

        $trashed = $this->makeInvoice('RE-2024-T5', now()->toDateTimeString());

        $otherCompany = Company::create([
            'name' => 'Other Company',
            'email' => 'other@example.com',
            'status' => 'active',
        ]);

        $this->artisan('invoices:purge-trashed', [
            '--company' => $otherCompany->id,
            '--force' => true,
        ])->assertExitCode(0);

        // Trashed invoice belongs to a different company — untouched.
        $this->assertDatabaseHas('invoices', ['id' => $trashed->id]);
    }
}
