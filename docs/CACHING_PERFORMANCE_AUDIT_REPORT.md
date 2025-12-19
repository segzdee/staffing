# OvertimeStaff Caching Strategy and Performance Instrumentation Audit

**Date:** December 19, 2025
**Scope:** Full audit of caching, session, queue, rate limiting, and asset optimization

---

## Executive Summary

The OvertimeStaff application has a solid foundation for caching and performance but several critical gaps exist:

| Area | Status | Priority |
|------|--------|----------|
| Cache Driver | FILE (should be REDIS) | **CRITICAL** |
| Session Driver | FILE (should be REDIS) | **HIGH** |
| Queue Driver | REDIS (correctly configured) | OK |
| Existing Cache Usage | 40+ locations found | GOOD |
| Performance Instrumentation | NOT INSTALLED | **HIGH** |
| Rate Limiting | Comprehensive | GOOD |
| Asset Bundling | Vite (modern) | GOOD |

---

## 1. Current Caching Configuration

### 1.1 Cache Driver Configuration

**File:** `/Users/ots/Desktop/Staffing/config/cache.php`

```php
'default' => env('CACHE_DRIVER', 'file'),
```

**Current .env Setting:**
```env
CACHE_DRIVER=file
```

**Issue:** The application is using FILE cache driver despite Redis being available and running. File-based caching is:
- Slower than in-memory caching
- Not suitable for multi-server deployments
- Cannot share cache across application instances

**Recommendation:** Switch to Redis immediately.

### 1.2 Redis Configuration

**File:** `/Users/ots/Desktop/Staffing/config/database.php`

Redis is properly configured with separate databases:
- `default` (database 0): General Redis operations
- `cache` (database 1): Dedicated cache store

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
    ],
    'cache' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),
    ],
],
```

---

## 2. Session and Queue Configuration

### 2.1 Session Configuration

**File:** `/Users/ots/Desktop/Staffing/config/session.php`

**Current Setting:**
```env
SESSION_DRIVER=file
```

**Good Practices Already Implemented:**
- Session encryption enabled (`'encrypt' => true`)
- Secure cookies in production (`'secure' => env('APP_ENV') === 'production'`)
- HTTP-only cookies enabled
- SameSite set to 'lax'

**Issue:** Using FILE sessions in a multi-server or high-traffic environment creates:
- Session inconsistency across servers
- Performance bottlenecks
- Disk I/O overhead

**Recommendation:** Switch to Redis for sessions.

### 2.2 Queue Configuration

**File:** `/Users/ots/Desktop/Staffing/config/queue.php`

**Current .env Setting:**
```env
QUEUE_CONNECTION=redis
```

**Status:** Correctly configured for Redis with proper queues defined.

### 2.3 Horizon Configuration

**File:** `/Users/ots/Desktop/Staffing/config/horizon.php`

Horizon is properly configured with:
- Multiple queue priorities: `default`, `emails`, `payments`, `notifications`
- Auto-scaling enabled for production (1-10 processes)
- Wait time thresholds configured
- Job trimming configured (60 minutes for recent, 7 days for failed)

**Note:** The `'middleware' => ['web']` should include authentication for security:
```php
'middleware' => ['web', 'auth', 'admin'],
```

---

## 3. Existing Cache Usage Analysis

### 3.1 Current Cache::remember() Implementations

Found **40+ locations** using caching appropriately:

| Service/Model | Cache Key Pattern | TTL | Purpose |
|--------------|-------------------|-----|---------|
| `SystemSettings` | `system_settings:*` | 3600s (1hr) | Platform configuration |
| `HolidayService` | `holidays:*` | 86400s (24hr) | Holiday lookups |
| `LiveMarketService` | `market_stats_*` | 300s (5min) | Market statistics |
| `DashboardController` | `dashboard_stats_*` | 30s | Dashboard metrics |
| `FeatureFlagService` | `feature_flags:*` | varies | Feature toggles |
| `CurrencyService` | `exchange_rate:*` | varies | Currency rates |
| `RegionalPricingService` | `regional_pricing:*` | varies | Regional pricing |
| `WhiteLabelService` | `whitelabel:*` | varies | White-label configs |
| `MarketRate` | `market_rate_*` | 3600s | Market rate data |
| `Translation` | `translation:*` | varies | Translation strings |
| `Locale` | `locale:*` | varies | Locale data |
| `User` (CachesUserProfile) | `user_profile:*` | 3600s | User profiles |

### 3.2 Cache Helper Methods

The application has a dedicated trait for user caching:

**File:** `/Users/ots/Desktop/Staffing/app/Traits/CachesUserProfile.php`

```php
trait CachesUserProfile
{
    const CACHE_PREFIX = 'user_profile:';
    const CACHE_TTL = 3600; // 1 hour

