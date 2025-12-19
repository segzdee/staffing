# Route and Navigation Audit Report
## OvertimeStaff Application
**Generated:** December 19, 2025

---

## Executive Summary

This comprehensive audit covers 1,198 routes across the OvertimeStaff application, analyzing route definitions, controller mappings, view file references, navigation components, and authentication/authorization gating.

### Key Findings
- **Total Routes:** 1,198
- **Critical Issues:** 12 broken routes (404 candidates)
- **Missing Middleware:** 4 routes without proper auth protection
- **Navigation Issues:** 8 sidebar links to non-existent routes
- **Hardcoded URLs:** 0 (application uses route() helper consistently)

---

## 1. BROKEN ROUTES (404 Candidates)

### 1.1 Missing Route Definitions

The following routes are referenced in Blade views but DO NOT exist in the route files:

| Route Name | Referenced In | Issue |
|------------|---------------|-------|
| `business.templates.index` | `/resources/views/templates/index.blade.php`, sidebar | Route not defined |
| `business.templates.store` | `/resources/views/templates/create.blade.php` | Route not defined |
| `business.templates.createShifts` | `/resources/views/templates/index.blade.php` | Route not defined |
| `business.templates.duplicate` | `/resources/views/templates/show.blade.php` | Route not defined |
| `business.templates.deactivate` | `/resources/views/templates/show.blade.php` | Route not defined |
| `business.templates.activate` | `/resources/views/templates/index.blade.php` | Route not defined |
| `business.templates.delete` | `/resources/views/templates/index.blade.php` | Route not defined |
| `worker.swaps.index` | `/resources/views/swaps/show.blade.php` | Route not defined |
| `worker.swaps.accept` | `/resources/views/swaps/show.blade.php` | Route not defined |
| `worker.swaps.cancel` | `/resources/views/swaps/show.blade.php` | Route not defined |
| `worker.swaps.my` | `/resources/views/swaps/create.blade.php` | Route not defined |
| `worker.swaps.offer` | `/resources/views/swaps/create.blade.php` | Route not defined |

### 1.2 Sidebar Navigation Issues

#### Business Sidebar (`/resources/views/business/partials/sidebar.blade.php`)

| Line | Route Reference | Status |
|------|-----------------|--------|
| 23 | `business.templates.index` | BROKEN - Route does not exist |

**Note:** The controller `Business\TemplateController` exists but routes are not defined. The controller handles Communication Templates, NOT Shift Templates. There's a naming conflict.

#### Agency Sidebar (`/resources/views/agency/partials/sidebar.blade.php`)

| Line | Route Reference | Status |
|------|-----------------|--------|
| 16 | `agency.clients.index` | BROKEN - Route is `dashboard.agency.clients` |
| 30 | `agency.assignments` | BROKEN - Route does not exist |
| 39 | `agency.commissions` | BROKEN - Route is `dashboard.agency.commissions` |
| 46 | `agency.stripe.status` | BROKEN - Route does not exist |
| 62 | `agency.analytics` | BROKEN - Route is `agency.analytics.dashboard` |

#### Worker Sidebar (`/resources/views/worker/partials/sidebar.blade.php`)

| Line | Route Reference | Status |
|------|-----------------|--------|
| 9 | `worker.market` | BROKEN - Route does not exist |
| 44 | `worker.recommended` | BROKEN - Route does not exist |

### 1.3 Dashboard Navigation Config (`/config/dashboard.php`)

| Route Reference | Status | Correct Route |
|-----------------|--------|---------------|
| `worker.earnings.pending` | BROKEN | Route does not exist |
| `agency.shifts.index` | BROKEN | Should be `agency.shifts.browse` |
| `business.analytics` | BROKEN | Should be `business.analytics.index` |

---

## 2. ROUTES MISSING AUTH/PERMISSION MIDDLEWARE

### 2.1 Public Routes (Intentionally No Auth) - VERIFIED SAFE

These routes are correctly public:
- `/` (home)
- `/login`, `/register`
- `/terms`, `/privacy-policy`, `/contact`, `/about`
- `/pricing`
- `/profile/{username}` (public profile)
- `/workers` (worker search)

### 2.2 Routes Needing Review

| Route | Current Middleware | Concern |
|-------|-------------------|---------|
| `/api/featured-workers` | None | Exposes featured worker data publicly |
| `/api/market/public` | None | Intentionally public but review data exposure |
| `/api/market/simulate` | None | Simulation endpoint - consider removing in production |

### 2.3 Admin Routes - PROPERLY PROTECTED

All admin routes are within the `admin` middleware group with proper checks.

### 2.4 Business Routes - PROPERLY PROTECTED

All business routes use `auth` + `business` middleware.

### 2.5 Worker Routes - PROPERLY PROTECTED

All worker routes use `auth` + `worker` middleware.

### 2.6 Agency Routes - PROPERLY PROTECTED

