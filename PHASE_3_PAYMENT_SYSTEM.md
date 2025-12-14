# Phase 3: Payment System & Instant Payouts - COMPLETE ✅

## Overview

Phase 3 implements a complete payment system with:
- Stripe Payment Intent integration for escrow
- Automated 15-minute instant payouts
- Payment dispute handling
- Comprehensive transaction history
- Automated payment processing via scheduled jobs

---

## Architecture

### Payment Flow

```
1. SHIFT ASSIGNMENT
   ↓
2. HOLD IN ESCROW (Business charged via Payment Intent)
   ↓ [shift happens]
3. WORKER CHECKS OUT (shift completed)
   ↓ [wait 15 minutes]
4. RELEASE FROM ESCROW (payment calculated based on actual hours)
   ↓ [wait 15 minutes]
5. INSTANT PAYOUT (transfer to worker's Stripe Connect account)
   ↓
6. COMPLETED (worker receives funds)
```

### Timing Breakdown

- **T+0**: Shift assigned → Payment held in escrow immediately
- **T+shift duration**: Worker checks out
- **T+15 minutes**: Payment released from escrow (recalculated for actual hours)
- **T+30 minutes**: Instant payout processed to worker
- **T+30-60 minutes**: Worker receives funds in bank account (Stripe instant payout)

---

## Components Implemented

### 1. Service Layer ✅

**File:** `/app/Services/ShiftPaymentService.php` (Already existed - verified)

**Key Methods:**
- `holdInEscrow(ShiftAssignment $assignment)` - Capture payment from business
- `releaseFromEscrow(ShiftAssignment $assignment)` - Release 15 min after completion
- `instantPayout(ShiftPayment $payment)` - Transfer to worker
- `processReadyPayouts()` - Batch process all ready payouts
- `handleDispute($assignment, $reason)` - File payment dispute
- `resolveDispute($payment, $resolution)` - Admin dispute resolution

### 2. Automated Jobs ✅

**Created Files:**
- `/app/Jobs/ReleaseEscrowPayments.php` - Runs every 5 minutes
- `/app/Jobs/ProcessInstantPayouts.php` - Runs every minute

**Registered in:** `/app/Console/Kernel.php`

```php
// Runs every 5 minutes - releases payments 15 min after shift completion
$schedule->job(new ReleaseEscrowPayments)->everyFiveMinutes()->withoutOverlapping();

// Runs every minute - processes instant payouts 15 min after release
$schedule->job(new ProcessInstantPayouts)->everyMinute()->withoutOverlapping();
```

**To enable scheduled jobs:**
```bash
# Add to crontab (crontab -e):
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### 3. Webhook Handlers ✅

**File:** `/app/Http/Controllers/StripeWebHookController.php` (Already existed - verified)

**Handlers:**
- `handlePaymentIntentSucceeded()` - Confirms escrow hold
- `handlePaymentIntentPaymentFailed()` - Handles business payment failure
- `handleTransferCreated()` - Logs payout initiation
- `handleTransferPaid()` - Confirms payout completion

**Webhook URL:** `https://yourdomain.com/stripe/webhook`

**Required Stripe Webhooks:**
```
payment_intent.succeeded
payment_intent.payment_failed
transfer.created
transfer.paid
```

### 4. Transaction History ✅

**Created Files:**
- `/resources/views/my/transactions.blade.php` - Transaction history UI
- `/app/Http/Controllers/TransactionsController.php` - Transaction controller

**Features:**
- Complete payment history for workers and businesses
- Filter by status (all/payouts/pending/escrow/disputes)
- Payment timeline visualization
- Dispute filing interface
- CSV export functionality

**Route:** `/my/transactions`

### 5. Database Model ✅

**File:** `/app/Models/ShiftPayment.php` (Already existed - verified)

**Key Fields:**
- `shift_assignment_id` - Links to assignment
- `worker_id` / `business_id` - Participants
- `amount_gross` / `platform_fee` / `amount_net` - Money amounts
- `escrow_held_at` / `released_at` / `payout_completed_at` - Timeline
- `stripe_payment_intent_id` / `stripe_transfer_id` - Stripe references
- `status` - Current state (in_escrow, released, paid_out, disputed)
- `disputed` / `dispute_reason` - Dispute tracking

**Status Flow:**
```
pending → in_escrow → released → paid_out
                           ↓
                      disputed (can branch at any time)
```

---

## How It Works

### Step 1: Shift Assignment (Escrow Hold)

When a business assigns a worker to a shift:

```php
use App\Services\ShiftPaymentService;

$paymentService = new ShiftPaymentService();
$shiftPayment = $paymentService->holdInEscrow($assignment);
```

**What Happens:**
1. Creates Stripe Payment Intent for estimated amount (rate × hours)
2. Captures funds from business immediately
3. Creates `ShiftPayment` record with status `pending`
4. Webhook confirms → status changes to `in_escrow`
5. Funds held in platform Stripe account

