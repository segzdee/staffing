# Deployment Instructions: Frontend Polling, Caching & Database Indexes
**Date:** 2025-12-15

---

## 🚀 DEPLOYMENT STEPS

### Step 1: Run Database Migration
```bash
php artisan migrate
```

This will add 16 performance indexes to optimize dashboard queries.

**Expected Output:**
```
Migrating: 2025_12_15_000200_add_dashboard_performance_indexes
Migrated:  2025_12_15_000200_add_dashboard_performance_indexes (XXX.XXms)
```

### Step 2: Compile Frontend Assets
```bash
npm run build
```

This compiles the new `dashboard-updates.js` file.

**Expected Output:**
```
✓ built in X.XXs
```

### Step 3: Clear Application Cache
```bash
php artisan optimize:clear
```

This ensures all new routes, views, and configurations are loaded.

### Step 4: Verify Routes
```bash
php artisan route:list | grep "api/dashboard"
```

**Expected Output:**
```
GET|HEAD  api/dashboard/stats .......... Api\DashboardController@stats
GET|HEAD  api/dashboard/notifications/count Api\DashboardController@notificationsCount
```

---

## ✅ VERIFICATION

### 1. Test Dashboard Polling
1. Open browser: `http://localhost:8000/dashboard`
2. Open browser console (F12)
3. Look for polling logs (should see requests every 30 seconds)
4. Verify stats update automatically

### 2. Test Caching
1. Make API request: `GET /api/dashboard/stats`
2. Check response time (should be fast on second request)
3. Check Laravel logs for cache hits

### 3. Test Database Indexes
```sql
SHOW INDEXES FROM shift_assignments;
SHOW INDEXES FROM shifts;
SHOW INDEXES FROM shift_applications;
```

Should see new indexes:
- `idx_worker_status_created`
- `idx_business_status_date`
- `idx_worker_status`
- etc.

### 4. Test Error Handling
1. Temporarily break a query in dashboard controller
2. Verify dashboard still loads with fallback data
3. Check logs for error messages

---

## 🔧 TROUBLESHOOTING

### Issue: Polling Not Working
**Check:**
- Browser console for JavaScript errors
- Network tab for API requests
- Verify `window.userId` and `window.axios` are available
- Check if `dashboard-updates.js` is loaded

**Fix:**
```javascript
// In browser console
window.dashboardUpdates.update(); // Manual test
```

### Issue: Cache Not Working
**Check:**
- `.env` file has `CACHE_DRIVER` set (default: `file`)
- Storage permissions: `chmod -R 775 storage`
- Check cache directory exists: `storage/framework/cache`

**Fix:**
```bash
php artisan cache:clear
php artisan config:clear
```

### Issue: Migration Fails
**Check:**
- Database connection is working
- Tables exist before adding indexes
- No duplicate index names

**Fix:**
```bash
# Check migration status
php artisan migrate:status

# Rollback if needed
php artisan migrate:rollback --step=1
```

---

## 📊 MONITORING

### Cache Performance
Monitor cache hit rates:
```php
// Add to DashboardController temporarily
\Log::info('Cache hit: ' . Cache::has($cacheKey));
```

### Query Performance
Use EXPLAIN to verify index usage:
```sql
EXPLAIN SELECT * FROM shift_assignments 
WHERE worker_id = 1 AND status = 'completed';
```

Should show "Using index" in Extra column.

### Polling Performance
Monitor in browser:
- Network tab: Check request frequency
- Console: Check for errors
- Performance tab: Check CPU usage

---

## 🎯 EXPECTED RESULTS

### Performance Improvements
- **Dashboard Load:** 50-100ms (cached) vs 200-400ms (before)
- **Database Queries:** 1 per 30 seconds (cached) vs 8-12 per request
- **Query Speed:** 60-80% faster with indexes
- **Overall:** ~75% improvement in dashboard performance

### User Experience
- ✅ Stats update automatically every 30 seconds
- ✅ No page refresh needed
- ✅ Smooth animations on value changes
- ✅ Polling pauses when tab is hidden (saves resources)

---

**Status:** Ready for deployment. Follow steps above to deploy.