    public static function getCached(int $userId): ?self
    {
        return Cache::remember($cacheKey, static::CACHE_TTL, function () use ($userId) {
            return static::with([
                'workerProfile',
                'businessProfile',
                'agencyProfile',
            ])->find($userId);
        });
    }
}
```

---

## 4. Missing Cache Opportunities

### 4.1 Static/Semi-Static Data NOT Being Cached

The following data should be cached but is currently queried on every request:

#### Skills Master List
```php
// Current: No caching
$skills = Skill::active()->ordered()->get();

// Recommended implementation:
use Illuminate\Support\Facades\Cache;

class SkillService
{
    const CACHE_KEY = 'skills:active';
    const CACHE_TTL = 3600; // 1 hour

    public static function getActiveSkills()
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return Skill::active()->ordered()->get();
        });
    }

    public static function getSkillsByIndustry(string $industry)
    {
        return Cache::remember("skills:industry:{$industry}", self::CACHE_TTL, function () use ($industry) {
            return Skill::active()->byIndustry($industry)->ordered()->get();
        });
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        foreach (Skill::INDUSTRIES as $key => $label) {
            Cache::forget("skills:industry:{$key}");
        }
    }
}
```

#### Certification Types
```php
// Recommended implementation:
class CertificationTypeService
{
    const CACHE_KEY = 'certification_types:active';
    const CACHE_TTL = 3600;

    public static function getActiveTypes()
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return CertificationType::active()->orderBy('category')->orderBy('name')->get();
        });
    }

    public static function getByCategory(string $category)
    {
        return Cache::remember("certification_types:category:{$category}", self::CACHE_TTL, function () use ($category) {
            return CertificationType::active()->where('category', $category)->get();
        });
    }
}
```

#### Industries and Business Types
```php
// Recommended implementation:
class ReferenceDataService
{
    const CACHE_TTL = 86400; // 24 hours for reference data

    public static function getIndustries()
    {
        return Cache::remember('reference:industries', self::CACHE_TTL, function () {
            return Industry::active()->ordered()->get();
        });
    }

    public static function getBusinessTypes()
    {
        return Cache::remember('reference:business_types', self::CACHE_TTL, function () {
            return BusinessType::active()->ordered()->get();
        });
    }

    public static function getCountriesWithStates()
    {
        return Cache::remember('reference:countries_states', self::CACHE_TTL, function () {
            return Country::with('states')->where('is_active', true)->get();
        });
    }
}
```

### 4.2 Dashboard Statistics Caching Enhancement

The current dashboard caching (30 seconds) is good, but could be improved:

```php
// Recommended: Add tagged caching for easier invalidation
class DashboardCacheService
{
    public static function getWorkerStats(User $user): array
    {
        $cacheKey = "dashboard:worker:{$user->id}";

        return Cache::tags(['dashboard', "user:{$user->id}"])->remember($cacheKey, 30, function () use ($user) {
            return [
                'shifts_today' => ShiftAssignment::where('worker_id', $user->id)
                    ->whereDate('shifts.shift_date', today())
                    ->count(),
                // ... rest of stats
            ];
        });
    }

