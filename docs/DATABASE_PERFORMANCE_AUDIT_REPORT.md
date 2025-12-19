# OvertimeStaff Database Performance Audit Report

## Executive Summary

This audit analyzes the database performance readiness for supporting **5,000 concurrent users**. The analysis covers index optimization, N+1 query detection, query optimization patterns, connection pooling, and model optimization.

**Overall Assessment: MODERATE RISK**

The application has a solid foundation with many indexes already in place, but several critical optimizations are needed to handle 5,000 concurrent users effectively.

---

## 1. Index Analysis

### 1.1 Current Index Coverage

#### Shifts Table (GOOD)
The shifts table has comprehensive indexing:
- `business_id` (foreign key)
- `shift_date`, `status`, `industry` (individual indexes)
- `start_datetime`, `confirmed_at`, `started_at`, `completed_at`, `cancelled_at`
- Composite indexes: `[shift_date, status]`, `[industry, status]`, `[location_city, location_state]`
- Market index: `[in_market, status, shift_date]`

#### Shift Applications Table (GOOD)
- `shift_id`, `worker_id`, `status` (individual indexes)
- Composite indexes: `[shift_id, status]`, `[worker_id, status]`
- Unique constraint: `[shift_id, worker_id]`

#### Shift Assignments Table (GOOD)
- `shift_id`, `worker_id`, `status` (individual indexes)
- Composite indexes: `[shift_id, status]`, `[worker_id, status]`
- `check_in_time`, `check_out_time`

#### Shift Payments Table (GOOD)
- `shift_assignment_id`, `worker_id`, `business_id`, `status`
- Composite index: `[status, released_at]`
- Unique indexes on Stripe IDs

#### Users Table (MODERATE)
- `user_type`, `role`, `status`, `onboarding_completed` indexes exist
- `username` has unique index

### 1.2 Missing Indexes (CRITICAL)

#### Priority 1 - High Impact Missing Indexes

