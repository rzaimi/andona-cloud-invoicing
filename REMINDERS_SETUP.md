# 🔔 Automated Daily Reminders Setup

This guide explains how to configure and use the automated email reminder system.

## 📍 Email Templates Location

All email templates are located in:
```
/Users/user01/Herd/invoicing/resources/views/emails/
```

### Available Templates:
- `invoice-sent.blade.php` - Send invoice to customer
- `invoice-reminder.blade.php` - Payment reminders (due soon or overdue)
- `payment-received.blade.php` - Payment confirmation
- `offer-sent.blade.php` - Send offer to customer
- `offer-reminder.blade.php` - Offer expiry reminders
- `offer-accepted.blade.php` - Offer acceptance confirmation
- `welcome.blade.php` - Welcome new customers

## 🤖 Automated Daily Reminders

The system automatically sends:

### 1. Invoice Reminders
- **Due in 3 days**: Friendly reminder about upcoming payment
- **Overdue**: Urgent reminders sent on days 1, 7, 14, and 30 after due date

### 2. Offer Reminders
- **Expiring soon**: Reminder when offer expires in 3 days or less

## ⚙️ Setup Instructions

### Step 1: Configure SMTP Settings

Make sure each company has SMTP settings configured:
1. Go to **Settings** → **E-Mail Einstellungen**
2. Enter your SMTP details (host, port, username, password)
3. Save settings

Companies without SMTP configured will be skipped.

### Step 2: Test the Command Manually

Run the command manually to test:

```bash
# Dry run (no emails sent, just shows what would be sent)
php artisan reminders:send --dry-run

# Send reminders for all companies
php artisan reminders:send

# Send reminders for a specific company only
php artisan reminders:send --company=COMPANY-UUID-HERE
```

### Step 3: Schedule Automated Execution

The command is already scheduled to run daily at 9:00 AM in `routes/console.php`.

To enable scheduled tasks, you need ONE of the following:

#### Option A: Using Cron (Production - Linux/Mac)

Add this to your crontab:
```bash
# Edit crontab
crontab -e

# Add this line (adjust path to your project)
* * * * * cd /Users/user01/Herd/invoicing && php artisan schedule:run >> /dev/null 2>&1
```

#### Option B: Using Laravel Scheduler Worker (Production - Any OS)

If you have Laravel 11+, you can use the scheduler worker:
```bash
php artisan schedule:work
```

Keep this running (use a process manager like Supervisor).

#### Option C: Manual Testing (Development)

For testing, you can manually trigger the scheduler:
```bash
php artisan schedule:run
```

### Step 4: Configure Admin Email (Optional)

Set the admin email for failure notifications in `.env`:
```env
ADMIN_EMAIL=your-admin@example.com
```

## 📊 Reminder Schedule

### Invoice Reminders:
| Days Until/After Due Date | Reminder Sent |
|---------------------------|---------------|
| 3 days before | ✓ Friendly reminder |
| Due date | - (no reminder) |
| 1 day overdue | ✓ First overdue notice |
| 7 days overdue | ✓ Second reminder |
| 14 days overdue | ✓ Third reminder |
| 30 days overdue | ✓ Final notice |

### Offer Reminders:
| Days Until Expiry | Reminder Sent |
|-------------------|---------------|
| 3 days | ✓ Expiring soon |
| 2 days | ✓ Expiring soon |
| 1 day | ✓ Last chance |

## 🧪 Testing

### Test with Dry Run:
```bash
php artisan reminders:send --dry-run
```

This shows what emails would be sent without actually sending them.

### Test for Specific Company:
```bash
php artisan reminders:send --company=YOUR-COMPANY-UUID
```

### Check Logs:
```bash
tail -f storage/logs/laravel.log
```

## 📝 Customizing Email Content

To customize email templates:

1. Navigate to: `resources/views/emails/`
2. Edit the `.blade.php` file you want to customize
3. Use Blade syntax and the available variables:
   - `$invoice` or `$offer` - The document object
   - `$company` - The company object
   - `$customer` - The customer object (from invoice/offer)

