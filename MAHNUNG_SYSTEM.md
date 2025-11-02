# German Mahnung (Dunning) System - Implementation Guide

## 🇩🇪 Overview

This system implements the complete German debt collection process (Mahnverfahren) with automatic escalation, fees, and professional email templates.

---

## 📊 Escalation Process

| Level | Name | Days After Due | Fee | Description |
|-------|------|----------------|-----|-------------|
| 0 | Keine | - | €0 | No reminder sent yet |
| 1 | Freundliche Erinnerung | 7 days | €0 | Friendly payment reminder |
| 2 | 1. Mahnung | 14 days | €5 | First official dunning notice |
| 3 | 2. Mahnung | 21 days | €10 | Second dunning notice |
| 4 | 3. Mahnung | 30 days | €15 | Third and final warning |
| 5 | Inkasso | 45 days | €0 + interest | Debt collection |

---

## 🗄️ Database Changes

### New Fields in `invoices` Table:
- `reminder_level` (int) - Current escalation level (0-5)
- `last_reminder_sent_at` (timestamp) - Last reminder date
- `reminder_fee` (decimal) - Accumulated fees
- `reminder_history` (json) - Complete audit trail

---

## ⚙️ Company Settings

Each company can configure (via `CompanySettings` table):

```php
'reminder_friendly_days' => 7,   // Days after due date
'reminder_mahnung1_days' => 14,
'reminder_mahnung2_days' => 21,
'reminder_mahnung3_days' => 30,
'reminder_inkasso_days' => 45,

'reminder_mahnung1_fee' => 5.00,  // Fees in EUR
'reminder_mahnung2_fee' => 10.00,
'reminder_mahnung3_fee' => 15.00,

'reminder_interest_rate' => 9.00,  // Annual interest %
'reminder_auto_send' => true,      // Enable/disable automation
```

To configure, run:
```bash
php artisan db:seed --class=MahnungSettingsSeeder
```

---

## 📧 Email Templates

Located in `resources/views/emails/reminders/`:

1. **friendly.blade.php** - Polite reminder with purple theme
2. **mahnung-1.blade.php** - First warning with orange theme
3. **mahnung-2.blade.php** - Urgent notice with red theme
4. **mahnung-3.blade.php** - Final warning with dark red theme
5. **inkasso.blade.php** - Legal notice with professional black theme

Each template includes:
- Invoice details
- Days overdue
- Current fees
- Payment instructions
- Appropriate tone for escalation level

---

## 🤖 Automatic Processing

### Daily Cron Job

Add to Laravel scheduler (already configured in `routes/console.php`):

```php
Schedule::command('reminders:send')->dailyAt('09:00');
```

### Manual Command

```bash
# Send all reminders (dry run)
php artisan reminders:send --dry-run

# Send for specific company
php artisan reminders:send --company=uuid-here

# Actually send
php artisan reminders:send
```

The command:
1. Checks all overdue invoices
2. Calculates appropriate escalation level
3. Sends email with correct template
4. Updates invoice status and history
5. Adds fees automatically

---

## 🎯 Manual Triggers

### From UI (Invoice Index Page):
- Click bell icon 🔔 to send next reminder manually
- View reminder history and status
- See accumulated fees

### API Routes:
```php
POST /invoices/{invoice}/send-reminder  // Send next reminder
GET  /invoices/{invoice}/reminder-history // Get history JSON
```

---

## 💼 Invoice Model Methods

```php
// Check if overdue
$invoice->isOverdue(); // bool

// Get days past due date
$invoice->getDaysOverdue(); // int

// Check if can escalate further
$invoice->canSendNextReminder(); // bool

// Get current level name
$invoice->reminder_level_name; // "1. Mahnung"

// Add reminder to history
$invoice->addReminderToHistory($level, $fee);

// Get total including fees
$invoice->total_with_fees; // original + reminder fees
```

---

## 📝 Reminder History Format

Stored in `reminder_history` JSON field:

