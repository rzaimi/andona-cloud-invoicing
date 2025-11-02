# Company Creation Wizard Documentation

## Overview
A comprehensive 7-step wizard for creating new companies in the invoicing system. This wizard ensures that all critical settings are configured during company creation, making the company immediately functional.

## Wizard Flow

### Step 1: Company Information 🏢
**Required Fields:**
- Company Name *
- Email *

**Optional Fields:**
- Phone
- Address
- Postal Code
- City
- Country (default: Deutschland)
- Tax Number (Steuernummer)
- VAT Number (USt-IdNr.)
- Website

### Step 2: Email Configuration 📧
**Critical for functionality** - Without SMTP settings, the company cannot send invoices/offers.

**Required Fields:**
- SMTP Host *
- SMTP Port * (default: 587)
- SMTP Username *
- SMTP Password *
- SMTP Encryption * (TLS/SSL/None)
- From Address *
- From Name *

**Common Providers:**
- Gmail: smtp.gmail.com (Port 587)
- Outlook: smtp-mail.outlook.com (Port 587)
- 1&1/IONOS: smtp.ionos.de (Port 587)
- Strato: smtp.strato.de (Port 465)

### Step 3: Invoice & Offer Settings 📄
**Prefixes:**
- Invoice Prefix (default: RE-)
- Offer Prefix (default: AN-)
- Customer Prefix (default: KD-)

**Currency & Tax:**
- Currency (EUR/USD/GBP/CHF)
- Tax Rate (default: 19% = 0.19)
- Reduced Tax Rate (default: 7% = 0.07)

**Terms:**
- Payment Terms (default: 14 days)
- Offer Validity (default: 30 days)

**Formats:**
- Date Format (d.m.Y / Y-m-d / m/d/Y)
- Decimal Separator (, or .)
- Thousands Separator (. or , or space)

### Step 4: Mahnung Settings (German Dunning Process) 🔔
**Intervals (days after due date):**
- Friendly Reminder: 7 days
- 1. Mahnung: 14 days (€5.00 fee)
- 2. Mahnung: 21 days (€10.00 fee)
- 3. Mahnung: 30 days (€15.00 fee)
- Inkasso: 45 days

**Additional:**
- Interest Rate: 9.00% p.a. (Basiszins + 9% for B2B)
- Auto-send toggle (enabled by default)

### Step 5: Banking Information 🏦
**Required for invoice payment instructions:**
- IBAN * (max 34 chars)
- BIC/SWIFT * (max 11 chars)

**Optional:**
- Bank Name
- Account Holder

### Step 6: First User (Optional) 👤
**Create an admin user for the new company:**
- Name *
- Email * (must be unique)
- Password * (min 8 characters)
- Send Welcome Email toggle (enabled by default)

**Note:** User will be assigned `company_admin` role.

### Step 7: Review & Create ✅
**Displays a summary of all settings across all steps:**
- Company Info
- Email Configuration
- Invoice Settings
- Mahnung Settings
- Banking Info
- First User (if created)

**User can go back to any step to make changes before final submission.**

---

## Technical Implementation

### Backend
**Controller:** `app/Http/Controllers/CompanyWizardController.php`

**Routes:** `routes/modules/admin.php`
```php
Route::get('companies/wizard/start', [CompanyWizardController::class, 'start']);
Route::post('companies/wizard/update', [CompanyWizardController::class, 'updateStep']);
Route::post('companies/wizard/complete', [CompanyWizardController::class, 'complete']);
Route::post('companies/wizard/cancel', [CompanyWizardController::class, 'cancel']);
```

**Session Storage:**
- Wizard data is stored in the session under `company_wizard` key
- Persists across steps
- Cleared on completion or cancellation

**Key Methods:**
- `start()`: Initializes wizard with default values
- `updateStep()`: Validates and saves current step data
- `complete()`: Creates company with all settings in a transaction
- `cancel()`: Clears session and redirects

**Validation:**
Each step has its own validation rules implemented in `validateStep()`.

