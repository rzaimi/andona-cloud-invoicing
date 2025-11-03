<?php

namespace App\Http\Controllers;

use App\Modules\Company\Models\Company;
use Carbon\Carbon;

class EmailTemplateController extends Controller
{
    /**
     * Create a sample invoice object with all required methods
     */
    private function createSampleInvoice(int $daysOverdue = 0, float $reminderFee = 0.0): object
    {
        $dueDate = now()->subDays($daysOverdue);
        $issueDate = $dueDate->copy()->subDays(30);

        return new class($issueDate, $dueDate, $daysOverdue, $reminderFee) {
            public $id = 'sample-001';
            public $number = 'RE-2024-0001';
            public $status = 'sent';
            public $subtotal = 125.00;
            public $tax_rate = 0.19;
            public $tax_amount = 23.75;
            public $total = 148.75;
            public $notes = 'Vielen Dank für Ihren Auftrag!';
            public $items;
            public $issue_date;
            public $due_date;
            public $reminder_fee;

            private $days_overdue;

            public function __construct(Carbon $issueDate, Carbon $dueDate, int $daysOverdue, float $reminderFee)
            {
                $this->issue_date = $issueDate;
                $this->due_date = $dueDate;
                $this->days_overdue = $daysOverdue;
                $this->reminder_fee = $reminderFee;

                $this->items = collect([
                    (object) [
                        'id' => 'item-001',
                        'description' => 'Beispielprodukt',
                        'quantity' => 2,
                        'unit' => 'Stk.',
                        'unit_price' => 50.00,
                        'total' => 100.00,
                    ],
                    (object) [
                        'id' => 'item-002',
                        'description' => 'Weiteres Produkt',
                        'quantity' => 1,
                        'unit' => 'Stk.',
                        'unit_price' => 25.00,
                        'total' => 25.00,
                    ],
                ]);
            }

            public function getDaysOverdue(): int
            {
                return $this->days_overdue;
            }
        };
    }

    /**
     * Get sample data for email previews
     */
    private function getSampleData(int $daysOverdue = 0, float $reminderFee = 0.0): array
    {
        $companyId = $this->getEffectiveCompanyId();
        $companyModel = Company::find($companyId);

        // Create a company object with all required properties, using actual data or fallbacks
        $iban = $companyModel ? ($companyModel->bank_iban ?? 'DE89 3704 0044 0532 0130 00') : 'DE89 3704 0044 0532 0130 00';
        $bic = $companyModel ? ($companyModel->bank_bic ?? 'COBADEFFXXX') : 'COBADEFFXXX';
        
        $company = (object) [
            'id' => $companyModel?->id ?? $companyId ?? 'sample-company',
            'name' => $companyModel?->name ?? 'Musterfirma GmbH',
            'email' => $companyModel?->email ?? 'info@musterfirma.de',
            'phone' => $companyModel?->phone ?? '+49 (0) 123 456789',
            'address' => $companyModel?->address ?? 'Musterstraße 123',
            'postal_code' => $companyModel?->postal_code ?? '12345',
            'city' => $companyModel?->city ?? 'Musterstadt',
            'country' => $companyModel?->country ?? 'Deutschland',
            'website' => $companyModel?->website ?? 'https://www.musterfirma.de',
            'tax_number' => $companyModel?->tax_number ?? '123/456/78901',
            'vat_number' => $companyModel?->vat_number ?? 'DE123456789',
            'iban' => $iban,
            'bic' => $bic,
        ];

        $sampleInvoice = $this->createSampleInvoice($daysOverdue, $reminderFee);

        $sampleOffer = (object) [
            'id' => 'sample-offer-001',
            'number' => 'AN-2024-0001',
            'status' => 'sent',
            'issue_date' => now(),
            'valid_until' => now()->addDays(30),
            'subtotal' => 250.00,
            'tax_rate' => 0.19,
            'tax_amount' => 47.50,
            'total' => 297.50,
            'notes' => 'Dieses Angebot ist 30 Tage gültig.',
            'items' => collect([
                (object) [
                    'id' => 'item-001',
                    'description' => 'Beispielservice',
                    'quantity' => 1,
                    'unit' => 'Std.',
                    'unit_price' => 250.00,
                    'total' => 250.00,
                ],
            ]),
        ];

        $sampleCustomer = (object) [
            'id' => 'customer-001',
            'name' => 'Musterkunde GmbH',
            'contact_person' => 'Herr Mustermann',
            'email' => 'kunde@example.com',
            'phone' => '+49 (0) 987 654321',
            'address' => 'Kundenstraße 456',
            'postal_code' => '54321',
            'city' => 'Kundenstadt',
            'country' => 'Deutschland',
            'number' => 'KU-2024-0001',
        ];

        // Attach relationships
        $sampleInvoice->customer = $sampleCustomer;
        $sampleOffer->customer = $sampleCustomer;

        return [
            'company' => $company,
            'invoice' => $sampleInvoice,
            'offer' => $sampleOffer,
            'customer' => $sampleCustomer,
        ];
    }

