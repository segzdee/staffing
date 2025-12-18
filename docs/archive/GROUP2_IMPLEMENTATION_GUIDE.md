# GROUP 2: Worker Suspension + Reliability Scoring - Implementation Guide

## Overview

This implementation provides complete automation for worker suspension triggers and reliability score calculation for the OvertimeStaff platform.

## What Was Implemented

### Task 1: WKR-008 Automated Suspension Triggers

#### Database Changes
- **Migration**: `2025_12_15_060001_add_suspension_fields_to_users_table.php`
  - Added fields: `suspended_until`, `suspension_reason`, `suspension_count`, `last_suspended_at`
  - Indexed `suspended_until` for efficient queries

#### Core Service
- **WorkerSuspensionService** (`/app/Services/WorkerSuspensionService.php`)
  - No-show detection with escalating suspensions:
    - 1st offense: 7 days
    - 2nd offense: 30 days
    - 3rd+ offense: 90 days
  - Late cancellation pattern monitoring (2 cancellations within 24h of shift in 30 days = 14 day suspension)
  - Automatic suspension workflow
  - Auto-reinstatement when suspension period expires
  - Cancels pending applications when worker is suspended

#### Automated Job
- **CheckWorkerViolations** (`/app/Jobs/CheckWorkerViolations.php`)
  - Runs daily at 1:00 AM
  - Checks all active workers for violations
  - Auto-reinstates expired suspensions
  - Comprehensive logging for monitoring

#### Notifications
- **WorkerSuspendedNotification** (updated)
  - Email + database notification
  - Includes suspension duration, end date, and reason
  - Appeals process link
- **WorkerReinstatedNotification** (new)
  - Email + database notification
  - Welcome back message with guidelines

#### User Model Methods
- `isSuspended()` - Check current suspension status
- `suspend($days, $reason)` - Suspend a worker
- `reinstate()` - Reinstate a suspended worker
- `suspensionDaysRemaining()` - Get days left in suspension

### Task 2: WKR-007 Complete Reliability Score Engine

#### Database Changes
- **Migration**: `2025_12_15_060002_create_reliability_score_history_table.php`
  - Tracks score history over time
  - Stores component scores and metrics
  - Period tracking (90-day rolling window)

#### Model
- **ReliabilityScoreHistory** (`/app/Models/ReliabilityScoreHistory.php`)
  - Full relationship with User model
  - Score grading (A-F)
  - Historical tracking

#### Core Service
- **ReliabilityScoreService** (`/app/Services/ReliabilityScoreService.php`)
  - Comprehensive 0-100 score calculation with 4 weighted components:
    - **Attendance (40%)**: Completion rate and no-show tracking
    - **Cancellation Timing (25%)**: Early vs late cancellations
    - **Punctuality (20%)**: Clock-in timing analysis
    - **Responsiveness (15%)**: Confirmation speed
  - 90-day rolling scoring period
  - Minimum 5 shifts required for reliable score
  - Default score of 70 for new workers
  - Score history tracking
  - Matching priority multiplier (0.5x to 1.5x based on score)

#### Automated Job
- **RecalculateReliabilityScores** (`/app/Jobs/RecalculateReliabilityScores.php`)
  - Runs weekly on Sundays at 4:00 AM
  - Processes all active workers
  - Updates cached scores in user table
  - Logs significant score changes (±10 points)
  - Can be dispatched for individual workers on-demand

#### User Model Methods
- `reliabilityScoreHistory()` - Relationship to score history
- `getReliabilityScoreAttribute()` - Get current score (cached or latest)
- `getReliabilityGrade()` - Get letter grade (A-F)
- `updateReliabilityScore($score)` - Update cached score

## File Structure

```
/Users/ots/Desktop/Staffing/
├── app/
│   ├── Jobs/
│   │   ├── CheckWorkerViolations.php           (NEW)
│   │   └── RecalculateReliabilityScores.php    (NEW)
│   ├── Models/
│   │   ├── User.php                             (UPDATED)
│   │   └── ReliabilityScoreHistory.php          (NEW)
│   ├── Notifications/
│   │   ├── WorkerSuspendedNotification.php      (UPDATED)
│   │   └── WorkerReinstatedNotification.php     (NEW)
│   ├── Services/
│   │   ├── WorkerSuspensionService.php          (NEW)
│   │   └── ReliabilityScoreService.php          (NEW)
│   └── Console/
│       └── Kernel.php                           (UPDATED - scheduled jobs)
└── database/
    └── migrations/
        ├── 2025_12_15_060001_add_suspension_fields_to_users_table.php
        └── 2025_12_15_060002_create_reliability_score_history_table.php
```

## Installation & Setup

### Step 1: Run Migrations

```bash
cd /Users/ots/Desktop/Staffing
php artisan migrate
```

