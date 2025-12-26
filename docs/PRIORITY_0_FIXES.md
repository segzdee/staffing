# Priority 0: "Stop the Bleeding" - Critical Fixes

**Date**: December 23, 2025  
**Status**: In Progress

## Overview

This document tracks all Priority 0 (must-fix) security and correctness issues that must be resolved before any other work.

---

## ✅ Completed Fixes

### Security

1. **chmod 0777 Removal** ✅
   - Fixed 2 instances in `app/Helper.php`
   - Changed to `chmod 0644` (secure permissions)
   - **Files**: `app/Helper.php` (lines 142, 200)

2. **External Links Security** ✅
   - Fixed 64 links across 43 Blade templates
   - Added `rel="noopener noreferrer"` to all `target="_blank"` links
   - **Files**: 43 Blade template files

3. **HTML Sanitization - Helper::checkText()** ✅
   - **CRITICAL**: Fixed XSS vulnerability in `Helper::checkText()`
   - Was building `<a>` tags via regex on untrusted content
   - Now uses `HtmlSanitizationService` with proper escaping
   - **Files**: `app/Helper.php` (lines 44-72)

4. **HTML Sanitization - Helper::linkText()** ✅
   - Fixed XSS vulnerability in `Helper::linkText()`
   - Now uses `HtmlSanitizationService` with proper escaping
   - Added `rel="noopener noreferrer"` to generated links
   - **Files**: `app/Helper.php` (lines 456-459)

5. **Webhook Signature Verification** ✅
   - Added `webhook.verify` middleware to all webhook routes
   - Stripe subscription webhook
   - PayPal webhook
   - Paystack webhook
   - Stripe Connect webhook
   - **Files**: `routes/web.php` (lines 1682-1703)

### Payments Correctness

6. **Webhook Idempotency** ✅
   - Created `WebhookIdempotencyService`
   - Created `WebhookEvent` model
   - Created migration: `2025_12_23_000001_create_webhook_events_table.php`
   - Prevents duplicate webhook processing
   - **Files**: 
     - `app/Services/WebhookIdempotencyService.php`
     - `app/Models/WebhookEvent.php`
     - `database/migrations/2025_12_23_000001_create_webhook_events_table.php`

7. **Payment Ledger (Single Source of Truth)** ✅
   - Created `PaymentLedgerService`
   - Created `PaymentLedger` model
   - Created migration: `2025_12_23_000002_create_payment_ledger_table.php`
   - Immutable ledger entries for all payment mutations
   - Unique constraint: one escrow record per payment_intent_id
   - **Files**:
     - `app/Services/PaymentLedgerService.php`
     - `app/Models/PaymentLedger.php`
     - `database/migrations/2025_12_23_000002_create_payment_ledger_table.php`

### Data Integrity

8. **Tax Check orWhere Precedence Bug** ✅
   - **CRITICAL**: Fixed precedence issue in `User::isTaxable()`
   - `orWhere` was not properly grouped, causing incorrect tax calculations
   - Now properly groups: (region AND country) OR (country AND no region)
   - **Files**: `app/Models/User.php` (lines 282-300)

9. **Onboarding Unique Constraint** ✅
   - Created migration: `2025_12_23_000003_add_onboarding_unique_constraint.php`
   - Ensures one active onboarding progress per user
   - **Files**: `database/migrations/2025_12_23_000003_add_onboarding_unique_constraint.php`

---

## ⚠️ Pending Fixes

### Security

1. **HTML Sanitization Integration** ⚠️
   - Service created but needs integration in all user input points
   - Need to audit all `{!!` usage in Blade templates
   - Need to replace `Helper::checkText()` calls with sanitized version
   - **Status**: Service ready, integration pending

2. **Route Security Audit** ⚠️
   - Verify all payout routes have:
     - `auth` middleware
     - `role` middleware
     - 2FA gate (where applicable)
     - Idempotency handling
   - **Status**: Audit pending

3. **Admin Route Security** ⚠️
   - Verify all admin routes have:
     - `auth` middleware
     - `role:admin` middleware
     - Policy gates
     - Audit logging
   - **Status**: Audit pending

### Payments Correctness

4. **Stripe Webhook Routing Verification** ⚠️
   - Verify escrow logic actually runs on webhook events
   - Test payment_intent.succeeded routing
   - **Status**: Verification pending

5. **Idempotency Integration** ⚠️
   - Integrate `WebhookIdempotencyService` into webhook controllers
   - Add idempotency for:
     - payment_intent.succeeded
     - refunds
     - disputes
     - payout confirmations
   - **Status**: Service created, integration pending

6. **Payment Ledger Integration** ⚠️
   - Integrate `PaymentLedgerService` into payment flows
   - Ensure all payment mutations update ledger
   - Replace scattered field updates with ledger entries
   - **Status**: Service created, integration pending

### Data Integrity

7. **Additional orWhere Precedence Checks** ⚠️
   - Audit all `orWhere` usage for precedence issues
   - Fix any queries with incorrect grouping
   - **Status**: Partial audit done, full audit pending

8. **Database Constraints** ⚠️
   - Add foreign keys where safe
   - Verify unique indexes are in place
   - **Status**: Onboarding constraint added, others pending

---

## 📊 Statistics

- **Security Issues Fixed**: 5
- **Payment Issues Fixed**: 2
- **Data Integrity Issues Fixed**: 2
- **Total Priority 0 Fixes**: 9 completed, 8 pending

---

## 🎯 Next Steps

1. **Immediate**:
   - Integrate HTML sanitization in all user input points
   - Integrate webhook idempotency into controllers
   - Integrate payment ledger into payment flows

2. **Short-term**:
   - Complete route security audit
   - Verify Stripe webhook routing
   - Add remaining database constraints

3. **Testing**:
   - Write tests for all Priority 0 fixes
   - Test webhook idempotency
   - Test payment ledger integrity
   - Test tax calculation correctness

---

## ⚠️ Critical Notes

- All Priority 0 fixes must be tested before deployment
- Payment ledger integration is critical for financial correctness
- Webhook idempotency prevents duplicate processing
- HTML sanitization prevents XSS attacks
- Tax calculation bug fix prevents incorrect tax calculations

---

**Last Updated**: December 23, 2025
