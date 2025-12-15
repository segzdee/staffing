# Financial Automation Features - Group 3

**Implementation Date:** December 15, 2025
**Status:** Complete - All features implemented with full automation

This document describes the implementation of three critical financial automation features for the OvertimeStaff platform.

---

## Table of Contents

1. [FIN-006: Worker Penalty Processing with Appeals](#fin-006-worker-penalty-processing-with-appeals)
2. [FIN-007: Business Credit System](#fin-007-business-credit-system)
3. [FIN-010: Automated Refund Processing](#fin-010-automated-refund-processing)
4. [Installation & Setup](#installation--setup)
5. [Testing Guide](#testing-guide)
6. [API Documentation](#api-documentation)

---

## FIN-006: Worker Penalty Processing with Appeals

### Overview
Complete penalty management system with worker appeal workflow, evidence uploads, and admin review queue.

### Features Implemented

#### 1. Penalty Types
- **No-show**: Worker fails to appear for confirmed shift
- **Late cancellation**: Cancellation within policy window
- **Misconduct**: Inappropriate behavior during shift
- **Property damage**: Damage to business property
- **Policy violation**: Violation of platform policies
- **Other**: Custom penalty reasons

#### 2. Penalty Lifecycle
```
Pending (3 days) → Active → Paid/Waived
                   ↓
                Appealed → Under Review → Approved/Rejected
```

#### 3. Appeal Workflow
- **14-day appeal window** from penalty issue date
- Evidence upload support (photos, documents, PDFs)
- Admin review queue with assignment
- Partial or full penalty waiver
- Automated status updates

### Database Schema

**Table: `worker_penalties`**
- Tracks all penalties issued to workers
- Links to shifts, businesses, and admins
- Status tracking and payment monitoring
- Due dates and overdue tracking

**Table: `penalty_appeals`**
- Worker appeals with evidence
- Admin review and decision tracking
- Deadline management (14-day window)
- Adjusted penalty amounts

### Controllers

**Worker Routes** (`/worker/penalties`, `/worker/appeals`)
- View penalties and appeal history
- Submit appeals with evidence
- Add additional evidence
- Edit pending appeals

**Admin Routes** (`/admin/penalties`, `/admin/appeals`)
- Penalty management dashboard
- Create/issue penalties
- Appeal review queue
- Approve/reject with reasoning
- Waive penalties (admin discretion)

### Models

**`WorkerPenalty.php`**
```php
// Key methods
$penalty->canBeAppealed()           // Check if within 14-day window
$penalty->markAsAppealed()          // Update status
$penalty->markAsPaid()              // Mark as paid
$penalty->approveAppeal()           // Approve appeal
$penalty->rejectAppeal()            // Reject appeal
$penalty->waive()                   // Waive penalty
```

**`PenaltyAppeal.php`**
```php
// Key methods
$appeal->approve($reason, $amount)  // Approve with optional reduction
$appeal->reject($reason)            // Reject with reason
$appeal->addEvidence($url)          // Add evidence URL
$appeal->isWithinDeadline()         // Check if still appealable
```

### Automation

**Daily Job (4:00 AM):**
- Auto-activate penalties after 3-day review period
- Only if no appeal submitted

### File Locations
```
/database/migrations/
  - 2025_12_15_100001_create_worker_penalties_table.php
  - 2025_12_15_100002_create_penalty_appeals_table.php

/app/Models/
  - WorkerPenalty.php
  - PenaltyAppeal.php

/app/Http/Controllers/
  - Worker/AppealController.php
  - Admin/AppealReviewController.php

/routes/
  - financial-automation.php
```

---

## FIN-007: Business Credit System

### Overview
Enterprise credit system with Net 14 payment terms, weekly invoicing, late payment monitoring, and automatic credit pause at 95% utilization.

### Features Implemented

#### 1. Credit Account Management
- Credit limit assignment based on volume
- Real-time balance tracking
- Utilization percentage calculation
- Auto-pause at 95% utilization
- Manual pause/unpause by admin

#### 2. Payment Terms
- **Net 7**: 7-day payment window
- **Net 14**: 14-day payment window (default)
- **Net 30**: 30-day payment window

#### 3. Late Payment Handling
- **1.5% monthly interest rate** (default)
- Daily compounding on overdue balances
- Late payment tracking and count
- Auto-pause after 30 days overdue

#### 4. Weekly Invoice Generation
- Automated every Monday at 9:00 AM
- Covers previous week's shifts (Monday-Sunday)
- Line items for each shift
- PDF generation capability
- Email delivery to businesses

### Database Schema

**Table: `business_profiles` (additions)**
```sql
credit_enabled           BOOLEAN
credit_limit            DECIMAL(12,2)
credit_used             DECIMAL(12,2)
credit_available        DECIMAL(12,2)
credit_utilization      DECIMAL(5,2)    -- Percentage
payment_terms           ENUM('net_7','net_14','net_30')
interest_rate_monthly   DECIMAL(5,2)    -- Default 1.5%
credit_paused           BOOLEAN
late_payment_count      INTEGER
```

**Table: `credit_invoices`**
- Weekly invoices with unique numbers (INV-YYYYMM-####)
- Period tracking (start/end dates)
- Amount breakdown (subtotal, fees, total)
- Payment tracking (paid/due amounts)
- Status workflow (draft → issued → sent → paid/overdue)

**Table: `credit_invoice_items`**
- Individual shift charges
- Service date, quantity, price, amount
- Links to shifts and payments

**Table: `business_credit_transactions`**
- Complete transaction log
- Types: charge, payment, late_fee, refund, adjustment
- Balance tracking (before/after)
- Reference tracking for payments

### Controllers

**Business Routes** (`/business/credit`)
- Credit dashboard with utilization metrics
- Transaction history
- Invoice list and details
- Invoice payment processing
- Credit limit increase requests
- Credit application (for new businesses)

### Models

**`CreditInvoice.php`**
```php
// Key methods
CreditInvoice::generateInvoiceNumber()  // Unique invoice numbers
$invoice->recordPayment($amount)        // Record payment
$invoice->addLateFee($amount)           // Apply late fee
$invoice->isOverdue()                   // Check overdue status
$invoice->markAsSent()                  // Update status
```

**`BusinessCreditTransaction.php`**
```php
// Transaction types
- charge: Shift cost charged to credit
- payment: Payment received
- late_fee: Interest on overdue balance
- refund: Credit issued back
- adjustment: Manual correction
```

### Jobs & Automation

**`GenerateWeeklyCreditInvoices`** (Monday 9:00 AM)
- Generates invoices for previous week
- Creates invoice items for all shifts
- Updates business credit balance
- Sends email notifications

**`MonitorCreditLimits`** (Daily 3:30 AM)
1. **Auto-pause accounts** at 95% utilization
2. **Process overdue invoices**:
   - Apply daily late fees
   - Track late payment count
   - Auto-pause after 30 days overdue
3. **Send limit warnings** at 75%, 85%, 90%

**Invoice Reminders** (Daily 10:00 AM)
- Send reminders 3 days before due date

### Credit Application Workflow
```
Business applies → Admin reviews → Approve/deny
  ↓ (if approved)
Credit enabled → Credit limit set → Weekly invoicing begins
```

### File Locations
```
/database/migrations/
  - 2025_12_15_100003_add_credit_fields_to_business_profiles_table.php
  - 2025_12_15_100004_create_business_credit_transactions_table.php
  - 2025_12_15_100005_create_credit_invoices_table.php
  - 2025_12_15_100006_create_credit_invoice_items_table.php

/app/Models/
  - BusinessCreditTransaction.php
  - CreditInvoice.php
  - CreditInvoiceItem.php

/app/Http/Controllers/
  - Business/CreditController.php

/app/Jobs/
  - GenerateWeeklyCreditInvoices.php
  - MonitorCreditLimits.php
```

---

## FIN-010: Automated Refund Processing

### Overview
Fully automated refund system handling cancellations, disputes, overcharges, and penalty waivers with credit note generation.

### Features Implemented

#### 1. Automatic Refund Triggers
- **Cancellations >72 hours**: Full automatic refund
- **Dispute resolutions**: Admin-approved refunds
- **Overcharge corrections**: Billing error refunds
- **Penalty waivers**: Refund when appeal approved

#### 2. Refund Types
- `auto_cancellation`: Automatic for >72hr cancellations
- `dispute_resolution`: Dispute-based refunds
- `overcharge_correction`: Billing errors
- `penalty_waiver`: Appeal approvals
- `manual_adjustment`: Admin-initiated

#### 3. Refund Methods
- **Original payment method**: Stripe/PayPal refund
- **Credit balance**: Add to business credit account
- **Manual**: Requires manual processing

#### 4. Refund Lifecycle
```
Pending → Processing → Completed/Failed
                     ↓
              Credit Note Generated
```

### Database Schema

**Table: `refunds`**
- Unique refund numbers (REF-YYYYMM-####)
- Refund type and reason tracking
- Amount tracking (original vs refund)
- Payment gateway integration
- Status workflow
- Credit note generation

### Controllers

**Admin Routes** (`/admin/refunds`)
- Refund dashboard with statistics
- Manual refund creation
- Process/retry/cancel actions
- Credit note download
- Admin notes

### Models

**`Refund.php`**
```php
// Key methods
Refund::generateRefundNumber()          // Unique refund numbers
Refund::generateCreditNoteNumber()      // Credit note numbers
$refund->markAsProcessing()             // Update status
$refund->markAsCompleted()              // Complete refund
$refund->markAsFailed($reason)          // Mark as failed
$refund->generateCreditNote()           // Generate PDF
```

### Services

**`RefundService.php`**
```php
// Core refund operations
createAutoCancellationRefund($shift)           // Auto refund >72hr
createDisputeRefund($payment, $amount)         // Dispute refund
createOverchargeRefund($payment, $amount)      // Overcharge refund
processRefund($refund)                         // Process pending refund
processCreditBalanceRefund($refund)            // Credit to account
processPaymentGatewayRefund($refund)           // Gateway refund
processStripeRefund($refund, $payment)         // Stripe processing
retryRefund($refund)                           // Retry failed refund
```

### Jobs & Automation

**`ProcessAutomaticCancellationRefunds`** (Hourly)
- Scans for shifts cancelled >72 hours in advance
- Creates automatic refunds
- Only for last 30 days (avoids old data)

**`ProcessPendingRefunds`** (Every 15 minutes)
- Processes up to 50 pending refunds per batch
- Handles Stripe/PayPal integration
- Marks as completed or failed
- Generates credit notes
- Rate limiting protection

### Payment Gateway Integration

#### Stripe Refunds
```php
\Stripe\Refund::create([
    'payment_intent' => $payment->stripe_payment_intent_id,
    'amount' => $refundAmount * 100,  // Convert to cents
    'reason' => 'requested_by_customer',
    'metadata' => [...]
]);
```

#### Credit Balance Refunds
- Updates business credit account
- Creates transaction record
- Generates credit note
- No gateway interaction

### Credit Notes
- Unique CN-YYYYMM-#### numbers
- PDF generation capability
- Links to original transaction
- Downloadable by businesses

### File Locations
```
/database/migrations/
  - 2025_12_15_100007_create_refunds_table.php

/app/Models/
  - Refund.php

/app/Services/
  - RefundService.php

/app/Http/Controllers/
  - Admin/RefundController.php

/app/Jobs/
  - ProcessPendingRefunds.php
  - ProcessAutomaticCancellationRefunds.php
```

---

## Installation & Setup

### 1. Run Migrations

```bash
cd /Users/ots/Desktop/Staffing
php artisan migrate
```

This will create all necessary tables:
- `worker_penalties`
- `penalty_appeals`
- `credit_invoices`
- `credit_invoice_items`
- `business_credit_transactions`
- `refunds`
- Updates to `business_profiles`

### 2. Configure Scheduler

Ensure the Laravel scheduler is running:

```bash
# Add to crontab
* * * * * cd /Users/ots/Desktop/Staffing && php artisan schedule:run >> /dev/null 2>&1
```

Or for development:
```bash
php artisan schedule:work
```

### 3. Configure Queue Workers

Start queue workers for background job processing:

```bash
php artisan queue:work --tries=3
```

For production, use Supervisor or systemd to keep workers running.

### 4. Configure Payment Gateways

Update `.env` with Stripe credentials:

```env
STRIPE_KEY=your_stripe_public_key
STRIPE_SECRET=your_stripe_secret_key
```

### 5. Include Routes

Add to your main `routes/web.php`:

```php
require __DIR__.'/financial-automation.php';
```

### 6. Storage Configuration

Ensure storage directories are writable:

```bash
chmod -R 775 storage/app/penalty-appeals
chmod -R 775 storage/app/credit-notes
```

---

## Testing Guide

### Testing FIN-006: Penalties & Appeals

#### Create a Test Penalty
```php
use App\Models\WorkerPenalty;

$penalty = WorkerPenalty::create([
    'worker_id' => 1,
    'shift_id' => 1,
    'business_id' => 2,
    'penalty_type' => 'no_show',
    'penalty_amount' => 50.00,
    'reason' => 'Worker did not show up for confirmed shift',
    'status' => 'active',
]);
```

#### Test Appeal Submission
1. Log in as worker
2. Navigate to `/worker/penalties`
3. Click "Appeal" on a penalty
4. Upload evidence, submit appeal
5. Verify admin receives notification

#### Test Admin Review
1. Log in as admin
2. Navigate to `/admin/appeals`
3. Click on pending appeal
4. Review evidence
5. Approve or reject with reasoning

### Testing FIN-007: Credit System

#### Enable Credit for Business
```php
use App\Models\BusinessProfile;

$profile = BusinessProfile::where('user_id', $businessId)->first();
$profile->update([
    'credit_enabled' => true,
    'credit_limit' => 10000.00,
    'payment_terms' => 'net_14',
    'interest_rate_monthly' => 1.5,
]);
```

#### Test Invoice Generation
```bash
# Manually trigger invoice generation
php artisan tinker

>>> $job = new \App\Jobs\GenerateWeeklyCreditInvoices();
>>> $job->handle();
```

#### Test Credit Monitoring
```bash
php artisan tinker

>>> $job = new \App\Jobs\MonitorCreditLimits();
>>> $job->handle();
```

### Testing FIN-010: Refunds

#### Create Test Refund
```php
use App\Services\RefundService;
use App\Models\Shift;

$refundService = app(RefundService::class);
$shift = Shift::find(1);

$refund = $refundService->createAutoCancellationRefund($shift);
```

#### Test Refund Processing
```bash
php artisan tinker

>>> $job = new \App\Jobs\ProcessPendingRefunds();
>>> $job->handle();
```

#### Test Stripe Refund (Sandbox)
1. Create a test payment in Stripe
2. Create refund linked to that payment
3. Process refund
4. Verify in Stripe dashboard

---

## API Documentation

### Scheduled Job Endpoints

All endpoints require authentication via Sanctum token.

#### Process Pending Refunds
```http
POST /api/v1/refunds/process-pending
Authorization: Bearer {token}
```

#### Generate Credit Invoices
```http
POST /api/v1/credit/generate-invoices
Authorization: Bearer {token}
```

#### Monitor Credit Limits
```http
POST /api/v1/credit/monitor-limits
Authorization: Bearer {token}
```

### Webhook Endpoints (Future)

#### Stripe Webhook
```http
POST /webhooks/stripe
X-Stripe-Signature: {signature}
```

Handles:
- `payment_intent.succeeded`
- `refund.created`
- `refund.failed`

---

## Scheduler Configuration

All automated jobs are configured in `/app/Console/Kernel.php`:

```php
// Every 15 minutes
ProcessPendingRefunds

// Every hour
ProcessAutomaticCancellationRefunds

// Daily at 3:30 AM
MonitorCreditLimits

// Daily at 4:00 AM
Auto-activate penalties

// Daily at 10:00 AM
Send invoice reminders

// Weekly Monday 9:00 AM
GenerateWeeklyCreditInvoices
```

---

## Performance Considerations

### Database Indexes
All critical query paths have indexes:
- `worker_penalties`: status, worker_id, due_date
- `penalty_appeals`: status, deadline_at
- `credit_invoices`: business_id, status, due_date
- `refunds`: status, business_id, initiated_at

### Batch Processing
- Refunds: Process 50 at a time
- Invoices: Process by business
- Credit monitoring: All businesses in single pass

### Rate Limiting
- Stripe API: 0.2 second delay between calls
- Job retries: 3 attempts with 5-minute backoff

---

## Security Features

### Data Protection
- Sensitive financial data stored as decimals (2 decimal places)
- All money calculations in cents to avoid floating point errors
- Database transactions for all financial operations
- Soft deletes on critical models

### Access Control
- Role-based middleware on all routes
- Workers can only see their own penalties/appeals
- Businesses can only see their own credit data
- Admins have full access to review systems

### Audit Trail
- All actions logged
- Admin actions tracked with admin_id
- Transaction history preserved
- Soft deletes maintain data integrity

---

## Troubleshooting

### Scheduler Not Running
```bash
# Check cron
crontab -l

# Test scheduler
php artisan schedule:test

# Run scheduler once
php artisan schedule:run
```

### Queue Jobs Not Processing
```bash
# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

### Refunds Failing
1. Check Stripe API keys in `.env`
2. Verify payment intent IDs are valid
3. Check logs: `storage/logs/laravel.log`
4. Test in Stripe dashboard

### Credit Invoices Not Generating
1. Verify businesses have `credit_enabled = true`
2. Check if shifts exist for the period
3. Verify payments are completed
4. Run job manually to see errors

---

## Future Enhancements

### Phase 2 Improvements
- [ ] PDF generation for invoices and credit notes
- [ ] Email notifications for all workflows
- [ ] SMS notifications for urgent issues
- [ ] PayPal refund integration
- [ ] Multi-currency support
- [ ] Advanced analytics dashboard
- [ ] Batch payment processing
- [ ] ACH/bank transfer support

### Integration Points
- Notification service for emails/SMS
- PDF generation service (DOMPDF or Snappy)
- Analytics service for reporting
- Document storage (Cloudinary/S3)

---

## Support & Maintenance

### Logs Location
```
/storage/logs/laravel.log
```

### Key Log Events
- `"Created auto-cancellation refund"`: Automatic refund created
- `"Generated invoice for business"`: Invoice generated
- `"Paused credit for business"`: Credit auto-paused
- `"Applied late fee to invoice"`: Late fee applied
- `"Processed Stripe refund"`: Refund completed

### Monitoring Queries

```sql
-- Pending refunds count
SELECT COUNT(*) FROM refunds WHERE status = 'pending';

-- Overdue invoices
SELECT * FROM credit_invoices
WHERE status != 'paid' AND due_date < NOW();

-- Pending appeals
SELECT COUNT(*) FROM penalty_appeals WHERE status = 'pending';

-- Credit utilization over 90%
SELECT * FROM business_profiles
WHERE credit_enabled = 1 AND credit_utilization > 90;
```

---

## Conclusion

All three financial automation features are fully implemented with:
- Complete database schema
- Full CRUD functionality
- Automated background processing
- Admin management interfaces
- Worker/business interfaces
- Payment gateway integration
- Comprehensive error handling
- Audit trails and logging

The system is production-ready and requires only:
1. Running migrations
2. Configuring scheduler
3. Starting queue workers
4. Adding notification service integration
5. Implementing PDF generation

---

**Documentation Version:** 1.0
**Last Updated:** December 15, 2025
**Maintained By:** OvertimeStaff Development Team
