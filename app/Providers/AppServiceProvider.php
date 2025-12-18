<?php

namespace App\Providers;

use App\Models\AdminSettings;
use App\Models\ShiftAssignment;
use App\Observers\ShiftAssignmentObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
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
        // Set default string length for MySQL compatibility
        Schema::defaultStringLength(191);

        Blade::withoutDoubleEncoding();
        Paginator::useBootstrap();

        try {
            // Check if admin_settings table exists before querying
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
        } catch (\Exception $e) {
            // Fallback default
            View::share('size', 10);
        }

        // Register model observers
        ShiftAssignment::observe(ShiftAssignmentObserver::class);

        // ADM-007: Feature Flags Blade Directives
        $this->registerFeatureFlagDirectives();
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
            return feature($key);
        });

        // @featureFor('key', $user) ... @endfeatureFor
        Blade::if('featureFor', function (string $key, $user) {
            return feature($key, $user);
        });

        // @featureDisabled('key') ... @endfeatureDisabled
        Blade::if('featureDisabled', function (string $key) {
            return ! feature($key);
        });
    }
}
