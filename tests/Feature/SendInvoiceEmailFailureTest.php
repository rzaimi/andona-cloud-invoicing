<?php

namespace Tests\Feature;

use App\Jobs\SendInvoiceEmail;
use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\User\Models\User;
use App\Services\InvoiceMailer;
use RuntimeException;
use Tests\TestCase;

class SendInvoiceEmailFailureTest extends TestCase
{
    public function test_queue_failure_keeps_the_original_exception(): void
    {
        $company = Company::create([
            'name' => 'Mail GmbH',
            'email' => 'mail@example.com',
            'status' => 'active',
        ]);
        $user = User::factory()->create(['company_id' => $company->id]);
        $customer = Customer::create([
            'company_id' => $company->id,
            'name' => 'Kunde',
            'email' => 'kunde@example.com',
            'status' => 'active',
            'customer_type' => 'business',
        ]);
        $invoice = Invoice::create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'number' => 'RE-2026-MAIL',
            'status' => 'draft',
            'issue_date' => now(),
            'due_date' => now()->addDays(14),
            'subtotal' => 100,
            'tax_rate' => 0.19,
            'tax_amount' => 19,
            'total' => 119,
        ]);

        $cause = new RuntimeException('Unable to set format');
        $mailer = $this->createMock(InvoiceMailer::class);
        $mailer->expects($this->once())->method('send')->willReturn([
            'ok' => false,
            'error' => 'Unable to set format (RuntimeException at /vendor/dompdf/dompdf/lib/Cpdf.php:6106)',
            'exception' => $cause,
        ]);

        $job = new SendInvoiceEmail($invoice->id, 'kunde@example.com');

        try {
            $job->handle($mailer);
            $this->fail('The job should rethrow the mailer failure.');
        } catch (RuntimeException $e) {
            $this->assertSame($cause, $e->getPrevious());
            $this->assertStringContainsString('Cpdf.php:6106', $e->getMessage());
        }
    }
}
