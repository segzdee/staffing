<?php

namespace App\Providers;

use App\Models\AdminSettings;
use App\Models\Shift;
use App\Models\ShiftApplication;
use App\Models\ShiftAssignment;
use App\Models\ShiftPayment;
use App\Observers\ShiftApplicationObserver;
use App\Observers\ShiftAssignmentObserver;
use App\Observers\ShiftObserver;
use App\Observers\ShiftPaymentObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::withoutDoubleEncoding();
        Paginator::useBootstrap();

        try {
            // Set default string length for MySQL compatibility (only if DB is available)
            // This is safe to call even without DB connection - it just sets a config value
            Schema::defaultStringLength(191);

            // Check if admin_settings table exists before querying
            // Schema::hasTable() will handle connection errors gracefully
            if (Schema::hasTable('admin_settings')) {
                $setting = AdminSettings::first();
                if ($setting) {
                    $size = round((($setting->file_size_allowed) / 1024), 0);
                    View::share('size', $size);
                } else {
                    // Default 10MB for new installations
                    View::share('size', 10);
                }
            } else {
                // Default 10MB when table doesn't exist (OvertimeStaff)
                View::share('size', 10);
            }
        } catch (\PDOException|\Illuminate\Database\QueryException|\Exception $e) {
            // Fallback default - database connection failed or table doesn't exist
            View::share('size', 10);
        }

        // Register model observers (safe - they only run when models are used)
        try {
            Shift::observe(ShiftObserver::class);
            ShiftApplication::observe(ShiftApplicationObserver::class);
            ShiftAssignment::observe(ShiftAssignmentObserver::class);
            ShiftPayment::observe(ShiftPaymentObserver::class);
        } catch (\Exception $e) {
            // Log but don't crash if observers fail to register
            \Log::warning('Failed to register model observers', ['error' => $e->getMessage()]);
        }

        // ADM-007: Feature Flags Blade Directives
        try {
            $this->registerFeatureFlagDirectives();
        } catch (\Exception $e) {
            \Log::warning('Failed to register feature flag directives', ['error' => $e->getMessage()]);
        }

        // Register money formatting Blade directives
        try {
            $this->registerMoneyDirectives();
        } catch (\Exception $e) {
            \Log::warning('Failed to register money directives', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Register custom Blade directives for feature flags.
     *
     * ADM-007: Feature Flags System
     *
     * Usage in Blade templates:
     *
     *   @feature('new_dashboard')
     *       <div>New dashboard content</div>
     *
     *   @endfeature
     *
     *   @feature('premium_feature')
     *       <div>Premium content</div>
     *
     *   @else
     *       <div>Standard content</div>
     *
     *   @endfeature
     */
    protected function registerFeatureFlagDirectives(): void
    {
        // @feature('key') ... @endfeature
        Blade::if('feature', function (string $key) {
            try {
                return feature($key);
            } catch (\Exception $e) {
                \Log::warning('Feature flag check failed', ['key' => $key, 'error' => $e->getMessage()]);

                return false;
            }
        });

        // @featureFor('key', $user) ... @endfeatureFor
        Blade::if('featureFor', function (string $key, $user) {
            try {
                return feature($key, $user);
            } catch (\Exception $e) {
                \Log::warning('Feature flag check failed', ['key' => $key, 'error' => $e->getMessage()]);

                return false;
            }
        });

        // @featureDisabled('key') ... @endfeatureDisabled
        Blade::if('featureDisabled', function (string $key) {
            try {
                return ! feature($key);
            } catch (\Exception $e) {
                \Log::warning('Feature flag check failed', ['key' => $key, 'error' => $e->getMessage()]);

                return true; // Default to disabled if check fails
            }
        });
    }

    /**
     * Register money formatting Blade directives.
     *
     * Usage in Blade templates:
     *
     *   @money($shift->final_rate)           // Formats Money object: $25.00
     *   @money($cents, true)                 // Formats cents integer: $25.00
     *
     *   @moneyDecimal($shift->final_rate)    // Returns decimal: 25.00
     *
     * Handles null values gracefully, returning $0.00
     */
    protected function registerMoneyDirectives(): void
    {
        // @money($value) - Format Money object or cents to currency string
        Blade::directive('money', function ($expression) {
            return "<?php
                \$__moneyValue = {$expression};
                if (\$__moneyValue === null) {
                    echo '\$0.00';
                } elseif (\$__moneyValue instanceof \Money\Money) {
                    echo '\$' . number_format(\$__moneyValue->getAmount() / 100, 2);
                } elseif (is_numeric(\$__moneyValue)) {
                    echo '\$' . number_format(\$__moneyValue / 100, 2);
                } else {
                    echo '\$0.00';
                }
            ?>";
        });

        // @moneyDecimal($value) - Get decimal value from Money object or cents
        Blade::directive('moneyDecimal', function ($expression) {
            return "<?php
                \$__moneyValue = {$expression};
                if (\$__moneyValue === null) {
                    echo '0.00';
                } elseif (\$__moneyValue instanceof \Money\Money) {
                    echo number_format(\$__moneyValue->getAmount() / 100, 2);
                } elseif (is_numeric(\$__moneyValue)) {
                    echo number_format(\$__moneyValue / 100, 2);
                } else {
                    echo '0.00';
                }
            ?>";
        });

        // @moneyRaw($value) - Get raw decimal value (no formatting) for calculations
        Blade::directive('moneyRaw', function ($expression) {
            return "<?php
                \$__moneyValue = {$expression};
                if (\$__moneyValue === null) {
                    echo 0;
                } elseif (\$__moneyValue instanceof \Money\Money) {
                    echo \$__moneyValue->getAmount() / 100;
                } elseif (is_numeric(\$__moneyValue)) {
                    echo \$__moneyValue / 100;
                } else {
                    echo 0;
                }
            ?>";
        });
    }
}
