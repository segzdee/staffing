<?php

namespace App\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        try {
            Broadcast::routes(['middleware' => ['web', 'auth:sanctum']]);

            // Require channels file - wrap in try-catch to prevent bootstrap failures
            if (file_exists(base_path('routes/channels.php'))) {
                require base_path('routes/channels.php');
            }
        } catch (\Exception $e) {
            // Log but don't crash if broadcasting setup fails
            try {
                \Illuminate\Support\Facades\Log::warning('Broadcast service provider failed', ['error' => $e->getMessage()]);
            } catch (\Exception $logError) {
                // If logging fails, silently continue
            }
        }
    }
}
