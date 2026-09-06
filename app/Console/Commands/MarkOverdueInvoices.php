<?php

namespace App\Console\Commands;

use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Models\InvoiceAuditLog;
use Illuminate\Console\Command;

class MarkOverdueInvoices extends Command
{
    protected $signature = 'invoices:mark-overdue
                            {--company= : Mark invoices for a specific company only}';

    protected $description = 'Reconcile sent invoices past their due date: fully paid ones become paid, open ones become overdue';

    public function handle(): int
    {
        // Sent AND overdue invoices are reconciled: either can turn out to be
        // fully covered by payments recorded outside the normal flow.
        $query = Invoice::whereIn('status', ['sent', 'overdue'])
            ->whereDate('due_date', '<', now()->toDateString());

        if ($company = $this->option('company')) {
            $query->where('company_id', $company);
        }

        $overdue = 0;
        $paid = 0;

        // Per-invoice updates so every status change lands in the GoBD audit log.
        $query->orderBy('id')->chunkById(100, function ($invoices) use (&$overdue, &$paid) {
            foreach ($invoices as $invoice) {
                // Payments are the source of truth: an invoice covered by
                // completed payments must never be flagged overdue (or dunned),
                // even when the status column was left stale.
                $oldStatus = $invoice->status;

                if ($invoice->getPaidAmount() >= (float) $invoice->total) {
                    $invoice->update(['status' => 'paid']);

                    InvoiceAuditLog::log(
                        $invoice->id,
                        'status_changed',
                        $oldStatus,
                        'paid',
                        null,
                        'Automatisch als bezahlt markiert (Zahlungen decken den Rechnungsbetrag)'
                    );

                    $paid++;

                    continue;
                }

                if ($oldStatus === 'overdue') {
                    continue;
                }

                $invoice->update(['status' => 'overdue']);

                InvoiceAuditLog::log(
                    $invoice->id,
                    'status_changed',
                    'sent',
                    'overdue',
                    null,
                    'Automatisch als überfällig markiert (Fälligkeitsdatum überschritten)'
                );

                $overdue++;
            }
        });

        $this->info("{$overdue} invoice(s) marked overdue, {$paid} reconciled as paid.");

        return self::SUCCESS;
    }
}
