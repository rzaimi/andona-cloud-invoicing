<?php

namespace App\Jobs;

use App\Modules\Invoice\Models\Invoice;
use App\Services\InvoiceMailer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendInvoiceEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly string $invoiceId,
        public readonly string $to,
        public readonly ?string $subject = null,
        public readonly ?string $customMessage = null,
        public readonly ?string $cc = null,
    ) {}

    public function handle(InvoiceMailer $mailer): void
    {
        $invoice = Invoice::with(['customer', 'company', 'items.product', 'layout', 'user', 'correctsInvoice'])
            ->find($this->invoiceId);

        if (! $invoice) {
            Log::warning('SendInvoiceEmail skipped: invoice not found', [
                'invoice_id' => $this->invoiceId,
            ]);

            return;
        }

        $result = $mailer->send(
            invoice: $invoice,
            to: $this->to,
            subject: $this->subject,
            customMessage: $this->customMessage,
            cc: $this->cc,
        );

        if (! ($result['ok'] ?? false)) {
            throw new \RuntimeException($result['error'] ?? 'Invoice email could not be sent.');
        }
    }
}
