<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushNotificationToken;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * COM-002: Push Notification Controller
 *
 * Handles push notification token registration and management
 * for mobile and web clients.
 */
class PushNotificationController extends Controller
{
    public function __construct(
        protected PushNotificationService $pushService
    ) {}

    /**
     * Register a device push notification token.
     *
     * POST /api/push/register
     *
     * @bodyParam token string required The device push token (FCM/APNs)
     * @bodyParam platform string The platform type (fcm, apns, web). Default: fcm
     * @bodyParam device_id string The unique device identifier
     * @bodyParam device_name string The device name (e.g., "iPhone 15 Pro")
     * @bodyParam device_model string The device model
     * @bodyParam app_version string The app version
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string|max:500',
            'platform' => 'nullable|string|in:fcm,apns,web',
            'device_id' => 'nullable|string|max:255',
            'device_name' => 'nullable|string|max:255',
            'device_model' => 'nullable|string|max:255',
            'app_version' => 'nullable|string|max:50',
        ]);

        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        try {
            $pushToken = $this->pushService->registerToken(
                $user,
                $validated['token'],
                $validated['platform'] ?? 'fcm',
                [
                    'device_id' => $validated['device_id'] ?? null,
                    'device_name' => $validated['device_name'] ?? null,
                    'device_model' => $validated['device_model'] ?? null,
                    'app_version' => $validated['app_version'] ?? null,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Push notification token registered successfully',
                'data' => [
                    'token_id' => $pushToken->id,
                    'platform' => $pushToken->platform,
                    'is_active' => $pushToken->is_active,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to register push token', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to register push notification token',
            ], 500);
        }
    }

    /**
     * Unregister (remove) a device push notification token.
     *
     * DELETE /api/push/unregister
     *
     * @bodyParam token string required The device push token to remove
     */
    public function unregister(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string|max:500',
        ]);

        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        try {
            // Only allow users to remove their own tokens
            $deleted = PushNotificationToken::where('user_id', $user->id)
                ->where('token', $validated['token'])
                ->delete();

            if ($deleted) {
                return response()->json([
                    'success' => true,
                    'message' => 'Push notification token removed successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Token not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to unregister push token', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to remove push notification token',
            ], 500);
        }
    }

    /**
     * Send a test push notification to the authenticated user.
     *
     * GET /api/push/test
     */
    public function test(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        try {
            $log = $this->pushService->sendTest($user);

            if (! $log) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active push tokens found for your account. Please register a device first.',
                ], 404);
            }

            return response()->json([
                'success' => $log->wasSent(),
                'message' => $log->wasSent()
                    ? 'Test notification sent successfully'
                    : 'Test notification failed: '.$log->error_message,
                'data' => [
                    'log_id' => $log->id,
                    'status' => $log->status,
                    'message_id' => $log->message_id,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send test notification', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send test notification: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all registered push tokens for the authenticated user.
     *
     * GET /api/push/tokens
     */
    public function tokens(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $tokens = PushNotificationToken::where('user_id', $user->id)
            ->orderBy('last_used_at', 'desc')
            ->get()
            ->map(fn ($token) => [
                'id' => $token->id,
                'platform' => $token->platform,
                'device_name' => $token->device_name,
                'device_model' => $token->device_model,
                'app_version' => $token->app_version,
                'is_active' => $token->is_active,
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'registered_at' => $token->created_at->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data' => $tokens,
        ]);
    }

    /**
     * Get push notification statistics for the authenticated user.
     *
     * GET /api/push/stats
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $stats = $this->pushService->getUserStats($user);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Handle delivery receipt callback from FCM.
     *
     * POST /api/push/receipt
     *
     * This endpoint can be called by FCM to report delivery status.
     */
    public function receipt(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message_id' => 'required|string',
            'status' => 'required|string|in:delivered,clicked,opened,failed',
        ]);

        try {
            $this->pushService->handleDeliveryReceipt(
                $validated['message_id'],
                $validated['status']
            );

            return response()->json([
                'success' => true,
                'message' => 'Receipt processed',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process delivery receipt', [
                'message_id' => $validated['message_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process receipt',
            ], 500);
        }
    }
}
