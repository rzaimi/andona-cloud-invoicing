<?php

namespace App\Modules\Invoice\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customer\Models\Customer;
use App\Modules\Invoice\Models\Invoice;
use App\Modules\Invoice\Models\InvoiceItem;
use App\Modules\Invoice\Models\InvoiceLayout;
use App\Traits\LogsEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    use LogsEmails;

    public function index(Request $request)
    {
        $companyId = $this->getEffectiveCompanyId();
        
        $query = Invoice::forCompany($companyId)
            ->with(['customer:id,name', 'user:id,name'])
            ->select('invoices.*'); // Ensure all invoice fields including is_correction are selected
        
        // Apply status filter
        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        // Apply search filter
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('number', 'like', "%{$request->search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($request) {
                        $customerQuery->where('name', 'like', "%{$request->search}%");
                    });
            });
        }
        
        $invoices = $query->latest()->paginate(15)->withQueryString();

        // Calculate statistics
        $stats = [
            'total' => Invoice::forCompany($companyId)->count(),
            'draft' => Invoice::forCompany($companyId)->where('status', 'draft')->count(),
            'sent' => Invoice::forCompany($companyId)->where('status', 'sent')->count(),
            'paid' => Invoice::forCompany($companyId)->where('status', 'paid')->count(),
            'overdue' => Invoice::forCompany($companyId)->where('status', 'overdue')->count(),
            'cancelled' => Invoice::forCompany($companyId)->where('status', 'cancelled')->count(),
        ];

        return Inertia::render('invoices/index', [
            'invoices' => $invoices,
            'filters' => $request->only(['status', 'search']),
            'stats' => $stats,
        ]);
    }

    public function create()
    {
        $companyId = $this->getEffectiveCompanyId();
        $customers = Customer::forCompany($companyId)
            ->active()
            ->select('id', 'name', 'email')
            ->get();

        $layouts = InvoiceLayout::forCompany($companyId)
            ->get();

        $products = \App\Modules\Product\Models\Product::where('company_id', $companyId)
            ->where('status', 'active')
            ->select('id', 'name', 'description', 'price', 'unit', 'tax_rate', 'sku', 'number')
            ->orderBy('name')
            ->get();

        return Inertia::render('invoices/create', [
            'customers' => $customers,
            'layouts' => $layouts,
            'products' => $products,
            'settings' => \App\Modules\Company\Models\Company::find($this->getEffectiveCompanyId())->getDefaultSettings(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'layout_id' => 'nullable|exists:invoice_layouts,id',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.unit' => 'nullable|string|max:10',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $user = $request->user();
            $effectiveCompanyId = $this->getEffectiveCompanyId();
            $company = \App\Modules\Company\Models\Company::find($effectiveCompanyId);

            // Generate invoice number before creating
            $prefix = $company->getSetting('invoice_prefix', 'RE-');
            $year = now()->year;
            $lastNumber = Invoice::where('company_id', $effectiveCompanyId)
                    ->whereYear('created_at', $year)
                    ->count() + 1;
            $invoiceNumber = $prefix . $year . '-' . str_pad($lastNumber, 4, '0', STR_PAD_LEFT);

            // Create invoice
            $invoice = Invoice::create([
                'number' => $invoiceNumber,
                'company_id' => $effectiveCompanyId,
                'customer_id' => $validated['customer_id'],
                'user_id' => $user->id,
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'notes' => $validated['notes'],
                'layout_id' => $validated['layout_id'],
                'tax_rate' => $company->getSetting('tax_rate', 0.19),
            ]);

            // Create invoice items
            foreach ($validated['items'] as $index => $itemData) {
                $item = new InvoiceItem([
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'unit' => $itemData['unit'] ?? 'Stk.',
                    'tax_rate' => $invoice->tax_rate, // Use invoice tax rate by default
                    'sort_order' => $index,
                ]);
                $item->calculateTotal();
                $invoice->items()->save($item);
            }

            // Calculate totals
            $invoice->calculateTotals();
            $invoice->save();
        });

        return redirect()->route('invoices.index')
            ->with('success', 'Rechnung wurde erfolgreich erstellt.');
    }

    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['customer', 'items', 'layout', 'user']);

        return Inertia::render('invoices/show', [
            'invoice' => $invoice,
            'settings' => $invoice->company->getDefaultSettings(),
        ]);
    }

    public function edit(Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $customers = Customer::forCompany($invoice->company_id)
            ->active()
            ->select('id', 'name', 'email')
            ->get();

        $layouts = InvoiceLayout::forCompany($invoice->company_id)
            ->get();

        $products = \App\Modules\Product\Models\Product::where('company_id', $invoice->company_id)
            ->where('status', 'active')
            ->select('id', 'name', 'description', 'price', 'unit', 'tax_rate', 'sku', 'number')
            ->orderBy('name')
            ->get();

        $invoice->load(['items', 'correctsInvoice', 'correctedByInvoice']);

        return Inertia::render('invoices/edit', [
            'invoice' => $invoice,
            'customers' => $customers,
            'layouts' => $layouts,
            'products' => $products,
            'settings' => $invoice->company->getDefaultSettings(),
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'layout_id' => 'nullable|exists:invoice_layouts,id',
            'status' => 'required|in:draft,sent,paid,overdue,cancelled',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.unit' => 'nullable|string|max:10',
        ]);

        DB::transaction(function () use ($validated, $invoice) {
            // Update invoice
            $invoice->update([
                'customer_id' => $validated['customer_id'],
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'notes' => $validated['notes'],
                'layout_id' => $validated['layout_id'],
                'status' => $validated['status'],
            ]);

            // Delete existing items and create new ones
            $invoice->items()->delete();

            foreach ($validated['items'] as $index => $itemData) {
                $item = new InvoiceItem([
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'unit' => $itemData['unit'] ?? 'Stk.',
                    'tax_rate' => $invoice->tax_rate, // Use invoice tax rate by default
                    'sort_order' => $index,
                ]);
                $item->calculateTotal();
                $invoice->items()->save($item);
            }

            // Recalculate totals
            $invoice->calculateTotals();
            $invoice->save();
        });

        return redirect()->route('invoices.index')
            ->with('success', 'Rechnung wurde erfolgreich aktualisiert.');
    }

    public function destroy(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);

        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', 'Rechnung wurde erfolgreich gelöscht.');
    }

    public function pdf(Invoice $invoice)
    {
        $invoice->load(['customer', 'items', 'layout', 'user', 'company', 'correctsInvoice']);

        // Get layout - either assigned to invoice or company default
        if ($invoice->layout) {
            $layout = $invoice->layout;
        } else {
            $layout = InvoiceLayout::forCompany($invoice->company_id)
                ->where('is_default', true)
                ->first();
        }

        // If no layout exists, create a minimal default layout
        if (!$layout) {
            $layout = (object) [
                'settings' => [
                    'colors' => [
                        'primary' => '#3b82f6',
                        'secondary' => '#1f2937',
                        'accent' => '#e5e7eb',
                        'text' => '#1f2937',
                    ],
                    'fonts' => [
                        'heading' => 'DejaVu Sans',
                        'body' => 'DejaVu Sans',
                        'size' => 'medium',
                    ],
                    'layout' => [
                        'margin_top' => 20,
                        'margin_right' => 20,
                        'margin_bottom' => 20,
                        'margin_left' => 20,
                        'header_height' => 120,
                        'footer_height' => 80,
                    ],
                    'branding' => [
                        'show_logo' => true,
                        'logo_position' => 'top-right',
                        'company_info_position' => 'top-left',
                        'show_header_line' => true,
                        'show_footer_line' => true,
                        'show_footer' => true,
                    ],
                    'content' => [
                        'show_company_address' => true,
                        'show_company_contact' => true,
                        'show_customer_number' => true,
                        'show_tax_number' => true,
                        'show_unit_column' => true,
                        'show_notes' => true,
                        'show_bank_details' => true,
                        'show_company_registration' => true,
                        'show_payment_terms' => true,
                        'show_item_images' => false,
                        'show_item_codes' => false,
                        'show_tax_breakdown' => false,
                        'show_payment_terms' => true,
                    ],
                ],
            ];
        }

        $html = view('pdf.invoice', [
            'layout' => $layout,
            'invoice' => $invoice,
            'company' => $invoice->company,
            'customer' => $invoice->customer,
        ])->render();

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
            ]);

        return $pdf->download("Rechnung-{$invoice->number}.pdf");
    }

    public function preview(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['customer', 'items', 'layout', 'user', 'company']);

        // Get layout - either assigned to invoice or company default
        if ($invoice->layout) {
            $layout = $invoice->layout;
        } else {
            $layout = InvoiceLayout::forCompany($invoice->company_id)
                ->where('is_default', true)
                ->first();
        }

        // If no layout exists, create a minimal default layout
        if (!$layout) {
            $layout = (object) [
                'settings' => [
                    'colors' => [
                        'primary' => '#3b82f6',
                        'secondary' => '#1f2937',
                        'accent' => '#e5e7eb',
                        'text' => '#1f2937',
                    ],
                    'fonts' => [
                        'heading' => 'DejaVu Sans',
                        'body' => 'DejaVu Sans',
                        'size' => 'medium',
                    ],
                    'layout' => [
                        'margin_top' => 20,
                        'margin_right' => 20,
                        'margin_bottom' => 20,
                        'margin_left' => 20,
                        'header_height' => 120,
                        'footer_height' => 80,
                    ],
                    'branding' => [
                        'show_logo' => true,
                        'logo_position' => 'top-right',
                        'company_info_position' => 'top-left',
                        'show_header_line' => true,
                        'show_footer_line' => true,
                        'show_footer' => true,
                    ],
                    'content' => [
                        'show_company_address' => true,
                        'show_company_contact' => true,
                        'show_customer_number' => true,
                        'show_tax_number' => true,
                        'show_unit_column' => true,
                        'show_notes' => true,
                        'show_bank_details' => true,
                        'show_company_registration' => true,
                        'show_payment_terms' => true,
                        'show_item_images' => false,
                        'show_item_codes' => false,
                        'show_tax_breakdown' => false,
                    ],
                ],
            ];
        }

        return view('pdf.invoice', [
            'layout' => $layout,
            'invoice' => $invoice,
            'company' => $invoice->company,
            'customer' => $invoice->customer,
            'preview' => true,
        ]);
    }

    public function send(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $validated = $request->validate([
            'to' => 'required|email',
            'cc' => 'nullable|email',
            'subject' => 'required|string|max:255',
            'message' => 'nullable|string|max:2000',
        ]);

        $companyId = $this->getEffectiveCompanyId();
        $company = \App\Modules\Company\Models\Company::find($companyId);

        // Validate customer email exists
        if (!$invoice->customer || !$invoice->customer->email) {
            return back()->withErrors(['email' => 'Kunde hat keine E-Mail-Adresse hinterlegt.']);
        }

        // Check if SMTP is configured
        if (!$company->smtp_host || !$company->smtp_username) {
            return back()->withErrors(['email' => 'SMTP-Einstellungen sind nicht konfiguriert. Bitte konfigurieren Sie die E-Mail-Einstellungen.']);
        }

        try {
            // Configure SMTP settings dynamically for this company
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', $company->smtp_host);
            Config::set('mail.mailers.smtp.port', $company->smtp_port);
            Config::set('mail.mailers.smtp.username', $company->smtp_username);
            Config::set('mail.mailers.smtp.password', $company->smtp_password);
            Config::set('mail.mailers.smtp.encryption', $company->smtp_encryption ?: 'tls');
            Config::set('mail.from.address', $company->smtp_from_address ?: $company->email);
            Config::set('mail.from.name', $company->smtp_from_name ?: $company->name);

            // Generate PDF
            $pdf = $this->generateInvoicePdf($invoice);

            // Send email
            Mail::send('emails.invoice-sent', [
                'invoice' => $invoice,
                'company' => $company,
                'customMessage' => $validated['message'] ?? null,
            ], function ($message) use ($validated, $invoice, $pdf, $company) {
                $message->to($validated['to']);
                
                if (!empty($validated['cc'])) {
                    $message->cc($validated['cc']);
                }
                
                $message->subject($validated['subject'] ?: "Rechnung {$invoice->number}");
                $message->attachData($pdf->output(), "Rechnung_{$invoice->number}.pdf", [
                    'mime' => 'application/pdf',
                ]);
            });

            // Log the email
            $this->logEmail(
                companyId: $companyId,
                recipientEmail: $validated['to'],
                subject: $validated['subject'] ?: "Rechnung {$invoice->number}",
                type: 'invoice',
                customerId: $invoice->customer_id,
                recipientName: $invoice->customer->name,
                body: $validated['message'] ?? null,
                relatedType: 'Invoice',
                relatedId: $invoice->id,
                metadata: [
                    'cc' => $validated['cc'] ?? null,
                    'invoice_number' => $invoice->number,
                    'invoice_total' => $invoice->total,
                    'has_pdf_attachment' => true,
                ]
            );

            // Update invoice status to 'sent' if it's still 'draft'
            if ($invoice->status === 'draft') {
                $invoice->update(['status' => 'sent']);
            }

            return redirect()->back()->with('success', 'Rechnung wurde erfolgreich per E-Mail versendet.');

        } catch (\Exception $e) {
            \Log::error('Failed to send invoice email: ' . $e->getMessage());
            return back()->withErrors(['email' => 'E-Mail konnte nicht versendet werden: ' . $e->getMessage()]);
        }
    }

    private function generateInvoicePdf(Invoice $invoice)
    {
        $invoice->load(['items', 'customer', 'company', 'user', 'correctsInvoice']);
        
        $layout = $invoice->layout ?: InvoiceLayout::forCompany($invoice->company_id)
            ->where('is_default', true)
            ->first();

        if (!$layout) {
            $layout = (object) [
                'template' => 'minimal',
                'settings' => [
                    'colors' => [
                        'primary' => '#3B82F6',
                        'secondary' => '#64748B',
                        'accent' => '#F59E0B',
                    ],
                    'fonts' => [
                        'heading' => 'DejaVu Sans',
                        'body' => 'DejaVu Sans',
                        'size' => 'medium',
                    ],
                    'layout' => [
                        'margin_top' => 20,
                        'margin_right' => 20,
                        'margin_bottom' => 20,
                        'margin_left' => 20,
                        'header_height' => 120,
                        'footer_height' => 80,
                    ],
                    'branding' => [
                        'show_logo' => true,
                        'logo_position' => 'top-right',
                        'company_info_position' => 'top-left',
                        'show_header_line' => true,
                        'show_footer_line' => true,
                        'show_footer' => true,
                    ],
                    'content' => [
                        'show_company_address' => true,
                        'show_company_contact' => true,
                        'show_customer_number' => true,
                        'show_tax_number' => true,
                        'show_unit_column' => true,
                        'show_notes' => true,
                        'show_bank_details' => true,
                        'show_company_registration' => true,
                        'show_payment_terms' => true,
                        'show_item_images' => false,
                        'show_item_codes' => false,
                        'show_tax_breakdown' => false,
                    ],
                ],
            ];
        }

        return Pdf::loadView('pdf.invoice', [
            'layout' => $layout,
            'invoice' => $invoice,
            'company' => $invoice->company,
            'customer' => $invoice->customer,
        ]);
    }

    /**
     * Manually send next reminder for an invoice (Mahnung)
     */
    public function sendReminder(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);
        
        // Check if invoice can receive another reminder
        if (!$invoice->canSendNextReminder()) {
            return redirect()->back()->with('error', 'Diese Rechnung kann keine weiteren Mahnungen erhalten.');
        }

        $companyId = $this->getEffectiveCompanyId();
        $company = \App\Modules\Company\Models\Company::find($companyId);

        // Check if company has SMTP configured
        if (!$company->smtp_host || !$company->smtp_username) {
            return redirect()->back()->with('error', 'E-Mail Einstellungen sind nicht konfiguriert.');
        }

        // Check if customer has email
        if (!$invoice->customer || !$invoice->customer->email) {
            return redirect()->back()->with('error', 'Kunde hat keine E-Mail-Adresse.');
        }

        try {
            // Configure SMTP
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', $company->smtp_host);
            Config::set('mail.mailers.smtp.port', $company->smtp_port);
            Config::set('mail.mailers.smtp.username', $company->smtp_username);
            Config::set('mail.mailers.smtp.password', $company->smtp_password);
            Config::set('mail.mailers.smtp.encryption', $company->smtp_encryption ?: 'tls');
            Config::set('mail.from.address', $company->smtp_from_address ?: $company->email);
            Config::set('mail.from.name', $company->smtp_from_name ?: $company->name);

            // Get next reminder level and fee
            $nextLevel = $invoice->getNextReminderLevel();
            $fee = $this->getReminderFee($nextLevel, $company);

            // Send the reminder email
            $this->sendMahnungEmail($invoice, $company, $nextLevel, $fee);

            // Update invoice
            $invoice->addReminderToHistory($nextLevel, $fee);
            if ($nextLevel > Invoice::REMINDER_FRIENDLY) {
                $invoice->status = 'overdue';
            }
            $invoice->save();

            $levelName = $invoice->getReminderLevelNameForLevel($nextLevel);
            return redirect()->back()->with('success', "{$levelName} wurde erfolgreich versendet.");

        } catch (\Exception $e) {
            \Log::error("Manual reminder failed: {$e->getMessage()}", [
                'invoice_id' => $invoice->id,
            ]);
            return redirect()->back()->with('error', 'Fehler beim Versenden der Mahnung: ' . $e->getMessage());
        }
    }

    /**
     * Get reminder fee for a specific level
     */
    private function getReminderFee(int $level, $company): float
    {
        return match($level) {
            Invoice::REMINDER_MAHNUNG_1 => (float) $company->getSetting('reminder_mahnung1_fee', 5.00),
            Invoice::REMINDER_MAHNUNG_2 => (float) $company->getSetting('reminder_mahnung2_fee', 10.00),
            Invoice::REMINDER_MAHNUNG_3 => (float) $company->getSetting('reminder_mahnung3_fee', 15.00),
            default => 0.00,
        };
    }

    /**
     * Send Mahnung email (same as in SendDailyReminders command)
     */
    private function sendMahnungEmail(Invoice $invoice, $company, int $level, float $fee)
    {
        $invoice->load(['items', 'customer', 'company', 'user']);

        // Determine which email template to use
        $template = match($level) {
            Invoice::REMINDER_FRIENDLY => 'emails.reminders.friendly',
            Invoice::REMINDER_MAHNUNG_1 => 'emails.reminders.mahnung-1',
            Invoice::REMINDER_MAHNUNG_2 => 'emails.reminders.mahnung-2',
            Invoice::REMINDER_MAHNUNG_3 => 'emails.reminders.mahnung-3',
            Invoice::REMINDER_INKASSO => 'emails.reminders.inkasso',
            default => 'emails.reminders.friendly',
        };

        // Determine subject
        $subject = match($level) {
            Invoice::REMINDER_FRIENDLY => "Freundliche Zahlungserinnerung - Rechnung {$invoice->number}",
            Invoice::REMINDER_MAHNUNG_1 => "1. Mahnung - Rechnung {$invoice->number}",
            Invoice::REMINDER_MAHNUNG_2 => "2. Mahnung - Rechnung {$invoice->number}",
            Invoice::REMINDER_MAHNUNG_3 => "3. und LETZTE Mahnung - Rechnung {$invoice->number}",
            Invoice::REMINDER_INKASSO => "Inkassoankündigung - Rechnung {$invoice->number}",
            default => "Zahlungserinnerung - Rechnung {$invoice->number}",
        };

        // Generate PDF
        $pdf = $this->generateInvoicePdf($invoice);

        // Calculate additional data for Inkasso level
        $inkassoFee = $level == Invoice::REMINDER_INKASSO ? 50.00 : 0;
        $delayInterest = $level == Invoice::REMINDER_INKASSO 
            ? $invoice->total * 0.09 * ($invoice->getDaysOverdue() / 365) 
            : 0;

        Mail::send($template, [
            'invoice' => $invoice,
            'company' => $company,
            'fee' => $fee,
            'inkassoFee' => $inkassoFee,
            'delayInterest' => $delayInterest,
        ], function ($message) use ($invoice, $pdf, $subject) {
            $message->to($invoice->customer->email);
            $message->subject($subject);
            $message->attachData($pdf->output(), "Rechnung_{$invoice->number}.pdf", [
                'mime' => 'application/pdf',
            ]);
        });

        // Log the mahnung email
        $this->logEmail(
            companyId: $company->id,
            recipientEmail: $invoice->customer->email,
            subject: $subject,
            type: 'mahnung',
            customerId: $invoice->customer_id,
            recipientName: $invoice->customer->name,
            relatedType: 'Invoice',
            relatedId: $invoice->id,
            metadata: [
                'reminder_level' => $level,
                'reminder_level_name' => $invoice->getReminderLevelNameForLevel($level),
                'invoice_number' => $invoice->number,
                'invoice_total' => $invoice->total,
                'reminder_fee' => $fee,
                'days_overdue' => $invoice->getDaysOverdue(),
                'has_pdf_attachment' => true,
            ]
        );
    }

    /**
     * View reminder history for an invoice
     */
    public function reminderHistory(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        return response()->json([
            'reminder_level' => $invoice->reminder_level,
            'reminder_level_name' => $invoice->reminder_level_name,
            'last_reminder_sent_at' => $invoice->last_reminder_sent_at,
            'reminder_fee' => $invoice->reminder_fee,
            'reminder_history' => $invoice->reminder_history ?? [],
            'days_overdue' => $invoice->getDaysOverdue(),
            'can_send_next' => $invoice->canSendNextReminder(),
            'next_level' => $invoice->canSendNextReminder() ? $invoice->getNextReminderLevel() : null,
            'next_level_name' => $invoice->canSendNextReminder() 
                ? $invoice->getReminderLevelNameForLevel($invoice->getNextReminderLevel())
                : null,
        ]);
    }

    /**
     * Download invoice as XRechnung (XML)
     */
    public function downloadXRechnung(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $eRechnungService = app(\App\Services\ERechnungService::class);
        $result = $eRechnungService->downloadXRechnung($invoice);

        return response($result['content'], 200, [
            'Content-Type' => $result['mime_type'],
            'Content-Disposition' => 'attachment; filename="' . $result['filename'] . '"',
        ]);
    }

    /**
     * Download invoice as ZUGFeRD (PDF with embedded XML)
     */
    public function downloadZugferd(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $eRechnungService = app(\App\Services\ERechnungService::class);
        $result = $eRechnungService->downloadZugferd($invoice);

        return response($result['content'], 200, [
            'Content-Type' => $result['mime_type'],
            'Content-Disposition' => 'attachment; filename="' . $result['filename'] . '"',
        ]);
    }

    /**
     * Create a correction invoice (Stornorechnung)
     */
    public function createCorrection(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        // Validate that invoice can be corrected
        if (!$invoice->canBeCorrect()) {
            return back()->with('error', 'Diese Rechnung kann nicht storniert werden.');
        }

        $validated = $request->validate([
            'correction_reason' => 'required|string|max:500',
            'create_new_invoice' => 'boolean',
            'send_email' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            // Load relationships
            $invoice->load(['customer', 'items', 'company']);

            // Create the correction invoice (Stornorechnung)
            $correctionInvoice = new Invoice();
            $correctionInvoice->company_id = $invoice->company_id;
            $correctionInvoice->customer_id = $invoice->customer_id;
            $correctionInvoice->user_id = auth()->id();
            $correctionInvoice->status = 'sent'; // Correction invoices are immediately sent
            $correctionInvoice->issue_date = now();
            $correctionInvoice->due_date = now()->addDays(14);
            $correctionInvoice->tax_rate = $invoice->tax_rate;
            $correctionInvoice->notes = "Stornorechnung für " . $invoice->number . "\nGrund: " . $validated['correction_reason'];
            $correctionInvoice->payment_method = $invoice->payment_method;
            $correctionInvoice->payment_terms = $invoice->payment_terms;
            $correctionInvoice->layout_id = $invoice->layout_id;
            
            // Correction invoice fields
            $correctionInvoice->is_correction = true;
            $correctionInvoice->corrects_invoice_id = $invoice->id;
            $correctionInvoice->correction_reason = $validated['correction_reason'];
            
            // Negative amounts (canceling the original)
            $correctionInvoice->subtotal = -$invoice->subtotal;
            $correctionInvoice->tax_amount = -$invoice->tax_amount;
            $correctionInvoice->total = -$invoice->total;
            
            // IMPORTANT: Generate number BEFORE saving (number field is NOT NULL)
            // Temporarily save with a placeholder to get the ID for number generation
            $company = $invoice->company;
            $prefix = $company->getSetting('invoice_prefix', 'RE-');
            $originalNumber = $invoice->number;
            $correctionInvoice->number = str_replace($prefix, $prefix . 'STORNO-', $originalNumber);
            
            // Now save with the number
            $correctionInvoice->save();

            // Copy items with negative quantities
            foreach ($invoice->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $correctionInvoice->id,
                    'product_id' => $item->product_id,
                    'description' => $item->description,
                    'quantity' => -$item->quantity, // Negative quantity
                    'unit_price' => $item->unit_price,
                    'unit' => $item->unit,
                    'tax_rate' => $item->tax_rate,
                    'total' => -$item->total, // Negative total
                    'sort_order' => $item->sort_order,
                ]);
            }

            // Mark original invoice as corrected
            $invoice->corrected_by_invoice_id = $correctionInvoice->id;
            $invoice->corrected_at = now();
            $invoice->status = 'cancelled';
            $invoice->save();

            DB::commit();

            // Send email if requested
            $emailSent = false;
            if ($validated['send_email'] ?? false) {
                try {
                    $company = $correctionInvoice->company;
                    if ($company->smtp_host && $company->smtp_username && $correctionInvoice->customer && $correctionInvoice->customer->email) {
                        // Configure SMTP settings dynamically
                        Config::set('mail.default', 'smtp');
                        Config::set('mail.mailers.smtp.host', $company->smtp_host);
                        Config::set('mail.mailers.smtp.port', $company->smtp_port);
                        Config::set('mail.mailers.smtp.username', $company->smtp_username);
                        Config::set('mail.mailers.smtp.password', $company->smtp_password);
                        Config::set('mail.mailers.smtp.encryption', $company->smtp_encryption ?: 'tls');
                        Config::set('mail.from.address', $company->smtp_from_address ?: $company->email);
                        Config::set('mail.from.name', $company->smtp_from_name ?: $company->name);

                        // Generate PDF
                        $pdf = $this->generateInvoicePdf($correctionInvoice);

                        // Send email
                        Mail::send('emails.invoice-sent', [
                            'invoice' => $correctionInvoice,
                            'company' => $company,
                            'customer' => $correctionInvoice->customer,
                            'message' => 'Die Stornorechnung ' . $correctionInvoice->number . ' wurde erstellt und storniert die Rechnung ' . $invoice->number . '.',
                        ], function ($message) use ($correctionInvoice, $company, $pdf) {
                            $message->to($correctionInvoice->customer->email)
                                ->subject('Stornorechnung ' . $correctionInvoice->number)
                                ->attachData($pdf->output(), 'Stornorechnung_' . $correctionInvoice->number . '.pdf', [
                                    'mime' => 'application/pdf',
                                ]);
                        });

                        // Log email
                        $this->logEmail('invoice-sent', $correctionInvoice->customer->email, $correctionInvoice->id, $correctionInvoice->company_id);
                        $emailSent = true;
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to send correction invoice email: ' . $e->getMessage());
                    // Don't fail the entire operation if email fails
                }
            }

            $successMessage = 'Stornorechnung ' . $correctionInvoice->number . ' wurde erfolgreich erstellt. Die ursprüngliche Rechnung ' . $invoice->number . ' wurde storniert.';
            if ($emailSent) {
                $successMessage .= ' Die Stornorechnung wurde per E-Mail versendet.';
            }

            return redirect()->route('invoices.edit', $correctionInvoice->id)
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Invoice correction failed: ' . $e->getMessage());
            return back()->with('error', 'Fehler beim Erstellen der Stornorechnung: ' . $e->getMessage());
        }
    }
}
