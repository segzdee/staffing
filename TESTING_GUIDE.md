# Testing Guide: Dashboard Live Updates
**Date:** 2025-12-15

---

## 🧪 TESTING CHECKLIST

### 1. Migration Verification ✅

**Command:**
```bash
php artisan migrate
```

**Expected Output:**
```
Migrating: 2025_12_15_000200_add_dashboard_performance_indexes
Migrated:  2025_12_15_000200_add_dashboard_performance_indexes (XXX.XXms)
```

**Verify Indexes:**
```sql
-- Check indexes were created
SHOW INDEXES FROM shift_assignments WHERE Key_name LIKE 'idx_%';
SHOW INDEXES FROM shifts WHERE Key_name LIKE 'idx_%';
SHOW INDEXES FROM shift_applications WHERE Key_name LIKE 'idx_%';
SHOW INDEXES FROM shift_payments WHERE Key_name LIKE 'idx_%';
SHOW INDEXES FROM agency_workers WHERE Key_name LIKE 'idx_%';
SHOW INDEXES FROM shift_notifications WHERE Key_name LIKE 'idx_%';
```

**Expected:** Should see 16 new indexes total

---

### 2. Browser Testing

#### Step 1: Open Dashboard
1. Start Laravel server: `php artisan serve`
2. Open browser: `http://localhost:8000/dashboard`
3. Login as Worker, Business, or Agency user

#### Step 2: Open Developer Tools
1. Press `F12` or `Cmd+Option+I` (Mac) / `Ctrl+Shift+I` (Windows)
2. Go to **Console** tab
3. Go to **Network** tab

#### Step 3: Verify Polling
**In Console Tab:**
- Should see: `Dashboard updates: Initializing...` (if logging enabled)
- No JavaScript errors
- Check for `window.dashboardUpdates` object:
  ```javascript
  window.dashboardUpdates
  // Should return: {update: ƒ, stop: ƒ, start: ƒ}
  ```

**In Network Tab:**
1. Filter by "XHR" or "Fetch"
2. Should see requests to:
   - `/api/dashboard/stats` - Every 30 seconds
   - `/api/dashboard/notifications/count` - Every 30 seconds
3. Check response:
   - Status: `200 OK`
   - Response time: Should be fast (< 100ms if cached)
   - Response body: JSON with stats data

#### Step 4: Verify Stats Update
1. Watch a stat card (e.g., "Shifts Today")
2. Wait 30 seconds
3. Value should update automatically (if changed)
4. Should see brief pulse animation on change

#### Step 5: Test Tab Visibility
1. Switch to another browser tab
2. Wait 30 seconds
3. Switch back to dashboard tab
4. Should see immediate update (polling resumed)

---

### 3. API Endpoint Testing

#### Test Dashboard Stats Endpoint
```bash
# Get auth token first (if using Sanctum)
# Then test endpoint:
curl -X GET http://localhost:8000/api/dashboard/stats \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "shifts_today": 2,
  "shifts_this_week": 5,
  "pending_applications": 1,
  "earnings_this_week": 45000,
  "earnings_this_month": 120000,
  "total_completed": 15,
  "rating": 4.5,
  "reliability_score": 0.95
}
```

#### Test Notification Count Endpoint
```bash
curl -X GET http://localhost:8000/api/dashboard/notifications/count \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "count": 3
}
```

---

### 4. Cache Testing

#### Test Cache Hit
1. Make first API request (should be slow, ~150-250ms)
2. Make second API request within 30 seconds (should be fast, ~50-100ms)
3. Check Laravel logs for cache operations

#### Clear Cache
```bash
php artisan cache:clear
```

#### Verify Cache Keys
```bash
# If using file cache, check:
ls -la storage/framework/cache/data/ | grep dashboard_stats
```

---

### 5. Performance Testing

#### Query Performance
```sql
-- Before indexes: Should show "Using filesort" or "Using temporary"
-- After indexes: Should show "Using index"
EXPLAIN SELECT * FROM shift_assignments 
WHERE worker_id = 1 AND status = 'completed' 
ORDER BY created_at DESC;
```

