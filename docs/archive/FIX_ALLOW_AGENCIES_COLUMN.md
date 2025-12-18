# Fix: allow_agencies Column Missing

## Issue
**Error**: `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'allow_agencies' in 'where clause'`

**Location**: `app/Http/Controllers/DashboardController.php:230`

## Root Cause
The `DashboardController::agencyDashboard()` method queries shifts with:
```php
->where('allow_agencies', true)
```

But the `allow_agencies` column didn't exist in the `shifts` table.

## Fix Applied

### 1. Created Migration
**File**: `database/migrations/2025_12_14_173735_add_allow_agencies_to_shifts_table.php`

```php
Schema::table('shifts', function (Blueprint $table) {
    $table->boolean('allow_agencies')->default(true)->after('posted_by_agent')->index();
});
```

### 2. Updated Shift Model
**File**: `app/Models/Shift.php`

- Added `'allow_agencies'` to `$fillable` array
- Added `'allow_agencies' => 'boolean'` to `$casts` array

### 3. Migration Executed
✅ Migration ran successfully

## Result
- ✅ Column `allow_agencies` now exists in `shifts` table
- ✅ Default value is `true` (backward compatible)
- ✅ Query in `DashboardController` now works
- ✅ All existing shifts default to allowing agencies

## Testing
```php
// Test query
$shifts = Shift::where('allow_agencies', true)->get();
```

## Notes
- Default value is `true` to maintain backward compatibility
- Businesses can set this to `false` to restrict agency access to specific shifts
- Column is indexed for performance

---

**Status**: ✅ **FIXED**