### Step 2: Shift Completion (Escrow Release)

Worker checks out 15 minutes after shift ends:

**Automatic (via ReleaseEscrowPayments job):**
```php
// Finds all completed shifts where:
// - check_out_at >= 15 minutes ago
// - payment status = 'in_escrow'

$paymentService->releaseFromEscrow($assignment);
```

**What Happens:**
1. Calculates actual payment based on hours_worked
2. Refunds business if actual < estimated
3. Updates `ShiftPayment` amounts
4. Changes status to `released`
5. Sets `released_at` timestamp

### Step 3: Instant Payout (Worker Gets Paid)

15 minutes after release (30 minutes total after checkout):

**Automatic (via ProcessInstantPayouts job):**
```php
// Finds all payments where:
// - status = 'released'
// - released_at >= 15 minutes ago
// - not disputed

$paymentService->processReadyPayouts();
```

**What Happens:**
1. Creates Stripe Transfer to worker's Connect account
2. Updates status to `paid_out`
3. Records transfer ID
4. Notifies worker
5. Worker receives funds in 15-30 minutes (Stripe instant payout)

### Step 4: Dispute Handling

If issues arise, either party can file dispute:

```php
$paymentService->handleDispute($assignment, "Hours incorrect: worked 6 hours, charged for 8");
```

**What Happens:**
1. Payment status → `disputed`
2. Payout process halted
3. Admin notified
4. Both parties notified
5. Admin reviews and resolves via:
   - Refund business (full or partial)
   - Pay worker (full or adjusted)
   - Split difference

---

## Testing the Payment System

### Prerequisites

1. **Stripe Account Setup:**
   ```bash
   # Add to .env
   STRIPE_KEY=pk_test_...
   STRIPE_SECRET=sk_test_...
   STRIPE_WEBHOOK_SECRET=whsec_...
   ```

2. **Enable Cron for Scheduled Jobs:**
   ```bash
   * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
   ```

3. **Configure Webhooks in Stripe Dashboard:**
   - Go to Developers → Webhooks
   - Add endpoint: `https://yourdomain.com/stripe/webhook`
   - Select events: payment_intent.*, transfer.*

### Test Scenario 1: Successful Payment Flow

1. **Create test business with payment method:**
   ```bash
   # In Stripe test mode, use test card:
   # 4242 4242 4242 4242
   # Any future expiry, any CVC
   ```

2. **Create test worker with Stripe Connect:**
   ```bash
   # Worker must complete Stripe Connect onboarding
   # In test mode, use "Skip this account form" option
   ```

3. **Assign worker to shift:**
   - Business posts shift
   - Worker applies
   - Business accepts → Payment immediately held in escrow

4. **Complete shift:**
   - Worker checks in
   - Worker checks out

5. **Wait for automation:**
   - **T+15 min**: Run `php artisan schedule:run` or wait for cron
   - Payment released from escrow
   - **T+30 min**: Run schedule again
   - Instant payout processed

6. **Verify in transaction history:**
   - Worker: `/my/transactions` → Should show "Paid Out"
   - Business: `/my/transactions` → Should show "Completed"

### Test Scenario 2: Hours Mismatch

1. Assign shift (8 hours estimated)
2. Worker works 6 hours (check out earlier)
3. Payment auto-adjusts:
   - Business refunded for 2 hours
   - Worker paid for 6 hours

### Test Scenario 3: Dispute Flow

1. Complete payment flow
2. Before payout completes, file dispute:
   - Go to `/my/transactions`
   - Click "File Dispute" on transaction
   - Select reason and provide explanation
3. Verify:
   - Status → "Disputed"
   - Payout halted
   - Admin dashboard shows dispute

### Manual Testing Commands

```bash
# Manually trigger payment release (testing)
php artisan tinker
$assignment = App\Models\ShiftAssignment::find(1);
$service = new App\Services\ShiftPaymentService();
$service->releaseFromEscrow($assignment);

# Manually trigger instant payout
$payment = App\Models\ShiftPayment::find(1);
$service->instantPayout($payment);

# Process all ready payouts
$service->processReadyPayouts();
```

---

## API Integration (For AI Agents)

AI agents can monitor and manage payments:

```php
// GET /api/agent/payments?shift_id=123
// Returns payment status for shift

// POST /api/agent/payments/release
// Manually release payment (admin override)

// POST /api/agent/payments/dispute
// File dispute on behalf of client
```

---

## Monitoring & Logs

### Key Log Messages

```
"Escrow held successfully" - Payment captured from business
"Payment released from escrow" - Available for payout
"Instant payout initiated" - Transfer created
"Payout completed" - Worker received funds
"Dispute filed" - Issue raised
```

### Check Logs