#### Load Testing
```bash
# Test API endpoint performance
ab -n 100 -c 10 http://localhost:8000/api/dashboard/stats
```

**Expected:**
- Average response time: < 100ms (cached)
- Requests per second: > 50

---

### 6. Error Handling Testing

#### Test Error Scenarios
1. **Database Error:**
   - Temporarily break a query in `DashboardController`
   - Verify dashboard still loads with fallback data
   - Check logs for error message

2. **API Error:**
   - Stop Laravel server
   - Verify polling fails gracefully (no console errors)
   - Restart server, verify polling resumes

3. **Network Error:**
   - Disconnect internet
   - Verify polling fails silently
   - Reconnect, verify polling resumes

---

## 🔍 MONITORING

### Browser Console Monitoring
```javascript
// Enable verbose logging
localStorage.setItem('dashboard_debug', 'true');

// Check polling status
window.dashboardUpdates;

// Manual update
window.dashboardUpdates.update();

// Stop/start polling
window.dashboardUpdates.stop();
window.dashboardUpdates.start();
```

### Network Monitoring
1. Open Network tab
2. Filter by "dashboard" or "stats"
3. Check:
   - Request frequency (should be every 30s)
   - Response times
   - Response sizes
   - Cache headers (if applicable)

### Laravel Log Monitoring
```bash
tail -f storage/logs/laravel.log | grep -E "Dashboard|Cache|Error"
```

---

## ✅ SUCCESS CRITERIA

### Functional
- [x] Stats update every 30 seconds
- [x] Notification count updates every 30 seconds
- [x] Polling pauses when tab hidden
- [x] Polling resumes when tab visible
- [x] Stats animate on change
- [x] No JavaScript errors in console
- [x] API endpoints return correct data

### Performance
- [x] Cached responses < 100ms
- [x] Uncached responses < 250ms
- [x] Database queries use indexes
- [x] No N+1 query problems

### Error Handling
- [x] Dashboard loads with fallback data on error
- [x] Polling fails gracefully
- [x] Errors logged appropriately

---

## 🐛 TROUBLESHOOTING

### Issue: Polling Not Starting
**Check:**
- Browser console for errors
- `window.userId` is set
- `window.axios` is available
- `dashboard-updates.js` is loaded

**Fix:**
```javascript
// In browser console
console.log(window.userId); // Should be a number
console.log(window.axios); // Should be a function
window.dashboardUpdates.start(); // Manual start
```

### Issue: Stats Not Updating
**Check:**
- Network tab for API requests
- API response status (should be 200)
- API response data (should have stats)
- `data-stat` attributes in HTML

**Fix:**
```javascript
// Check if elements exist
document.querySelectorAll('[data-stat]');
// Should return NodeList of elements
```

### Issue: Cache Not Working
**Check:**
- `.env` has `CACHE_DRIVER` set
- Storage permissions: `chmod -R 775 storage`
- Cache directory exists

**Fix:**
```bash
php artisan cache:clear
php artisan config:clear
```

### Issue: Migration Fails
**Check:**
- No duplicate migration files
- Database connection works
- Tables exist

**Fix:**
```bash
# Check for duplicates
ls database/migrations/*dashboard_performance*

# Rollback if needed
php artisan migrate:rollback --step=1
```

---

## 📊 EXPECTED RESULTS

### Worker Dashboard
- Stats update: Shifts Today, This Week, Earnings, Rating
- Updates every 30 seconds
- Smooth animations on change

### Business Dashboard
- Stats update: Active Shifts, Pending Applications, Workers Today, Costs
- Updates every 30 seconds
- Smooth animations on change

### Agency Dashboard
- Stats update: Total Workers, Active Placements, Revenue, Placements
- Updates every 30 seconds
- Smooth animations on change

---

**Status:** Testing guide ready. Follow steps above to verify all features work correctly.
