# Phase 4: Advanced Matching Algorithm & Discovery - COMPLETE ✅

## Overview

Phase 4 implements an AI-powered matching system that intelligently connects workers with shifts based on multiple factors including skills, location, availability, industry experience, and ratings. The system provides:
- Personalized shift recommendations for workers
- Worker availability broadcasting
- Real-time available worker discovery for businesses
- Comprehensive admin analytics dashboard
- Match score calculations and visualizations

---

## Architecture

### Matching Algorithm

```
MATCH SCORE CALCULATION (0-100 points):

1. Skills Match (40 points)
   - Compares worker's skills against shift requirements
   - 100% match = 40 points
   - Partial matches scored proportionally

2. Location Proximity (25 points)
   - Uses Haversine formula for distance calculation
   - ≤ 5 miles = 25 points
   - ≤ 10 miles = 20 points
   - ≤ 25 miles = 15 points
   - ≤ preferred radius = 10 points
   - > preferred radius = 0 points

3. Availability Match (20 points)
   - Day of week availability
   - Time of day preferences (morning/afternoon/night)
   - Preferred shift = 20 points
   - Available but not preferred = 15 points

4. Industry Experience (10 points)
   - 5+ years = 10 points
   - 2-4 years = 8 points
   - 1-2 years = 6 points
   - Some experience = 4 points

5. Rating (5 points)
   - ≥ 4.5 stars = 5 points
   - ≥ 4.0 stars = 4 points
   - ≥ 3.5 stars = 3 points
   - ≥ 3.0 stars = 2 points
```

### Dynamic Rate Calculation

```
BASE RATE × MULTIPLIERS:

1. Urgency Multiplier
   - Critical: +50%
   - Urgent: +30%
   - Normal: 0%

2. Time Until Shift
   - Same/next day: +25%
   - 2-3 days: +15%
   - Within a week: +10%
   - More than a week: 0%

3. Industry Demand
   - Healthcare: +15%
   - Professional: +10%
   - Events: +5%
   - Warehouse: +5%
   - Others: 0%

4. Day of Week
   - Weekend: +10%
   - Weekday: 0%

5. Time of Day
   - Night (10pm-6am): +20%
   - Evening (6pm-10pm): +10%
   - Day: 0%

Example:
Base Rate: $20/hr
Critical urgency + Same day + Healthcare + Weekend + Night shift
= $20 × (1.0 + 0.50 + 0.25 + 0.15 + 0.10 + 0.20)
= $20 × 2.20 = $44/hr
```

---

## Components Implemented

### 1. Matching Service ✅

**File:** `/app/Services/ShiftMatchingService.php` (already existed, verified)

**Key Methods:**
- `matchShiftsForWorker(User $worker)` - Returns ranked shifts for a worker
- `matchWorkersForShift(Shift $shift)` - Returns ranked workers for a shift
- `calculateWorkerShiftMatch(User $worker, Shift $shift)` - Core algorithm
- `calculateDynamicRate(array $params)` - Adjusts rates based on demand
- `findAvailableWorkers($industry, $location, $date)` - Active availability broadcasts
- `predictFillTime(Shift $shift)` - Estimates how quickly a shift will fill

### 2. Worker Recommended Shifts ✅

**Created Files:**
- `/app/Http/Controllers/ShiftController.php` - Has `recommended()` method
- `/resources/views/shifts/recommended.blade.php` - Recommendation UI (514 lines)

**Features:**
- Personalized shift recommendations with match scores
- Visual match indicators (high/medium/low)
- Match reasons breakdown
- Fill time predictions
- Quick apply functionality
- Auto-refresh for real-time updates
- Filtering by industry and minimum match score

**Route:** `/worker/shifts/recommended`

**Key UI Elements:**
```html
- Match score badge (color-coded: green 80+, yellow 60-79, red <60)
- Urgency indicators with animation
- "Why this is a great match" breakdown
- Estimated earnings calculator
- Fill prediction warnings ("Expected to fill within 1 hour")
```

### 3. Availability Broadcasting ✅