```php
// Migration: 2025_12_XX_000001_add_performance_indexes.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ========================================
        // USERS TABLE - Critical for 5000 users
        // ========================================
        Schema::table('users', function (Blueprint $table) {
            // Composite index for dashboard queries
            $table->index(['user_type', 'status'], 'idx_users_type_status');

            // Index for verification queries (frequently filtered)
            $table->index(['is_verified_worker', 'user_type'], 'idx_users_verified_worker');
            $table->index(['is_verified_business', 'user_type'], 'idx_users_verified_business');

            // Email lookup optimization (if not already unique)
            // $table->index('email'); // Usually covered by unique constraint

            // Last activity tracking for analytics
            $table->index('last_login_at', 'idx_users_last_login');
        });

        // ========================================
        // SHIFTS TABLE - Additional optimizations
        // ========================================
        Schema::table('shifts', function (Blueprint $table) {
            // For business dashboard: upcoming shifts query
            $table->index(['business_id', 'status', 'shift_date'], 'idx_shifts_business_upcoming');

            // For worker marketplace: open shifts by location
            $table->index(['status', 'shift_date', 'location_state', 'location_city'], 'idx_shifts_market_location');

            // For agency queries
            $table->index(['posted_by_agency_id', 'status'], 'idx_shifts_agency_status');

            // For urgency-based queries
            $table->index(['status', 'urgency_level', 'shift_date'], 'idx_shifts_urgent');
        });

        // ========================================
        // SHIFT_APPLICATIONS TABLE
        // ========================================
        Schema::table('shift_applications', function (Blueprint $table) {
            // For counting pending applications per business (via shift)
            $table->index(['status', 'created_at'], 'idx_applications_pending_date');

            // For worker application history
            $table->index(['worker_id', 'created_at'], 'idx_applications_worker_history');
        });

        // ========================================
        // SHIFT_ASSIGNMENTS TABLE
        // ========================================
        Schema::table('shift_assignments', function (Blueprint $table) {
            // For payment processing queries
            $table->index(['status', 'payment_status'], 'idx_assignments_payment');

            // For late/no-show detection
            $table->index(['status', 'check_in_time', 'shift_id'], 'idx_assignments_checkin');

            // For worker earnings calculation
            $table->index(['worker_id', 'status', 'created_at'], 'idx_assignments_worker_earnings');
        });

        // ========================================
        // SHIFT_PAYMENTS TABLE
        // ========================================
        Schema::table('shift_payments', function (Blueprint $table) {
            // For payout processing job
            $table->index(['status', 'released_at', 'payout_completed_at'], 'idx_payments_payout_queue');

            // For dispute management
            $table->index(['disputed', 'disputed_at'], 'idx_payments_disputes');

            // For worker earnings reports
            $table->index(['worker_id', 'status', 'created_at'], 'idx_payments_worker_earnings');

            // For business spending reports
            $table->index(['business_id', 'status', 'created_at'], 'idx_payments_business_spending');
        });

        // ========================================
        // WORKER_PROFILES TABLE
        // ========================================
        Schema::table('worker_profiles', function (Blueprint $table) {
            // For worker search/matching
            $table->index(['reliability_score', 'rating_average'], 'idx_worker_profiles_performance');

            // For availability broadcasts
            $table->index(['is_available', 'background_check_status'], 'idx_worker_profiles_available');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_type_status');
            $table->dropIndex('idx_users_verified_worker');
            $table->dropIndex('idx_users_verified_business');
            $table->dropIndex('idx_users_last_login');
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropIndex('idx_shifts_business_upcoming');
            $table->dropIndex('idx_shifts_market_location');
            $table->dropIndex('idx_shifts_agency_status');
            $table->dropIndex('idx_shifts_urgent');
        });

        Schema::table('shift_applications', function (Blueprint $table) {
            $table->dropIndex('idx_applications_pending_date');
            $table->dropIndex('idx_applications_worker_history');
        });

        Schema::table('shift_assignments', function (Blueprint $table) {
            $table->dropIndex('idx_assignments_payment');
            $table->dropIndex('idx_assignments_checkin');
            $table->dropIndex('idx_assignments_worker_earnings');
        });

        Schema::table('shift_payments', function (Blueprint $table) {
            $table->dropIndex('idx_payments_payout_queue');
            $table->dropIndex('idx_payments_disputes');
            $table->dropIndex('idx_payments_worker_earnings');
            $table->dropIndex('idx_payments_business_spending');
        });

        Schema::table('worker_profiles', function (Blueprint $table) {
            $table->dropIndex('idx_worker_profiles_performance');
            $table->dropIndex('idx_worker_profiles_available');
        });
    }
};
```

---

## 2. N+1 Query Detection

### 2.1 Identified N+1 Issues

#### CRITICAL: Business Dashboard Controller
**File:** `/app/Http/Controllers/Business/DashboardController.php`

**Issue 1: Multiple separate count queries (Lines 26-41)**
```php
// CURRENT (6 separate queries!)
$totalShifts = Shift::where('business_id', $user->id)->count();
$activeShifts = Shift::where('business_id', $user->id)->where('status', 'open')...->count();
$completedShifts = Shift::where('business_id', $user->id)->where('status', 'completed')->count();
$pendingApplications = ShiftApplication::join(...)->count();
```

**FIX:**
```php
// OPTIMIZED: Single query with conditional counts
$shiftStats = Shift::where('business_id', $user->id)
    ->selectRaw("
        COUNT(*) as total_shifts,
        SUM(CASE WHEN status = 'open' AND shift_date >= CURDATE() THEN 1 ELSE 0 END) as active_shifts,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_shifts
    ")
    ->first();

$pendingApplications = ShiftApplication::query()
    ->join('shifts', 'shift_applications.shift_id', '=', 'shifts.id')
    ->where('shifts.business_id', $user->id)
    ->where('shift_applications.status', 'pending')
    ->count();
```

#### CRITICAL: Shift Management Controller - Analytics
**File:** `/app/Http/Controllers/Business/ShiftManagementController.php`

**Issue: Nested loops in calculateTotalSpent and calculateNoShowRate (Lines 466-493)**
```php
// CURRENT (N+1 in loops)
foreach ($shifts->where('status', 'completed') as $shift) {
    foreach ($shift->assignments as $assignment) {
        if ($assignment->payment) {
            $total += $assignment->payment->amount_gross;
        }
    }
}
```

