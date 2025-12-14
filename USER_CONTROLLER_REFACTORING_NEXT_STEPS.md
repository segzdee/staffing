# UserController Refactoring - Next Steps

## Current Status
✅ **Completed**: 2/8 controllers (25% complete)
- User\DashboardController (3 methods, 450 lines) ✓
- User\SettingsController (12 methods, 450 lines) ✓

⏳ **Remaining**: 6 controllers (75% remaining)

---

## Priority Order (High-Value First)

### 1. SubscriptionController (CRITICAL - Payments)
**Priority**: HIGHEST
**Estimated Lines**: 600
**Risk Level**: High (payment processing)

**Methods to Extract** (11 total):
```php
// From UserController line 554
public function mySubscribers()
{
    $subscriptions = auth()->user()->mySubscriptions()->orderBy('id','desc')->paginate(20);
    return view('users.my_subscribers')->withSubscriptions($subscriptions);
}

// Line 562
public function mySubscriptions()
{
    $subscriptions = auth()->user()->userSubscriptions()->orderBy('id','desc')->paginate(20);
    return view('users.my_subscriptions')->withSubscriptions($subscriptions);
}

// Line 568
public function myPayments()
{
    if (request()->is('my/payments')) {
        $transactions = auth()->user()->myPayments()->orderBy('id','desc')->paginate(20);
    } elseif (request()->is('my/payments/received')) {
        $transactions = auth()->user()->myPaymentsReceived()->orderBy('id','desc')->paginate(20);
    } else {
        abort(404);
    }
    return view('users.my_payments')->withTransactions($transactions);
}

// Line 868 - CRITICAL: Creates subscription plans
public function saveSubscription() // ~140 lines

// Line 1011 - CRITICAL: Stripe integration
protected function createPlanStripe() // ~35 lines

// Line 1047 - CRITICAL: Paystack integration
protected function createPlanPaystack() // ~35 lines

// Line 1504
public function invoice($id) // ~17 lines

// Line 1728
public function invoiceDeposits($id) // ~20 lines

// Line 1748
public function myPurchases() // ~13 lines

// Line 1761
public function ajaxMyPurchases() // ~17 lines

// Line 1565
public function cancelSubscription($id) // ~25 lines
```

**Testing Requirements**:
- Test subscription creation (monthly, quarterly, yearly)
- Test Stripe plan creation
- Test Paystack plan creation
- Test subscription cancellation
- Test invoice generation
- **DO NOT TEST in production with real money**

---

### 2. WithdrawalController (CRITICAL - Money)
**Priority**: HIGH
**Estimated Lines**: 250
**Risk Level**: High (money operations)

**Methods to Extract** (5 total):
```php
// Line 581
public function payoutMethod() // ~7 lines

// Line 588
public function payoutMethodConfigure() // ~84 lines - Complex Stripe Connect setup

// Line 1188
public function withdrawals() // ~7 lines

// Line 1195
public function makeWithdrawals() // ~66 lines - CRITICAL: Creates withdrawal requests

// Line 1261
public function deleteWithdrawal() // ~16 lines
```

**Testing Requirements**:
- Test payout method configuration
- Test withdrawal request creation
- Test withdrawal deletion
- Verify balance checks work
- **DO NOT TEST with real bank accounts**

---

### 3. MediaController (HIGH - Frequently Used)
**Priority**: MEDIUM-HIGH
**Estimated Lines**: 300
**Risk Level**: Medium (file operations)

**Methods to Extract** (4 total):
```php
// Line 672
public function uploadAvatar(Request $request) // ~113 lines - Complex image processing

// Line 1085
public function uploadCover(Request $request) // ~192 lines - Base64 image upload

// Line 1277
public function deleteImageCover() // ~21 lines

// Line 1616
public function downloadFile($id) // ~30 lines - Purchased file download
```

**Testing Requirements**:
- Test avatar upload (JPG, PNG)
- Test cover upload
- Test cover deletion
- Test file download (purchased content)
- Verify file permissions

---

### 4. VerificationController (MEDIUM)
**Priority**: MEDIUM
**Estimated Lines**: 150
**Risk Level**: Low

**Methods to Extract** (2 total):
```php
// Line 1410
public function verifyAccount() // ~5 lines

// Line 1415
public function verifyAccountSend() // ~94 lines - ID verification submission
```

---

### 5. PaymentCardController (MEDIUM)
**Priority**: MEDIUM
**Estimated Lines**: 150
**Risk Level**: Medium (Stripe integration)

**Methods to Extract** (4 total):
```php
// Line 1521
public function formAddUpdatePaymentCard() // ~11 lines

// Line 1532
public function addUpdatePaymentCard() // ~33 lines - Stripe PM attachment

// Line 1646
public function myCards() // ~37 lines

// Line 1719
public function deletePaymentCard() // ~9 lines
```

---

### 6. InteractionController (LOW)
**Priority**: LOW
**Estimated Lines**: 200
**Risk Level**: Low

