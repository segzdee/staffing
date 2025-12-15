# Quick Start Guide - Financial Automation Group 3

This guide will help you get the three financial automation features up and running in 10 minutes.

---

## Step 1: Run Migrations (2 minutes)

```bash
cd /Users/ots/Desktop/Staffing

# Run all new migrations
php artisan migrate

# Expected output:
# - 2025_12_15_100001_create_worker_penalties_table
# - 2025_12_15_100002_create_penalty_appeals_table
# - 2025_12_15_100003_add_credit_fields_to_business_profiles_table
# - 2025_12_15_100004_create_business_credit_transactions_table
# - 2025_12_15_100005_create_credit_invoices_table
# - 2025_12_15_100006_create_credit_invoice_items_table
# - 2025_12_15_100007_create_refunds_table
```

---

## Step 2: Verify Database (1 minute)

```bash
# Open MySQL
mysql -u sail -p
# Password: password

USE overtimestaff;

# Check new tables
SHOW TABLES LIKE '%penalty%';
SHOW TABLES LIKE '%credit%';
SHOW TABLES LIKE '%refund%';

# Verify business_profiles columns
DESCRIBE business_profiles;

EXIT;
```

---

## Step 3: Include Routes (1 minute)

Edit `/Users/ots/Desktop/Staffing/routes/web.php`:

```php
// At the end of the file, add:
require __DIR__.'/financial-automation.php';
```

---

## Step 4: Test Feature 1 - Penalties (2 minutes)

```bash
php artisan tinker
```

```php
// Create a test penalty
$penalty = \App\Models\WorkerPenalty::create([
    'worker_id' => 1,  // Replace with actual worker user ID
    'shift_id' => 1,   // Replace with actual shift ID
    'business_id' => 2, // Replace with actual business user ID
    'penalty_type' => 'no_show',
    'penalty_amount' => 50.00,
    'reason' => 'Worker did not show up for confirmed shift on Dec 15',
    'status' => 'active',
    'issued_at' => now(),
    'due_date' => now()->addDays(7),
]);

echo "Penalty created: {$penalty->id}\n";

// Test if it can be appealed
echo $penalty->canBeAppealed() ? "Can be appealed\n" : "Cannot be appealed\n";

// Create an appeal
$appeal = \App\Models\PenaltyAppeal::create([
    'penalty_id' => $penalty->id,
    'worker_id' => $penalty->worker_id,
    'appeal_reason' => 'I was sick that day and tried to call but nobody answered. I have a doctor\'s note as proof.',
    'status' => 'pending',
    'submitted_at' => now(),
    'deadline_at' => now()->addDays(14),
]);

echo "Appeal created: {$appeal->id}\n";

// Verify penalty status changed
$penalty->refresh();
echo "Penalty status: {$penalty->status}\n"; // Should be 'appealed'

exit();
```

---

## Step 5: Test Feature 2 - Credit System (2 minutes)

```bash
php artisan tinker
```

```php
// Enable credit for a business
$business = \App\Models\User::where('role', 'business')->first();

if (!$business) {
    echo "No business found. Create one first.\n";
    exit();
}

$profile = $business->businessProfile;

$profile->update([
    'credit_enabled' => true,
    'credit_limit' => 10000.00,
    'credit_used' => 0.00,
    'credit_available' => 10000.00,
    'credit_utilization' => 0.00,
    'payment_terms' => 'net_14',
    'interest_rate_monthly' => 1.5,
]);

echo "Credit enabled for business: {$business->name}\n";
echo "Credit limit: \${$profile->credit_limit}\n";

// Create a test invoice
$invoice = \App\Models\CreditInvoice::create([
    'business_id' => $business->id,
    'invoice_date' => now(),
    'due_date' => now()->addDays(14),
    'period_start' => now()->startOfWeek(),
    'period_end' => now()->endOfWeek(),
    'subtotal' => 500.00,
    'late_fees' => 0,
    'adjustments' => 0,
    'total_amount' => 500.00,
    'amount_paid' => 0,
    'amount_due' => 500.00,
    'status' => 'issued',
]);

echo "Invoice created: {$invoice->invoice_number}\n";

// Create invoice item
$item = \App\Models\CreditInvoiceItem::create([
    'invoice_id' => $invoice->id,
    'description' => 'Test shift - John Doe',
    'service_date' => now(),
    'quantity' => 1,
    'unit_price' => 500.00,
    'amount' => 500.00,
]);

echo "Invoice item created\n";

// Update credit balance
$profile->credit_used = 500.00;
$profile->credit_available = $profile->credit_limit - 500.00;
$profile->credit_utilization = ($profile->credit_used / $profile->credit_limit) * 100;
$profile->save();

echo "Credit used: \${$profile->credit_used}\n";
echo "Credit available: \${$profile->credit_available}\n";
echo "Utilization: {$profile->credit_utilization}%\n";

exit();
```

---

## Step 6: Test Feature 3 - Refunds (2 minutes)

```bash
php artisan tinker
```