**Created Files:**
- `/app/Http/Controllers/Worker/AvailabilityBroadcastController.php` (163 lines)
- `/resources/views/worker/availability/index.blade.php` (464 lines)

**Features:**

**For Workers:**
- Set availability window (from/to datetime)
- Select interested industries
- Set minimum rate preference
- Define maximum distance
- Add personal message
- Extend active broadcasts
- Cancel broadcasts
- View broadcast history
- Track responses received

**Worker Routes:**
- `GET /worker/availability/broadcast` - View/manage broadcasts
- `POST /worker/availability/broadcast` - Create broadcast
- `DELETE /worker/availability/broadcast/{id}` - Cancel broadcast
- `POST /worker/availability/broadcast/{id}/extend` - Extend duration

**Database Model:** `AvailabilityBroadcast`
```php
Fields:
- worker_id
- available_from (datetime)
- available_to (datetime)
- industries (JSON array)
- preferred_rate (decimal)
- location_radius (integer, miles)
- message (text)
- status (active/expired/cancelled)
- responses_count (tracks invitations received)
```

### 4. Business: Find Available Workers ✅

**Created Files:**
- `/app/Http/Controllers/Business/AvailableWorkersController.php` (206 lines)
- `/resources/views/business/available_workers/index.blade.php` (418 lines)

**Features:**

**For Businesses:**
- See all workers currently broadcasting availability
- Filter by industry, distance, and specific shift
- View worker profiles and skills
- See match scores for specific shifts
- Send individual shift invitations
- Bulk invite multiple workers
- Quick invite functionality

**Business Routes:**
- `GET /business/available-workers` - View broadcasting workers
- `POST /business/available-workers/invite` - Invite worker to shift
- `POST /business/available-workers/bulk-invite` - Invite multiple workers
- `GET /business/available-workers/match/{shiftId}` - Match workers for shift

**Database Model:** `ShiftInvitation`
```php
Fields:
- shift_id
- worker_id
- business_id
- message (text)
- status (pending/accepted/rejected)
- created_at
```

### 5. Admin Analytics Dashboard ✅

**Created Files:**
- `/app/Http/Controllers/Admin/MatchingAnalyticsController.php` (329 lines)
- `/resources/views/admin/matching/analytics.blade.php` (502 lines)

**Metrics Tracked:**

**Overall Performance:**
- Fill rate (% of shifts filled)
- Average time to fill (hours)
- Average match score
- High match rate (% of matches scoring 80+)
- Application acceptance rate

**Industry Breakdown:**
- Total shifts per industry
- Fill rate by industry
- Average time to fill by industry

**Matching Quality:**
- Match score distribution (90-100%, 80-89%, 70-79%, 60-69%, 0-59%)
- Daily fill rate trends
- Application trends over time

**Engagement Metrics:**
- Availability broadcasts
- Average responses per broadcast
- Total worker invitations
- Invitation acceptance rate

**Worker Performance:**
- Top 10 workers by completed shifts
- Worker ratings and statistics

**Urgency Analysis:**
- Shifts by urgency level (normal/urgent/critical)
- Fill performance by urgency

**Admin Route:** `/admin/matching/analytics`

**Charts Included:**
- Line chart: Daily fill rate trends
- Doughnut chart: Match score distribution
- Bar chart: Shifts by urgency level
- Tables: Industry performance, top workers

**AI Insights:**
The dashboard automatically provides recommendations based on metrics:
- Low fill rate warnings
- Match quality improvement suggestions
- Fill time optimization tips
- Success confirmations

---

## How It Works

### Scenario 1: Worker Finds Perfect Shift

1. **Worker logs in** → Dashboard shows upcoming assignments
2. **Clicks "Recommended Shifts"** → `/worker/shifts/recommended`
3. **Algorithm runs:**
   ```php
   $shifts = $matchingService->matchShiftsForWorker($worker);
   // Returns shifts sorted by match score (descending)
   ```
