# Setup Summary - Invoice/Offer Enhancements

## ✅ Completed Features

### 1. Product Selection for Invoice/Offer Positions

**What was implemented:**
- Created a reusable `ProductSelectorDialog` component that allows users to:
  - Select products from a searchable list
  - Add custom one-time items
  - Search products by name, description, SKU, or number
  
**Files created/modified:**
- ✅ `resources/js/components/product-selector-dialog.tsx` - NEW dialog component
- ✅ `app/Modules/Invoice/Controllers/InvoiceController.php` - Added products to create/edit methods
- ✅ `app/Modules/Offer/Controllers/OfferController.php` - Added products to create/edit methods  
- ✅ `resources/js/pages/invoices/create.tsx` - Integrated ProductSelectorDialog
- ✅ Need to integrate in: `invoices/edit.tsx`, `offers/create.tsx`, `offers/edit.tsx`

**How to use:**
1. Open invoice/offer create page
2. Click "Position hinzufügen" button
3. Choose between:
   - **Aus Produkten**: Search and select from existing products
   - **Benutzerdefiniert**: Enter custom item details manually

### 2. SMTP Configuration (Database Ready)

**What was implemented:**
- ✅ Database migration completed - SMTP columns added to `companies` table:
  - `smtp_host` - Mail server hostname
  - `smtp_port` - Mail server port (default: 587)
  - `smtp_username` - SMTP authentication username
  - `smtp_password` - SMTP authentication password
  - `smtp_encryption` - Encryption type (tls/ssl/none)
  - `smtp_from_address` - Sender email address
  - `smtp_from_name` - Sender display name

## 🚧 Remaining Work

### 3. SMTP Settings UI (Not Started)

**What needs to be done:**
1. Update Company model to include SMTP fields in $fillable
2. Create email settings page: `resources/js/pages/settings/email.tsx`
3. Add route in `routes/settings.php` or settings controller
4. Create form with fields for all SMTP settings
5. Add "Test Connection" button to verify settings work
6. Add encryption dropdown (TLS, SSL, None)

**Example UI structure:**
```tsx
// Email Settings Form
- SMTP Host (input)
- SMTP Port (number input, default 587)
- Username (input)
- Password (password input)
- Encryption (select: TLS/SSL/None)
- From Address (email input)
- From Name (text input)
- [Test Connection] button
- [Save Settings] button
```

### 4. Email Templates (Not Started)

**What needs to be created:**

1. **Base Email Layout** - `resources/views/emails/layouts/base.blade.php`
```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>@yield('title')</title>
    <style>
        /* Email-safe styles */
        body { font-family: Arial, sans-serif; }
        .container { max-width: 600px; margin: 0 auto; }
        .header { background: #f8f9fa; padding: 20px; }
        .content { padding: 20px; }
        .footer { background: #f8f9fa; padding: 10px; text-align: center; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            @if($company->logo)
                <img src="{{ asset('storage/'.$company->logo) }}" alt="{{ $company->name }}" height="50">
            @endif
            <h2>{{ $company->name }}</h2>
        </div>
        <div class="content">
            @yield('content')
        </div>
        <div class="footer">
            <p>{{ $company->name }} | {{ $company->email }} | {{ $company->phone }}</p>
            <p>{{ $company->address }}</p>
        </div>
    </div>
</body>
</html>
```

2. **Invoice Email** - `resources/views/emails/invoice-sent.blade.php`
```html
@extends('emails.layouts.base')

@section('title', 'Rechnung '.$invoice->number)

@section('content')
    <h3>Sehr geehrte{{ $invoice->customer->gender === 'female' ? ' Frau' : 'r Herr' }} {{ $invoice->customer->name }},</h3>
    
    <p>im Anhang finden Sie die Rechnung <strong>{{ $invoice->number }}</strong> vom {{ $invoice->issue_date->format('d.m.Y') }}.</p>
    
    <table style="width: 100%; margin: 20px 0;">
        <tr>
            <td><strong>Rechnungsnummer:</strong></td>
            <td>{{ $invoice->number }}</td>
        </tr>
        <tr>
            <td><strong>Rechnungsdatum:</strong></td>
            <td>{{ $invoice->issue_date->format('d.m.Y') }}</td>
        </tr>
        <tr>
            <td><strong>Fälligkeitsdatum:</strong></td>
            <td>{{ $invoice->due_date->format('d.m.Y') }}</td>
        </tr>
        <tr>
            <td><strong>Rechnungsbetrag:</strong></td>
            <td><strong>{{ number_format($invoice->total, 2, ',', '.') }} €</strong></td>
        </tr>
    </table>
    
    @if($company->bank_iban)
    <h4>Zahlungsinformationen:</h4>
    <p>
        Bank: {{ $company->bank_name }}<br>
        IBAN: {{ $company->bank_iban }}<br>
        BIC: {{ $company->bank_bic }}
    </p>
    @endif
    
    <p>Bei Fragen stehen wir Ihnen gerne zur Verfügung.</p>
    
    <p>Mit freundlichen Grüßen<br>{{ $company->name }}</p>
@endsection
```

