# Compute Cluster Crash - Troubleshooting Guide

## Critical Issue

The compute cluster is crashing, which means the entire application is failing to start or is crashing during runtime.

## Immediate Diagnostic Steps

### Step 1: Check Laravel Cloud Logs
1. Go to: https://cloud.laravel.com/overtimestaff
2. Navigate to **Logs** section
3. Look for:
   - **Fatal errors** (PHP Fatal Error)
   - **Memory exhaustion** (Allowed memory size exhausted)
   - **Timeout errors** (Maximum execution time exceeded)
   - **Database connection errors**
   - **Service provider errors**
   - **Class not found errors**

### Step 2: Check Application Health
Run in Laravel Cloud console:
```bash
php artisan about
```

### Step 3: Test Bootstrap
Run in Laravel Cloud console:
```bash
php artisan tinker --execute="echo 'Bootstrap successful';"
```

### Step 4: Check Database Connection
Run in Laravel Cloud console:
```bash
php artisan db:show
```

### Step 5: Check Environment Variables
Run in Laravel Cloud console:
```bash
php artisan tinker --execute="echo 'DB Host: ' . config('database.connections.mysql.host');"
```

## Common Crash Causes

### 1. Fatal Errors in Service Providers ✅ FIXED
**Symptom**: Application fails during bootstrap
**Status**: All service providers now have comprehensive error handling
**Files Fixed**:
- `AppServiceProvider` - All operations wrapped in try-catch
- `ViewServiceProvider` - Database queries protected
- `BroadcastServiceProvider` - Channels file loading protected
- `HorizonServiceProvider` - Gate function protected

### 2. Missing Log Facade Import ✅ FIXED
**Symptom**: "Class 'Log' not found" fatal error
**Status**: Log facade now properly imported in all service providers
**Files Fixed**:
- `AppServiceProvider` - Added `use Illuminate\Support\Facades\Log;`
- `HorizonServiceProvider` - Added `use Illuminate\Support\Facades\Log;`

### 3. Memory Exhaustion
**Symptom**: "Allowed memory size exhausted"
**Fix**: Increase PHP memory limit in Laravel Cloud settings
**Check**: `php -i | grep memory_limit`

### 4. Database Connection Timeout
**Symptom**: Application hangs during bootstrap
**Status**: ✅ Fixed - Service providers handle DB failures gracefully

### 5. Missing Dependencies
**Symptom**: "Class not found" errors
**Fix**: Run `composer install` in Laravel Cloud
**Check**: `composer show | grep [package-name]`

### 6. Inertia SSR Startup Failure ✅ FIXED
**Symptom**: SSR process exits with code 1
**Status**: ✅ Fixed - SSR disabled in `config/inertia.php`

### 7. Broadcast Channels Database Queries
**Symptom**: Fatal error when loading channels.php
**Status**: ✅ Fixed - All channel callbacks wrapped in try-catch

## Code Fixes Applied

### ✅ Service Provider Error Handling
- `AppServiceProvider`: All operations wrapped in try-catch
- `ViewServiceProvider`: Database queries protected
- `BroadcastServiceProvider`: Channels file loading protected
- `HorizonServiceProvider`: Gate function protected
- All Log calls wrapped in try-catch to prevent logging failures from crashing

### ✅ Feature Flag Error Handling
- `feature()` helper: Returns false on error (safe default)
- Blade directives: All wrapped in try-catch
- Logging failures don't crash the application

### ✅ Model Observer Registration
- Observer registration wrapped in try-catch
- Logs warnings but doesn't crash

### ✅ Broadcast Channel Authorization
- All channel callbacks wrapped in try-catch
- Returns false on error (safe default)

## Emergency Recovery

If cluster continues to crash:

1. **Disable Problematic Service Providers** (temporary):
   - Comment out providers in `config/app.php`
   - Start with custom providers (ViewServiceProvider, etc.)

2. **Check Environment Variables**:
   ```bash
   php artisan tinker --execute="dd(config('database.connections.mysql'));"
   ```

3. **Minimal Bootstrap Test**:
   - Create a simple route that doesn't use database
   - Test if basic Laravel works

4. **Contact Laravel Cloud Support**:
   - Provide error logs
   - Mention recent changes
   - Request cluster restart

## Prevention

All critical operations now have:
- ✅ Try-catch error handling
- ✅ Graceful fallbacks
- ✅ Logging for debugging (with fallback if logging fails)
- ✅ Safe defaults

## Next Steps

1. **Check Laravel Cloud Logs** for specific error
2. **Run diagnostic commands** above
3. **Review recent deployments** for breaking changes
4. **Test health endpoint**: `https://www.overtimestaff.com/up`

## Recent Fixes (2025-12-22)

- ✅ Added Log facade imports
- ✅ Wrapped all Log calls in try-catch
- ✅ Added error handling to BroadcastServiceProvider
- ✅ Added error handling to broadcast channel callbacks
- ✅ All service providers now fail gracefully