### Example: Customize Invoice Reminder

Edit `resources/views/emails/invoice-reminder.blade.php`:

```blade
<p>Sehr geehrte Damen und Herren{{ $invoice->customer ? ' von ' . $invoice->customer->name : '' }},</p>

<!-- Your custom text here -->
```

## 🎯 Changing the Schedule Time

Edit `routes/console.php`:

```php
// Run at 9:00 AM
Schedule::command('reminders:send')
    ->dailyAt('09:00')

// Or run at 8:30 AM
Schedule::command('reminders:send')
    ->dailyAt('08:30')

// Or run twice a day
Schedule::command('reminders:send')
    ->twiceDaily(9, 15) // 9:00 AM and 3:00 PM
```

## 🔍 Monitoring

### Check if scheduler is running:
```bash
php artisan schedule:list
```

### View scheduled tasks:
The output will show:
- Command name
- Next run time
- Description

### Check logs:
All reminder activities are logged to `storage/logs/laravel.log`:
- Successful sends
- Failures with error messages
- Skipped companies (no SMTP)

## 🚨 Troubleshooting

### Reminders not sending?

1. **Check SMTP Configuration**
   ```bash
   php artisan reminders:send --dry-run
   ```
   Look for "SMTP not configured" warnings

2. **Check if scheduler is running**
   ```bash
   php artisan schedule:list
   ```

3. **Check logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. **Test email manually**
   Try sending a regular invoice/offer email from the UI

5. **Check due dates**
   Make sure you have invoices due in 3 days or offers expiring soon

### Common Issues:

**"SMTP not configured"**
- Go to Settings → E-Mail Einstellungen
- Configure SMTP for each company

**"No emails sent"**
- Check if you have invoices/offers matching the criteria
- Use `--dry-run` to see what would be sent

**"Scheduler not running"**
- Make sure cron is configured (see Step 3)
- Or run `php artisan schedule:work` in background

## 📈 Best Practices

1. **Test First**: Always use `--dry-run` before enabling
2. **Monitor Logs**: Check logs regularly for failures
3. **Update Templates**: Customize templates to match your brand
4. **Set Admin Email**: Configure failure notifications
5. **Schedule Wisely**: Choose times when customers are likely to read emails (e.g., 9:00 AM)

## 🎨 Email Template Variables

### Available in All Templates:
- `$company` - Company object (name, email, phone, address, etc.)

### Invoice Templates (`invoice-sent`, `invoice-reminder`, `payment-received`):
- `$invoice` - Invoice object
  - `$invoice->number` - Invoice number
  - `$invoice->issue_date` - Issue date
  - `$invoice->due_date` - Due date
  - `$invoice->total` - Total amount
  - `$invoice->customer` - Customer object
  - `$invoice->items` - Invoice items

### Offer Templates (`offer-sent`, `offer-reminder`, `offer-accepted`):
- `$offer` - Offer object
  - `$offer->number` - Offer number
  - `$offer->issue_date` - Issue date
  - `$offer->valid_until` - Expiry date
  - `$offer->total` - Total amount
  - `$offer->customer` - Customer object
  - `$offer->items` - Offer items

### Welcome Template:
- `$customer` - Customer object (optional)
- `$specialOffer` - Special offer text (optional)

## 💡 Tips

1. **Adjust Reminder Frequency**: Edit the command to change which days reminders are sent
2. **Add More Reminder Types**: Create new templates for other scenarios
3. **Integrate with Calendar**: Use the calendar module to show upcoming reminders
4. **Track Sent Reminders**: Add a `reminders_sent` table to avoid duplicate sends
5. **Customer Preferences**: Add opt-out functionality for customers who don't want reminders

---

**Need Help?** Check the Laravel documentation on [Task Scheduling](https://laravel.com/docs/11.x/scheduling)