3. **Offer Email** - `resources/views/emails/offer-sent.blade.php`
```html
@extends('emails.layouts.base')

@section('title', 'Angebot '.$offer->number)

@section('content')
    <h3>Sehr geehrte{{ $offer->customer->gender === 'female' ? ' Frau' : 'r Herr' }} {{ $offer->customer->name }},</h3>
    
    <p>vielen Dank für Ihr Interesse. Im Anhang finden Sie unser Angebot <strong>{{ $offer->number }}</strong>.</p>
    
    <table style="width: 100%; margin: 20px 0;">
        <tr>
            <td><strong>Angebotsnummer:</strong></td>
            <td>{{ $offer->number }}</td>
        </tr>
        <tr>
            <td><strong>Angebotsdatum:</strong></td>
            <td>{{ $offer->issue_date->format('d.m.Y') }}</td>
        </tr>
        <tr>
            <td><strong>Gültig bis:</strong></td>
            <td>{{ $offer->valid_until->format('d.m.Y') }}</td>
        </tr>
        <tr>
            <td><strong>Angebotssumme:</strong></td>
            <td><strong>{{ number_format($offer->total, 2, ',', '.') }} €</strong></td>
        </tr>
    </table>
    
    <p>Wir freuen uns auf Ihre Rückmeldung und stehen Ihnen für Rückfragen gerne zur Verfügung.</p>
    
    <p>Mit freundlichen Grüßen<br>{{ $company->name }}</p>
@endsection
```

### 5. Send Functionality (Not Started)

**InvoiceController - Add send() method:**
```php
public function send(Request $request, Invoice $invoice)
{
    $this->authorize('update', $invoice);
    
    $validated = $request->validate([
        'recipient_email' => 'required|email',
        'subject' => 'nullable|string|max:255',
        'message' => 'nullable|string',
    ]);
    
    // Get company
    $company = $invoice->company;
    
    // Configure mailer with company SMTP settings
    if ($company->smtp_host) {
        config([
            'mail.mailers.smtp.host' => $company->smtp_host,
            'mail.mailers.smtp.port' => $company->smtp_port,
            'mail.mailers.smtp.username' => $company->smtp_username,
            'mail.mailers.smtp.password' => $company->smtp_password,
            'mail.mailers.smtp.encryption' => $company->smtp_encryption,
            'mail.from.address' => $company->smtp_from_address,
            'mail.from.name' => $company->smtp_from_name,
        ]);
    }
    
    // Generate PDF
    $pdf = Pdf::loadView('pdf.invoice', [
        'invoice' => $invoice->load(['items', 'customer', 'layout', 'company']),
        'layout' => $invoice->layout ?? $company->defaultInvoiceLayout(),
    ]);
    
    // Send email
    try {
        Mail::send('emails.invoice-sent', [
            'invoice' => $invoice,
            'company' => $company,
            'customMessage' => $validated['message'] ?? null,
        ], function($message) use ($validated, $invoice, $pdf) {
            $message->to($validated['recipient_email'])
                    ->subject($validated['subject'] ?? 'Rechnung '.$invoice->number)
                    ->attachData($pdf->output(), 'rechnung-'.$invoice->number.'.pdf', [
                        'mime' => 'application/pdf',
                    ]);
        });
        
        // Update invoice status
        $invoice->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);
        
        return back()->with('success', 'Rechnung wurde erfolgreich versendet.');
    } catch (\Exception $e) {
        return back()->with('error', 'Fehler beim Versenden: ' . $e->getMessage());
    }
}
```

