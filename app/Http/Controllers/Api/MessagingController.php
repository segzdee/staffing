<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmsLog;
use App\Models\UserPhonePreference;
use App\Services\MessagingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * COM-004: Messaging API Controller
 *
 * Handles user messaging preferences and message history.
 */
class MessagingController extends Controller
{
    protected MessagingService $messagingService;

    public function __construct(MessagingService $messagingService)
    {
        $this->messagingService = $messagingService;
    }

    /**
     * Get user's messaging preferences
     */
    public function getPreferences(Request $request): JsonResponse
    {
        $user = $request->user();
        $preference = UserPhonePreference::getOrCreateForUser($user);

        return response()->json([
            'data' => [
                'phone_number' => $preference->phone_number,
                'country_code' => $preference->country_code,
                'full_phone_number' => $preference->full_phone_number,
                'whatsapp_enabled' => $preference->whatsapp_enabled,
                'sms_enabled' => $preference->sms_enabled,
                'preferred_channel' => $preference->preferred_channel,
                'verified' => $preference->verified,
                'verified_at' => $preference->verified_at?->toIso8601String(),
                'marketing_opt_in' => $preference->marketing_opt_in,
                'transactional_opt_in' => $preference->transactional_opt_in,
                'urgent_alerts_opt_in' => $preference->urgent_alerts_opt_in,
                'quiet_hours' => $preference->quiet_hours,
                'is_in_quiet_hours' => $preference->isInQuietHours(),
            ],
        ]);
    }

    /**
     * Update user's messaging preferences
     */
    public function updatePreferences(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone_number' => ['sometimes', 'string', 'max:20'],
            'country_code' => ['sometimes', 'string', 'max:5'],
            'sms_enabled' => ['sometimes', 'boolean'],
            'whatsapp_enabled' => ['sometimes', 'boolean'],
            'preferred_channel' => ['sometimes', Rule::in(['sms', 'whatsapp'])],
            'marketing_opt_in' => ['sometimes', 'boolean'],
            'transactional_opt_in' => ['sometimes', 'boolean'],
            'urgent_alerts_opt_in' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();
        $preference = UserPhonePreference::getOrCreateForUser($user);

        // Validate preferred channel is enabled
        if (isset($validated['preferred_channel'])) {
            $channel = $validated['preferred_channel'];

            if ($channel === 'whatsapp' && ! ($validated['whatsapp_enabled'] ?? $preference->whatsapp_enabled)) {
                return response()->json([
                    'message' => 'Cannot set WhatsApp as preferred when it is disabled',
                ], 422);
            }

            if ($channel === 'sms' && ! ($validated['sms_enabled'] ?? $preference->sms_enabled)) {
                return response()->json([
                    'message' => 'Cannot set SMS as preferred when it is disabled',
                ], 422);
            }
        }

        // Phone number change requires re-verification
        if (isset($validated['phone_number']) && $validated['phone_number'] !== $preference->phone_number) {
            $validated['verified'] = false;
            $validated['verified_at'] = null;
        }

        $preference->update($validated);

        Log::info('User messaging preferences updated', [
            'user_id' => $user->id,
            'changes' => array_keys($validated),
        ]);

        return response()->json([
            'message' => 'Preferences updated successfully',
            'data' => [
                'phone_number' => $preference->phone_number,
                'country_code' => $preference->country_code,
                'whatsapp_enabled' => $preference->whatsapp_enabled,
                'sms_enabled' => $preference->sms_enabled,
                'preferred_channel' => $preference->preferred_channel,
                'verified' => $preference->verified,
                'marketing_opt_in' => $preference->marketing_opt_in,
                'transactional_opt_in' => $preference->transactional_opt_in,
                'urgent_alerts_opt_in' => $preference->urgent_alerts_opt_in,
            ],
        ]);
    }

