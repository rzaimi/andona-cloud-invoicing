<?php

namespace Database\Seeders;

use App\Models\EmailLog;
use App\Modules\Company\Models\Company;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use Illuminate\Database\Seeder;

class EmailLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            $customers = Customer::where('company_id', $company->id)->limit(5)->get();
            $invoices = Invoice::where('company_id', $company->id)->limit(10)->get();

            if ($customers->isEmpty()) {
                continue;
            }

            // Create various email logs for this company
            $this->createInvoiceEmails($company, $customers, $invoices);
            $this->createMahnungEmails($company, $customers, $invoices);
            $this->createOfferEmails($company, $customers);
            $this->createReminderEmails($company, $customers, $invoices);
            $this->createPaymentConfirmations($company, $customers, $invoices);
        }

        $this->command->info('✅ Email logs seeded successfully!');
    }

    /**
     * Create invoice email logs
     */
    private function createInvoiceEmails($company, $customers, $invoices)
    {
        $count = min(8, $customers->count());
        
        for ($i = 0; $i < $count; $i++) {
            $customer = $customers->random();
            $invoice = $invoices->isNotEmpty() ? $invoices->random() : null;
            
            EmailLog::create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'recipient_email' => $customer->email,
                'recipient_name' => $customer->name,
                'subject' => "Rechnung " . ($invoice ? $invoice->number : 'RE-' . rand(1000, 9999)),
                'body' => "Sehr geehrte Damen und Herren,\n\nanbei erhalten Sie die Rechnung für unsere Leistungen.\n\nMit freundlichen Grüßen\n" . $company->name,
                'type' => 'invoice',
                'related_type' => 'Invoice',
                'related_id' => $invoice?->id,
                'status' => 'sent',
                'metadata' => [
                    'invoice_number' => $invoice ? $invoice->number : 'RE-' . rand(1000, 9999),
                    'invoice_total' => $invoice ? $invoice->total : rand(100, 5000),
                    'has_pdf_attachment' => true,
                ],
                'sent_at' => now()->subDays(rand(1, 30))->subHours(rand(0, 23)),
            ]);
        }
    }

    /**
     * Create Mahnung email logs
     */
    private function createMahnungEmails($company, $customers, $invoices)
    {
        $mahnungTypes = [
            ['level' => 1, 'name' => 'Freundliche Erinnerung', 'fee' => 0, 'days' => 7],
            ['level' => 2, 'name' => '1. Mahnung', 'fee' => 5.00, 'days' => 14],
            ['level' => 3, 'name' => '2. Mahnung', 'fee' => 10.00, 'days' => 21],
            ['level' => 4, 'name' => '3. Mahnung', 'fee' => 15.00, 'days' => 30],
            ['level' => 5, 'name' => 'Inkasso', 'fee' => 0, 'days' => 45],
        ];

        $count = min(10, $customers->count());
        
        for ($i = 0; $i < $count; $i++) {
            $customer = $customers->random();
            $invoice = $invoices->isNotEmpty() ? $invoices->random() : null;
            $mahnung = $mahnungTypes[array_rand($mahnungTypes)];
            
            EmailLog::create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'recipient_email' => $customer->email,
                'recipient_name' => $customer->name,
                'subject' => $mahnung['name'] . " - Rechnung " . ($invoice ? $invoice->number : 'RE-' . rand(1000, 9999)),
                'body' => null,
                'type' => 'mahnung',
                'related_type' => 'Invoice',
                'related_id' => $invoice?->id,
                'status' => 'sent',
                'metadata' => [
                    'reminder_level' => $mahnung['level'],
                    'reminder_level_name' => $mahnung['name'],
                    'invoice_number' => $invoice ? $invoice->number : 'RE-' . rand(1000, 9999),
                    'invoice_total' => $invoice ? $invoice->total : rand(100, 5000),
                    'reminder_fee' => $mahnung['fee'],
                    'days_overdue' => $mahnung['days'] + rand(0, 5),
                    'has_pdf_attachment' => true,
                ],
                'sent_at' => now()->subDays(rand(1, 45))->subHours(rand(0, 23)),
            ]);
        }
    }

    /**
     * Create offer email logs
     */
    private function createOfferEmails($company, $customers)
    {
        $count = min(5, $customers->count());
        
        for ($i = 0; $i < $count; $i++) {
            $customer = $customers->random();
            $offerNumber = 'AN-' . date('Y') . '-' . str_pad(rand(1, 999), 4, '0', STR_PAD_LEFT);
            
            EmailLog::create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'recipient_email' => $customer->email,
                'recipient_name' => $customer->name,
                'subject' => "Angebot " . $offerNumber,
                'body' => "Sehr geehrte Damen und Herren,\n\nvielen Dank für Ihre Anfrage. Anbei erhalten Sie unser Angebot.\n\nWir freuen uns auf Ihre Rückmeldung.\n\nMit freundlichen Grüßen\n" . $company->name,
                'type' => 'offer',
                'related_type' => 'Offer',
                'related_id' => null,
                'status' => 'sent',
                'metadata' => [
                    'offer_number' => $offerNumber,
                    'offer_total' => rand(500, 8000),
                    'valid_until' => now()->addDays(30)->format('d.m.Y'),
                    'has_pdf_attachment' => true,
                ],
                'sent_at' => now()->subDays(rand(1, 20))->subHours(rand(0, 23)),
            ]);
        }
    }

    /**
     * Create reminder email logs
     */
    private function createReminderEmails($company, $customers, $invoices)
    {
        $count = min(4, $customers->count());
        
        for ($i = 0; $i < $count; $i++) {
            $customer = $customers->random();
            $invoice = $invoices->isNotEmpty() ? $invoices->random() : null;
            
            EmailLog::create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'recipient_email' => $customer->email,
                'recipient_name' => $customer->name,
                'subject' => "Zahlungserinnerung - Rechnung " . ($invoice ? $invoice->number : 'RE-' . rand(1000, 9999)),
                'body' => "Sehr geehrte Damen und Herren,\n\nwir möchten Sie freundlich daran erinnern, dass die Zahlung für die Rechnung " . ($invoice ? $invoice->number : 'RE-' . rand(1000, 9999)) . " in 3 Tagen fällig ist.\n\nVielen Dank für Ihre Aufmerksamkeit.\n\nMit freundlichen Grüßen\n" . $company->name,
                'type' => 'reminder',
                'related_type' => 'Invoice',
                'related_id' => $invoice?->id,
                'status' => 'sent',
                'metadata' => [
                    'invoice_number' => $invoice ? $invoice->number : 'RE-' . rand(1000, 9999),
                    'days_until_due' => 3,
                    'has_pdf_attachment' => false,
                ],
                'sent_at' => now()->subDays(rand(1, 15))->subHours(rand(0, 23)),
            ]);
        }
    }

    /**
     * Create payment confirmation email logs
     */
    private function createPaymentConfirmations($company, $customers, $invoices)
    {
        $count = min(3, $customers->count());
        
        for ($i = 0; $i < $count; $i++) {
            $customer = $customers->random();
            $invoice = $invoices->isNotEmpty() ? $invoices->random() : null;
            
            EmailLog::create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'recipient_email' => $customer->email,
                'recipient_name' => $customer->name,
                'subject' => "Zahlungsbestätigung - Rechnung " . ($invoice ? $invoice->number : 'RE-' . rand(1000, 9999)),
                'body' => "Sehr geehrte Damen und Herren,\n\nwir bestätigen den Eingang Ihrer Zahlung für Rechnung " . ($invoice ? $invoice->number : 'RE-' . rand(1000, 9999)) . ".\n\nVielen Dank für Ihr Vertrauen.\n\nMit freundlichen Grüßen\n" . $company->name,
                'type' => 'payment_received',
                'related_type' => 'Invoice',
                'related_id' => $invoice?->id,
                'status' => 'sent',
                'metadata' => [
                    'invoice_number' => $invoice ? $invoice->number : 'RE-' . rand(1000, 9999),
                    'payment_amount' => $invoice ? $invoice->total : rand(100, 5000),
                    'payment_date' => now()->subDays(rand(1, 10))->format('d.m.Y'),
                    'has_pdf_attachment' => false,
                ],
                'sent_at' => now()->subDays(rand(1, 10))->subHours(rand(0, 23)),
            ]);
        }

        // Add one or two failed emails for demonstration
        if ($customers->count() > 0) {
            $customer = $customers->random();
            
            EmailLog::create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'recipient_email' => 'invalid-email@nonexistent-domain-xyz123.com',
                'recipient_name' => $customer->name,
                'subject' => "Rechnung RE-" . rand(1000, 9999),
                'body' => null,
                'type' => 'invoice',
                'related_type' => 'Invoice',
                'related_id' => null,
                'status' => 'failed',
                'error_message' => 'SMTP Error: Could not connect to SMTP host. Connection refused.',
                'metadata' => [
                    'invoice_number' => 'RE-' . rand(1000, 9999),
                    'has_pdf_attachment' => true,
                ],
                'sent_at' => now()->subDays(rand(1, 5))->subHours(rand(0, 23)),
            ]);
        }
    }
}