**Methods to Extract** (6 total):
```php
// Line 1331
public function like(Request $request) // ~51 lines

// Line 1298
public function reportCreator(Request $request) // ~33 lines

// Line 1382
public function ajaxNotifications() // ~28 lines

// Line 1778
public function myPosts() // ~19 lines

// Line 1606
public function myBookmarks() // ~10 lines

// Line 1797
public function blockCountries() // ~9 lines

// Line 1806
public function blockCountriesStore() // ~11 lines
```

---

## Implementation Guide

### Step-by-Step Process for Each Controller

1. **Create Controller File**
   ```bash
   # Example for SubscriptionController
   New-Item app/Http/Controllers/User/SubscriptionController.php
   ```

2. **Add Namespace and Base Structure**
   ```php
   <?php

   namespace App\Http\Controllers\User;

   use App\Http\Controllers\Controller;
   use Illuminate\Http\Request;
   use App\Models\AdminSettings;
   // Add other required models

   class SubscriptionController extends Controller
   {
       use \App\Http\Controllers\Traits\Functions;

       protected $request;
       protected $settings;

       public function __construct(Request $request, AdminSettings $settings)
       {
           $this->request = $request;
           $this->settings = $settings::first();
       }

       // Methods here
   }
   ```

3. **Copy Methods from UserController**
   - Open UserController.php
   - Copy each method with its full implementation
   - Paste into new controller
   - Update route names if needed

4. **Update Routes (Do Last)**
   ```php
   // OLD
   Route::get('/my/subscriptions', 'UserController@mySubscriptions');

   // NEW
   Route::get('/my/subscriptions', 'User\SubscriptionController@mySubscriptions');
   ```

---

## Testing Checklist

After creating each controller and updating routes:

### SubscriptionController Tests
- [ ] `/my/subscribers` loads
- [ ] `/my/subscriptions` loads
- [ ] `/my/payments` loads
- [ ] `/my/payments/received` loads
- [ ] `/settings/subscription/update` works (FREE subscription only!)
- [ ] Invoice pages load
- [ ] Subscription cancellation works

### WithdrawalController Tests
- [ ] `/settings/payout` loads
- [ ] `/settings/withdrawals` loads
- [ ] Withdrawal request creates (small amount)
- [ ] Withdrawal can be deleted

### MediaController Tests
- [ ] Avatar upload works
- [ ] Cover upload works
- [ ] Cover can be deleted
- [ ] File download works (if applicable)

### VerificationController Tests
- [ ] Verification form loads
- [ ] Verification can be submitted

### PaymentCardController Tests
- [ ] Card management page loads
- [ ] **DO NOT add real cards in testing**

### InteractionController Tests
- [ ] Like/unlike works
- [ ] Bookmarks load
- [ ] User posts load
- [ ] Notifications load via Ajax

---

## Route Migration Template

Create a backup of routes/web.php first:
```bash
cp routes/web.php routes/web.php.backup
```

Then update routes section by section:

```php
// OLD USER ROUTES (Keep as comments during migration)
// Route::get('/dashboard', 'UserController@dashboard');
// Route::get('/settings', 'UserController@settings');
// etc...

// NEW USER ROUTES
Route::namespace('User')->group(function() {
    // Dashboard & Profile
    Route::get('/dashboard', 'DashboardController@dashboard');
    Route::get('/{slug}', 'DashboardController@profile');
    Route::get('/{slug}/post/{id}', 'DashboardController@postDetail');

    // Settings
    Route::get('/settings', 'SettingsController@index');
    Route::post('/settings/update', 'SettingsController@update');
    // ... more settings routes

    // Subscriptions (TO BE ADDED)
    // Route::get('/my/subscriptions', 'SubscriptionController@mySubscriptions');
    // etc...
});
```

---

## Rollback Plan

If issues arise:

1. **Revert routes immediately**:
   ```bash
   cp routes/web.php.backup routes/web.php
   ```

2. **Keep original UserController intact** until all new controllers are tested

3. **Can run old and new controllers side-by-side** initially

---

## Completion Estimate

| Controller | Time Estimate | Complexity |
|-----------|---------------|------------|
| SubscriptionController | 1.5 hours | High |
| WithdrawalController | 45 minutes | Medium |
| MediaController | 1 hour | Medium |
| VerificationController | 20 minutes | Low |
| PaymentCardController | 30 minutes | Low |
| InteractionController | 30 minutes | Low |
| Route Updates & Testing | 1 hour | Medium |
| **TOTAL** | **~5.5 hours** | - |

---

## Success Criteria

✅ All 8 controllers created
✅ All 47 methods extracted
✅ Routes updated and working
✅ All critical flows tested
✅ No breaking changes
✅ Original UserController can be archived

---

## Next Session Checklist

When you continue:

1. [ ] Review this document
2. [ ] Start with SubscriptionController (highest priority)
3. [ ] Test payment flows carefully
4. [ ] Move to WithdrawalController
5. [ ] Continue through remaining controllers
6. [ ] Update routes section by section
7. [ ] Test thoroughly
8. [ ] Commit incrementally

---

## Current Git Status

```
Completed Controllers: 2
Files Created: 2
Commits Made: 3
Branch: main
Ready to Continue: YES
```

All documentation and planning is complete. Ready to implement remaining 6 controllers in next session.
