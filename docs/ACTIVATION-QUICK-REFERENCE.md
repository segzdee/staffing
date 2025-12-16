# Worker Activation - Quick Reference Guide

## Quick Links

### Key Files
```
Middleware:     app/Http/Middleware/EnsureWorkerActivated.php
Controller:     app/Http/Controllers/Worker/ActivationController.php
Service:        app/Services/WorkerActivationService.php
Model:          app/Models/WorkerProfile.php
Migrations:     database/migrations/2025_12_15_170004_*.php
                database/migrations/2025_12_15_170006_*.php
Routes:         routes/web.php (lines 282-352)
                routes/api.php (lines 240-246)
```

## Quick Commands

### Check Activation Status
```php
$worker = User::find($id);
$status = app(WorkerActivationService::class)->getActivationStatus($worker);
```

### Check Eligibility
```php
$eligibility = app(WorkerActivationService::class)->checkActivationEligibility($worker);
if ($eligibility['eligible']) {
    // Can activate
}
```

### Activate Worker
```php
$result = app(WorkerActivationService::class)->activateWorker($worker);
if ($result['success']) {
    // Activated successfully
}
```

### Force Activate (Admin)
```php
$result = app(WorkerActivationService::class)->activateWorker(
    $worker,
    auth()->id(),
    'admin'
);
```

### Disable Matching
```php
$worker->workerProfile->disableMatching('Compliance review in progress');
```

### Enable Matching
```php
$worker->workerProfile->enableMatching();
```

## Quick Checks

### Model Methods
```php
// Main activation check
$worker->workerProfile->isActivated(); // true/false

// Individual checks
$worker->workerProfile->isPhoneVerified();
$worker->workerProfile->hasCompletedRTW();
$worker->workerProfile->hasPaymentSetup();
$worker->workerProfile->isReadyForActivation();

// RTW expiry
$worker->workerProfile->isRTWExpiring(30); // expires in 30 days?
$worker->workerProfile->isRTWExpired();
```

## Activation Requirements Checklist

### Required (All Must Pass)
- [ ] Email verified (`email_verified_at` set)
- [ ] Phone verified (`phone_verified` = true)
- [ ] Profile 80%+ complete
- [ ] Identity verified (`identity_verified` = true)
- [ ] Right to Work verified (`rtw_verified` = true)
- [ ] Background check in progress/approved
- [ ] Payment setup complete (`payment_setup_complete` = true)

### Recommended (Optional)
- [ ] 3+ skills added
- [ ] 1+ certification uploaded
- [ ] Availability schedule set
- [ ] 50+ character bio written
- [ ] Emergency contact added
- [ ] Profile photo approved

## Database Fields

### worker_profiles Table
```sql
-- Activation
is_activated                BOOLEAN DEFAULT FALSE
activated_at                TIMESTAMP NULL
is_matching_eligible        BOOLEAN DEFAULT FALSE
matching_eligibility_reason VARCHAR(255) NULL

-- Verification
phone_verified              BOOLEAN DEFAULT FALSE
phone_verified_at           TIMESTAMP NULL
rtw_verified                BOOLEAN DEFAULT FALSE
rtw_verified_at             TIMESTAMP NULL
rtw_document_type           VARCHAR(255) NULL
rtw_document_url            VARCHAR(255) NULL
rtw_expiry_date             DATE NULL

-- Payment
payment_setup_complete      BOOLEAN DEFAULT FALSE
payment_setup_at            TIMESTAMP NULL

-- Guidance
first_shift_guidance_shown  BOOLEAN DEFAULT FALSE
first_shift_completed_at    TIMESTAMP NULL

-- Photo
profile_photo_status        ENUM('none', 'pending', 'approved', 'rejected')
profile_photo_rejected_reason VARCHAR(255) NULL

-- Tracking
onboarding_started_at       TIMESTAMP NULL
onboarding_last_step_at     TIMESTAMP NULL
```

## Routes Reference

### Protected Routes (Require Activation)
```
GET  /worker/market                          # Browse shifts
GET  /worker/assignments                     # My assignments
POST /worker/applications/apply/{shift_id}   # Apply to shift
GET  /worker/recommended                     # Recommended shifts
GET  /worker/swaps                           # Shift swaps
POST /shifts/{shift}/apply                   # Global apply
```

### Exempt Routes (No Activation Needed)
```
GET  /worker/dashboard                       # Dashboard
GET  /worker/profile                         # Profile
GET  /worker/onboarding/*                    # All onboarding
GET  /worker/activation/*                    # All activation
GET  /worker/identity-verification           # KYC
GET  /worker/right-to-work                   # RTW
GET  /worker/payment-setup                   # Payment
GET  /worker/skills                          # Skills
GET  /worker/certifications                  # Certifications
```

## API Endpoints

### Check Eligibility
```http
GET /api/worker/activation/eligibility
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "eligible": true,
    "checks": { ... },
    "summary": { ... }
  }
}
```

### Activate Account
```http
POST /api/worker/activation/activate
Authorization: Bearer {token}

Response:
{
  "success": true,
  "message": "Worker activated successfully!",
  "worker": { ... },
  "profile": { ... }
}
```

### Get Status
```http
GET /api/worker/activation/status
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "is_activated": true,
    "activated_at": "2025-12-15T10:30:00Z",
    "is_matching_eligible": true,
    "tier": "bronze",
    "reliability_score": 80.0
  }
}
```

## Constants

### WorkerActivationService
```php
INITIAL_RELIABILITY_SCORE = 80.0
INITIAL_TIER = 'bronze'
MIN_PROFILE_COMPLETENESS = 80
REFERRAL_BONUS_AMOUNT = 2500  // cents ($25)
```