**Database Transaction:**
Company creation uses DB transaction to ensure atomicity:
1. Create company record
2. Save invoice settings via SettingsService
3. Save Mahnung settings via SettingsService
4. Create first user (if requested)
5. Assign company_admin role to user

### Frontend
**Main Component:** `resources/js/pages/companies/wizard.tsx`

**Step Components:**
- `wizard/Step1CompanyInfo.tsx`
- `wizard/Step2EmailSettings.tsx`
- `wizard/Step3InvoiceSettings.tsx`
- `wizard/Step4MahnungSettings.tsx`
- `wizard/Step5BankingInfo.tsx`
- `wizard/Step6FirstUser.tsx`
- `wizard/Step7Review.tsx`

**Features:**
- Progress bar (percentage based on current step)
- Visual step indicators with icons
- Color-coded step states (active/completed/pending)
- Back/Forward navigation
- Cancel button with confirmation
- Real-time validation errors
- Responsive design (mobile-friendly)

**State Management:**
- Uses Inertia's `useForm` hook
- Data persisted on backend via POST requests
- Preserves scroll position between steps

### UI Components Used
- Card, CardHeader, CardTitle, CardContent, CardDescription
- Button, Input, Label
- Select, SelectTrigger, SelectContent, SelectItem
- Switch (for toggles)
- Alert, AlertDescription
- Progress (progress bar)
- Lucide Icons (Building2, Mail, FileText, Bell, Landmark, User, CheckCircle, etc.)

---

## User Experience

### Navigation
1. User clicks "Neue Firma erstellen" on Companies Index
2. Redirects to wizard start page (`/companies/wizard/start`)
3. User completes each step in sequence
4. Can go back to previous steps to review/edit
5. Final step shows complete summary
6. Clicks "Firma erstellen" to complete

### Validation
- Per-step validation prevents proceeding with errors
- Error messages displayed inline under fields
- Required fields marked with *

### Success
- On successful creation: Redirects to Companies Index
- Success message: "Firma '{name}' wurde erfolgreich erstellt!"

### Cancellation
- Cancel button available on all steps
- Confirmation dialog: "Möchten Sie den Wizard wirklich abbrechen?"
- Clears all wizard data
- Redirects to Companies Index

---

## Benefits

### 1. Complete Setup
✅ Company is fully configured and ready to use immediately
✅ No missing critical settings (like SMTP)
✅ No need to navigate multiple settings pages

### 2. User Guidance
✅ Step-by-step process is easy to follow
✅ Inline help and descriptions
✅ Default values provided
✅ Common SMTP providers listed

### 3. Error Prevention
✅ Validation at each step
✅ Required fields clearly marked
✅ Can't proceed with invalid data
✅ Review step catches mistakes

### 4. Professional UX
✅ Modern, clean interface
✅ Progress indicator
✅ Visual feedback (icons, colors)
✅ Mobile-responsive
✅ Fast and intuitive

### 5. Reduced Support
✅ Less chance of misconfiguration
✅ No "forgotten settings" issues
✅ Guided process reduces confusion
✅ Default values work for most cases

---

## Default Values

The wizard pre-fills sensible defaults for most settings:

**Invoice Settings:**
- Invoice Prefix: `RE-`
- Offer Prefix: `AN-`
- Customer Prefix: `KD-`
- Currency: `EUR`
- Tax Rate: `19%`
- Reduced Tax Rate: `7%`
- Payment Terms: `14 days`
- Offer Validity: `30 days`
- Date Format: `d.m.Y` (01.11.2025)
- Decimal Separator: `,`
- Thousands Separator: `.`

**Mahnung Settings:**
- Friendly Reminder: `7 days`
- 1. Mahnung: `14 days, €5.00`
- 2. Mahnung: `21 days, €10.00`
- 3. Mahnung: `30 days, €15.00`
- Inkasso: `45 days`
- Interest Rate: `9.00% p.a.`
- Auto-send: `enabled`