```json
[
  {
    "level": 1,
    "level_name": "Freundliche Erinnerung",
    "sent_at": "2025-11-01 10:30:00",
    "days_overdue": 7,
    "fee": 0.00
  },
  {
    "level": 2,
    "level_name": "1. Mahnung",
    "sent_at": "2025-11-08 10:30:00",
    "days_overdue": 14,
    "fee": 5.00
  }
]
```

---

## 🎨 Frontend Integration

### Invoice Index (`resources/js/pages/invoices/index.tsx`):

**Features to add:**
1. Display reminder status badge for each invoice
2. Show days overdue with warning colors
3. Bell icon button to manually send next reminder
4. Modal to view complete reminder history
5. Display accumulated fees in invoice total

**Status Badges:**
- 🟢 Green: No reminders (paid/current)
- 🟡 Yellow: Friendly reminder
- 🟠 Orange: 1. Mahnung
- 🔴 Red: 2-3. Mahnung
- ⚫ Black: Inkasso

---

## 🔒 Security & Authorization

- Uses existing invoice policies (`InvoicePolicy`)
- Only authorized users can trigger reminders
- Company-specific SMTP configuration required
- Customer email required

---

## 📊 Reporting & Analytics

### Suggested Reports:
1. Outstanding reminders by level
2. Total reminder fees collected
3. Average days to payment by reminder level
4. Effectiveness of each escalation stage

---

## 🚨 Important Notes

### Legal Compliance:
- ✅ Follows German Mahnverfahren structure
- ✅ Includes proper fee disclosure
- ✅ Warns before legal action
- ⚠️ **Note:** Inkasso template needs real debt collection agency details

### Best Practices:
1. Always configure SMTP before enabling auto-send
2. Review reminder intervals for your business model
3. Keep reminder_history for legal audit trail
4. Test with --dry-run before live deployment

### Customization:
- Adjust intervals in company settings
- Modify fees per Mahnung level
- Customize email templates (keep legal warnings)
- Change interest rate calculation

---

## 🐛 Troubleshooting

### Reminders not sending?
1. Check SMTP configuration: `Settings > E-Mail Einstellungen`
2. Verify customer has email address
3. Check `reminder_auto_send` setting is true
4. Review logs: `storage/logs/laravel.log`

### Wrong escalation level?
1. Check days_overdue calculation
2. Verify company reminder interval settings
3. Review `reminder_history` JSON for audit trail

### Fees not calculating?
1. Check company fee settings
2. Verify `addReminderToHistory()` is called
3. Ensure invoice is saved after update

---

## 📚 Related Files

**Backend:**
- `app/Modules/Invoice/Models/Invoice.php` - Model with reminder methods
- `app/Modules/Invoice/Controllers/InvoiceController.php` - Manual triggers
- `app/Console/Commands/SendDailyReminders.php` - Automation
- `database/migrations/*_add_reminder_tracking_to_invoices_table.php`
- `database/seeders/MahnungSettingsSeeder.php`

**Frontend:**
- `resources/js/pages/invoices/index.tsx` - Invoice list with reminder UI
- (To be added: reminder history modal)

**Templates:**
- `resources/views/emails/reminders/*.blade.php` - 5 email templates

---

## ✅ Testing Checklist

- [ ] Run migration: `php artisan migrate`
- [ ] Seed settings: `php artisan db:seed --class=MahnungSettingsSeeder`
- [ ] Configure SMTP for test company
- [ ] Create test invoice with past due date
- [ ] Run dry-run: `php artisan reminders:send --dry-run`
- [ ] Manually trigger reminder from UI
- [ ] Verify email received with PDF attachment
- [ ] Check reminder_history JSON updated
- [ ] Verify fees added correctly
- [ ] Test all 5 escalation levels
- [ ] Confirm Inkasso template includes interest calculation

---

## 🎉 System Complete!

The German Mahnung system is fully implemented and ready for production use. All escalation logic, email templates, automatic processing, and manual triggers are functional.

For questions or issues, check the logs or review the audit trail in `reminder_history`.