```php
// Create a test refund
$business = \App\Models\User::where('role', 'business')->first();

$refund = \App\Models\Refund::create([
    'business_id' => $business->id,
    'shift_id' => 1, // Replace with actual shift ID
    'refund_amount' => 100.00,
    'original_amount' => 100.00,
    'refund_type' => 'auto_cancellation',
    'refund_reason' => 'cancellation_72hr',
    'reason_description' => 'Shift cancelled more than 72 hours in advance',
    'refund_method' => 'credit_balance',
    'status' => 'pending',
    'initiated_at' => now(),
]);

echo "Refund created: {$refund->refund_number}\n";

// Test refund processing (credit balance)
$refundService = app(\App\Services\RefundService::class);
$success = $refundService->processRefund($refund);

if ($success) {
    echo "Refund processed successfully\n";
    $refund->refresh();
    echo "Status: {$refund->status}\n";
    echo "Credit note: {$refund->credit_note_number}\n";
} else {
    echo "Refund processing failed\n";
}

exit();
```

---

## Step 7: Test Automated Jobs (Optional)

### Test Refund Processing Job
```bash
php artisan tinker
```

```php
// Dispatch the job
$job = new \App\Jobs\ProcessPendingRefunds();
$job->handle();

// Check results
$completed = \App\Models\Refund::completed()->count();
echo "Completed refunds: {$completed}\n";

exit();
```

### Test Credit Monitoring Job
```bash
php artisan tinker
```

```php
$job = new \App\Jobs\MonitorCreditLimits();
$job->handle();

echo "Credit monitoring complete\n";

exit();
```

### Test Invoice Generation Job
```bash
php artisan tinker
```

```php
// Generate invoices for last week
$job = new \App\Jobs\GenerateWeeklyCreditInvoices();
$job->handle();

$invoices = \App\Models\CreditInvoice::count();
echo "Total invoices: {$invoices}\n";

exit();
```

---

## Step 8: Start Scheduler (For Automation)

### Option A: Development
```bash
# In a separate terminal
php artisan schedule:work
```

This will run the scheduler every minute (development only).

### Option B: Production (Cron)
```bash
# Open crontab
crontab -e

# Add this line:
* * * * * cd /Users/ots/Desktop/Staffing && php artisan schedule:run >> /dev/null 2>&1

# Save and exit
```

### Verify Scheduler
```bash
# See upcoming scheduled tasks
php artisan schedule:list

# Test scheduler once
php artisan schedule:run
```

---

## Step 9: Start Queue Workers

```bash
# In a separate terminal
php artisan queue:work --tries=3
```

Keep this running to process background jobs.

---

## Step 10: Test Routes (Optional)

### Start Development Server
```bash
php artisan serve
```

### Test Worker Routes
```
http://localhost:8000/worker/penalties
http://localhost:8000/worker/appeals/create
```

### Test Business Routes
```
http://localhost:8000/business/credit
http://localhost:8000/business/credit/invoices
```

### Test Admin Routes
```
http://localhost:8000/admin/penalties
http://localhost:8000/admin/appeals
http://localhost:8000/admin/refunds
```

---

## Verification Checklist

- [ ] All 7 migrations ran successfully
- [ ] Worker penalty created and appealed
- [ ] Business credit account enabled
- [ ] Test invoice created
- [ ] Test refund created and processed
- [ ] Scheduler configured
- [ ] Queue workers running
- [ ] Routes accessible

---

## Troubleshooting

### Migrations Fail
```bash
# Check database connection
php artisan tinker
>>> DB::connection()->getPdo();

# If connection fails, check .env:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=overtimestaff
DB_USERNAME=sail
DB_PASSWORD=password
```

### Class Not Found Errors
```bash
# Clear and rebuild autoload
composer dump-autoload

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Queue Jobs Not Processing
```bash
# Check queue connection in .env
QUEUE_CONNECTION=database

# Check failed jobs
php artisan queue:failed

# Retry all failed
php artisan queue:retry all
```

### Route Not Found
```bash
# Make sure you added to routes/web.php:
require __DIR__.'/financial-automation.php';

# Clear route cache
php artisan route:clear

# List all routes
php artisan route:list | grep -i penalty
php artisan route:list | grep -i credit
php artisan route:list | grep -i refund
```

---

## Next Steps

1. **Create Views**: Implement Blade templates for all routes
2. **Add Notifications**: Integrate email/SMS notifications
3. **Generate PDFs**: Implement PDF generation for invoices and credit notes
4. **Write Tests**: Create unit and integration tests
5. **Deploy**: Move to production with proper monitoring

---

## Quick Commands Reference

```bash
# Check pending refunds
php artisan tinker
>>> \App\Models\Refund::pending()->count()

# Check pending appeals
>>> \App\Models\PenaltyAppeal::pending()->count()

# Check credit accounts
>>> \App\Models\BusinessProfile::where('credit_enabled', true)->count()

# View recent invoices
>>> \App\Models\CreditInvoice::latest()->limit(5)->get(['invoice_number', 'status', 'total_amount'])

# Check queue status
php artisan queue:work --once

# View logs
tail -f storage/logs/laravel.log
```

---

## Support

For issues or questions:
1. Check logs: `/storage/logs/laravel.log`
2. Review documentation: `/docs/FINANCIAL_AUTOMATION_GROUP_3.md`
3. Check implementation summary: `/GROUP_3_IMPLEMENTATION_SUMMARY.md`

---

**Quick Start Complete!** All three financial automation features should now be operational.
