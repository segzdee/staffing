# Authentication Audit - Quick Reference

## Critical Issues Summary

### 🚨 Must Fix Immediately

1. **Admin Routes Prefix** (`routes/web.php:281`)
   - Change `/admin` to `/panel/admin`
   - Impact: Security risk, inconsistent with requirements

2. **Login Rate Limiting** (`app/Http/Controllers/Auth/LoginController.php:54`)
   - Add `maxAttempts = 5` and `decayMinutes = 15`
   - Add failed login attempt logging
   - Impact: Vulnerable to brute force attacks

3. **Dev Routes Protection** (`routes/web.php:293-369`)
   - Wrap `/dev-info`, `/db-test`, `/create-test-user` in environment check
   - Impact: Database info exposed in production

4. **Clear Cache Route** (`routes/web.php:72-77`)
   - Remove or protect with admin middleware
   - Impact: DoS attack vector

### ⚠️ High Priority

5. **Generic Routes Role Protection** (`routes/web.php:187-208`)
   - Add role middleware to shifts, messages, settings routes
   - Impact: Unauthorized access to role-specific features

6. **Authenticate Middleware URL Preservation** (`app/Http/Middleware/Authenticate.php:16-24`)
   - Preserve intended URL in session
   - Impact: Poor UX, users not redirected to intended page

7. **Post-Login Redirect** (`app/Http/Controllers/Auth/LoginController.php:81`)
   - Route by user type instead of generic dashboard
   - Impact: Poor user experience

## File Locations

### Configuration Files
- `config/auth.php` - Authentication guards and providers
- `app/Http/Kernel.php` - Middleware registration
- `app/Providers/RouteServiceProvider.php` - Route configuration

### Controllers
- `app/Http/Controllers/Auth/LoginController.php` - Login logic
- `app/Http/Controllers/Auth/RegisterController.php` - Registration logic
- `app/Http/Controllers/Auth/ResetPasswordController.php` - Password reset
- `app/Http/Controllers/DashboardController.php` - Dashboard routing

### Middleware
- `app/Http/Middleware/Authenticate.php` - Authentication check
- `app/Http/Middleware/WorkerMiddleware.php` - Worker role check
- `app/Http/Middleware/BusinessMiddleware.php` - Business role check
- `app/Http/Middleware/AgencyMiddleware.php` - Agency role check
- `app/Http/Middleware/AdminMiddleware.php` - Admin role check
- `app/Http/Middleware/ApiAgentMiddleware.php` - API agent authentication

### Routes
- `routes/web.php` - Web routes (main file)
- `routes/api.php` - API routes

## User Type Verification

### User Model Methods
- `$user->isWorker()` - Checks `user_type === 'worker'`
- `$user->isBusiness()` - Checks `user_type === 'business'`
- `$user->isAgency()` - Checks `user_type === 'agency'`
- `$user->isAiAgent()` - Checks `user_type === 'ai_agent'`
- `$user->isAdmin()` - Checks `role === 'admin'` (note: uses `role`, not `user_type`)

### Profile Relationships
- `$user->workerProfile` - WorkerProfile relationship
- `$user->businessProfile` - BusinessProfile relationship
- `$user->agencyProfile` - AgencyProfile relationship
- `$user->aiAgentProfile` - AiAgentProfile relationship

## Dashboard Routes

| User Type | Route | Middleware |
|-----------|-------|------------|
| Worker | `/worker/dashboard` | `auth`, `worker` |
| Business | `/business/dashboard` | `auth`, `business` |
| Agency | `/agency/dashboard` | `auth`, `agency` |
| Admin | `/admin/` (should be `/panel/admin/`) | `auth`, `admin` |
| Generic | `/dashboard` | `auth` (routes by user type) |

## Security Checklist

- [ ] Admin routes use `/panel/admin` prefix
- [ ] Login rate limiting configured (5 attempts, 15 min lockout)
- [ ] Failed login attempts logged
- [ ] Dev routes protected by environment check
- [ ] Clear cache route removed or protected
- [ ] Generic routes protected by role middleware
- [ ] Authenticate middleware preserves intended URL
- [ ] Post-login redirect routes by user type
- [ ] Email verification implemented (currently bypassed)
- [ ] Password reset redirects to login
- [ ] Logout clears all session data
- [ ] API routes use Sanctum (currently uses token driver)

## Testing Commands

```bash
# Test authentication
php artisan test --filter=AuthenticationTest

# Check route protection
php artisan route:list --compact | grep -E "(worker|business|agency|admin)"

# Verify middleware
php artisan route:list --path=admin
php artisan route:list --path=worker
php artisan route:list --path=business
```

## Quick Fixes

### Fix Admin Route Prefix
```php
// routes/web.php:281
Route::prefix('panel/admin')->middleware(['auth', 'admin'])->name('admin.')->group(function() {
    // ...
});
```

### Add Login Rate Limiting
```php
// app/Http/Controllers/Auth/LoginController.php
protected $maxAttempts = 5;
protected $decayMinutes = 15;
```

### Protect Dev Routes
```php
// routes/web.php
if (app()->environment('local', 'development')) {
    Route::get('/dev-info', function() { ... });
    Route::get('/db-test', function() { ... });
    Route::get('/create-test-user', function() { ... });
}
```

### Remove Clear Cache Route
```php
// routes/web.php:72-77 - DELETE or protect
Route::middleware(['auth', 'admin'])->get('/clear-cache', function() {
    // ... only if needed
});
```
