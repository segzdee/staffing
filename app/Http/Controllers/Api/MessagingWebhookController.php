<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SmsService;
use App\Services\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * COM-004: Messaging Webhook Controller
 *
 * Handles incoming webhooks from SMS and WhatsApp providers
 * for delivery status updates and incoming messages.
 */
class MessagingWebhookController extends Controller
{
    protected WhatsAppService $whatsAppService;

    protected SmsService $smsService;

    public function __construct(WhatsAppService $whatsAppService, SmsService $smsService)
    {
        $this->whatsAppService = $whatsAppService;
        $this->smsService = $smsService;
    }

    /**
     * Handle WhatsApp webhook verification (GET request)
     *
     * Meta sends a GET request to verify the webhook URL during setup.
     */
    public function verifyWhatsApp(Request $request): Response
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if (! $mode || ! $token || ! $challenge) {
            Log::warning('WhatsApp webhook verification missing parameters');

            return response('Bad Request', 400);
        }

        $response = $this->whatsAppService->verifyWebhookChallenge($mode, $token, $challenge);

        if ($response) {
            Log::info('WhatsApp webhook verified successfully');

            return response($response, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('WhatsApp webhook verification failed', [
            'mode' => $mode,
            'expected_token' => config('whatsapp.meta.verify_token'),
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Handle WhatsApp webhook events (POST request)
     *
     * Receives message status updates and incoming messages from Meta.
     */
    public function handleWhatsApp(Request $request): JsonResponse
    {
        // Verify signature for security
        $signature = $request->header('X-Hub-Signature-256');
        $payload = $request->getContent();

        if ($signature && ! $this->whatsAppService->verifyWebhookSignature($payload, $signature)) {
            Log::warning('WhatsApp webhook signature verification failed');

            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $data = $request->all();

        // Log webhook if enabled
        if (config('whatsapp.logging.log_webhooks')) {
            Log::debug('WhatsApp webhook received', ['data' => $data]);
        }

        try {
            $this->whatsAppService->handleWebhook($data);

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('WhatsApp webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return 200 to prevent Meta from retrying
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Handle Twilio SMS status callback
     *
     * Twilio sends POST requests when message status changes.
     */
    public function handleTwilioStatus(Request $request): Response
    {
        // Validate Twilio request signature (optional but recommended)
        if (! $this->validateTwilioRequest($request)) {
            Log::warning('Invalid Twilio webhook request');

            return response('Forbidden', 403);
        }

        $payload = $request->all();

        Log::debug('Twilio status webhook', [
            'message_sid' => $payload['MessageSid'] ?? null,
            'status' => $payload['MessageStatus'] ?? null,
        ]);

        try {
            $this->smsService->handleDeliveryWebhook('twilio', $payload);

            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('Twilio webhook error', ['error' => $e->getMessage()]);

            return response('OK', 200); // Return 200 to prevent retries
        }
    }

    /**
     * Handle Twilio WhatsApp status callback
     */
    public function handleTwilioWhatsApp(Request $request): Response
    {
        if (! $this->validateTwilioRequest($request)) {
            return response('Forbidden', 403);
        }

        $payload = $request->all();

        Log::debug('Twilio WhatsApp webhook', [
            'message_sid' => $payload['MessageSid'] ?? null,
            'status' => $payload['MessageStatus'] ?? null,
        ]);

        try {
            // Map Twilio status to our status handling
            $this->smsService->handleDeliveryWebhook('twilio', $payload);

            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('Twilio WhatsApp webhook error', ['error' => $e->getMessage()]);

            return response('OK', 200);
        }
    }

    /**
     * Handle Vonage (Nexmo) delivery receipt
     */
    public function handleVonageStatus(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::debug('Vonage DLR webhook', [
            'message_id' => $payload['messageId'] ?? null,
            'status' => $payload['status'] ?? null,
        ]);

        try {
            $this->smsService->handleDeliveryWebhook('vonage', $payload);

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('Vonage webhook error', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'ok']);
        }
    }

    /**
     * Handle MessageBird status webhook
     */
    public function handleMessageBirdStatus(Request $request): JsonResponse
    {
        // Verify MessageBird signature
        $signature = $request->header('MessageBird-Signature');
        if ($signature && ! $this->validateMessageBirdSignature($request, $signature)) {
            Log::warning('Invalid MessageBird webhook signature');

            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $payload = $request->all();

        Log::debug('MessageBird webhook', [
            'id' => $payload['id'] ?? null,
            'status' => $payload['status'] ?? null,
        ]);

        try {
            $this->smsService->handleDeliveryWebhook('messagebird', $payload);

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('MessageBird webhook error', ['error' => $e->getMessage()]);

            return response()->json(['status' => 'ok']);
        }
    }

    /**
     * Handle incoming SMS (for two-way messaging)
     */
    public function handleIncomingSms(Request $request): Response
    {
        $provider = $request->route('provider', 'twilio');
        $payload = $request->all();

        Log::info('Incoming SMS received', [
            'provider' => $provider,
            'from' => $payload['From'] ?? $payload['from'] ?? 'unknown',
        ]);

        // Could dispatch an event or queue job here for processing
        // For now, just acknowledge receipt

        return response('OK', 200);
    }

    /**
     * Validate Twilio request signature
     */
    protected function validateTwilioRequest(Request $request): bool
    {
        $authToken = config('services.twilio.token');

        if (! $authToken) {
            // If no auth token configured, skip validation (development mode)
            return true;
        }

        $signature = $request->header('X-Twilio-Signature');

        if (! $signature) {
            return false;
        }

        $url = $request->fullUrl();
        $params = $request->all();

        // Sort parameters and build string
        ksort($params);
        $data = $url;
        foreach ($params as $key => $value) {
            $data .= $key.$value;
        }

        $expected = base64_encode(hash_hmac('sha1', $data, $authToken, true));

        return hash_equals($expected, $signature);
    }

    /**
     * Validate MessageBird webhook signature
     */
    protected function validateMessageBirdSignature(Request $request, string $signature): bool
    {
        $signingKey = config('services.messagebird.signing_key');

        if (! $signingKey) {
            return true; // Skip if not configured
        }

        $timestamp = $request->header('MessageBird-Request-Timestamp');
        $body = $request->getContent();

        $payload = $timestamp.'.'.$body;
        $expected = hash_hmac('sha256', $payload, $signingKey);

        return hash_equals($expected, $signature);
    }
}