**Email Settings:**
- Port: `587`
- Encryption: `TLS`

---

## Future Enhancements

### Possible Additions:
1. **Setup Templates**
   - "Small Business" preset
   - "Professional Services" preset
   - "Enterprise" preset

2. **Import from Existing Company**
   - Copy settings from another company
   - Only change company-specific info

3. **Logo Upload**
   - Upload company logo during wizard
   - Preview how it looks on invoices

4. **Test SMTP Connection**
   - "Test Connection" button on Step 2
   - Verify SMTP settings work before proceeding

5. **Save as Draft**
   - Allow saving progress and returning later
   - Email link to resume wizard

6. **Post-Wizard Checklist**
   - Show setup checklist after completion
   - Guide to next steps (add customers, create invoice layout, etc.)

---

## Access

**Route:** `/companies/wizard/start`

**Permission:** `super_admin` role required

**Entry Point:** "Neue Firma erstellen" button on Companies Index (`/companies`)

---

## Testing

### Manual Testing Checklist:
- [ ] Start wizard from Companies Index
- [ ] Enter company info and proceed
- [ ] Enter SMTP settings and proceed
- [ ] Configure invoice settings and proceed
- [ ] Configure Mahnung settings and proceed
- [ ] Enter banking info and proceed
- [ ] Create first user (toggle on/off)
- [ ] Review all settings in Step 7
- [ ] Go back and edit a previous step
- [ ] Cancel wizard (confirm data is cleared)
- [ ] Complete wizard and verify company is created
- [ ] Check that all settings are saved correctly
- [ ] Verify first user can log in (if created)
- [ ] Test sending an invoice with the new company

### Validation Testing:
- [ ] Try to proceed without required fields
- [ ] Enter invalid email addresses
- [ ] Enter invalid IBAN/BIC
- [ ] Use duplicate company email
- [ ] Use duplicate user email
- [ ] Test password minimum length (8 chars)

---

## Troubleshooting

### Common Issues:

**1. Session Expired**
- **Symptom:** Wizard redirects to start after completing step
- **Solution:** Check session configuration, increase lifetime

**2. SMTP Validation Fails**
- **Symptom:** Can't proceed past Step 2 with valid SMTP
- **Solution:** Check validation rules in `validateStep()`

**3. Transaction Rollback**
- **Symptom:** Company creation fails with error
- **Solution:** Check logs, verify DB constraints, foreign keys

**4. Settings Not Saved**
- **Symptom:** Company created but settings missing
- **Solution:** Verify `SettingsService` is working correctly

**5. First User Not Created**
- **Symptom:** Company created but user missing
- **Solution:** Check `create_user` toggle value, verify User model

---

## Code Examples

### Starting the Wizard
```php
// Link from Companies Index
<Link href={route("companies.wizard.start")}>
    <Plus className="mr-2 h-4 w-4" />
    Neue Firma erstellen
</Link>
```

### Accessing Wizard Data in Controller
```php
$wizardData = session('company_wizard', []);
$companyInfo = $wizardData['company_info'] ?? [];
$emailSettings = $wizardData['email_settings'] ?? [];
```

### Creating Company with All Settings
```php
DB::beginTransaction();
try {
    $company = Company::create([...]);
    
    foreach ($wizardData['invoice_settings'] as $key => $value) {
        $this->settingsService->setCompany($key, $value, $company->id, $type);
    }
    
    DB::commit();
} catch (\Exception $e) {
    DB::rollBack();
    // Handle error
}
```

---

## Summary

The Company Creation Wizard transforms the company creation process from:
- **Before:** Create company → Navigate to multiple settings pages → Configure each separately → Easy to forget critical settings
- **After:** Start wizard → Follow 7 guided steps → Review → Create → **Fully functional company ready to use!**

This improves user experience, reduces support requests, prevents misconfiguration, and ensures every company is properly set up from day one.

🎉 **Result:** Professional, complete, and user-friendly company onboarding!