```bash
tail -f storage/logs/laravel.log | grep -i "payment\|escrow\|payout"
```

### Database Queries

```sql
-- Payments in escrow
SELECT * FROM shift_payments WHERE status = 'in_escrow';

-- Ready for payout (should be picked up by job)
SELECT * FROM shift_payments 
WHERE status = 'released' 
AND released_at <= NOW() - INTERVAL 15 MINUTE;

-- Disputed payments
SELECT * FROM shift_payments WHERE disputed = 1;

-- Payment success rate
SELECT status, COUNT(*) as count 
FROM shift_payments 
GROUP BY status;
```

---

## Error Handling

### Common Issues

**1. Business Payment Fails:**
- Webhook: `payment_intent.payment_failed`
- Status → `failed`
- Assignment → `payment_failed`
- Notify business to update payment method

**2. Worker Missing Connect Account:**
- Cannot create payout
- Email worker to complete Connect onboarding
- Payment remains in `released` state until resolved

**3. Webhook Not Received:**
- Check Stripe webhook logs
- Verify endpoint URL in Stripe dashboard
- Check webhook secret in `.env`

**4. Scheduled Job Not Running:**
- Verify crontab entry
- Check `php artisan schedule:list`
- Run manually: `php artisan schedule:run`

### Recovery Commands

```bash
# Retry failed payouts
php artisan tinker
$failed = App\Models\ShiftPayment::where('status', 'failed')->get();
foreach ($failed as $payment) {
    $service = new App\Services\ShiftPaymentService();
    $service->instantPayout($payment);
}

# Force release stuck escrow (admin only)
$stuck = App\Models\ShiftPayment::where('status', 'in_escrow')
    ->where('escrow_held_at', '<', now()->subDays(1))->get();
foreach ($stuck as $payment) {
    $service->releaseFromEscrow($payment->assignment);
}
```

---

## Security Considerations

### Payment Intent Security
- Uses customer's saved payment method
- `off_session: true` for automated charging
- Metadata includes assignment/shift IDs for tracking
- Immediate capture prevents auth expiration

### Payout Security
- Worker must complete Stripe Connect verification
- Identity verification required by Stripe
- Instant payouts require eligible Connect account
- Platform controls when payouts occur (escrow period)

### Dispute Security
- Only assignment participants can file disputes
- Disputes immediately halt payouts
- Admin-only resolution
- Audit trail maintained

---

## Performance Optimization

### Database Indexes

```sql
-- Add indexes for faster queries
CREATE INDEX idx_shift_payments_status ON shift_payments(status);
CREATE INDEX idx_shift_payments_worker ON shift_payments(worker_id, status);
CREATE INDEX idx_shift_payments_business ON shift_payments(business_id, status);
CREATE INDEX idx_shift_payments_released ON shift_payments(released_at) WHERE status = 'released';
```

### Job Queue

For high volume, use queue workers:

```bash
# Add to .env
QUEUE_CONNECTION=database

# Run migrations
php artisan queue:table
php artisan migrate

# Start queue workers (supervisor recommended)
php artisan queue:work --tries=3
```

---

## Stripe Fees

### Platform Revenue Model

```
Shift Payment = $100
├─ Business pays: $100 (captured)
├─ Platform fee (10%): $10
├─ Stripe fee (~3%): $3
├─ Worker receives: $87
└─ Platform net: $7
```

**Configured in:**
- ShiftPaymentService: `$platformFeePercentage = 0.15` (15%)
- Or AdminSettings: `fee_commission` field

---

## Next Steps

Phase 3 is complete! Next phases:

- **Phase 4**: Advanced matching algorithm
- **Phase 5**: Agency commission system
- **Phase 6**: AI agent integration
- **Phase 7**: Analytics dashboard

---

## Quick Reference

### Files Created/Modified

**Created:**
- `/app/Jobs/ReleaseEscrowPayments.php` ✅
- `/app/Jobs/ProcessInstantPayouts.php` ✅
- `/resources/views/my/transactions.blade.php` ✅
- `/app/Http/Controllers/TransactionsController.php` ✅

**Modified:**
- `/app/Console/Kernel.php` (added scheduled jobs) ✅
- `/routes/web.php` (added transaction routes) ✅

**Verified Existing:**
- `/app/Services/ShiftPaymentService.php` ✅
- `/app/Models/ShiftPayment.php` ✅
- `/app/Http/Controllers/StripeWebHookController.php` ✅

### Routes Added

```php
GET  /my/transactions - View transaction history
POST /my/transactions/{id}/dispute - File dispute
GET  /my/transactions/export - Export CSV
```

### Cron Setup

```bash
# Add to crontab
* * * * * cd /path/to/overtimestaff && php artisan schedule:run >> /dev/null 2>&1
```

---

**Status: Phase 3 COMPLETE** ✅