4. **Worker sees:**
   - Top shift: 95% match (green indicator)
   - "Excellent overall match"
   - Near location (5 miles)
   - Matches skills and experience
   - $35/hr (premium rate due to urgency)
   - "Expected to fill within 1 hour - Apply now!"
5. **Worker clicks "Quick Apply"** → Application submitted
6. **Business receives notification** → High-match worker applied

### Scenario 2: Business Needs Urgent Worker

1. **Business posts urgent shift** → Dynamic rate increases by 30%
2. **Shift saved with urgency_level='urgent'**
3. **Matching service notifies relevant workers:**
   ```php
   $matchedWorkers = $matchingService->matchWorkersForShift($shift);
   // Top 20 workers notified via push/email
   ```
4. **Business also checks "Available Workers"** → `/business/available-workers`
5. **Sees 3 workers broadcasting availability right now:**
   - Worker A: 88% match, 10 miles away, healthcare experience
   - Worker B: 75% match, 5 miles away, available now
   - Worker C: 92% match, 15 miles away, 5+ years experience
6. **Business sends invitations to Workers A and C**
7. **Workers receive notification immediately**
8. **Worker C accepts** → Shift filled in 15 minutes

### Scenario 3: Worker Broadcasts Availability

1. **Worker has free afternoon** → Goes to `/worker/availability/broadcast`
2. **Sets broadcast:**
   - Available: 2:00 PM - 8:00 PM today
   - Industries: Hospitality, Events
   - Minimum rate: $25/hr
   - Max distance: 15 miles
   - Message: "Experienced bartender, available for immediate start"
3. **Broadcast saved:**
   ```php
   AvailabilityBroadcast::create([...])
   ```
4. **Broadcast appears to all relevant businesses** searching for workers
5. **2 businesses send invitations:**
   - Event staffing shift, 3pm-7pm, $30/hr
   - Bar shift, 5pm-11pm, $28/hr
6. **Worker accepts event shift** → Booked within 30 minutes
7. **Broadcast auto-expires** after end time

### Scenario 4: Admin Monitors Performance

1. **Admin logs in** → `/admin/matching/analytics`
2. **Views key metrics (last 30 days):**
   - Fill rate: 82% (green, good)
   - Avg time to fill: 8.5 hours (acceptable)
   - Avg match score: 77 (needs improvement)
   - Application acceptance: 65%
3. **Sees chart:** Daily fill rate trending upward
4. **Industry breakdown shows:**
   - Healthcare: 95% fill rate, 3h avg time
   - Retail: 68% fill rate, 15h avg time (needs attention)
5. **Dashboard suggests:**
   - "Low match quality in retail - adjust skill weights"
   - "Recruit more retail-experienced workers"
6. **Admin adjusts matching weights** in service configuration
7. **Monitors improvement over next week**

---

## Testing the Matching System

### Prerequisites

1. **Create test data:**
   ```bash
   php artisan tinker

   # Create workers with varied profiles
   $worker1 = User::find(1);
   $worker1->workerProfile->update([
       'location_lat' => 40.7128,
       'location_lng' => -74.0060,
       'industries_experience' => ['hospitality', 'events'],
       'availability' => [
           'monday' => true,
           'tuesday' => true,
           'preferred_shifts' => ['afternoon', 'evening']
       ]
   ]);

   # Add skills
   $skill = Skill::firstOrCreate(['name' => 'Bartending', 'industry' => 'hospitality']);
   $worker1->skills()->attach($skill->id, ['proficiency_level' => 'expert', 'years_experience' => 5]);

   # Create test shifts
   $shift = Shift::create([
       'business_id' => 2,
       'title' => 'Event Bartender Needed',
       'industry' => 'hospitality',
       'location_lat' => 40.7489,
       'location_lng' => -73.9680,
       'shift_date' => now()->addDays(2),
       'start_time' => '18:00',
       'end_time' => '23:00',
       'base_rate' => 25,
       'urgency_level' => 'urgent',
       'status' => 'open'
   ]);
   ```

### Test Scenario 1: Recommended Shifts

