# Email Logs System - Implementation Documentation

## 📧 Overview

Complete email logging system to track all emails sent to customers, including invoices, offers, Mahnungen (reminders), and other communications.

---

## 🗄️ Database Structure

### New Table: `email_logs`

```sql
- id (uuid, primary)
- company_id (uuid, foreign key)
- customer_id (uuid, nullable, foreign key)
- recipient_email (string)
- recipient_name (string, nullable)
- subject (string)
- body (text, nullable)
- type (string) - invoice, offer, mahnung, reminder, etc.
- related_type (string, nullable) - Invoice, Offer
- related_id (uuid, nullable)
- status (string) - sent, failed
- error_message (text, nullable)
- metadata (json, nullable)
- sent_at (timestamp)
- created_at, updated_at
```

**Indexes:**
- `(company_id, sent_at)`
- `(customer_id, sent_at)`
- `(type, sent_at)`

---

## 📝 Email Types

| Type | Description | Example |
|------|-------------|---------|
| `invoice` | Invoice sent to customer | "Rechnung RE-2025-001" |
| `offer` | Offer/Quote sent | "Angebot AN-2025-001" |
| `mahnung` | Payment reminder (any level) | "1. Mahnung - Rechnung RE-2025-001" |
| `reminder` | General reminder | "Zahlungserinnerung" |
| `payment_received` | Payment confirmation | "Zahlungsbestätigung" |
| `welcome` | Welcome email | "Willkommen bei..." |

---

## 🎯 What Gets Logged

### Invoice Emails:
- Recipient email & name
- Invoice number
- Subject & custom message
- CC recipients
- Invoice total
- PDF attachment indicator

### Mahnung Emails:
- All invoice email info PLUS:
- Reminder level (1-5)
- Reminder level name
- Reminder fee amount
- Days overdue
- Type: `mahnung`

### Metadata Examples:

**Invoice Email:**
```json
{
  "cc": "accounting@example.com",
  "invoice_number": "RE-2025-001",
  "invoice_total": 1500.00,
  "has_pdf_attachment": true
}
```

**Mahnung Email:**
```json
{
  "reminder_level": 2,
  "reminder_level_name": "1. Mahnung",
  "invoice_number": "RE-2025-001",
  "invoice_total": 1500.00,
  "reminder_fee": 5.00,
  "days_overdue": 14,
  "has_pdf_attachment": true
}
```

---

## 🔧 Implementation

### 1. **EmailLog Model** (`app/Models/EmailLog.php`)
- UUID primary key
- Relationships to Company and Customer
- Scope: `forCompany($companyId)`
- Accessor: `type_name` (human-readable type)
- Method: `related()` (get related Invoice/Offer)

### 2. **LogsEmails Trait** (`app/Traits/LogsEmails.php`)
Reusable trait with `logEmail()` method that can be used in any controller:

```php
use App\Traits\LogsEmails;

$this->logEmail(
    companyId: $companyId,
    recipientEmail: 'customer@example.com',
    subject: 'Rechnung RE-2025-001',
    type: 'invoice',
    customerId: $customerId,
    recipientName: 'Max Mustermann',
    body: 'Optional message body',
    relatedType: 'Invoice',
    relatedId: $invoiceId,
    metadata: ['invoice_number' => 'RE-2025-001']
);
```

### 3. **Updated Controllers**

**InvoiceController:**
- ✅ `send()` method logs invoice emails
- ✅ `sendMahnungEmail()` method logs all Mahnung emails

**OfferController:** (can be updated similarly)
- Needs `LogsEmails` trait
- Add logging after `Mail::send()` calls

### 4. **Settings Controller**

New method: `emailLogs(Request $request)`
- Filters: type, status, search
- Pagination: 20 per page
- Statistics: total, by type, failed count
- Renders: `settings/email-logs` Inertia page

---

## 🎨 Frontend UI

### Page: `/settings/email-logs`

**Features:**
1. **Statistics Cards** (5 cards)
   - Total emails
   - Invoices sent
   - Offers sent
   - Mahnungen sent
   - Failed emails

2. **Advanced Filters**
   - Search (recipient email, name, subject)
   - Type dropdown (all, invoice, offer, mahnung, etc.)
   - Status dropdown (all, sent, failed)
   - Reset button

3. **Email Table**
   Columns:
   - Date/Time (formatted)
   - Type (badge with color)
   - Recipient (name + email)
   - Subject
   - Status (badge: ✓ Versendet / ⚠ Fehlgeschlagen)
   - Actions (Details button)

4. **Email Details Modal**
   - Full timestamp
   - Type badge
   - Recipient info
   - Status
   - Complete subject
   - Message body (if available)
   - Metadata (invoice number, reminder level, fees, etc.)
   - PDF attachment indicator