    public static function invalidateUserDashboard(int $userId): void
    {
        Cache::tags(["user:{$userId}"])->flush();
    }
}
```

### 4.3 API Response Caching

For public API endpoints that don't change frequently:

```php
// Recommended: Add response caching middleware
// app/Http/Middleware/CacheResponse.php

class CacheResponse
{
    public function handle(Request $request, Closure $next, int $minutes = 5)
    {
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        $cacheKey = 'api:response:' . sha1($request->fullUrl());

        return Cache::remember($cacheKey, $minutes * 60, function () use ($request, $next) {
            return $next($request);
        });
    }
}

// Register in Kernel.php:
protected $routeMiddleware = [
    'cache.response' => \App\Http\Middleware\CacheResponse::class,
];

// Usage in routes:
Route::get('/api/public/skills', [SkillController::class, 'index'])
    ->middleware('cache.response:60'); // Cache for 60 minutes
```

---

## 5. Performance Instrumentation

### 5.1 Current State

**Laravel Telescope:** NOT INSTALLED
**Laravel Debugbar:** NOT INSTALLED
**Query Logging:** NOT CONFIGURED

### 5.2 Recommended: Install Laravel Telescope

```bash
composer require laravel/telescope --dev

php artisan telescope:install
php artisan migrate
```

**Configuration for production:**
```php
// app/Providers/TelescopeServiceProvider.php
protected function gate()
{
    Gate::define('viewTelescope', function ($user) {
        return $user->isAdmin();
    });
}

// Only enable in local/staging
public function register()
{
    $this->hideSensitiveRequestDetails();

    Telescope::filter(function (IncomingEntry $entry) {
        if ($this->app->environment('production')) {
            return $entry->isReportableException() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        }
        return true;
    });
}
```

### 5.3 Recommended: Query Logging for Development

```php
// app/Providers/AppServiceProvider.php
public function boot()
{
    if (config('app.debug')) {
        DB::listen(function ($query) {
            if ($query->time > 100) { // Log slow queries (>100ms)
                Log::channel('queries')->warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time . 'ms',
                ]);
            }
        });
    }
}
```

Add to logging.php:
```php
'queries' => [
    'driver' => 'daily',
    'path' => storage_path('logs/queries.log'),
    'level' => 'debug',
    'days' => 7,
],
```

### 5.4 Recommended: Performance Metrics Service

```php
// app/Services/PerformanceMetricsService.php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class PerformanceMetricsService
{
    public function collectMetrics(): array
    {
        return [
            'cache_stats' => $this->getCacheStats(),
            'database_stats' => $this->getDatabaseStats(),
            'queue_stats' => $this->getQueueStats(),
            'memory_usage' => memory_get_peak_usage(true) / 1024 / 1024 . ' MB',
            'timestamp' => now()->toIso8601String(),
        ];
    }

    protected function getCacheStats(): array
    {
        if (config('cache.default') !== 'redis') {
            return ['driver' => 'file', 'note' => 'Limited stats available'];
        }

        $info = Redis::info();
        return [
            'driver' => 'redis',
            'used_memory' => $info['used_memory_human'] ?? 'N/A',
            'connected_clients' => $info['connected_clients'] ?? 0,
            'hits' => $info['keyspace_hits'] ?? 0,
            'misses' => $info['keyspace_misses'] ?? 0,
            'hit_rate' => $this->calculateHitRate($info),
        ];
    }

    protected function getDatabaseStats(): array
    {
        $slowQueries = DB::table('information_schema.processlist')
            ->where('TIME', '>', 1)
            ->count();

        return [
            'slow_queries' => $slowQueries,
            'connection_count' => DB::connection()->getPdo()->getAttribute(\PDO::ATTR_CONNECTION_STATUS) ? 1 : 0,
        ];
    }

    protected function getQueueStats(): array
    {
        return [
            'pending_jobs' => Redis::llen('queues:default') ?? 0,
            'failed_jobs' => DB::table('failed_jobs')->count(),
        ];
    }

    protected function calculateHitRate(array $info): string
    {
        $hits = $info['keyspace_hits'] ?? 0;
        $misses = $info['keyspace_misses'] ?? 0;
        $total = $hits + $misses;

        if ($total === 0) {
            return 'N/A';
        }

        return round(($hits / $total) * 100, 2) . '%';
    }
}
```

---

## 6. Rate Limiting Configuration

### 6.1 Current Implementation

**File:** `/Users/ots/Desktop/Staffing/app/Providers/RouteServiceProvider.php`

**Excellent implementation found:**

| Rate Limiter | Limit | Scope |
|-------------|-------|-------|
| `api` | 60/min | User ID or IP |
| `login` | 5/min | Email + IP |
| `password-reset` | 3/hour | Email + IP |
| `2fa-code` | 3/min | User ID |
| `2fa` | 5/5min | Session or IP |
| `registration` | 5/hour | IP |
| `verification` | 3/hour | User ID |
| `verification-code` | 5/10min | User ID |
| `password-change` | 5/hour | User ID |

### 6.2 Routes Using Rate Limiting

All critical authentication routes are protected:
- POST `/login` - `throttle:login`
- POST `/register` - `throttle:registration`
- POST `/password/email` - `throttle:password-reset`
- POST `/password/reset` - `throttle:password-reset`
- POST `/email/resend` - `throttle:verification`

### 6.3 Missing Rate Limiters

The following endpoints should have dedicated rate limiters:

```php
// Add to RouteServiceProvider::configureRateLimiting()

