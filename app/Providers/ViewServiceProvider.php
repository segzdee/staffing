<?php

namespace App\Providers;

use App\Models\AdminSettings;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        try {
            // Check if database connection is available and table exists
            if (\Illuminate\Support\Facades\Schema::hasTable('admin_settings')) {
                // Admin Settings - ensure $settings is never null
                $settings = AdminSettings::first();
                // Share settings, or a default object if none exists
                View()->share('settings', $settings ?? new \stdClass);
            } else {
                // Share empty object as fallback when table doesn't exist
                View()->share('settings', new \stdClass);
            }
        } catch (\Exception $exception) {
            // Share empty object as fallback to prevent undefined variable errors
            View()->share('settings', new \stdClass);
        }

    }

    /**
     * Register any application services.
     *
     * This service provider is a great spot to register your various container
     * bindings with the application. As you can see, we are registering our
     * "Registrar" implementation here. You can add your own bindings too!
     *
     * @return void
     */
    public function register() {}
}
