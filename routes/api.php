<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Demo route for development only - displays PHP configuration
if (app()->environment('local', 'development')) {
    Route::get("/demo", function(){
        return response()->json([
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'memory_limit' => ini_get('memory_limit'),
            'environment' => app()->environment(),
        ]);
    });
}

// Dashboard API (for live updates)
Route::middleware('auth:sanctum')->group(function() {
    Route::get('dashboard/stats', [App\Http\Controllers\Api\DashboardController::class, 'stats']);
    Route::get('dashboard/notifications/count', [App\Http\Controllers\Api\DashboardController::class, 'notificationsCount']);

    // Legacy notification endpoint (kept for compatibility)
    Route::get('notifications/unread-count', [App\Http\Controllers\Api\DashboardController::class, 'notificationsCount']);
});

// Live Market API (for real-time updates)
Route::middleware('auth:sanctum')->prefix('market')->group(function() {
    Route::get('/live', [App\Http\Controllers\LiveMarketController::class, 'apiIndex'])->name('api.market.live');
});
