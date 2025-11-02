# Implementation Plan - Invoice/Offer Improvements

## ✅ Completed

### 1. Product Selection for Invoice/Offer Items
- ✅ Created `ProductSelectorDialog` component (`resources/js/components/product-selector-dialog.tsx`)
- ✅ Updated `InvoiceController::create()` and `edit()` to pass products
- ✅ Updated `OfferController::create()` and `edit()` to pass products
- ✅ Integrated `ProductSelectorDialog` into invoice create page
- ✅ Component supports:
  - Search and filter products by name, description, SKU, number
  - Select existing products with auto-populated pricing
  - Add custom one-time items with manual input
  - Two tabs: "From Products" and "Custom"

### Features:
- Product search with real-time filtering
- Display product details (name, description, SKU, number, price, unit)
- Click to add product as invoice/offer item
- Custom item form with description, quantity, unit price, and unit fields
- German unit selection (Stk., Std., Tag, etc.)

## 🚧 TODO - Email Functionality

### 2. SMTP Configuration

#### Backend Changes Needed:
1. **Company Settings Table** - Add SMTP columns:
```php
// Migration: add_smtp_settings_to_company_settings_table.php
$table->string('smtp_host')->nullable();
$table->integer('smtp_port')->default(587);
$table->string('smtp_username')->nullable();
$table->string('smtp_password')->nullable();
$table->string('smtp_encryption')->default('tls'); // tls, ssl, null
$table->string('smtp_from_address')->nullable();
$table->string('smtp_from_name')->nullable();
```

2. **SettingsService** - Add SMTP getter methods
3. **CompanySettings UI** - Add SMTP configuration form tab
4. **Dynamic Mail Configuration** - Runtime SMTP config based on company

#### Frontend Changes Needed:
1. Create `resources/js/pages/settings/email.tsx`
2. Add SMTP form fields (host, port, username, password, encryption, from address/name)
3. Add "Test Connection" button to verify SMTP settings

### 3. Email Templates

Create blade templates for emails:
- `resources/views/emails/invoice-sent.blade.php` - Invoice email template
- `resources/views/emails/offer-sent.blade.php` - Offer email template

Each template should include:
- Company logo and branding
- Personalized greeting
- Document number and date
- PDF attachment
- Payment instructions (for invoices)
- Call-to-action buttons

### 4. Send Functionality

#### InvoiceController:
```php
public function send(Invoice $invoice)
{
    // Authorize
    // Get customer email
    // Get company SMTP settings
    // Configure mailer
    // Send email with PDF attachment
    // Update invoice status to 'sent'
    // Return success message
}
```

#### OfferController:
```php
public function send(Offer $offer)
{
    // Similar to InvoiceController::send()
    // Update offer status to 'sent'
}
```

### 5. UI Changes

**Invoice Index Page** (`resources/js/pages/invoices/index.tsx`):
- Add "Send" button with email icon
- Show modal/confirmation before sending
- Display success/error toast after sending

**Offer Index Page** (`resources/js/pages/offers/index.tsx`):
- Add "Send" button with email icon  
- Show modal/confirmation before sending
- Display success/error toast after sending

**Invoice Edit Page** (`resources/js/pages/invoices/edit.tsx`):
- Add "Send to Customer" button in header
- Modal with email preview before sending

**Offer Edit Page** (`resources/js/pages/offers/edit.tsx`):
- Add "Send to Customer" button in header
- Modal with email preview before sending

## Next Steps

1. Run migration to add SMTP settings columns
2. Update SettingsService for SMTP config
3. Create email settings UI page
4. Create email blade templates  
5. Implement send() methods in controllers
6. Add Send buttons to frontend pages
7. Test email sending with real SMTP credentials

## Files to Update

### Backend:
- `database/migrations/[timestamp]_add_smtp_settings_to_company_settings_table.php` (NEW)
- `app/Services/SettingsService.php` (UPDATE)
- `app/Modules/Settings/Controllers/SettingsController.php` (UPDATE)
- `app/Modules/Invoice/Controllers/InvoiceController.php` (UPDATE - add send method)
- `app/Modules/Offer/Controllers/OfferController.php` (UPDATE - add send method)
- `routes/modules/invoices.php` (UPDATE - add send route)
- `routes/modules/offers.php` (UPDATE - add send route)

### Frontend:
- `resources/js/pages/settings/email.tsx` (NEW)
- `resources/js/pages/invoices/index.tsx` (UPDATE - add send button)
- `resources/js/pages/invoices/edit.tsx` (UPDATE - add send button)
- `resources/js/pages/offers/index.tsx` (UPDATE - add send button)
- `resources/js/pages/offers/edit.tsx` (UPDATE - add send button)

### Email Templates:
- `resources/views/emails/invoice-sent.blade.php` (NEW)
- `resources/views/emails/offer-sent.blade.php` (NEW)
- `resources/views/emails/layouts/base.blade.php` (NEW - base email layout)