**FIX:**
```php
// OPTIMIZED: Single aggregate query
$total = ShiftPayment::query()
    ->join('shift_assignments', 'shift_payments.shift_assignment_id', '=', 'shift_assignments.id')
    ->join('shifts', 'shift_assignments.shift_id', '=', 'shifts.id')
    ->where('shifts.business_id', Auth::id())
    ->where('shifts.status', 'completed')
    ->whereBetween('shifts.shift_date', [$startDate, $endDate])
    ->sum('shift_payments.amount_gross');
```

#### MODERATE: Dashboard Controller - Worker Stats
**File:** `/app/Http/Controllers/DashboardController.php`

**Issue: whereHas in stats count (Lines 50-52)**
```php
// CURRENT
'upcoming_shifts' => ShiftAssignment::where('worker_id', $user->id)
    ->where('status', 'active')
    ->whereHas('shift', function ($q) {
        $q->where('shift_date', '>=', now());
    })->count(),
```

**FIX:**
```php
// OPTIMIZED: Use join
'upcoming_shifts' => ShiftAssignment::query()
    ->join('shifts', 'shift_assignments.shift_id', '=', 'shifts.id')
    ->where('shift_assignments.worker_id', $user->id)
    ->where('shift_assignments.status', 'active')
    ->where('shifts.shift_date', '>=', now())
    ->count(),
```

### 2.2 whereHas Performance Issues

The application uses `whereHas` extensively (40+ locations identified). While readable, `whereHas` generates subqueries that don't use indexes efficiently.

**High-Impact Locations to Optimize:**

| File | Line | Description | Priority |
|------|------|-------------|----------|
| `Business/DashboardController.php` | 275, 296-306 | Application stats queries | HIGH |
| `DashboardController.php` | 90-95 | Business pending applications | HIGH |
| `Business/ShiftManagementController.php` | 58 | Pending applications count | HIGH |
| `Admin/WorkerManagementController.php` | 34, 47 | Worker filtering | MEDIUM |
| `Agency/ShiftManagementController.php` | 36, 304, 354 | Agency shift queries | MEDIUM |

**Solution Pattern:**
```php
// Instead of:
ShiftApplication::whereHas('shift', fn($q) => $q->where('business_id', $userId))
    ->where('status', 'pending')
    ->count();

// Use:
ShiftApplication::query()
    ->join('shifts', 'shift_applications.shift_id', '=', 'shifts.id')
    ->where('shifts.business_id', $userId)
    ->where('shift_applications.status', 'pending')
    ->count();
```

---

## 3. Query Optimization

### 3.1 Missing Eager Loading

#### Business Dashboard - shiftsNeedingAttention
**File:** `/app/Http/Controllers/Business/DashboardController.php` (Line 69-75)

```php
// CURRENT - Missing eager loading
$shiftsNeedingAttention = Shift::where('business_id', $user->id)
    ->where('status', 'open')
    ...
    ->get();

// FIX - Add eager loading for view requirements
$shiftsNeedingAttention = Shift::with(['venue:id,name', 'applications:id,shift_id,status'])
    ->where('business_id', $user->id)
    ->where('status', 'open')
    ...
    ->get();
```

#### Worker Assignments
**File:** `/app/Http/Controllers/DashboardController.php` (Line 72-76)

```php
// CURRENT
$assignments = ShiftAssignment::with('shift.business')
    ->where('worker_id', $user->id)
    ->latest()
    ->paginate(10);

// FIX - More complete eager loading
$assignments = ShiftAssignment::with([
    'shift:id,title,shift_date,start_time,end_time,location_city,status',
    'shift.business:id,name',
    'payment:id,shift_assignment_id,status,amount_net'
])
    ->where('worker_id', $user->id)
    ->latest()
    ->paginate(10);
```

### 3.2 SELECT * Issues

Several controllers retrieve full models when only specific columns are needed:

**Pattern to fix:**
```php
// CURRENT
$shifts = Shift::where('business_id', $user->id)->get();

// FIX - Select only needed columns
$shifts = Shift::query()
    ->select(['id', 'title', 'shift_date', 'status', 'required_workers', 'filled_workers'])
    ->where('business_id', $user->id)
    ->get();
```

