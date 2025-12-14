# OvertimeStaff Laravel 8 → 11 Upgrade Guide

## Phase 1: Composer Dependencies

### ✅ Step 1: Update composer.json (COMPLETED)
- Updated PHP constraint: `^7.3|^8.0` → `^8.2`
- Updated Laravel: `^8.12` → `^11.0`
- Updated Laravel packages (Cashier, UI, Socialite, Tinker)
- Updated dev dependencies (PHPUnit, Collision, Ignition)
- Added Laravel Pint for code styling

### 🔄 Step 2: Run Composer Update

```bash
composer update
```

**Expected Issues:**
1. Some packages may not be compatible with Laravel 11
2. Payment gateway packages may need updates
3. Media processing packages may conflict

**If composer update fails:**
```bash
# Try updating Laravel framework first
composer update laravel/framework --with-all-dependencies

# Then update other packages
composer update
```

**Packages That May Need Removal/Replacement:**
- `fahim/laravel5-paypal-ipn` - Laravel 5 specific, may need replacement
- `laravelcollective/html` - Consider native Blade components instead
- `pbmedia/laravel-ffmpeg` - Check Laravel 11 compatibility

### 🔄 Step 3: Clear Caches After Update

```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

---

## Phase 2: Laravel 11 Breaking Changes

### ✅ Configuration Files

Laravel 11 introduced a streamlined configuration structure. Many config files are now optional.

#### Required Changes:

**1. Remove/Update Deprecated Config Files:**
```bash
# Laravel 11 no longer uses these by default
# Keep only if you have custom settings
- config/broadcasting.php (now optional)
- config/hashing.php (now optional)
- config/view.php (now optional)
```

**2. Update `config/app.php`:**
- Remove `aliases` array (now auto-discovered)
- Remove `providers` array (now auto-discovered)
- Keep only custom aliases/providers

**3. Update `.env` for Laravel 11:**
```env
# Add these new variables
APP_MAINTENANCE_DRIVER=file
APP_MAINTENANCE_STORE=database

# Update session driver (recommended)
SESSION_DRIVER=database

# Update queue connection
QUEUE_CONNECTION=database
```

### ✅ Routing Changes

**1. Update `routes/web.php` structure:**
```php
<?php

use Illuminate\Support\Facades\Route;

// Laravel 11 no longer auto-imports Route facade in route files
// Ensure Route is imported at the top
```

**2. Check route model binding:**
- Laravel 11 changed implicit binding behavior
- Ensure all route parameters match model route keys

### ✅ Middleware Changes

**1. Update `app/Http/Kernel.php`:**

Laravel 11 restructured middleware. Your current `Kernel.php` needs migration:

**OLD (Laravel 8):**
```php
protected $middleware = [
    // Global middleware
];

protected $middlewareGroups = [
    'web' => [...],
    'api' => [...],
];

protected $routeMiddleware = [
    'auth' => \App\Http\Middleware\Authenticate::class,
];
```

**NEW (Laravel 11):**
```php
// Create bootstrap/app.php if it doesn't exist
// Middleware registration is now in bootstrap/app.php
```

**Migration Steps:**
1. Create `bootstrap/app.php` with middleware registration
2. Move middleware aliases from Kernel to bootstrap/app.php
3. Update middleware priority if needed

**Example `bootstrap/app.php`:**
```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'worker' => \App\Http\Middleware\WorkerMiddleware::class,
            'business' => \App\Http\Middleware\BusinessMiddleware::class,
            'agency' => \App\Http\Middleware\AgencyMiddleware::class,
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'api.agent' => \App\Http\Middleware\ApiAgentMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

### ✅ Service Provider Changes

**1. Check `app/Providers/RouteServiceProvider.php`:**
- Laravel 11 simplified route service provider
- Remove if only using default behavior
- Keep if custom route configuration exists

**2. Update `app/Providers/AppServiceProvider.php`:**
```php
use Illuminate\Support\Facades\Schema;

public function boot(): void
{
    // Laravel 11 requires explicit default string length
    Schema::defaultStringLength(191);
}
```

### ✅ Database Changes

**1. Update migrations for Laravel 11:**
```php
// OLD: Anonymous class migrations
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up() { }
    public function down() { }
};

// NEW: Same syntax works in Laravel 11
// No changes needed for migration structure
```

**2. Check for deprecated methods:**
- `->charset()` → Use `->charset` property
- `->collation()` → Use `->collation` property

**3. Update Model attributes:**
```php
// NEW: Laravel 11 prefers property type hints
class User extends Model
{
    // OLD
    protected $fillable = [...];
    protected $casts = [...];

    // NEW (optional but recommended)
    protected $fillable = [...];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
```

### ✅ Eloquent Changes

**Key Updates:**
1. **Accessor/Mutator Syntax:**
```php
// OLD (Laravel 8)
public function getNameAttribute($value) {
    return ucfirst($value);
}

// NEW (Laravel 11 - recommended)
use Illuminate\Database\Eloquent\Casts\Attribute;

protected function name(): Attribute
{
    return Attribute::make(
        get: fn ($value) => ucfirst($value),
    );
}
```

