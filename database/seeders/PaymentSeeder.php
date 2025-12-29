<?php

namespace Database\Seeders;

use App\Modules\Company\Models\Company;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Payment\Models\Payment;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            // Get paid and sent invoices for this company
            $invoices = Invoice::where('company_id', $company->id)
                ->whereIn('status', ['paid', 'sent'])
                ->with('user')
                ->get();

            if ($invoices->isEmpty()) {
                continue;
            }

            // Create payments for invoices
            foreach ($invoices as $invoice) {
                $user = $invoice->user;
                
                // Some invoices get full payment, some get partial payments
                $paymentType = collect(['full', 'full', 'full', 'partial'])->random();
                
                if ($paymentType === 'full') {
                    // Single full payment
                    $paymentDate = $invoice->issue_date->copy()->addDays(rand(1, 30));
                    
                    Payment::create([
                        'invoice_id' => $invoice->id,
                        'company_id' => $company->id,
                        'amount' => $invoice->total,
                        'payment_date' => $paymentDate,
                        'payment_method' => collect(['Überweisung', 'SEPA-Lastschrift', 'PayPal', 'Kreditkarte'])->random(),
                        'reference' => 'ZAH-' . strtoupper(uniqid()),
                        'notes' => rand(0, 1) ? 'Vollständige Zahlung erhalten' : null,
                        'status' => 'completed',
                        'created_by' => $user->id,
                    ]);
                } else {
                    // Partial payments (2-3 payments)
                    $numPayments = rand(2, 3);
                    $remainingAmount = $invoice->total;
                    $paymentDate = $invoice->issue_date->copy();
                    
                    for ($i = 0; $i < $numPayments; $i++) {
                        if ($i === $numPayments - 1) {
                            // Last payment covers remaining amount
                            $paymentAmount = $remainingAmount;
                        } else {
                            // Partial payment (30-60% of remaining)
                            $paymentAmount = round($remainingAmount * (rand(30, 60) / 100), 2);
                            $remainingAmount -= $paymentAmount;
                        }
                        
                        $paymentDate = $paymentDate->copy()->addDays(rand(1, 20));
                        
                        Payment::create([
                            'invoice_id' => $invoice->id,
                            'company_id' => $company->id,
                            'amount' => $paymentAmount,
                            'payment_date' => $paymentDate,
                            'payment_method' => collect(['Überweisung', 'SEPA-Lastschrift', 'PayPal'])->random(),
                            'reference' => 'ZAH-' . strtoupper(uniqid()),
                            'notes' => $i === 0 ? 'Teilzahlung' : ($i === $numPayments - 1 ? 'Abschlusszahlung' : 'Zwischenzahlung'),
                            'status' => 'completed',
                            'created_by' => $user->id,
                        ]);
                    }
                }
            }
        }
    }
}