// Payment operations - sensitive, should be limited
RateLimiter::for('payment', function (Request $request) {
    return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
});

// Shift applications - prevent application spam
RateLimiter::for('shift-application', function (Request $request) {
    return Limit::perHour(20)->by($request->user()?->id ?: $request->ip());
});

// File uploads - prevent abuse
RateLimiter::for('upload', function (Request $request) {
    return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
});

// Search/filter operations - prevent scraping
RateLimiter::for('search', function (Request $request) {
    return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
});

// Report generation - resource intensive
RateLimiter::for('reports', function (Request $request) {
    return Limit::perHour(10)->by($request->user()?->id ?: $request->ip());
});
```

---

## 7. Asset Optimization

### 7.1 Current Setup

**Build Tool:** Vite (modern, excellent choice)
**File:** `/Users/ots/Desktop/Staffing/vite.config.js`

**Good Practices:**
- Using `laravel-vite-plugin`
- HMR configured for development
- Path aliases configured (`@` -> `resources/js`)
- Clean asset filenames with hashing

### 7.2 CSS Framework

**Using:** Tailwind CSS 3.4 with:
- Dark mode support (class-based)
- @tailwindcss/forms plugin
- Preline UI components
- Custom design tokens (CSS variables)

### 7.3 JavaScript

**Dependencies:**
- Axios (HTTP client)
- Lodash (utilities)
- Laravel Echo (real-time)
- Pusher/Reverb (WebSocket)
- Preline (UI library)

**Note:** Vue is commented out but infrastructure exists. Alpine.js is preferred for simple interactivity.

### 7.4 Optimization Recommendations

#### Add Build Optimization to Vite

```javascript
// vite.config.js additions
export default defineConfig({
    build: {
        // Enable minification
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: true, // Remove console.logs in production
            },
        },
        // Split chunks for better caching
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['lodash', 'axios'],
                    echo: ['laravel-echo', 'pusher-js'],
                },
            },
        },
        // Report bundle size
        reportCompressedSize: true,
    },
});
```

#### Add Image Optimization

```bash
npm install --save-dev vite-plugin-imagemin
```

```javascript
// vite.config.js
import viteImagemin from 'vite-plugin-imagemin';

