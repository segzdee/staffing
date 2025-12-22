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

## Immediate Actions

### Step 1: Check Laravel Cloud Logs
1. Go to: https://cloud.laravel.com/overtimestaff
2. Navigate to "Logs" or "Application" section
3. Check for PHP errors, database connection errors, or fatal errors

### Step 2: Verify Environment Variables
1. In Laravel Cloud console, check Environment Variables
2. Ensure all required variables are set
3. Verify database connection details

### Step 3: Run Health Check
The application has a health endpoint at `/up`. Try accessing:
- https://www.overtimestaff.com/up

### Step 4: Check Database Connection
Run in Laravel Cloud console:
```bash
php artisan db:show
```

### Step 5: Clear Caches
Run in Laravel Cloud console:
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Code Fixes Applied

### ✅ ViewServiceProvider Fix
- Added `Schema::hasTable()` check before querying `admin_settings`
- Prevents database query errors during bootstrap
- File: `app/Providers/ViewServiceProvider.php`

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