All agency routes use `auth` + `agency` middleware.

---

## 3. ROUTE-CONTROLLER VERIFICATION

### 3.1 Controllers That Exist With Defined Routes

| Controller Directory | Controller Count | Status |
|---------------------|------------------|--------|
| `/app/Http/Controllers/Admin/` | 15+ | All methods verified |
| `/app/Http/Controllers/Business/` | 23 | All methods verified |
| `/app/Http/Controllers/Worker/` | 36 | All methods verified |
| `/app/Http/Controllers/Agency/` | 11 | All methods verified |
| `/app/Http/Controllers/Shift/` | 3 | All methods verified |
| `/app/Http/Controllers/Api/` | 8+ | All methods verified |

### 3.2 Naming Conflicts Identified

**Issue:** `Business\TemplateController` handles Communication Templates but views reference Shift Templates.

- `business.communication-templates.*` routes exist and work correctly
- `business.templates.*` routes referenced in views do NOT exist
- `/resources/views/templates/` folder appears to be for Shift Templates but uses non-existent routes

---

## 4. VIEW FILE VERIFICATION

### 4.1 Missing View Files

All view files referenced by controllers exist. No orphaned controller->view references found.

### 4.2 View Files Using Broken Routes

| View File | Broken Route(s) |
|-----------|-----------------|
| `/resources/views/templates/index.blade.php` | `business.templates.index`, `business.templates.createShifts`, etc. |
| `/resources/views/templates/create.blade.php` | `business.templates.index`, `business.templates.store` |
| `/resources/views/templates/edit.blade.php` | `business.templates.index`, `business.templates.store` |
| `/resources/views/templates/show.blade.php` | All `business.templates.*` routes |
| `/resources/views/swaps/index.blade.php` | `worker.swaps.my` |
| `/resources/views/swaps/show.blade.php` | `worker.swaps.index`, `worker.swaps.accept`, `worker.swaps.cancel` |
| `/resources/views/swaps/create.blade.php` | `worker.swaps.my`, `worker.swaps.offer` |
| `/resources/views/shifts/recommended.blade.php` | `worker.recommended`, `worker.apply` |

---

## 5. HARDCODED URLS

### 5.1 Assessment

The application consistently uses Laravel's `route()` helper for generating URLs. No hardcoded URLs were found in navigation components.

**Good patterns observed:**
```php
{{ route('dashboard.index') }}
{{ route('business.shifts.index') }}
{{ route('settings.index') }}
```

---

## 6. RECOMMENDED FIXES

### Priority 1: Critical (Broken Navigation)

#### 6.1 Add Missing Shift Template Routes

Add to `routes/web.php` within the business middleware group:

```php
// Shift Templates
Route::prefix('templates')->name('business.templates.')->group(function () {
    Route::get('/', [Business\ShiftTemplateController::class, 'index'])->name('index');
    Route::get('/create', [Business\ShiftTemplateController::class, 'create'])->name('create');
    Route::post('/', [Business\ShiftTemplateController::class, 'store'])->name('store');
    Route::get('/{template}', [Business\ShiftTemplateController::class, 'show'])->name('show');
    Route::get('/{template}/edit', [Business\ShiftTemplateController::class, 'edit'])->name('edit');
    Route::put('/{template}', [Business\ShiftTemplateController::class, 'update'])->name('update');
    Route::delete('/{template}', [Business\ShiftTemplateController::class, 'delete'])->name('delete');
    Route::post('/{template}/duplicate', [Business\ShiftTemplateController::class, 'duplicate'])->name('duplicate');
    Route::post('/{template}/activate', [Business\ShiftTemplateController::class, 'activate'])->name('activate');
    Route::post('/{template}/deactivate', [Business\ShiftTemplateController::class, 'deactivate'])->name('deactivate');
    Route::post('/{template}/create-shifts', [Business\ShiftTemplateController::class, 'createShifts'])->name('createShifts');
});
```

Create new controller: `/app/Http/Controllers/Business/ShiftTemplateController.php`

#### 6.2 Add Missing Worker Swap Routes

Add to `routes/web.php` within the worker middleware group:

```php
// Shift Swaps
Route::prefix('swaps')->name('worker.swaps.')->group(function () {
    Route::get('/', [Worker\ShiftSwapController::class, 'index'])->name('index');
    Route::get('/my', [Worker\ShiftSwapController::class, 'mySwaps'])->name('my');
    Route::get('/{swap}', [Worker\ShiftSwapController::class, 'show'])->name('show');
    Route::post('/{assignment}/offer', [Worker\ShiftSwapController::class, 'offer'])->name('offer');
    Route::post('/{swap}/accept', [Worker\ShiftSwapController::class, 'accept'])->name('accept');
    Route::post('/{swap}/cancel', [Worker\ShiftSwapController::class, 'cancel'])->name('cancel');
});
```

