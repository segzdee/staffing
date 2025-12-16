<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\IdentityVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Onfido Webhook Controller - STAFF-REG-004
 *
 * Handles webhook callbacks from Onfido for verification results.
 */
class OnfidoWebhookController extends Controller
{
    protected IdentityVerificationService $verificationService;

    public function __construct(IdentityVerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
    }

    /**
     * Handle Onfido webhook.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('X-SHA2-Signature', '');

        // Log incoming webhook
        Log::info('Onfido webhook received', [
            'has_signature' => !empty($signature),
            'payload_preview' => substr($payload, 0, 200),
        ]);

        // Verify signature
        if (!$this->verificationService->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Invalid Onfido webhook signature');

            return response()->json([
                'success' => false,
                'message' => 'Invalid signature.',
            ], 401);
        }

        try {
            $data = json_decode($payload, true);

            if (!$data) {
                Log::warning('Invalid JSON payload in Onfido webhook');
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payload.',
                ], 400);
            }

            // Process the webhook
            $result = $this->verificationService->processWebhook($data);

            if ($result['success']) {
                Log::info('Onfido webhook processed successfully', [
                    'message' => $result['message'] ?? 'Processed',
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $result['message'] ?? 'Webhook processed.',
                ]);
            }

            Log::warning('Onfido webhook processing failed', [
                'error' => $result['error'] ?? 'Unknown error',
            ]);

            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Processing failed.',
            ], 422);
        } catch (\Exception $e) {
            Log::error('Onfido webhook exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return 200 to prevent Onfido from retrying
            // Log the error for investigation
            return response()->json([
                'success' => false,
                'message' => 'Internal error.',
            ]);
        }
    }

    /**
     * Handle check.completed webhook.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handleCheckCompleted(Request $request): JsonResponse
    {
        return $this->handle($request);
    }

    /**
     * Handle report.completed webhook.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handleReportCompleted(Request $request): JsonResponse
    {
        return $this->handle($request);
    }

    /**
     * Health check endpoint for webhook verification.
     *
     * @return JsonResponse
     */
    public function healthCheck(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'onfido-webhook',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