This will:
- Add suspension fields to `users` table
- Create `reliability_score_history` table

### Step 2: Verify Scheduled Jobs

Check that jobs are scheduled:

```bash
php artisan schedule:list
```

You should see:
- `CheckWorkerViolations` - Daily at 01:00
- `RecalculateReliabilityScores` - Weekly (Sunday) at 04:00

### Step 3: Clear Caches

```bash
php artisan optimize:clear
```

## Testing

### Test 1: Manual Suspension

```bash
php artisan tinker
```

```php
// Get a worker
$worker = User::where('user_type', 'worker')->first();

// Suspend the worker
$suspensionService = app(\App\Services\WorkerSuspensionService::class);
$suspensionService->suspendWorker($worker, 7, 'Test suspension', 'manual');

// Check suspension status
$worker->fresh()->isSuspended(); // Should return true
$worker->suspensionDaysRemaining(); // Should return 7 (or less if time has passed)

// Get suspension summary
$summary = $suspensionService->getSuspensionSummary($worker);
print_r($summary);

// Reinstate the worker
$suspensionService->reinstateWorker($worker, 'Test reinstatement');
$worker->fresh()->isSuspended(); // Should return false
```

### Test 2: Check Worker Violations Job

```bash
php artisan tinker
```

```php
// Dispatch the job immediately
\App\Jobs\CheckWorkerViolations::dispatch();

// Check logs
tail -f storage/logs/laravel.log
```

### Test 3: Reliability Score Calculation

```bash
php artisan tinker
```

```php
// Get a worker with shift history
$worker = User::where('user_type', 'worker')->first();

// Calculate reliability score
$scoreService = app(\App\Services\ReliabilityScoreService::class);
$scoreData = $scoreService->calculateScore($worker);

print_r($scoreData);
// Should show:
// - Overall score (0-100)
// - Component scores (attendance, cancellation, punctuality, responsiveness)
// - Grade (A-F)
// - Metrics used in calculation

// Save the score
$scoreService->recalculateAndSave($worker);

// Check score history
$history = $worker->reliabilityScoreHistory()->first();
echo "Score: " . $history->score . " (Grade: " . $history->grade . ")\n";

// Get matching priority multiplier
$multiplier = $scoreService->getMatchingPriorityMultiplier($history->score);
echo "Matching Priority: {$multiplier}x\n";
```

### Test 4: Recalculate All Scores

```bash
php artisan tinker
```

```php
// Dispatch the job for all workers
\App\Jobs\RecalculateReliabilityScores::dispatch();

// Or for a specific worker
$worker = User::where('user_type', 'worker')->first();
\App\Jobs\RecalculateReliabilityScores::dispatch($worker->id);
```

### Test 5: No-Show Violation Detection

```bash
php artisan tinker
```

```php
$worker = User::where('user_type', 'worker')->first();

// Create a no-show assignment (for testing)
$assignment = \App\Models\ShiftAssignment::where('worker_id', $worker->id)->first();
if ($assignment) {
    $assignment->update(['status' => 'no_show']);
}

// Check for violations
$suspensionService = app(\App\Services\WorkerSuspensionService::class);
$result = $suspensionService->checkNoShowViolations($worker);
print_r($result);
```

### Test 6: Late Cancellation Pattern Detection

```bash
php artisan tinker
```

```php
$worker = User::where('user_type', 'worker')->first();

// Simulate late cancellations (cancelled within 24h of shift)
// You would need to create test data for this

$suspensionService = app(\App\Services\WorkerSuspensionService::class);
$result = $suspensionService->checkLateCancellationPattern($worker);
print_r($result);
```

## Monitoring

### Key Log Messages

The implementation includes comprehensive logging:

**Suspension Events:**
```
Worker suspended: worker_id, reason, days, suspended_until
Worker reinstated: worker_id, note
Auto-reinstated {count} workers
```

**Reliability Score Events:**
```
Reliability score calculated: worker_id, score, grade
Significant reliability score change: worker_id, old_score, new_score, difference
```

**Job Execution:**
```
CheckWorkerViolations job completed: workers_checked, workers_suspended, workers_reinstated
RecalculateReliabilityScores job completed: workers_processed, execution_time
```

### Checking Logs

```bash
# Real-time log monitoring
tail -f storage/logs/laravel.log

# Filter for suspension events
grep "suspended" storage/logs/laravel.log

# Filter for reliability score events
grep "Reliability score" storage/logs/laravel.log
```

## API Integration Examples

### Get Worker Suspension Status

```php
// In a controller or service
$worker = User::find($workerId);

if ($worker->isSuspended()) {
    $daysRemaining = $worker->suspensionDaysRemaining();

    return response()->json([
        'suspended' => true,
        'reason' => $worker->suspension_reason,
        'suspended_until' => $worker->suspended_until,
        'days_remaining' => $daysRemaining
    ]);
}
```

