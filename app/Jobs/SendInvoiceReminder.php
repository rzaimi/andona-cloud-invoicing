<?php

namespace App\Jobs;

use App\Modules\Invoice\Models\Invoice;
use App\Modules\Mahnung\Services\DunningService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendInvoiceReminder implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * One pending reminder job per invoice: if the daily command runs again
     * before the queue drained (worker down, backlog), the re-dispatch is
     * dropped instead of queueing a duplicate Mahnung. The lock is released
     * when the job completes; uniqueFor is the stuck-job safety timeout.
     */
    public int $uniqueFor = 21600;

    public function __construct(
        public readonly string $invoiceId,
    ) {}

    public function uniqueId(): string
    {
        return $this->invoiceId;
    }

    public function handle(DunningService $dunning): void
    {
        $invoice = Invoice::find($this->invoiceId);

        if (! $invoice) {
            Log::warning('SendInvoiceReminder skipped: invoice missing', [
                'invoice_id' => $this->invoiceId,
            ]);

            return;
        }

        $result = $dunning->sendNext($invoice, respectThresholds: true);

        if (! $result['ok']) {
            Log::info('SendInvoiceReminder skipped: '.$result['error'], [
                'invoice_id' => $this->invoiceId,
            ]);
        }
    }
}