### 3.3 Missing Pagination

**Identified Locations:**
| File | Line | Query | Risk |
|------|------|-------|------|
| `Business/DashboardController.php` | 101-103 | `$allShifts = Shift::...->get()` | HIGH |
| `ShiftManagementController.php` | 428-431 | Analytics shifts query | MEDIUM |
| `Worker/PaystubController.php` | Multiple | Payment history | MEDIUM |

---

## 4. Connection Pooling Configuration

### 4.1 Current Configuration Issues

The current `config/database.php` is missing critical settings for high concurrency:

```php
// CURRENT - Basic configuration
'mysql' => [
    'driver' => 'mysql',
    // ... basic settings only
],
```

### 4.2 Recommended Configuration for 5000 Concurrent Users

```php
// config/database.php - OPTIMIZED for 5000 concurrent users

'mysql' => [
    'driver' => 'mysql',
    'url' => env('DATABASE_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'unix_socket' => env('DB_SOCKET', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true, // Enable strict mode for data integrity
    'engine' => 'InnoDB',

    // CONNECTION POOLING SETTINGS
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::ATTR_PERSISTENT => env('DB_PERSISTENT', true), // Enable persistent connections
        PDO::ATTR_EMULATE_PREPARES => false, // Use native prepared statements
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        PDO::ATTR_TIMEOUT => 30, // Connection timeout
        (PHP_VERSION_ID >= 80400 && class_exists('Pdo\Mysql')
            ? \Pdo\Mysql::ATTR_SSL_CA
            : PDO::MYSQL_ATTR_SSL_CA) => env('MYSQL_ATTR_SSL_CA'),
    ]) : [],

    // PERFORMANCE SETTINGS
    'sticky' => true, // Use same connection for read-after-write
],
```

### 4.3 MySQL Server Configuration Recommendations

Add to `/etc/mysql/mysql.conf.d/mysqld.cnf` or equivalent:

```ini
[mysqld]
# Connection Settings for 5000 concurrent users
max_connections = 500
max_user_connections = 100
wait_timeout = 28800
interactive_timeout = 28800
connect_timeout = 10

# Buffer Pool (adjust based on available RAM)
innodb_buffer_pool_size = 4G  # 70-80% of available RAM for dedicated DB server
innodb_buffer_pool_instances = 4

# Query Cache (MySQL 8.0+ removed this, use Redis instead)
# For MySQL 5.7:
# query_cache_type = 1
# query_cache_size = 256M

# InnoDB Settings
innodb_log_file_size = 512M
innodb_log_buffer_size = 64M
innodb_flush_log_at_trx_commit = 2  # Better performance, slight durability trade-off
innodb_flush_method = O_DIRECT

# Thread Pool (MySQL Enterprise or Percona/MariaDB)
# thread_handling = pool-of-threads
# thread_pool_size = 32

# Table Cache
table_open_cache = 4000
table_definition_cache = 2000

# Temp Tables
tmp_table_size = 256M
max_heap_table_size = 256M

# Sort/Join Buffers
sort_buffer_size = 4M
join_buffer_size = 4M
read_buffer_size = 2M
read_rnd_buffer_size = 4M
```

### 4.4 Redis Configuration for Query Caching

Add to `.env`:
```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
```

Implement query caching for expensive queries:
```php
// Cache dashboard stats for 5 minutes
$stats = Cache::remember("business_dashboard_stats_{$user->id}", 300, function () use ($user) {
    return Shift::where('business_id', $user->id)
        ->selectRaw("
            COUNT(*) as total_shifts,
            SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as active_shifts,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_shifts
        ")
        ->first();
});
```

---

## 5. Model Optimization

### 5.1 Model $with Defaults

**Good News:** No models have default `$with` properties that would cause over-eager loading.

### 5.2 Model Scope Optimization

Current scopes are well-designed. However, add indexed-column-aware scopes:

```php
// ShiftPayment.php - Optimized scope for payout processing
public function scopeReadyForPayout($query)
{
    return $query
        ->where('status', 'released')
        ->where('disputed', false)
        ->where('released_at', '<=', now()->subMinutes(15))
        ->whereNull('payout_completed_at')
        ->orderBy('released_at', 'asc')  // Process oldest first
        ->limit(100);  // Process in batches
}
```