plugins: [
    viteImagemin({
        gifsicle: { optimizationLevel: 7 },
        optipng: { optimizationLevel: 7 },
        mozjpeg: { quality: 80 },
        webp: { quality: 80 },
    }),
]
```

#### Add Lazy Loading for Components

```javascript
// resources/js/app.js
// Use dynamic imports for heavy components
const LiveShiftMarket = () => import('./components/live-shift-market.js');

// Only load when needed
if (document.querySelector('[data-live-market]')) {
    LiveShiftMarket().then(module => module.init());
}
```

---

## 8. Immediate Action Items

### Priority 1: CRITICAL (Do This Week)

1. **Switch to Redis for Cache and Session**

```env
# .env changes
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

2. **Verify Redis is running:**
```bash
brew services list | grep redis
redis-cli ping
```

### Priority 2: HIGH (Do This Sprint)

1. **Install Laravel Telescope for development:**
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

2. **Create caching services for static data:**
- Skills cache service
- Certification types cache service
- Reference data cache service

3. **Add missing rate limiters:**
- Payment operations
- File uploads
- Search/filter endpoints

### Priority 3: MEDIUM (Do This Month)

1. **Implement query logging for slow query detection**
2. **Add tagged caching for better cache invalidation**
3. **Configure Vite build optimization**
4. **Add image optimization pipeline**

### Priority 4: LOW (Ongoing)

1. **Monitor cache hit rates via Redis INFO**
2. **Review Telescope for N+1 queries**
3. **Implement APM (Application Performance Monitoring)**
4. **Consider CDN for static assets**

---

## 9. Cache Service Implementation Code

### 9.1 Complete Skills Cache Service

```php
<?php

namespace App\Services;

use App\Models\Skill;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SkillCacheService
{
    private const CACHE_PREFIX = 'skills:';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get all active skills.
     */
    public function getActiveSkills(): Collection
    {
        return Cache::remember(
            self::CACHE_PREFIX . 'active',
            self::CACHE_TTL,
            fn () => Skill::active()->ordered()->get()
        );
    }

    /**
     * Get skills by industry.
     */
    public function getByIndustry(string $industry): Collection
    {
        return Cache::remember(
            self::CACHE_PREFIX . "industry:{$industry}",
            self::CACHE_TTL,
            fn () => Skill::active()->byIndustry($industry)->ordered()->get()
        );
    }

    /**
     * Get skills by category.
     */
    public function getByCategory(string $category): Collection
    {
        return Cache::remember(
            self::CACHE_PREFIX . "category:{$category}",
            self::CACHE_TTL,
            fn () => Skill::active()->byCategory($category)->ordered()->get()
        );
    }

    /**
     * Get skills requiring certification.
     */
    public function getRequiringCertification(): Collection
    {
        return Cache::remember(
            self::CACHE_PREFIX . 'requiring_certification',
            self::CACHE_TTL,
            fn () => Skill::active()->requiresCertification()->ordered()->get()
        );
    }

    /**
     * Get skills grouped by industry.
     */
    public function getGroupedByIndustry(): Collection
    {
        return Cache::remember(
            self::CACHE_PREFIX . 'grouped_by_industry',
            self::CACHE_TTL,
            fn () => Skill::active()->ordered()->get()->groupBy('industry')
        );
    }

    /**
     * Clear all skill caches.
     */
    public function clearCache(): void
    {
        $keys = [
            'active',
            'requiring_certification',
            'grouped_by_industry',
        ];

        foreach ($keys as $key) {
            Cache::forget(self::CACHE_PREFIX . $key);
        }

        // Clear industry-specific caches
        foreach (Skill::INDUSTRIES as $industry => $label) {
            Cache::forget(self::CACHE_PREFIX . "industry:{$industry}");
        }

        // Clear category caches (you may need to adjust based on your categories)
        $categories = Skill::query()->distinct()->pluck('category');
        foreach ($categories as $category) {
            if ($category) {
                Cache::forget(self::CACHE_PREFIX . "category:{$category}");
            }
        }
    }
}
```

### 9.2 Reference Data Cache Service