2. **Date Casting:**
```php
// Simplified date casting
protected $casts = [
    'created_at' => 'datetime',  // No longer needs :Y-m-d format
];
```

### ✅ Validation Changes

**1. Check for deprecated rules:**
- `date_format` still works
- New rules available: `decimal`, `uppercase`, `lowercase`

**2. Update custom validation rules:**
```php
// Laravel 11 prefers invokable rules
class CustomRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value !== 'valid') {
            $fail('The :attribute must be valid.');
        }
    }
}
```

### ✅ Authentication Changes

**1. Update `app/Http/Controllers/Auth/*`:**
- Check for deprecated `Auth::routes()` usage
- Laravel 11 uses same authentication scaffolding

**2. Update password reset:**
- No major changes needed
- Check `ResetPasswordController` for any deprecated methods

---

## Phase 3: Third-Party Package Compatibility

### 🔄 Packages Requiring Updates:

**1. Payment Gateways:**
```bash
# Check compatibility
composer show laravel/cashier  # Should be ^15.0
composer show srmklive/paypal  # Update to ~3.0 or ^4.0
```

**2. Media Processing:**
```bash
# Check FFmpeg compatibility
composer show pbmedia/laravel-ffmpeg  # May need ^8.0 or ^9.0

# Check Cloudinary
composer show cloudinary-labs/cloudinary-laravel  # Update if available
```

**3. Image Processing:**
```bash
# Intervention Image v2 works, but v3 recommended
composer require intervention/image ^3.0
```

**4. Socialite Providers:**
```bash
# Already updated to ^5.15
composer show laravel/socialite
```

### ⚠️ Packages That May Need Replacement:

**1. `fahim/laravel5-paypal-ipn`:**
- Laravel 5 specific package
- Consider replacing with `srmklive/paypal` exclusively

**2. `laravelcollective/html`:**
- Still works in Laravel 11
- Consider migrating to Blade components for forms

**3. `rap2hpoutre/laravel-log-viewer`:**
- Works but consider `opcodesio/log-viewer` (modern alternative)

---

## Phase 4: Testing After Upgrade

### 🧪 Critical Tests:

**1. Database Connection:**
```bash
php artisan migrate:status
php artisan db:seed --class=OvertimeStaffSeeder
```

**2. Authentication:**
- Test login/register/logout
- Test password reset
- Test social login (if used)

**3. Routes:**
```bash
php artisan route:list
# Verify all routes are registered
```

**4. Queue System:**
```bash
php artisan queue:work
# Test job processing
```

**5. File Uploads:**
- Test image uploads
- Test file storage (S3/Cloudinary/B2)
- Test image resizing

**6. Payment Integration:**
- Test Stripe payments
- Test PayPal payments
- Test webhook processing

---

## Phase 5: Known Issues & Solutions

### Issue 1: "Class 'Route' not found"
**Solution:** Import Route facade in route files
```php
use Illuminate\Support\Facades\Route;
```

### Issue 2: Middleware not found
**Solution:** Register middleware aliases in `bootstrap/app.php`

### Issue 3: Config cache errors
**Solution:**
```bash
php artisan config:clear
php artisan cache:clear
```

### Issue 4: Database connection failed
**Solution:** Check `.env` database credentials and run:
```bash
php artisan migrate:fresh --seed
```

### Issue 5: "Class 'Facade\Ignition' not found"
**Solution:** Already updated to `spatie/laravel-ignition` in composer.json

---

## Phase 6: Post-Upgrade Optimization

### 1. Update Code Style with Pint:
```bash
./vendor/bin/pint
```

### 2. Run Static Analysis:
```bash
composer require --dev larastan/larastan
./vendor/bin/phpstan analyse
```

### 3. Update PHPUnit Configuration:
```xml
<!-- phpunit.xml -->
<phpunit bootstrap="vendor/autoload.php">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

### 4. Clear All Caches:
```bash
php artisan optimize:clear
composer dump-autoload
```

---

## Next Steps After Laravel 11 Upgrade

1. ✅ **Laravel Mix → Vite Migration**
   - See `VITE_MIGRATION.md` (to be created)

2. ✅ **Vue 2 → Vue 3 Upgrade**
   - See `VUE3_MIGRATION.md` (to be created)

3. ✅ **Frontend Consolidation**
   - Bootstrap → Tailwind only
   - jQuery → Alpine.js/Vanilla JS

4. ✅ **Performance Optimization**
   - Add Laravel Octane
   - Optimize database queries
   - Add Redis caching

---

## Rollback Plan (If Needed)

If the upgrade fails critically:

```bash
# 1. Revert composer.json
git checkout composer.json composer.lock

# 2. Reinstall old dependencies
composer install

# 3. Clear caches
php artisan config:clear
php artisan cache:clear

# 4. Run migrations if database changed
php artisan migrate:fresh --seed
```

---

## Support Resources

- **Laravel 11 Upgrade Guide:** https://laravel.com/docs/11.x/upgrade
- **Laravel 11 Release Notes:** https://laravel.com/docs/11.x/releases
- **Breaking Changes:** https://github.com/laravel/framework/blob/11.x/UPGRADE.md

---

**Last Updated:** December 14, 2024
**Current Status:** Phase 1 Complete - Ready for `composer update`
