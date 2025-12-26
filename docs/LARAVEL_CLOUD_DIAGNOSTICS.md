# Laravel Cloud Diagnostics Guide

## Quick Diagnostic Command

Run this command in Laravel Cloud console to check everything at once:

```bash
php artisan app:diagnose
```

This will check:
- ✅ Database connection
- ✅ Redis connection
- ✅ Cache configuration
- ✅ Session configuration
- ✅ Queue configuration
- ✅ Environment variables
- ✅ Storage links

## Manual Diagnostic Steps

### Step 1: Check Laravel Cloud Logs

1. **Access Laravel Cloud Dashboard**
   - Go to: https://cloud.laravel.com/overtimestaff
   - Navigate to **Logs** section

2. **Look for these error types:**
   - `PHP Fatal Error` - Critical errors that crash the app
   - `Connection refused` - Database or Redis connection failures
   - `Class not found` - Missing dependencies
   - `SQLSTATE` - Database errors
   - `Redis connection failed` - Redis errors
   - `Service provider` errors

3. **Recent error patterns to check:**
   ```
   - "502 Bad Gateway" errors
   - "Connection refused" errors
   - "Class 'X' not found" errors
   - "SQLSTATE[HY000] [2002]" - Database connection
   - "Connection refused" - Redis connection
   ```

### Step 2: Verify Environment Variables

Run these commands in Laravel Cloud console to check environment variables:

```bash
# Check database configuration
php artisan tinker --execute="
echo 'DB_HOST: ' . config('database.connections.mysql.host') . PHP_EOL;
echo 'DB_DATABASE: ' . config('database.connections.mysql.database') . PHP_EOL;
echo 'DB_USERNAME: ' . config('database.connections.mysql.username') . PHP_EOL;
"

# Check Redis configuration
php artisan tinker --execute="
echo 'REDIS_HOST: ' . config('database.redis.default.host') . PHP_EOL;
echo 'REDIS_PORT: ' . config('database.redis.default.port') . PHP_EOL;
echo 'REDIS_SCHEME: ' . (config('database.redis.default.scheme') ?? 'tcp') . PHP_EOL;
"

# Check application configuration
php artisan tinker --execute="
echo 'APP_ENV: ' . config('app.env') . PHP_EOL;
echo 'APP_DEBUG: ' . (config('app.debug') ? 'true' : 'false') . PHP_EOL;
echo 'APP_URL: ' . config('app.url') . PHP_EOL;
echo 'CACHE_DRIVER: ' . config('cache.default') . PHP_EOL;
echo 'SESSION_DRIVER: ' . config('session.driver') . PHP_EOL;
"
```

### Step 3: Test Database Connection

```bash
# Test database connection
php artisan db:show

# Test with tinker
php artisan tinker --execute="
try {
    DB::connection()->getPdo();
    echo 'Database: Connected successfully' . PHP_EOL;
} catch (\Exception \$e) {
    echo 'Database: Connection failed - ' . \$e->getMessage() . PHP_EOL;
}
"
```

### Step 4: Test Redis Connection

```bash
# Test Redis connection
php artisan tinker --execute="
try {
    use Illuminate\Support\Facades\Redis;
    Redis::ping();
    echo 'Redis: Connected successfully' . PHP_EOL;
    
    // Test cache
    Cache::put('test', 'value', 10);
    echo 'Cache: Working - ' . Cache::get('test') . PHP_EOL;
} catch (\Exception \$e) {
    echo 'Redis: Connection failed - ' . \$e->getMessage() . PHP_EOL;
    echo 'Tip: Set CACHE_DRIVER=file and SESSION_DRIVER=file if Redis is unavailable' . PHP_EOL;
}
"
```

### Step 5: Clear All Caches