```php
<?php

namespace App\Services;

use App\Models\Industry;
use App\Models\BusinessType;
use App\Models\CertificationType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ReferenceDataCacheService
{
    private const CACHE_PREFIX = 'reference:';
    private const CACHE_TTL = 86400; // 24 hours

    /**
     * Get all active industries.
     */
    public function getIndustries(): Collection
    {
        return Cache::remember(
            self::CACHE_PREFIX . 'industries',
            self::CACHE_TTL,
            fn () => Industry::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
        );
    }

    /**
     * Get all active business types.
     */
    public function getBusinessTypes(): Collection
    {
        return Cache::remember(
            self::CACHE_PREFIX . 'business_types',
            self::CACHE_TTL,
            fn () => BusinessType::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
        );
    }

    /**
     * Get all active certification types.
     */
    public function getCertificationTypes(): Collection
    {
        return Cache::remember(
            self::CACHE_PREFIX . 'certification_types',
            self::CACHE_TTL,
            fn () => CertificationType::query()
                ->where('is_active', true)
                ->orderBy('category')
                ->orderBy('name')
                ->get()
        );
    }

    /**
     * Get certification types grouped by category.
     */
    public function getCertificationTypesGrouped(): Collection
    {
        return Cache::remember(
            self::CACHE_PREFIX . 'certification_types_grouped',
            self::CACHE_TTL,
            fn () => CertificationType::query()
                ->where('is_active', true)
                ->orderBy('category')
                ->orderBy('name')
                ->get()
                ->groupBy('category')
        );
    }

    /**
     * Get Skill industries constant as options.
     */
    public function getSkillIndustryOptions(): array
    {
        return Cache::remember(
            self::CACHE_PREFIX . 'skill_industries',
            self::CACHE_TTL,
            fn () => \App\Models\Skill::getIndustryOptions()
        );
    }

    /**
     * Clear all reference data caches.
     */
    public function clearCache(): void
    {
        $keys = [
            'industries',
            'business_types',
            'certification_types',
            'certification_types_grouped',
            'skill_industries',
        ];

        foreach ($keys as $key) {
            Cache::forget(self::CACHE_PREFIX . $key);
        }
    }
}
```

---

## 10. Environment Configuration Updates

### Recommended .env Updates

```env
# ==============================================
# CACHING (CHANGE FROM FILE TO REDIS)
# ==============================================
CACHE_DRIVER=redis
CACHE_PREFIX=overtimestaff_cache_

# ==============================================
# SESSIONS (CHANGE FROM FILE TO REDIS)
# ==============================================
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_CONNECTION=default

# ==============================================
# REDIS (VERIFY THESE ARE SET)
# ==============================================
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1

# ==============================================
# QUEUE (ALREADY CORRECT)
# ==============================================
QUEUE_CONNECTION=redis

# ==============================================
# HORIZON (VERIFY)
# ==============================================
HORIZON_PREFIX=overtimestaff_horizon_
```

---

## Appendix A: Logging Channels Summary

Current specialized logging channels:
- `security` - 90 days retention
- `admin` - 90 days retention
- `disputes` - 180 days retention (compliance)

**Missing but recommended:**
- `queries` - Slow query logging
- `performance` - Performance metrics
- `cache` - Cache operations

---

## Appendix B: Files Modified/Created in This Audit

This audit recommends creating:

1. `/Users/ots/Desktop/Staffing/app/Services/SkillCacheService.php`
2. `/Users/ots/Desktop/Staffing/app/Services/ReferenceDataCacheService.php`
3. `/Users/ots/Desktop/Staffing/app/Services/PerformanceMetricsService.php`
4. `/Users/ots/Desktop/Staffing/app/Http/Middleware/CacheResponse.php`

And modifying:
1. `.env` - Change CACHE_DRIVER and SESSION_DRIVER
2. `config/logging.php` - Add queries channel
3. `app/Providers/RouteServiceProvider.php` - Add missing rate limiters
4. `vite.config.js` - Add build optimizations

---

*Report generated by autonomous development agent*