1. **Login as worker** (user with skills/profile setup)
2. **Navigate to** `/worker/shifts/recommended`
3. **Verify:**
   - Shifts are displayed
   - Match scores are calculated (0-100)
   - High-match shifts (80+) have green border
   - Match reasons are displayed
   - Fill predictions show when applicable
4. **Apply filters:**
   - Select specific industry → Results update
   - Set min match to 80% → Only high matches shown
5. **Click "Quick Apply"** → Application submitted
6. **Check** `/worker/applications` → Application appears

### Test Scenario 2: Availability Broadcasting

1. **Login as worker**
2. **Navigate to** `/worker/availability/broadcast`
3. **Create broadcast:**
   - Set available from: Now
   - Set available to: 4 hours from now
   - Select industries: Hospitality, Events
   - Set min rate: $20/hr
   - Add message: "Available for immediate start"
4. **Submit** → "BROADCASTING NOW" card appears
5. **Verify countdown timer** updates every minute
6. **In another browser, login as business**
7. **Navigate to** `/business/available-workers`
8. **Verify:** Worker's broadcast appears
9. **Invite worker to a shift** → Invitation sent
10. **Check worker dashboard** → Responses count increments
11. **Cancel broadcast** → Status changes to cancelled

### Test Scenario 3: Business Finding Workers

1. **Login as business**
2. **Navigate to** `/business/available-workers`
3. **Verify:**
   - Currently broadcasting workers appear
   - "AVAILABLE NOW" indicator animates
   - Worker details, skills, and availability shown
4. **Select a shift from dropdown** → Match scores calculate
5. **Invite worker** → Modal opens
6. **Fill invitation form** → Submit
7. **Check** `shift_invitations` table → Record created
8. **Worker logs in** → Receives notification

### Test Scenario 4: Admin Analytics

1. **Login as admin**
2. **Navigate to** `/admin/matching/analytics`
3. **Verify dashboard loads** with:
   - Key metric cards (fill rate, avg time, match score, etc.)
   - Charts render correctly
   - Industry performance table populated
   - Top workers list shows data
4. **Change date range** → Metrics recalculate
5. **Check insights section** → Recommendations appear based on metrics

### Manual Testing Commands

```bash
# Test match calculation
php artisan tinker
$service = new App\Services\ShiftMatchingService();
$worker = App\Models\User::find(1);
$shift = App\Models\Shift::find(1);
$score = $service->calculateWorkerShiftMatch($worker, $shift);
echo "Match Score: " . $score;

# Test dynamic rate calculation
$rate = $service->calculateDynamicRate([
    'base_rate' => 20,
    'shift_date' => now()->addDay(),
    'industry' => 'healthcare',
    'urgency_level' => 'urgent',
    'start_time' => '22:00'
]);
echo "Dynamic Rate: $" . $rate;

# Test find available workers
$available = $service->findAvailableWorkers('hospitality', null, now());
echo "Available Workers: " . count($available);

# Test fill time prediction
$prediction = $service->predictFillTime($shift);
echo "Fill Prediction: " . $prediction;
```

---

## Integration with Existing Features

### Phase 2: Shift Marketplace
- Matching algorithm integrated into shift browsing
- Recommended shifts appear on worker dashboard
- Match scores shown on shift detail pages

### Phase 3: Payment System
- Dynamic rates factored into payment calculations
- High-match workers receive priority notifications
- Premium rates for urgent shifts increase escrow amounts

### Future Phases:
- **Phase 5**: Agency workers included in matching
- **Phase 6**: AI agents can query matching API
- **Phase 7**: Analytics feed into performance dashboards

---

## API Integration (For AI Agents)

```php
// Future API endpoints for Phase 6

GET /api/agent/match/shifts?worker_id={id}
// Returns top matched shifts for a worker

GET /api/agent/match/workers?shift_id={id}
// Returns top matched workers for a shift

POST /api/agent/match/calculate
// Body: { worker_id, shift_id }
// Returns match score and breakdown

GET /api/agent/available-workers?industry={industry}
// Returns workers currently broadcasting
```

---

## Performance Optimization

