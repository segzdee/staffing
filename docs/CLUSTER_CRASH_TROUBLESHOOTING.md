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

## Common Crash Causes

### 1. Fatal Errors in Service Providers
**Symptom**: Application fails during bootstrap
**Check**: Look for "Fatal error" in logs
**Fix**: All service providers now have error handling

### 2. Memory Exhaustion
**Symptom**: "Allowed memory size exhausted"
**Fix**: Increase PHP memory limit in Laravel Cloud settings

### 3. Database Connection Timeout
**Symptom**: Application hangs during bootstrap
**Fix**: Service providers now handle DB failures gracefully

### 4. Missing Dependencies
**Symptom**: "Class not found" errors
**Fix**: Run `composer install` in Laravel Cloud

### 5. Inertia SSR Startup Failure
**Symptom**: SSR process exits with code 1
**Status**: ✅ Fixed - SSR disabled in config

## Code Fixes Applied

### ✅ Service Provider Error Handling
- `AppServiceProvider`: All operations wrapped in try-catch
- `ViewServiceProvider`: Database queries protected
- `HorizonServiceProvider`: Gate function protected

### ✅ Feature Flag Error Handling
- `feature()` helper: Returns false on error (safe default)
- Blade directives: All wrapped in try-catch

### ✅ Model Observer Registration
- Observer registration wrapped in try-catch
- Logs warnings but doesn't crash

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
- ✅ Logging for debugging
- ✅ Safe defaults

## Next Steps

1. **Check Laravel Cloud Logs** for specific error
2. **Run diagnostic commands** above
3. **Review recent deployments** for breaking changes
4. **Test health endpoint**: `https://www.overtimestaff.com/up`