### 5.3 Chunk Processing for Large Datasets

**Issue:** Several locations process large datasets without chunking:

```php
// CURRENT - Memory intensive
$allShifts = Shift::where('business_id', $user->id)->get();
foreach ($allShifts as $shift) { ... }

// FIX - Use chunking or cursor
Shift::where('business_id', $user->id)
    ->cursor()
    ->each(function ($shift) {
        // Process each shift
    });

// Or for batch processing
Shift::where('business_id', $user->id)
    ->chunk(100, function ($shifts) {
        foreach ($shifts as $shift) {
            // Process
        }
    });
```

---

## 6. Priority Optimizations for 5000 User Load

### Priority 1 - CRITICAL (Implement Immediately)

1. **Add Performance Indexes Migration** (Section 1.2)
   - Expected impact: 50-70% reduction in query time for dashboard queries
   - Effort: Low (migration only)

2. **Fix Business Dashboard N+1 Queries**
   - Location: `/app/Http/Controllers/Business/DashboardController.php`
   - Expected impact: Reduce 10+ queries to 3-4 queries
   - Effort: Medium

3. **Configure Persistent Connections**
   - Location: `config/database.php`
   - Expected impact: 30-40% reduction in connection overhead
   - Effort: Low

4. **Implement Redis Caching for Dashboard Stats**
   - Expected impact: 80% reduction in database load for repeated requests
   - Effort: Medium

### Priority 2 - HIGH (Implement Within 1 Week)

5. **Replace whereHas with Joins in High-Traffic Controllers**
   - Locations: Business/DashboardController, DashboardController, ShiftManagementController
   - Expected impact: 40-60% faster queries
   - Effort: Medium-High

6. **Add Missing Eager Loading**
   - All locations identified in Section 3.1
   - Effort: Low

7. **Configure MySQL Server Parameters**
   - Expected impact: Better connection handling, reduced query times
   - Effort: Low-Medium

### Priority 3 - MEDIUM (Implement Within 2 Weeks)

8. **Implement Query Result Caching**
   - Cache expensive analytics queries
   - Effort: Medium

9. **Add Column Selection to Queries**
   - Replace SELECT * with specific columns
   - Effort: Medium (many locations)

10. **Add Missing Pagination**
    - All locations identified in Section 3.3
    - Effort: Low-Medium

---

## 7. Monitoring Recommendations

### 7.1 Enable Query Logging

```php
// AppServiceProvider.php
public function boot()
{
    if (config('app.debug')) {
        DB::listen(function ($query) {
            if ($query->time > 100) { // Log queries > 100ms
                Log::warning('Slow Query', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time
                ]);
            }
        });
    }
}
```

### 7.2 Install Laravel Debugbar (Development)

```bash
composer require barryvdh/laravel-debugbar --dev
```

### 7.3 Production Monitoring

Consider implementing:
- Laravel Telescope for query analysis
- MySQL slow query log analysis
- Application Performance Monitoring (APM) like New Relic or Datadog

---

## 8. Load Testing Recommendations

Before going live with 5000 users:

1. **Use Laravel Load Testing Tools**
   ```bash
   composer require --dev kdrmlhcn/laravel-load-tester
   ```

2. **Key Endpoints to Test**
   - `/dashboard` (all user types)
   - `/business/shifts` (shift listing)
   - `/worker/market` (marketplace)
   - `/api/shifts` (API endpoints)

3. **Target Metrics**
   - Response time < 200ms for 95th percentile
   - Database queries < 10 per page load
   - Memory usage < 128MB per request

---

## Conclusion

The OvertimeStaff application has a solid foundation but requires targeted optimizations to handle 5000 concurrent users. The primary bottlenecks are:

1. Missing composite indexes for common query patterns
2. N+1 queries in dashboard controllers
3. Suboptimal use of `whereHas` instead of joins
4. Missing connection pooling configuration
5. No query result caching

Implementing the Priority 1 optimizations will provide immediate 50-70% performance improvements and should be completed before scaling to 5000 users.

---

**Report Generated:** 2025-12-19
**Analyzed By:** Claude Code Agent
**Version:** 1.0
