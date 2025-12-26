# Quick Fix Guide for Laravel Cloud Issues

## Step 1: Run Diagnostics

Copy and paste this into Laravel Cloud console:

```bash
php artisan app:diagnose
```

**What to look for:**
- ❌ Red X marks = Critical issues
- ⚠️ Yellow warnings = Non-critical issues
- ✅ Green checkmarks = Working correctly

## Step 2: Check Logs

1. Go to: https://cloud.laravel.com/overtimestaff
2. Click **Logs** in the sidebar
3. Look for recent errors (last 5-10 minutes)
4. Copy any error messages you see

## Step 3: Run Auto-Fix

Copy and paste this into Laravel Cloud console:

```bash
php artisan app:fix-issues --force
```

This will:
- Clear all caches
- Rebuild config/route/view caches
- Check and fix storage links
- Identify Redis/database issues

## Step 4: Fix Based on Diagnostic Results

### If Database Connection Failed

**Error:** `SQLSTATE[HY000] [2002] Connection refused` or `Access denied`

**Fix:**
1. Go to Laravel Cloud → Environment Variables
2. Verify these are set:
   ```
   DB_CONNECTION=mysql
   DB_HOST=db-a0a22c02-3a24-4c4f-a286-c2ce91c97325.eu-central-1.public.db.laravel.cloud
   DB_PORT=3306
   DB_USERNAME=YOUR_DATABASE_USERNAME
   DB_PASSWORD=YOUR_DATABASE_PASSWORD
   DB_DATABASE=staffing
   ```
3. Save and redeploy
4. Run: `php artisan db:show` to verify

### If Redis Connection Failed

**Error:** `Connection refused` or `Redis connection failed`

**Fix Option 1: Fix Redis (Recommended)**
1. Go to Laravel Cloud → Environment Variables
2. Verify these are set:
   ```
   REDIS_HOST=tls://cache-a0a22d2a-b96f-4397-ba7f-1110e25f50c1.eu-central-1.public.caches.laravel.cloud
   REDIS_USERNAME=application
   REDIS_PORT=6379
   REDIS_PASSWORD=YOUR_REDIS_PASSWORD
   REDIS_DB=0
   REDIS_CACHE_DB=0
   ```
3. Save and redeploy
4. Run: `php artisan app:diagnose` to verify

**Fix Option 2: Disable Redis Temporarily**
1. Go to Laravel Cloud → Environment Variables
2. Set these:
   ```
   CACHE_DRIVER=file
   SESSION_DRIVER=file
   QUEUE_CONNECTION=database
   ```
3. Save and redeploy
4. Run: `php artisan app:fix-issues --force`

### If Missing Environment Variables

**Error:** Configuration shows `null` or empty values

**Fix:**
1. Go to Laravel Cloud → Environment Variables
2. Add all required variables (see checklist below)
3. Save and redeploy
4. Run: `php artisan config:clear && php artisan config:cache`

### If 502 Bad Gateway Persists

**After running diagnostics and fixes:**

1. **Check if application is running:**
   ```bash
   php artisan about
   ```

2. **Test a simple route:**
   ```bash
   php artisan tinker --execute="echo 'Bootstrap successful';"
   ```

3. **Check for fatal errors in logs:**
   - Look for `PHP Fatal Error`
   - Look for `Class not found`
   - Look for syntax errors

4. **Restart the application:**
   - In Laravel Cloud dashboard, try restarting the application
   - Or trigger a new deployment

## Complete Environment Variables Checklist

Copy this checklist and verify each variable in Laravel Cloud:

### Database (Required)
- [ ] `DB_CONNECTION=mysql`
- [ ] `DB_HOST=db-a0a22c02-3a24-4c4f-a286-c2ce91c97325.eu-central-1.public.db.laravel.cloud`
- [ ] `DB_PORT=3306`
- [ ] `DB_USERNAME=YOUR_DATABASE_USERNAME`
- [ ] `DB_PASSWORD=YOUR_DATABASE_PASSWORD`
- [ ] `DB_DATABASE=staffing`

### Redis (Required if using Redis)
- [ ] `REDIS_HOST=tls://cache-a0a22d2a-b96f-4397-ba7f-1110e25f50c1.eu-central-1.public.caches.laravel.cloud`
- [ ] `REDIS_USERNAME=application`
- [ ] `REDIS_PORT=6379`
- [ ] `REDIS_PASSWORD=YOUR_REDIS_PASSWORD`
- [ ] `REDIS_DB=0`
- [ ] `REDIS_CACHE_DB=0`

### Application (Required)
- [ ] `APP_ENV=production`
- [ ] `APP_DEBUG=false`
- [ ] `APP_URL=https://www.overtimestaff.com`

### Cache/Session (Set based on Redis availability)
- [ ] `CACHE_DRIVER=redis` (or `file` if Redis unavailable)
- [ ] `SESSION_DRIVER=redis` (or `file` if Redis unavailable)
- [ ] `QUEUE_CONNECTION=redis` (or `database` if Redis unavailable)

## Quick Command Reference

```bash
# Run full diagnostics
php artisan app:diagnose

# Auto-fix common issues
php artisan app:fix-issues --force

# Test database
php artisan db:show

# Test Redis
php artisan tinker --execute="Redis::ping();"

# Clear all caches
php artisan optimize:clear

# Rebuild caches
php artisan config:cache && php artisan route:cache && php artisan view:cache

# Check migration status
php artisan migrate:status

# Run pending migrations
php artisan migrate --force
```

## After Fixing Issues

1. **Verify the fix:**
   ```bash
   php artisan app:diagnose
   ```

2. **Test the application:**
   - Visit: https://www.overtimestaff.com
   - Check if 502 error is resolved

3. **Monitor logs:**
   - Watch Laravel Cloud logs for 5-10 minutes
   - Ensure no new errors appear

4. **If still failing:**
   - Collect diagnostic output: `php artisan app:diagnose > diagnostics.txt`
   - Check logs for specific error messages
   - Contact Laravel Cloud support with diagnostic output

## Emergency Recovery

If nothing works, try this complete reset:

```bash
# 1. Clear everything
php artisan optimize:clear

# 2. Disable Redis temporarily
# Set in Laravel Cloud environment variables:
# CACHE_DRIVER=file
# SESSION_DRIVER=file
# QUEUE_CONNECTION=database

# 3. Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 4. Test
php artisan app:diagnose
```

## Getting Help

If issues persist after following this guide:

1. **Collect information:**
   ```bash
   php artisan app:diagnose > diagnostics.txt
   php artisan about >> diagnostics.txt
   ```

2. **Check Laravel Cloud status:**
   - Visit Laravel Cloud status page
   - Check for service outages

3. **Contact support with:**
   - Diagnostic output
   - Error logs from Laravel Cloud
   - List of recent changes
   - Time when issue started
