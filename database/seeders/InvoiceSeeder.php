<?php

namespace Database\Seeders;

use App\Modules\Company\Models\Company;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Models\InvoiceItem;
use App\Modules\Invoice\Models\InvoiceAuditLog;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::with(['customers', 'users'])->get();

        $germanServices = [
            [
                'description' => 'Webentwicklung - Responsive Website',
                'unit' => 'Std.',
                'unit_price_range' => [80, 120],
                'quantity_range' => [20, 60],
            ],
            [
                'description' => 'SEO-Optimierung und Beratung',
                'unit' => 'Std.',
                'unit_price_range' => [90, 150],
                'quantity_range' => [10, 30],
            ],
            [
                'description' => 'Content Management System Setup',
                'unit' => 'Stk.',
                'unit_price_range' => [1500, 3500],
                'quantity_range' => [1, 1],
            ],
            [
                'description' => 'E-Commerce Shop Entwicklung',
                'unit' => 'Stk.',
                'unit_price_range' => [3000, 8000],
                'quantity_range' => [1, 1],
            ],
            [
                'description' => 'Wartung und Support',
                'unit' => 'Monat',
                'unit_price_range' => [200, 500],
                'quantity_range' => [1, 12],
            ],
            [
                'description' => 'Grafikdesign und Corporate Identity',
                'unit' => 'Std.',
                'unit_price_range' => [70, 100],
                'quantity_range' => [15, 40],
            ],
            [
                'description' => 'Datenbank-Design und -Optimierung',
                'unit' => 'Std.',
                'unit_price_range' => [100, 140],
                'quantity_range' => [8, 25],
            ],
            [
                'description' => 'Mobile App Entwicklung',
                'unit' => 'Stk.',
                'unit_price_range' => [5000, 15000],
                'quantity_range' => [1, 1],
            ],
        ];

        foreach ($companies as $company) {
            $customers = $company->customers;
            $users = $company->users;

            if ($customers->isEmpty() || $users->isEmpty()) {
                continue;
            }

            // Create one realistic Abschlag → Schlussrechnung chain per company
            $this->createAbschlagChain($company, $customers->first(), $users->first());

            // Create invoices
            for ($i = 1; $i <= 15; $i++) {
                $customer = $customers->random();
                $user = $users->random();
                $issueDate = Carbon::now()->subDays(rand(1, 120));
                $dueDate = $issueDate->copy()->addDays($company->getSetting('payment_terms', 14));

                // Generate invoice number before creating
                $prefix = $company->getSetting('invoice_prefix', 'RE-');
                $year = now()->year;
                $lastNumber = Invoice::where('company_id', $company->id)
                    ->whereYear('created_at', $year)
                    ->count() + 1;
                $invoiceNumber = $prefix . $year . '-' . str_pad($lastNumber, 4, '0', STR_PAD_LEFT);

                $invoice = Invoice::create([
                    'number' => $invoiceNumber,
                    'company_id' => $company->id,
                    'customer_id' => $customer->id,
                    'user_id' => $user->id,
                    'issue_date' => $issueDate,
                    'due_date' => $dueDate,
                    'status' => collect(['draft', 'sent', 'paid', 'overdue'])->random(),
                    'tax_rate' => $customer->customer_type === 'business' ?
                        $company->getSetting('tax_rate', 0.19) :
                        $company->getSetting('reduced_tax_rate', 0.07),
                    'notes' => $company->getSetting('invoice_footer'),
                    'payment_terms' => $company->getSetting('invoice_terms'),
                    'payment_method' => collect(['Überweisung', 'SEPA-Lastschrift', 'PayPal'])->random(),
                ]);

                // Create invoice items
                $selectedServices = collect($germanServices)->random(rand(1, 4));

                // German tax rates: 19% (standard), 7% (reduced), 0% (tax-free)
                $germanTaxRates = [0.19, 0.07, 0.00];
                
                foreach ($selectedServices as $index => $serviceData) {
                    $quantity = rand($serviceData['quantity_range'][0], $serviceData['quantity_range'][1]);
                    $unitPrice = rand($serviceData['unit_price_range'][0], $serviceData['unit_price_range'][1]);

                    // Randomly assign tax rates to demonstrate mixed-rate invoices
                    // 60% standard (19%), 30% reduced (7%), 10% tax-free (0%)
                    $rand = rand(1, 100);
                    if ($rand <= 60) {
                        $itemTaxRate = 0.19; // Standard rate
                    } elseif ($rand <= 90) {
                        $itemTaxRate = 0.07; // Reduced rate (books, food)
                    } else {
                        $itemTaxRate = 0.00; // Tax-free (exports, medical)
                    }

                    $item = new InvoiceItem([
                        'description' => $serviceData['description'],
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'unit' => $serviceData['unit'],
                        'tax_rate' => $itemTaxRate, // Item-level tax rate (new feature!)
                        'sort_order' => $index,
                    ]);
                    $item->calculateTotal();
                    $invoice->items()->save($item);
                }

                $invoice->calculateTotals();
                $invoice->save();

                // Create audit log entries to simulate realistic history
                $this->createAuditTrail($invoice, $user, $issueDate);
            }
        }
    }

    /**
     * Create a realistic Abschlag 1 → 2 → 3 → Schlussrechnung chain for a
     * construction project.  Numbers are chosen so each intermediate "Verbleibender
     * Betrag" (remaining amount) is always positive and the Schlussrechnung settles
     * the contract to a small final balance.
     *
     * Chain mechanics demonstrated:
     *   - Abschlag 1  : first partial invoice, no previous references
     *   - Abschlag 2  : references Abschlag 1 (new feature: abschlag→abschlag chain)
     *   - Abschlag 3  : standalone partial invoice (no back-refs, to keep numbers clean)
     *   - Schlussrechnung: full project scope, deducts all three Abschlags
     */
    private function createAbschlagChain($company, $customer, $user): void
    {
        $prefix   = $company->getSetting('invoice_prefix', 'RE-');
        $year     = now()->year;
        $taxRate  = $company->getSetting('tax_rate', 0.19);
        $terms    = $company->getSetting('invoice_terms');
        $footer   = $company->getSetting('invoice_footer');

        $bv = 'BV Neubau Musterstraße 12, Berlin';
        $an = 'AUF-' . $year . '-DEMO';

        $nextNumber = function () use ($company, $prefix, $year): string {
            $count = Invoice::where('company_id', $company->id)
                ->whereYear('created_at', $year)
                ->count() + 1;
            return $prefix . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
        };

        $makeInvoice = function (array $attrs) use ($company, $customer, $user, $taxRate, $terms, $footer, $nextNumber): Invoice {
            return Invoice::create(array_merge([
                'number'         => $nextNumber(),
                'company_id'     => $company->id,
                'customer_id'    => $customer->id,
                'user_id'        => $user->id,
                'tax_rate'       => $taxRate,
                'notes'          => $footer,
                'payment_terms'  => $terms,
                'payment_method' => 'Überweisung',
                'vat_regime'     => 'standard',
            ], $attrs));
        };

        $addItems = function (Invoice $inv, array $positions, float $itemTaxRate) use ($taxRate): void {
            foreach ($positions as $idx => $pos) {
                $item = new InvoiceItem([
                    'description' => $pos['description'],
                    'quantity'    => $pos['quantity'],
                    'unit'        => $pos['unit'],
                    'unit_price'  => $pos['unit_price'],
                    'tax_rate'    => $itemTaxRate,
                    'sort_order'  => $idx,
                ]);
                $item->calculateTotal();
                $inv->items()->save($item);
            }
            $inv->calculateTotals();
            $inv->save();
        };

        // ── All 10 contract positions (used partially in each Abschlag, fully in Schlussrechnung) ──
        $pos = [
            ['description' => 'Erdarbeiten und Ausschachtung', 'quantity' => 250, 'unit' => 'm³',  'unit_price' => 180.00],   // 45 000
            ['description' => 'Fundament und Bodenplatte',     'quantity' => 1,   'unit' => 'Psch.','unit_price' => 58000.00], // 58 000
            ['description' => 'Rohbau Mauerwerk EG',           'quantity' => 1,   'unit' => 'Psch.','unit_price' => 72000.00], // 72 000  → A1 total net 175 000
            ['description' => 'Rohbau Mauerwerk OG',           'quantity' => 1,   'unit' => 'Psch.','unit_price' => 68000.00], // 68 000
            ['description' => 'Rohbaudecken und Treppen',      'quantity' => 1,   'unit' => 'Psch.','unit_price' => 55000.00], // 55 000
            ['description' => 'Dachstuhl und Eindeckung',      'quantity' => 1,   'unit' => 'Psch.','unit_price' => 82000.00], // 82 000  → A2 total net 205 000
            ['description' => 'Fenster und Außentüren',        'quantity' => 12,  'unit' => 'Stk.', 'unit_price' =>  3200.00], // 38 400
            ['description' => 'Elektroinstallation',           'quantity' => 1,   'unit' => 'Psch.','unit_price' => 45000.00], // 45 000
            ['description' => 'Sanitär- und Heizungsanlage',   'quantity' => 1,   'unit' => 'Psch.','unit_price' => 68000.00], // 68 000  → A3 total net 151 400
            ['description' => 'Innenputz, Estrich und Fliesen','quantity' => 1,   'unit' => 'Psch.','unit_price' => 52000.00], // 52 000  → Schluss net 583 400
        ];

        // ── Abschlag 1 – Bauphase 1 (Erdarbeiten, Fundament, Rohbau EG) ──────────
        // Net: 175 000 €  |  no prior refs
        $baseDate = Carbon::now()->subDays(90);
        $a1 = $makeInvoice([
            'issue_date'     => $baseDate,
            'due_date'       => $baseDate->copy()->addDays(14),
            'status'         => 'paid',
            'invoice_type'   => 'abschlagsrechnung',
            'sequence_number'=> 1,
            'bauvorhaben'    => $bv,
            'auftragsnummer' => $an,
        ]);
        $addItems($a1, array_slice($pos, 0, 3), $taxRate);
        $this->createAuditTrail($a1, $user, $baseDate);

        // ── Abschlag 2 – Bauphase 2 (Rohbau OG, Decken, Dachstuhl) ─────────────
        // Net: 205 000 €  |  refs A1 (175 000) → Verbleibender Betrag = 30 000
        $a2Date = $baseDate->copy()->addDays(30);
        $a2 = $makeInvoice([
            'issue_date'     => $a2Date,
            'due_date'       => $a2Date->copy()->addDays(14),
            'status'         => 'paid',
            'invoice_type'   => 'abschlagsrechnung',
            'sequence_number'=> 2,
            'bauvorhaben'    => $bv,
            'auftragsnummer' => $an,
            'abschlag_refs'  => [[
                'invoice_id' => $a1->id,
                'number'     => $a1->number,
                'amount'     => (float) $a1->total,   // gross incl. VAT
                'date'       => $a1->issue_date->format('Y-m-d'),
            ]],
        ]);
        $addItems($a2, array_slice($pos, 3, 3), $taxRate);
        $this->createAuditTrail($a2, $user, $a2Date);

        // ── Abschlag 3 – Bauphase 3 (Fenster, Elektro, Sanitär) ─────────────────
        // Net: 151 400 €  |  standalone partial (no back-refs to keep math clean)
        $a3Date = $baseDate->copy()->addDays(60);
        $a3 = $makeInvoice([
            'issue_date'     => $a3Date,
            'due_date'       => $a3Date->copy()->addDays(14),
            'status'         => 'sent',
            'invoice_type'   => 'abschlagsrechnung',
            'sequence_number'=> 3,
            'bauvorhaben'    => $bv,
            'auftragsnummer' => $an,
        ]);
        $addItems($a3, array_slice($pos, 6, 3), $taxRate);
        $this->createAuditTrail($a3, $user, $a3Date);

        // ── Schlussrechnung – Gesamtabrechnung ───────────────────────────────────
        // Full scope (all 10 positions), net 583 400 €
        // Deducts A1 + A2 + A3 gross totals → small final balance remains
        $schlussDate = $baseDate->copy()->addDays(80);
        $schluss = $makeInvoice([
            'issue_date'     => $schlussDate,
            'due_date'       => $schlussDate->copy()->addDays(30),
            'status'         => 'sent',
            'invoice_type'   => 'schlussrechnung',
            'bauvorhaben'    => $bv,
            'auftragsnummer' => $an,
            'abschlag_refs'  => [
                [
                    'invoice_id' => $a1->id,
                    'number'     => $a1->number,
                    'amount'     => (float) $a1->total,
                    'date'       => $a1->issue_date->format('Y-m-d'),
                ],
                [
                    'invoice_id' => $a2->id,
                    'number'     => $a2->number,
                    'amount'     => (float) $a2->total,
                    'date'       => $a2->issue_date->format('Y-m-d'),
                ],
                [
                    'invoice_id' => $a3->id,
                    'number'     => $a3->number,
                    'amount'     => (float) $a3->total,
                    'date'       => $a3->issue_date->format('Y-m-d'),
                ],
            ],
        ]);
        $addItems($schluss, $pos, $taxRate); // all 10 positions
        $this->createAuditTrail($schluss, $user, $schlussDate);
    }

    /**
     * Create realistic audit trail for an invoice based on its status
     */
    private function createAuditTrail(Invoice $invoice, $user, Carbon $issueDate): void
    {
        $itemCount = $invoice->items()->count();
        
        // 1. Always create "created" entry
        InvoiceAuditLog::create([
            'invoice_id' => $invoice->id,
            'user_id' => $user->id,
            'action' => 'created',
            'old_status' => null,
            'new_status' => 'draft',
            'changes' => null,
            'notes' => "Invoice created with {$itemCount} items",
            'ip_address' => $this->randomIp(),
            'user_agent' => $this->randomUserAgent(),
            'created_at' => $issueDate,
            'updated_at' => $issueDate,
        ]);

        // 2. If status is not draft, create status transition entries
        if ($invoice->status !== 'draft') {
            // Some invoices may have been updated before sending
            if (rand(0, 1)) {
                $updateTime = $issueDate->copy()->addMinutes(rand(5, 30));
                InvoiceAuditLog::create([
                    'invoice_id' => $invoice->id,
                    'user_id' => $user->id,
                    'action' => 'updated',
                    'old_status' => 'draft',
                    'new_status' => 'draft',
                    'changes' => [
                        'notes' => ['old' => null, 'new' => $invoice->notes],
                    ],
                    'notes' => "Invoice details updated",
                    'ip_address' => $this->randomIp(),
                    'user_agent' => $this->randomUserAgent(),
                    'created_at' => $updateTime,
                    'updated_at' => $updateTime,
                ]);
            }

            // Sent status
            $sentTime = $issueDate->copy()->addMinutes(rand(30, 120));
            InvoiceAuditLog::create([
                'invoice_id' => $invoice->id,
                'user_id' => $user->id,
                'action' => 'sent',
                'old_status' => 'draft',
                'new_status' => 'sent',
                'changes' => ['email' => $invoice->customer->email],
                'notes' => "Invoice sent via email to {$invoice->customer->email}",
                'ip_address' => $this->randomIp(),
                'user_agent' => $this->randomUserAgent(),
                'created_at' => $sentTime,
                'updated_at' => $sentTime,
            ]);

            // If paid, create payment entry
            if ($invoice->status === 'paid') {
                $paidTime = $sentTime->copy()->addDays(rand(3, 14));
                InvoiceAuditLog::create([
                    'invoice_id' => $invoice->id,
                    'user_id' => null, // System action
                    'action' => 'paid',
                    'old_status' => 'sent',
                    'new_status' => 'paid',
                    'changes' => [
                        'amount' => $invoice->total,
                        'total_paid' => $invoice->total,
                    ],
                    'notes' => "Invoice automatically marked as paid after receiving payment of " . number_format($invoice->total, 2) . " EUR",
                    'ip_address' => $this->randomIp(),
                    'user_agent' => 'System',
                    'created_at' => $paidTime,
                    'updated_at' => $paidTime,
                ]);
            }

            // If overdue, create overdue entry
            if ($invoice->status === 'overdue') {
                $overdueTime = $invoice->due_date->copy()->addDay();
                InvoiceAuditLog::create([
                    'invoice_id' => $invoice->id,
                    'user_id' => null, // System action
                    'action' => 'status_changed',
                    'old_status' => 'sent',
                    'new_status' => 'overdue',
                    'changes' => null,
                    'notes' => "Invoice automatically marked as overdue after due date passed",
                    'ip_address' => $this->randomIp(),
                    'user_agent' => 'System',
                    'created_at' => $overdueTime,
                    'updated_at' => $overdueTime,
                ]);
            }
        }
    }

    /**
     * Generate random IP address for demo data
     */
    private function randomIp(): string
    {
        return rand(10, 192) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 254);
    }

    /**
     * Generate random user agent for demo data
     */
    private function randomUserAgent(): string
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15',
        ];

        return $userAgents[array_rand($userAgents)];
    }
}