### Get Worker Reliability Score

```php
// In a controller or service
$worker = User::find($workerId);

return response()->json([
    'score' => $worker->reliability_score,
    'grade' => $worker->getReliabilityGrade(),
    'history' => $worker->reliabilityScoreHistory()->limit(12)->get()
]);
```

### Prevent Suspended Workers from Applying

```php
// In shift application controller
public function apply(Request $request, Shift $shift)
{
    $worker = auth()->user();

    if ($worker->isSuspended()) {
        return back()->with('error',
            "Your account is suspended until {$worker->suspended_until->format('F j, Y')}. " .
            "Reason: {$worker->suspension_reason}"
        );
    }

    // Continue with application logic...
}
```

## Configuration

### Suspension Thresholds

Edit `/app/Services/WorkerSuspensionService.php` to adjust:

```php
const NO_SHOW_FIRST_OFFENSE_DAYS = 7;
const NO_SHOW_SECOND_OFFENSE_DAYS = 30;
const NO_SHOW_THIRD_OFFENSE_DAYS = 90;
const LATE_CANCELLATION_PATTERN_DAYS = 14;
const LATE_CANCELLATION_THRESHOLD = 2;
const LATE_CANCELLATION_WINDOW_DAYS = 30;
const NO_SHOW_LOOKBACK_DAYS = 90;
```

### Reliability Score Weights

Edit `/app/Services/ReliabilityScoreService.php` to adjust:

```php
const WEIGHT_ATTENDANCE = 0.40;        // 40%
const WEIGHT_CANCELLATION = 0.25;      // 25%
const WEIGHT_PUNCTUALITY = 0.20;       // 20%
const WEIGHT_RESPONSIVENESS = 0.15;    // 15%
const SCORING_PERIOD_DAYS = 90;
const MINIMUM_SHIFTS_FOR_SCORE = 5;
```

## Edge Cases Handled

1. **Already Suspended Workers**: Won't be suspended again for the same violation
2. **Insufficient Data**: Workers with <5 shifts get a default score of 70
3. **Expired Suspensions**: Auto-reinstated daily
4. **Pending Applications**: Automatically cancelled when worker is suspended
5. **Score History**: Full historical tracking for trends
6. **Scoring Period**: Rolling 90-day window
7. **New Workers**: Default score of 70 (C grade)
8. **Manual Suspensions**: Separate from automated system
9. **Matching Priority**: Score directly impacts worker visibility (0.5x to 1.5x)

## Database Schema

### users table (additions)
```sql
suspended_until TIMESTAMP NULL
suspension_reason TEXT NULL
suspension_count INT UNSIGNED DEFAULT 0
last_suspended_at TIMESTAMP NULL
INDEX (suspended_until)
```

### reliability_score_history table
```sql
id BIGINT UNSIGNED PRIMARY KEY
user_id BIGINT UNSIGNED (foreign key)
score DECIMAL(5,2)
attendance_score DECIMAL(5,2)
cancellation_score DECIMAL(5,2)
punctuality_score DECIMAL(5,2)
responsiveness_score DECIMAL(5,2)
metrics JSON
period_start DATE
period_end DATE
created_at TIMESTAMP
updated_at TIMESTAMP
INDEX (user_id, created_at)
INDEX (score)
```

## Notification Templates

Both notifications support:
- Email delivery
- Database storage
- Queue processing (ShouldQueue)
- Customizable action URLs

## Performance Considerations

1. **Batch Processing**: Jobs process workers in batches with periodic pauses
2. **Query Optimization**: Indexed fields for fast lookups
3. **Caching**: Score cached in users table for quick access
4. **Job Timeouts**:
   - CheckWorkerViolations: 5 minutes (300s)
   - RecalculateReliabilityScores: 10 minutes (600s)
5. **Without Overlapping**: Prevents concurrent job execution

## Integration with Existing Features

The implementation integrates with:
- ShiftAssignment model (for no-shows and completions)
- ShiftApplication model (for cancellations)
- Rating system (for future enhancements)
- Notification system (email + database)
- Queue system (background processing)
- Task scheduler (automated execution)

## Future Enhancements

Potential improvements:
1. Appeal workflow for suspensions
2. Admin dashboard for suspension management
3. Real-time score updates (instead of weekly)
4. Score-based shift recommendations
5. Gamification badges based on reliability
6. Export reliability reports
7. Predictive suspension warnings

## Support

For issues or questions:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Verify migrations: `php artisan migrate:status`
3. Check scheduled jobs: `php artisan schedule:list`
4. Test in tinker: `php artisan tinker`

## License

This implementation is part of the OvertimeStaff platform.