### Database Indexes

```sql
-- For faster matching queries
CREATE INDEX idx_shifts_open_upcoming ON shifts(status, shift_date)
WHERE status = 'open' AND shift_date >= CURDATE();

CREATE INDEX idx_broadcasts_active ON availability_broadcasts(status, available_from, available_to)
WHERE status = 'active';

CREATE INDEX idx_workers_verified ON users(user_type, is_verified_worker, status)
WHERE user_type = 'worker';

CREATE INDEX idx_shift_industry_location ON shifts(industry, location_lat, location_lng);

CREATE INDEX idx_worker_skills ON worker_skills(worker_id, skill_id, proficiency_level);
```

### Caching Strategy

```php
// Cache frequently accessed data
Cache::remember('worker_' . $workerId . '_recommended_shifts', 600, function() use ($worker) {
    return $matchingService->matchShiftsForWorker($worker);
});

// Cache match scores for shift detail pages
Cache::remember('shift_' . $shiftId . '_match_' . $workerId, 3600, function() use ($worker, $shift) {
    return $matchingService->calculateWorkerShiftMatch($worker, $shift);
});

// Cache available workers list
Cache::remember('available_workers_' . $industry, 120, function() use ($industry) {
    return $matchingService->findAvailableWorkers($industry);
});
```

### Background Processing

```php
// Queue heavy matching calculations
dispatch(new CalculateMatchScoresJob($shift))->onQueue('matching');

// Process in batches
Bus::batch([
    new NotifyMatchedWorkersJob($shift, $workers->chunk(50))
])->dispatch();
```

---

## Monitoring & Logging

### Key Metrics to Track

```php
// Log match calculations
Log::info('Match calculated', [
    'worker_id' => $worker->id,
    'shift_id' => $shift->id,
    'score' => $matchScore,
    'skills_score' => $skillsScore,
    'location_score' => $locationScore,
    'availability_score' => $availabilityScore
]);

// Track broadcast effectiveness
Log::info('Broadcast created', [
    'worker_id' => $worker->id,
    'industries' => $broadcast->industries,
    'duration_hours' => $duration
]);

// Monitor fill rates
Log::info('Shift filled', [
    'shift_id' => $shift->id,
    'time_to_fill_minutes' => $timeToFill,
    'applications_count' => $applicationsCount,
    'match_score' => $assignedWorkerMatchScore
]);
```

### Database Queries for Monitoring

```sql
-- Check matching performance
SELECT
    DATE(created_at) as date,
    COUNT(*) as total_shifts,
    SUM(CASE WHEN status = 'filled' THEN 1 ELSE 0 END) as filled_shifts,
    AVG(TIMESTAMPDIFF(MINUTE, created_at, filled_at)) as avg_fill_minutes
FROM shifts
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;

-- Check broadcast engagement
SELECT
    COUNT(*) as total_broadcasts,
    AVG(responses_count) as avg_responses,
    SUM(responses_count) as total_responses
FROM availability_broadcasts
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Check invitation success rate
SELECT
    COUNT(*) as total_invitations,
    SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
    ROUND(SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) as acceptance_rate
FROM shift_invitations
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);
```

---

## Security Considerations

### Matching Algorithm
- Match scores are calculated server-side only
- Workers cannot manipulate their match scores
- Businesses cannot see exact scoring breakdown (to prevent gaming)
- Rate calculations are transparent but not editable

### Availability Broadcasting
- Workers can only broadcast their own availability
- Businesses can only invite workers who are broadcasting
- Broadcast durations are limited (max 12 hours)
- Expired broadcasts automatically hidden

### Admin Analytics
- Restricted to admin users only
- No PII exposed in aggregate metrics
- Individual worker/business data anonymized in reports

---

## Troubleshooting

### Common Issues

**1. Low Match Scores:**
- **Symptom:** All workers scoring below 50% for a shift
- **Cause:** Shift requirements too specific, or no workers in area
- **Fix:**
  - Broaden shift requirements
  - Increase location radius
  - Adjust minimum rate
  - Check if workers have completed profiles