**OfferController - Add send() method (similar):**
```php
public function send(Request $request, Offer $offer)
{
    // Similar to InvoiceController::send()
    // Use 'emails.offer-sent' template
    // Update offer status to 'sent'
}
```

**Add routes:**
```php
// In routes/modules/invoices.php
Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');

// In routes/modules/offers.php
Route::post('/offers/{offer}/send', [OfferController::class, 'send'])->name('offers.send');
```

### 6. Send Button UI (Not Started)

**Update these files to add Send buttons:**

1. `resources/js/pages/invoices/index.tsx` - Add send button in actions column
2. `resources/js/pages/invoices/edit.tsx` - Add send button in header
3. `resources/js/pages/offers/index.tsx` - Add send button in actions column
4. `resources/js/pages/offers/edit.tsx` - Add send button in header

**Example button implementation:**
```tsx
import { Send } from "lucide-react"
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog"

const [sendDialogOpen, setSendDialogOpen] = useState(false)
const [emailData, setEmailData] = useState({
    recipient_email: invoice.customer?.email || "",
    subject: `Rechnung ${invoice.number}`,
    message: "",
})

<Dialog open={sendDialogOpen} onOpenChange={setSendDialogOpen}>
    <DialogTrigger asChild>
        <Button variant="outline" size="sm">
            <Send className="h-4 w-4 mr-1" />
            Senden
        </Button>
    </DialogTrigger>
    <DialogContent>
        <DialogHeader>
            <DialogTitle>Rechnung per E-Mail versenden</DialogTitle>
            <DialogDescription>
                Rechnung {invoice.number} an Kunden senden
            </DialogDescription>
        </DialogHeader>
        
        <form onSubmit={(e) => {
            e.preventDefault()
            router.post(route('invoices.send', invoice.id), emailData, {
                onSuccess: () => setSendDialogOpen(false)
            })
        }}>
            <div className="space-y-4">
                <div>
                    <Label>Empfänger</Label>
                    <Input 
                        type="email" 
                        value={emailData.recipient_email}
                        onChange={(e) => setEmailData({...emailData, recipient_email: e.target.value})}
                        required
                    />
                </div>
                
                <div>
                    <Label>Betreff</Label>
                    <Input 
                        value={emailData.subject}
                        onChange={(e) => setEmailData({...emailData, subject: e.target.value})}
                    />
                </div>
                
                <div>
                    <Label>Nachricht (optional)</Label>
                    <Textarea 
                        value={emailData.message}
                        onChange={(e) => setEmailData({...emailData, message: e.target.value})}
                        placeholder="Zusätzliche Nachricht..."
                        rows={4}
                    />
                </div>
                
                <div className="flex justify-end space-x-2">
                    <Button type="button" variant="outline" onClick={() => setSendDialogOpen(false)}>
                        Abbrechen
                    </Button>
                    <Button type="submit">
                        <Send className="h-4 w-4 mr-2" />
                        Senden
                    </Button>
                </div>
            </div>
        </form>
    </DialogContent>
</Dialog>
```

## Next Steps

1. ✅ Product selection - COMPLETE
2. ✅ SMTP database structure - COMPLETE
3. ⏳ Complete SMTP settings UI
4. ⏳ Create email templates
5. ⏳ Implement send methods in controllers
6. ⏳ Add Send buttons to frontend
7. ⏳ Test with real SMTP credentials

## Testing Checklist

- [ ] Can add products from list to invoice/offer
- [ ] Can add custom items to invoice/offer
- [ ] Can save SMTP settings per company
- [ ] Can send test email from settings
- [ ] Can send invoice via email with PDF attachment
- [ ] Can send offer via email with PDF attachment
- [ ] Email templates display correctly
- [ ] Company branding appears in emails
- [ ] Invoice/offer status updates to 'sent' after sending

## Notes

- Laravel Mail is already configured in `config/mail.php`
- SMTP settings are stored per company for multi-tenancy support
- Each company can use different SMTP credentials
- PDF generation uses existing DomPDF integration
- Email templates use responsive, email-safe HTML/CSS