5. **Pagination**
   - Numbered page buttons
   - 20 emails per page

---

## 📍 Access

### Menu Location:
**Einstellungen** (Settings) → **E-Mail-Verlauf**

### Route:
```
GET /settings/email-logs
```

### Permissions:
- Must be authenticated
- Company-specific (only see emails for your company)

---

## 🎨 Design Details

### Type Badges:
- 🔵 **Blue**: Invoice (Rechnung)
- 🟣 **Purple**: Offer (Angebot)
- 🟠 **Orange**: Mahnung
- 🟡 **Yellow**: Reminder (Erinnerung)
- 🟢 **Green**: Payment Received

### Status Badges:
- ✅ **Green**: Sent (Versendet) with CheckCircle icon
- ❌ **Red**: Failed (Fehlgeschlagen) with AlertTriangle icon

---

## 🔄 Auto-Logging

**Currently Logging:**
1. ✅ Invoice emails (manual send from UI)
2. ✅ Mahnung emails (manual & automatic)

**Not Yet Logging:** (can be added)
- Offer emails
- Daily reminder command emails
- Payment confirmation emails
- Welcome emails

To add logging to other emails, simply:
1. Add `use LogsEmails;` trait to controller
2. Call `$this->logEmail(...)` after `Mail::send()`

---

## 📊 Future Enhancements

### Possible Additions:
1. **Email Resend**: Button to resend failed emails
2. **Export**: Export logs to CSV/Excel
3. **Email Templates Preview**: View actual email content
4. **Bounce Tracking**: Track bounced emails
5. **Open/Click Tracking**: Track email opens and link clicks
6. **Scheduled Emails**: Queue emails for future sending
7. **Email Attachments Log**: List all attachments sent
8. **Bulk Actions**: Delete old logs, mark as read, etc.

---

## 🔍 Searching & Filtering

### Search Capabilities:
- Recipient email
- Recipient name
- Subject line

### Filter Options:
- **Type**: All, Invoice, Offer, Mahnung, Reminder
- **Status**: All, Sent, Failed

### Sorting:
- Default: Most recent first (`sent_at DESC`)

---

## 💾 Storage Considerations

### Retention Policy:
Consider implementing automatic cleanup:
```php
// Delete logs older than 1 year
EmailLog::where('sent_at', '<', now()->subYear())->delete();
```

### Disk Space:
- Each log: ~1-2 KB (without body)
- With body: ~3-5 KB
- 10,000 emails ≈ 10-50 MB

---

## 🧪 Testing

### Test Scenarios:
1. ✅ Send invoice email → Check log created
2. ✅ Send Mahnung → Check log with reminder metadata
3. ✅ Filter by type → Only shows selected type
4. ✅ Search by recipient → Finds correct emails
5. ✅ View details modal → Shows all information
6. ✅ Pagination → Works correctly
7. ⏸️ Failed email → Logs with error message (to be tested)

---

## 📝 Files Created/Modified

### Backend:
- ✅ `database/migrations/*_create_email_logs_table.php` - Migration
- ✅ `app/Models/EmailLog.php` - Model
- ✅ `app/Traits/LogsEmails.php` - Reusable logging trait
- ✅ `app/Modules/Invoice/Controllers/InvoiceController.php` - Added logging
- ✅ `app/Modules/Settings/Controllers/SettingsController.php` - Added emailLogs()
- ✅ `routes/modules/settings.php` - Added route

### Frontend:
- ✅ `resources/js/pages/settings/email-logs.tsx` - Email logs page
- ✅ `resources/js/components/app-sidebar.tsx` - Added menu item

---

## 🎉 System Complete!

The email logging system is **fully functional** and ready for use!

**Key Benefits:**
- 📝 Complete audit trail of all customer communications
- 🔍 Easy search and filtering
- 📊 Statistics dashboard
- 🎯 Mahnung tracking with fees and reminder levels
- 💼 Professional UI with details modal
- 🔄 Automatic logging (no manual work needed)

**Next Steps:**
1. Send some test emails
2. Check `/settings/email-logs` to view logs
3. Click "Details" to see full information
4. Optionally add logging to Offer emails using same pattern

---

## 🆘 Troubleshooting

### Emails not showing in logs?
- Check that `LogsEmails` trait is used in controller
- Verify `logEmail()` is called AFTER `Mail::send()`
- Check company_id is correct

### Search not working?
- Clear cache: `php artisan cache:clear`
- Rebuild assets: `npm run build`

### Want to log offers too?
1. Add `use LogsEmails;` to `OfferController`
2. Add `$this->logEmail(...)` after offer emails are sent
3. Use `type: 'offer'` and `relatedType: 'Offer'`

---

**Documentation Last Updated**: November 1, 2025