**2. No Available Workers Showing:**
- **Symptom:** `/business/available-workers` shows empty
- **Cause:** No workers currently broadcasting
- **Fix:**
  - Encourage workers to use broadcast feature
  - Check database: `SELECT * FROM availability_broadcasts WHERE status='active'`
  - Verify time ranges haven't expired

**3. Slow Match Calculations:**
- **Symptom:** Recommended shifts page loads slowly
- **Cause:** Complex queries without caching
- **Fix:**
  - Implement caching (see Performance section)
  - Add database indexes
  - Limit results to top 20 matches

**4. Fill Prediction Inaccurate:**
- **Symptom:** Predictions don't match actual fill times
- **Cause:** Insufficient historical data
- **Fix:**
  - Requires at least 5 similar shifts in history
  - Adjust prediction algorithm weights
  - Returns 'unknown' if insufficient data

### Debug Commands

```bash
# Check worker profile completeness
php artisan tinker
$worker = User::find(1);
echo "Profile: " . ($worker->workerProfile ? "Yes" : "No");
echo "Skills: " . $worker->skills->count();
echo "Location: " . ($worker->workerProfile->location_lat ? "Yes" : "No");

# Test matching service
$service = new App\Services\ShiftMatchingService();
$shifts = $service->matchShiftsForWorker($worker);
echo "Matched Shifts: " . $shifts->count();

# Check active broadcasts
$broadcasts = App\Models\AvailabilityBroadcast::where('status', 'active')
    ->where('available_to', '>=', now())->get();
echo "Active Broadcasts: " . $broadcasts->count();
```

---

## Next Steps

Phase 4 is complete! Recommended next phases:

- **Phase 5**: Agency commission system and bulk worker management
- **Phase 6**: AI agent API integration for automated shift management
- **Phase 7**: Advanced analytics dashboard with predictive insights
- **Phase 8**: Real-time notifications and WebSocket integration

---

## Quick Reference

### Files Created (8)
1. `/app/Http/Controllers/Worker/AvailabilityBroadcastController.php` (163 lines)
2. `/app/Http/Controllers/Business/AvailableWorkersController.php` (206 lines)
3. `/app/Http/Controllers/Admin/MatchingAnalyticsController.php` (329 lines)
4. `/resources/views/shifts/recommended.blade.php` (514 lines)
5. `/resources/views/worker/availability/index.blade.php` (464 lines)
6. `/resources/views/business/available_workers/index.blade.php` (418 lines)
7. `/resources/views/admin/matching/analytics.blade.php` (502 lines)

### Files Modified (1)
1. `/routes/web.php` - Added 9 new routes for Phase 4 features

### Routes Added

**Worker Routes:**
```
GET  /worker/shifts/recommended - Personalized shift matches
GET  /worker/availability/broadcast - Manage availability broadcasts
POST /worker/availability/broadcast - Create broadcast
DELETE /worker/availability/broadcast/{id} - Cancel broadcast
POST /worker/availability/broadcast/{id}/extend - Extend broadcast
```

**Business Routes:**
```
GET  /business/available-workers - View broadcasting workers
POST /business/available-workers/invite - Invite worker to shift
POST /business/available-workers/bulk-invite - Bulk invitations
GET  /business/available-workers/match/{shiftId} - Match workers for shift
```

**Admin Routes:**
```
GET  /admin/matching/analytics - Matching performance dashboard
```

### Database Tables Used
- `availability_broadcasts` - Worker availability broadcasts
- `shift_invitations` - Business→Worker invitations
- `shifts` - Shift postings
- `shift_applications` - Worker applications
- `shift_assignments` - Confirmed assignments
- `users` - Workers and businesses
- `worker_profiles` - Worker details
- `skills` - Skill definitions
- `worker_skills` - Worker→Skill relationships

---

**Status: Phase 4 COMPLETE** ✅

All matching algorithm features are implemented and ready for testing. The system provides intelligent, AI-powered matching between workers and shifts with comprehensive analytics for monitoring performance.