Create new controller: `/app/Http/Controllers/Worker/ShiftSwapController.php`

#### 6.3 Fix Agency Sidebar Routes

Update `/resources/views/agency/partials/sidebar.blade.php`:

| Line | Current | Should Be |
|------|---------|-----------|
| 16 | `agency.clients.index` | `dashboard.agency.clients` |
| 30 | `agency.assignments` | `agency.placements.active` |
| 39 | `agency.commissions` | `agency.finance.commissions` |
| 46 | `agency.stripe.status` | Create new route OR use `agency.finance.overview` |
| 62 | `agency.analytics` | `agency.analytics.dashboard` |

#### 6.4 Fix Worker Sidebar Routes

Update `/resources/views/worker/partials/sidebar.blade.php`:

| Line | Current | Action |
|------|---------|--------|
| 9 | `worker.market` | Create route OR use `shifts.index` |
| 44 | `worker.recommended` | Create route targeting `Shift\ShiftController@recommended` |

### Priority 2: Add Missing Routes

#### 6.5 Add Worker Apply Route

```php
Route::post('/shifts/{shift}/apply', [Worker\ShiftApplicationController::class, 'apply'])
    ->name('worker.apply');
```

#### 6.6 Add Agency Stripe Status Route

```php
Route::get('/agency/stripe/status', [Agency\StripeConnectController::class, 'status'])
    ->name('agency.stripe.status');
```

### Priority 3: Code Cleanup

#### 6.7 Rename Template Controller

Consider renaming `Business\TemplateController` to `Business\CommunicationTemplateController` to avoid confusion with Shift Templates.

---

## 7. MIDDLEWARE AUDIT SUMMARY

### Properly Protected Route Groups

| Route Group | Middleware Stack | Status |
|-------------|-----------------|--------|
| Admin (`/admin/*`) | `auth`, `admin` | CORRECT |
| Business (`/business/*`) | `auth`, `business` | CORRECT |
| Worker (`/worker/*`) | `auth`, `worker` | CORRECT |
| Agency (`/agency/*`) | `auth`, `agency` | CORRECT |
| API Auth (`/api/*`) | `auth:sanctum` | CORRECT |
| Dashboard (`/dashboard/*`) | `auth` | CORRECT |

### Rate-Limited Endpoints

| Endpoint Pattern | Rate Limiter | Purpose |
|-----------------|--------------|---------|
| `/register`, `/login` | `throttle:registration`, `throttle:login` | Prevent brute force |
| `/password/email`, `/password/reset` | `throttle:password-reset` | Prevent abuse |
| `/email/resend` | `throttle:verification` | Prevent spam |
| `api/business/register` | `throttle:registration` | Prevent mass registration |
| `api/worker/verify-*` | `throttle:verification-code` | Prevent code brute force |

---

## 8. FILES REQUIRING CHANGES

### Views to Update

1. `/resources/views/business/partials/sidebar.blade.php`
2. `/resources/views/agency/partials/sidebar.blade.php`
3. `/resources/views/worker/partials/sidebar.blade.php`
4. `/resources/views/templates/index.blade.php`
5. `/resources/views/templates/create.blade.php`
6. `/resources/views/templates/edit.blade.php`
7. `/resources/views/templates/show.blade.php`
8. `/resources/views/swaps/index.blade.php`
9. `/resources/views/swaps/create.blade.php`
10. `/resources/views/swaps/show.blade.php`
11. `/resources/views/shifts/recommended.blade.php`

### Routes File

1. `/routes/web.php` - Add missing route definitions

### Controllers to Create

1. `/app/Http/Controllers/Business/ShiftTemplateController.php`
2. `/app/Http/Controllers/Worker/ShiftSwapController.php` (if not using existing `Shift\ShiftSwapController`)

---

## 9. VERIFICATION COMMANDS

Run these commands after implementing fixes:

```bash
# Clear route cache
php artisan route:clear

# Verify all routes are registered
php artisan route:list --name=business.templates
php artisan route:list --name=worker.swaps
php artisan route:list --name=agency.stripe

# Check for route definition errors
php artisan route:list 2>&1 | grep -i error

# Test specific routes
php artisan tinker
Route::has('business.templates.index') // Should return true after fix
Route::has('worker.swaps.index') // Should return true after fix
```

---

## 10. CONCLUSION

The OvertimeStaff application has a well-structured routing system with proper middleware protection across all authenticated areas. The primary issues are:

1. **Missing route definitions** for Shift Templates and Worker Swaps features
2. **Outdated sidebar navigation** using incorrect route names for Agency section
3. **Missing worker navigation routes** for Market and Recommended features

These issues appear to be from incomplete feature implementation rather than security vulnerabilities. All protected routes have appropriate authentication and authorization middleware in place.

**Estimated Fix Time:** 4-6 hours

**Risk Level:** Medium - Users may encounter 404 errors when clicking navigation items

---

*End of Report*