### OnboardingProgressService
```php
REQUIRED_WEIGHT_PERCENTAGE = 70    // 70% of total
RECOMMENDED_WEIGHT_PERCENTAGE = 30 // 30% of total
ACTIVATION_THRESHOLD = 80          // 80% minimum
CACHE_TTL = 300                    // 5 minutes
```

## Common Scenarios

### Scenario 1: Worker Completes Onboarding
```php
// Auto-validate steps
app(OnboardingService::class)->autoValidateSteps($worker);

// Check if can activate
$eligibility = app(WorkerActivationService::class)
    ->checkActivationEligibility($worker);

if ($eligibility['eligible']) {
    // Show "Activate Now" button
}
```

### Scenario 2: Worker Tries to Apply for Shift
```
1. Request hits middleware: EnsureWorkerActivated
2. Middleware checks:
   - Is worker? ✓
   - is_activated? ✗
3. Redirect to /worker/activation with error message
```

### Scenario 3: Admin Disables Worker
```php
$worker->workerProfile->disableMatching('Compliance violation');

// Worker can still:
// - Access profile
// - Update information
// - View onboarding

// Worker CANNOT:
// - Browse shifts
// - Apply to shifts
// - Access shift marketplace
```

### Scenario 4: RTW Document Expires
```php
// Check expiry
if ($worker->workerProfile->isRTWExpired()) {
    // Disable matching
    $worker->workerProfile->disableMatching('RTW document expired');

    // Send notification
    $worker->notify(new RTWExpiredNotification());
}

// Check expiring soon
if ($worker->workerProfile->isRTWExpiring(30)) {
    // Send warning
    $worker->notify(new RTWExpiringNotification());
}
```

## Troubleshooting

### Worker Says They Can't Access Shifts

**Step 1**: Check activation status
```php
$profile = $worker->workerProfile;
echo "Activated: " . ($profile->is_activated ? 'Yes' : 'No') . "\n";
echo "Matching Eligible: " . ($profile->is_matching_eligible ? 'Yes' : 'No') . "\n";
echo "Reason: " . ($profile->matching_eligibility_reason ?? 'N/A') . "\n";
```

**Step 2**: Check eligibility
```php
$eligibility = app(WorkerActivationService::class)
    ->checkActivationEligibility($worker);

foreach ($eligibility['checks'] as $key => $check) {
    if (!$check['passed'] && $check['required']) {
        echo "FAILED: {$check['name']} - {$check['message']}\n";
    }
}
```

**Step 3**: Check progress
```php
$progress = app(OnboardingProgressService::class)
    ->calculateOverallProgress($worker);

echo "Progress: {$progress}%\n";
echo "Need: 80%\n";
```

### Middleware Not Working

**Check 1**: Middleware registered?
```bash
php artisan route:list | grep worker.activated
```

**Check 2**: Route has middleware?
```bash
php artisan route:list | grep "worker/shifts"
```

**Check 3**: Clear caches
```bash
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

### Activation Not Triggering

**Check 1**: All required steps complete?
```php
$steps = app(OnboardingProgressService::class)
    ->getMissingSteps($worker);

print_r($steps['required']); // Should be empty
```

**Check 2**: Can activate?
```php
$canActivate = app(OnboardingProgressService::class)
    ->canActivate($worker);

var_dump($canActivate); // Should be true
```

**Check 3**: Activate manually
```php
$result = app(WorkerActivationService::class)->activateWorker($worker);
print_r($result);
```

## Testing Snippets

### Create Test Worker (Artisan Tinker)
```php
$user = User::factory()->create(['user_type' => 'worker']);
$profile = WorkerProfile::factory()->create(['user_id' => $user->id]);

// Complete requirements
$profile->update([
    'phone_verified' => true,
    'identity_verified' => true,
    'rtw_verified' => true,
    'payment_setup_complete' => true,
    'background_check_status' => 'approved',
    'profile_completion_percentage' => 85,
]);

$user->update(['email_verified_at' => now()]);

// Activate
app(WorkerActivationService::class)->activateWorker($user);
```

### Test Middleware
```php
// Via route
$response = $this->actingAs($worker)->get('/worker/shifts');

// Should redirect if not activated
$response->assertRedirect(route('worker.activation.index'));
```

## Analytics Queries

### Activation Rate
```sql
SELECT
    COUNT(*) as total_workers,
    SUM(CASE WHEN is_activated = 1 THEN 1 ELSE 0 END) as activated,
    ROUND(SUM(CASE WHEN is_activated = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as activation_rate
FROM users u
JOIN worker_profiles wp ON u.id = wp.user_id
WHERE u.user_type = 'worker';
```

### Average Time to Activation
```sql
SELECT AVG(days_to_activation) as avg_days
FROM worker_activation_logs
WHERE activated_at IS NOT NULL;
```

### Drop-off Points
```sql
SELECT
    CASE
        WHEN phone_verified = 0 THEN 'Phone Verification'
        WHEN identity_verified = 0 THEN 'Identity Verification'
        WHEN rtw_verified = 0 THEN 'Right to Work'
        WHEN payment_setup_complete = 0 THEN 'Payment Setup'
        WHEN profile_completion_percentage < 80 THEN 'Profile Completion'
        ELSE 'Ready to Activate'
    END as bottleneck,
    COUNT(*) as workers_stuck
FROM worker_profiles
WHERE is_activated = 0
GROUP BY bottleneck
ORDER BY workers_stuck DESC;
```

---

**Last Updated**: 2025-12-15
**Ticket**: STAFF-REG-011
