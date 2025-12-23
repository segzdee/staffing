# 502 Bad Gateway Error - Troubleshooting Guide

## Error Details
- **Error Code**: 502 Bad Gateway
- **Domain**: www.overtimestaff.com
- **Date**: 2025-12-22 10:34:18 UTC
- **Status**: Cloudflare working, origin server (Laravel Cloud) not responding

## Common Causes of 502 Errors

### 1. Database Connection Issues
- **Symptom**: Application crashes on bootstrap
- **Check**: Verify database credentials in Laravel Cloud environment variables
- **Fix**: Ensure `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` are set correctly

### 2. Missing Environment Variables
- **Symptom**: Application fails to start
- **Required Variables**:
  ```
  APP_ENV=production
  APP_DEBUG=false
  APP_URL=https://www.overtimestaff.com
  DB_CONNECTION=mysql
  DB_HOST=[Laravel Cloud DB Host]
  DB_DATABASE=[Database Name]
  DB_USERNAME=[Username]
  DB_PASSWORD=[Password]
  ```

### 3. Service Provider Errors
- **Fixed**: ViewServiceProvider now checks for table existence before querying
- **Status**: ✅ Fixed in latest commit

### 4. PHP-FPM Issues
- **Symptom**: PHP process not responding
- **Check**: Laravel Cloud console for PHP errors
- **Fix**: Restart application or check PHP version compatibility

### 5. Memory/Timeout Issues
- **Symptom**: Application times out during bootstrap
- **Check**: Increase memory limits in Laravel Cloud settings

### 6. Redis Connection Issues ✅ NEW
- **Symptom**: Application crashes when Redis is configured but unavailable
- **Check**: Verify Redis credentials in Laravel Cloud environment variables
- **Fix**: Ensure `REDIS_HOST`, `REDIS_PASSWORD`, `REDIS_USERNAME` are set correctly
- **Note**: If Redis is not available, temporarily set `CACHE_DRIVER=file` and `SESSION_DRIVER=file`

## Immediate Actions

### Step 1: Check Laravel Cloud Logs
1. Go to: https://cloud.laravel.com/overtimestaff
2. Navigate to "Logs" or "Application" section
3. Check for PHP errors, database connection errors, Redis errors, or fatal errors
4. Look for specific error messages about Redis, database, or service providers

### Step 2: Verify Environment Variables
1. In Laravel Cloud console, check Environment Variables
2. Ensure all required variables are set:
   - Database: `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
   - Redis: `REDIS_HOST`, `REDIS_PASSWORD`, `REDIS_USERNAME` (if using Redis)
   - Application: `APP_ENV`, `APP_DEBUG`, `APP_URL`
3. Verify database and Redis connection details

### Step 3: Test Redis Connection (If Using Redis)
Run in Laravel Cloud console:
```bash
php artisan tinker --execute="use Illuminate\Support\Facades\Redis; Redis::ping();"
```

If Redis fails, temporarily disable it:
```env
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database
```

### Step 4: Run Health Check
The application has a health endpoint at `/up`. Try accessing:
- https://www.overtimestaff.com/up

### Step 5: Check Database Connection
Run in Laravel Cloud console:
```bash
php artisan db:show
```

### Step 6: Clear Caches
Run in Laravel Cloud console:
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Code Fixes Applied

### ✅ ViewServiceProvider Fix (Commit: c58d8ba)
- Added `Schema::hasTable()` check before querying `admin_settings`
- Prevents database query errors during bootstrap
- File: `app/Providers/ViewServiceProvider.php`

### ✅ Enhanced Database Connection Error Handling (Commit: 9b763da)
- Added database connection check before `Schema::hasTable()` calls
- Catches `PDOException` and `QueryException` specifically
- Prevents 502 errors when database connection fails
- Applied to both `AppServiceProvider` and `ViewServiceProvider`
- Ensures graceful fallback when database is unavailable
- Files: `app/Providers/AppServiceProvider.php`, `app/Providers/ViewServiceProvider.php`

## Next Steps

1. **Check Laravel Cloud Logs** - Identify the exact error
2. **Verify Database Connection** - Ensure credentials are correct
3. **Check Environment Variables** - All required vars must be set
4. **Restart Application** - If available in Laravel Cloud console
5. **Run Migrations** - Ensure database schema is up to date

## Contact Laravel Cloud Support

If the issue persists:
1. Check Laravel Cloud status page
2. Review Laravel Cloud documentation
3. Contact Laravel Cloud support with:
   - Application name: overtimestaff
   - Error: 502 Bad Gateway
   - Time: 2025-12-22 10:34:18 UTC
   - Recent deployment: 742955a