    /**
     * Set quiet hours
     */
    public function setQuietHours(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start' => ['required', 'string', 'date_format:H:i'],
            'end' => ['required', 'string', 'date_format:H:i'],
            'timezone' => ['sometimes', 'string', 'timezone'],
        ]);

        $user = $request->user();
        $preference = UserPhonePreference::getOrCreateForUser($user);

        $preference->setQuietHours(
            $validated['start'],
            $validated['end'],
            $validated['timezone'] ?? 'UTC'
        );

        return response()->json([
            'message' => 'Quiet hours set successfully',
            'data' => [
                'quiet_hours' => $preference->fresh()->quiet_hours,
                'is_in_quiet_hours' => $preference->fresh()->isInQuietHours(),
            ],
        ]);
    }

    /**
     * Clear quiet hours
     */
    public function clearQuietHours(Request $request): JsonResponse
    {
        $user = $request->user();
        $preference = UserPhonePreference::getOrCreateForUser($user);

        $preference->clearQuietHours();

        return response()->json([
            'message' => 'Quiet hours cleared',
        ]);
    }

    /**
     * Enable WhatsApp
     */
    public function enableWhatsApp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'set_as_preferred' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();
        $preference = UserPhonePreference::getOrCreateForUser($user);

        if (! $preference->verified) {
            return response()->json([
                'message' => 'Phone number must be verified before enabling WhatsApp',
            ], 422);
        }

        $preference->enableWhatsApp();

        if ($validated['set_as_preferred'] ?? false) {
            $preference->setPreferredChannel('whatsapp');
        }

        return response()->json([
            'message' => 'WhatsApp enabled successfully',
            'data' => [
                'whatsapp_enabled' => true,
                'preferred_channel' => $preference->fresh()->preferred_channel,
            ],
        ]);
    }

    /**
     * Disable WhatsApp
     */
    public function disableWhatsApp(Request $request): JsonResponse
    {
        $user = $request->user();
        $preference = UserPhonePreference::getOrCreateForUser($user);

        $preference->disableWhatsApp();

        return response()->json([
            'message' => 'WhatsApp disabled',
            'data' => [
                'whatsapp_enabled' => false,
                'preferred_channel' => $preference->fresh()->preferred_channel,
            ],
        ]);
    }

    /**
     * Get message history
     */
    public function getHistory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'channel' => ['sometimes', Rule::in(['sms', 'whatsapp'])],
            'type' => ['sometimes', Rule::in(['otp', 'shift_reminder', 'urgent_alert', 'marketing', 'transactional'])],
            'status' => ['sometimes', Rule::in(['pending', 'queued', 'sent', 'delivered', 'failed', 'read'])],
            'from_date' => ['sometimes', 'date'],
            'to_date' => ['sometimes', 'date'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $query = SmsLog::forUser($request->user()->id)
            ->orderBy('created_at', 'desc');

        if (isset($validated['channel'])) {
            $query->channel($validated['channel']);
        }

        if (isset($validated['type'])) {
            $query->ofType($validated['type']);
        }

        if (isset($validated['status'])) {
            $query->status($validated['status']);
        }

        if (isset($validated['from_date'])) {
            $query->where('created_at', '>=', $validated['from_date']);
        }

        if (isset($validated['to_date'])) {
            $query->where('created_at', '<=', $validated['to_date']);
        }

        $perPage = $validated['per_page'] ?? 20;
        $messages = $query->paginate($perPage);

        return response()->json([
            'data' => $messages->items(),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    /**
     * Get messaging statistics
     */
    public function getStats(Request $request): JsonResponse
    {
        $user = $request->user();
        $stats = $this->messagingService->getUserStats($user);

        return response()->json([
            'data' => $stats,
        ]);
    }

    /**
     * Send test message (development only)
     */
    public function sendTest(Request $request): JsonResponse
    {
        if (! app()->environment('local', 'development')) {
            return response()->json([
                'message' => 'Test messages only available in development',
            ], 403);
        }

        $validated = $request->validate([
            'channel' => ['required', Rule::in(['sms', 'whatsapp'])],
            'message' => ['required', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $preference = UserPhonePreference::getOrCreateForUser($user);

        if (! $preference->verified) {
            return response()->json([
                'message' => 'Phone number not verified',
            ], 422);
        }

        // Send test message
        if ($validated['channel'] === 'whatsapp') {
            // For WhatsApp, we'd need to use a template
            return response()->json([
                'message' => 'WhatsApp test requires using a template',
            ], 422);
        }

        $log = app(\App\Services\SmsService::class)->send(
            $preference->full_phone_number,
            $validated['message'],
            $user,
            SmsLog::TYPE_TRANSACTIONAL
        );

        return response()->json([
            'message' => 'Test message sent',
            'data' => [
                'log_id' => $log->id,
                'status' => $log->status,
                'phone' => $log->phone_number,
            ],
        ]);
    }
}
