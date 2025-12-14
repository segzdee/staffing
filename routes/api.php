<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AgentController;

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

/*
|--------------------------------------------------------------------------
| AI Agent API Routes
|--------------------------------------------------------------------------
|
| API endpoints for AI agents to autonomously manage shifts, discover workers,
| and handle applications. All routes require X-Agent-API-Key header.
|
| Rate Limits: 60 requests/minute, 1000 requests/hour
|
*/

Route::prefix('agent')->middleware('api.agent')->group(function() {

    // Shift Management
    Route::post('shifts', [AgentController::class, 'createShift']);
    Route::get('shifts/{id}', [AgentController::class, 'getShift']);
    Route::put('shifts/{id}', [AgentController::class, 'updateShift']);
    Route::delete('shifts/{id}', [AgentController::class, 'cancelShift']);

    // Worker Discovery
    Route::get('workers/search', [AgentController::class, 'searchWorkers']);
    Route::post('workers/invite', [AgentController::class, 'inviteWorker']);

    // Matching Algorithm
    Route::post('match/workers', [AgentController::class, 'matchWorkers']);

    // Application Management
    Route::get('applications', [AgentController::class, 'getApplications']);
    Route::post('applications/{id}/accept', [AgentController::class, 'acceptApplication']);

    // Analytics & Stats
    Route::get('stats', [AgentController::class, 'getStats']);
});

// Dashboard API (for live updates)
Route::middleware('auth:sanctum')->group(function() {
    Route::get('dashboard/stats', [App\Http\Controllers\Api\DashboardController::class, 'stats']);
    Route::get('dashboard/notifications/count', [App\Http\Controllers\Api\DashboardController::class, 'notificationsCount']);
    
    // Legacy notification endpoint (kept for compatibility)
    Route::get('notifications/unread-count', [App\Http\Controllers\Api\DashboardController::class, 'notificationsCount']);
});