```bash
# Clear all caches
php artisan optimize:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Environment Variables Checklist

### Required Database Variables
```env
DB_CONNECTION=mysql
DB_HOST=db-a0a22c02-3a24-4c4f-a286-c2ce91c97325.eu-central-1.public.db.laravel.cloud
DB_PORT=3306
DB_USERNAME=YOUR_DATABASE_USERNAME
DB_PASSWORD=YOUR_DATABASE_PASSWORD
DB_DATABASE=staffing
```

### Required Redis Variables (if using Redis)
```env
REDIS_HOST=tls://cache-a0a22d2a-b96f-4397-ba7f-1110e25f50c1.eu-central-1.public.caches.laravel.cloud
REDIS_USERNAME=application
REDIS_PORT=6379
REDIS_PASSWORD=YOUR_REDIS_PASSWORD
REDIS_DB=0
REDIS_CACHE_DB=0
```

### Required Application Variables
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://www.overtimestaff.com
```

### Cache/Session Configuration
```env
CACHE_DRIVER=redis  # or 'file' if Redis unavailable
SESSION_DRIVER=redis  # or 'file' if Redis unavailable
QUEUE_CONNECTION=redis  # or 'database' if Redis unavailable
```

## Common Issues and Fixes

### Issue 1: Database Connection Failed

**Symptoms:**
- `SQLSTATE[HY000] [2002] Connection refused`
- `SQLSTATE[HY000] [1045] Access denied`

**Fix:**
1. Verify database credentials in Laravel Cloud environment variables
2. Check database is running in Laravel Cloud dashboard
3. Verify firewall rules allow connections
4. Test connection: `php artisan db:show`

### Issue 2: Redis Connection Failed

**Symptoms:**
- `Connection refused`
- `Redis connection failed`
- Cache operations fail

**Fix:**
1. Verify Redis credentials in Laravel Cloud environment variables
2. Check Redis is running in Laravel Cloud dashboard
3. **Temporary fix:** Set `CACHE_DRIVER=file` and `SESSION_DRIVER=file`
4. Test connection: `php artisan tinker --execute="Redis::ping();"`

### Issue 3: 502 Bad Gateway

**Symptoms:**
- Cloudflare shows 502 error
- Application not responding

**Common Causes:**
1. Database connection failure
2. Redis connection failure
3. Missing environment variables
4. Service provider errors
5. PHP fatal errors

**Fix:**
1. Check Laravel Cloud logs for specific error
2. Run `php artisan app:diagnose` to identify issues
3. Verify all environment variables are set
4. Clear caches: `php artisan optimize:clear`
5. Check service provider errors in logs

### Issue 4: Missing Environment Variables

**Symptoms:**
- Configuration shows `null` or empty values
- Application behaves unexpectedly

**Fix:**
1. Go to Laravel Cloud → Environment Variables
2. Add all required variables from checklist above
3. Save and redeploy
4. Clear config cache: `php artisan config:clear`

## Quick Recovery Commands

If the application is down, try these in order:

```bash
# 1. Clear all caches
php artisan optimize:clear

# 2. Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 3. Test database
php artisan db:show

# 4. Run diagnostics
php artisan app:diagnose

# 5. Check for pending migrations
php artisan migrate:status
```

## Emergency Fallback Configuration

If Redis is causing issues, temporarily disable it:

```env
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=database
```

Then clear caches and redeploy.

## Monitoring

After fixing issues, monitor:

1. **Laravel Cloud Logs** - Check for new errors
2. **Application Health** - Run `php artisan app:diagnose` periodically
3. **Database Performance** - Check connection count and query performance
4. **Redis Performance** - Check memory usage and connection count

## Getting Help

If issues persist:

1. **Collect diagnostic information:**
   ```bash
   php artisan app:diagnose > diagnostics.txt
   php artisan about >> diagnostics.txt
   ```

2. **Check Laravel Cloud status:**
   - Visit Laravel Cloud status page
   - Check for service outages

3. **Contact Support:**
   - Provide diagnostic output
   - Include error logs
   - Mention recent changes
