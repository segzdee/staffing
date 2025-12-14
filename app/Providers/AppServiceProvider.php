<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use App\Models\AdminSettings;
use App\Models\ShiftAssignment;
use App\Observers\ShiftAssignmentObserver;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;

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
                    $size = round((($setting->file_size_allowed)/1024), 0);
                    View::share("size", $size);
                } else {
                    // Default 10MB for new installations
                    View::share("size", 10);
                }
            } else {
                // Default 10MB when table doesn't exist (OvertimeStaff)
                View::share("size", 10);
            }
        } catch (\Exception $e) {
            // Fallback default
            View::share("size", 10);
        }

        // Register model observers
        ShiftAssignment::observe(ShiftAssignmentObserver::class);
    }
}