    public function previewInvoiceSent()
    {
        $data = $this->getSampleData();
        return view('emails.invoice-sent', [
            'invoice' => $data['invoice'],
            'company' => $data['company'],
            'customer' => $data['customer'],
            'preview' => true,
        ]);
    }

    public function previewInvoiceReminder()
    {
        $data = $this->getSampleData();
        return view('emails.invoice-reminder', [
            'invoice' => $data['invoice'],
            'company' => $data['company'],
            'customer' => $data['customer'],
            'preview' => true,
        ]);
    }

    public function previewOfferSent()
    {
        $data = $this->getSampleData();
        return view('emails.offer-sent', [
            'offer' => $data['offer'],
            'company' => $data['company'],
            'customer' => $data['customer'],
            'preview' => true,
        ]);
    }

    public function previewOfferAccepted()
    {
        $data = $this->getSampleData();
        return view('emails.offer-accepted', [
            'offer' => $data['offer'],
            'company' => $data['company'],
            'customer' => $data['customer'],
            'preview' => true,
        ]);
    }

    public function previewOfferReminder()
    {
        $data = $this->getSampleData();
        return view('emails.offer-reminder', [
            'offer' => $data['offer'],
            'company' => $data['company'],
            'customer' => $data['customer'],
            'preview' => true,
        ]);
    }

    public function previewPaymentReceived()
    {
        $data = $this->getSampleData();
        return view('emails.payment-received', [
            'invoice' => $data['invoice'],
            'company' => $data['company'],
            'customer' => $data['customer'],
            'preview' => true,
        ]);
    }

    public function previewWelcome()
    {
        $data = $this->getSampleData();
        return view('emails.welcome', [
            'company' => $data['company'],
            'customer' => $data['customer'],
            'preview' => true,
        ]);
    }

    public function previewFriendlyReminder()
    {
        $daysOverdue = 5;
        $data = $this->getSampleData($daysOverdue);
        return view('emails.reminders.friendly', [
            'invoice' => $data['invoice'],
            'company' => $data['company'],
            'customer' => $data['customer'],
            'days_overdue' => $daysOverdue,
            'preview' => true,
        ]);
    }

    public function previewMahnung1()
    {
        $daysOverdue = 15;
        $fee = 5.00; // Standard fee for 1. Mahnung
        $data = $this->getSampleData($daysOverdue, 0.0); // No previous fees for 1. Mahnung
        return view('emails.reminders.mahnung-1', [
            'invoice' => $data['invoice'],
            'company' => $data['company'],
            'customer' => $data['customer'],
            'days_overdue' => $daysOverdue,
            'fee' => $fee,
            'preview' => true,
        ]);
    }

    public function previewMahnung2()
    {
        $daysOverdue = 30;
        $previousFee = 5.00; // Fee from 1. Mahnung
        $fee = 10.00; // Standard fee for 2. Mahnung
        $data = $this->getSampleData($daysOverdue, $previousFee);
        return view('emails.reminders.mahnung-2', [
            'invoice' => $data['invoice'],
            'company' => $data['company'],
            'customer' => $data['customer'],
            'days_overdue' => $daysOverdue,
            'fee' => $fee,
            'preview' => true,
        ]);
    }

    public function previewMahnung3()
    {
        $daysOverdue = 45;
        $previousFee = 15.00; // Fees from 1. + 2. Mahnung (5 + 10)
        $fee = 15.00; // Standard fee for 3. Mahnung
        $data = $this->getSampleData($daysOverdue, $previousFee);
        return view('emails.reminders.mahnung-3', [
            'invoice' => $data['invoice'],
            'company' => $data['company'],
            'customer' => $data['customer'],
            'days_overdue' => $daysOverdue,
            'fee' => $fee,
            'preview' => true,
        ]);
    }

    public function previewInkasso()
    {
        $daysOverdue = 60;
        $data = $this->getSampleData($daysOverdue);
        return view('emails.reminders.inkasso', [
            'invoice' => $data['invoice'],
            'company' => $data['company'],
            'customer' => $data['customer'],
            'days_overdue' => $daysOverdue,
            'preview' => true,
        ]);
    }
}

