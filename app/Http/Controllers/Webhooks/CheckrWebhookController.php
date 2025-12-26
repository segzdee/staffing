<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\BackgroundCheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * STAFF-REG-006: Checkr Webhook Controller
 *
 * Handles incoming webhooks from Checkr for background check status updates.
 * Verifies webhook signatures and processes events.
 */
class CheckrWebhookController extends Controller
{
    protected BackgroundCheckService $bgCheckService;

    public function __construct(BackgroundCheckService $bgCheckService)
    {
        $this->bgCheckService = $bgCheckService;
    }

    /**
     * Handle incoming Checkr webhook.
     *
     * POST /webhooks/checkr
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        // Verify webhook signature
        if (! $this->verifySignature($request)) {
            Log::warning('Checkr webhook signature verification failed', [
                'ip' => $request->ip(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payload = $request->all();
        $type = $payload['type'] ?? 'unknown';

        Log::info('Checkr webhook received', [
            'type' => $type,
            'id' => $payload['id'] ?? null,
        ]);

        try {
            $result = match ($type) {
                'report.created',
                'report.completed',
                'report.upgraded',
                'report.suspended' => $this->handleReportEvent($payload),

                'candidate.created' => $this->handleCandidateCreated($payload),
                'candidate.driver_license.state_changed' => $this->handleDriverLicenseChange($payload),

                'invitation.created',
                'invitation.completed',
                'invitation.expired' => $this->handleInvitationEvent($payload),

                'adverse_action.created',
                'adverse_action.completed' => $this->handleAdverseActionEvent($payload),

                'account.credentialed' => $this->handleAccountCredentialed($payload),

                default => ['success' => true, 'message' => 'Event type not handled'],
            };

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Checkr webhook processing failed', [
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return 200 to prevent webhook retries for processing errors
            // The error is logged and can be investigated manually
            return response()->json([
                'success' => false,
                'error' => 'Processing error logged',
            ]);
        }
    }

    /**
     * Handle report-related events.
     */
    protected function handleReportEvent(array $payload): array
    {
        return $this->bgCheckService->processCheckrWebhook($payload);
    }

    /**
     * Handle candidate created event.
     */
    protected function handleCandidateCreated(array $payload): array
    {
        // Candidate creation is initiated by us, so we mainly log this
        $candidateId = $payload['data']['object']['id'] ?? null;

        Log::info('Checkr candidate created', ['candidate_id' => $candidateId]);

        return ['success' => true, 'action' => 'logged'];
    }

    /**
     * Handle driver license state change event.
     */
    protected function handleDriverLicenseChange(array $payload): array
    {
        // This can trigger additional checks if needed
        $candidateId = $payload['data']['object']['id'] ?? null;
        $newState = $payload['data']['object']['driver_license_state'] ?? null;

        Log::info('Checkr driver license state changed', [
            'candidate_id' => $candidateId,
            'new_state' => $newState,
        ]);

        return ['success' => true, 'action' => 'logged'];
    }

    /**
     * Handle invitation events.
     */
    protected function handleInvitationEvent(array $payload): array
    {
        $type = $payload['type'];
        $invitationId = $payload['data']['object']['id'] ?? null;
        $status = $payload['data']['object']['status'] ?? null;

        Log::info('Checkr invitation event', [
            'type' => $type,
            'invitation_id' => $invitationId,
            'status' => $status,
        ]);

        if ($type === 'invitation.expired') {
            // Handle expired invitation - may need to notify user
            // This would typically update the background check status
        }

        return ['success' => true, 'action' => 'logged'];
    }

    /**
     * Handle adverse action events from Checkr.
     */
    protected function handleAdverseActionEvent(array $payload): array
    {
        $type = $payload['type'];
        $adverseActionId = $payload['data']['object']['id'] ?? null;
        $reportId = $payload['data']['object']['report_id'] ?? null;

        Log::info('Checkr adverse action event', [
            'type' => $type,
            'adverse_action_id' => $adverseActionId,
            'report_id' => $reportId,
        ]);

        // Adverse actions are typically initiated by us, but we log Checkr's confirmation
        return ['success' => true, 'action' => 'logged'];
    }

    /**
     * Handle account credentialed event.
     */
    protected function handleAccountCredentialed(array $payload): array
    {
        // Account setup completion
        Log::info('Checkr account credentialed', [
            'account_id' => $payload['data']['object']['id'] ?? null,
        ]);

        return ['success' => true, 'action' => 'logged'];
    }

    /**
     * Verify Checkr webhook signature.
     *
     * Checkr uses HMAC-SHA256 signatures for webhook verification.
     */
    protected function verifySignature(Request $request): bool
    {
        $secret = config('services.checkr.webhook_secret');

        // If no secret configured, allow in development
        if (! $secret) {
            if (app()->environment('local', 'development', 'testing')) {
                Log::warning('Checkr webhook secret not configured, skipping verification in dev mode');

                return true;
            }

            return false;
        }

        $signature = $request->header('X-Checkr-Signature');

        if (! $signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Handle check completed - processes both clear and consider results.
     *
     * POST /webhooks/checkr/check-complete
     * (Alternative endpoint for custom handling)
     */
    public function handleCheckComplete(Request $request): JsonResponse
    {
        $payload = $request->all();

        // Verify this is a report completion event
        if (($payload['type'] ?? '') !== 'report.completed') {
            return response()->json(['error' => 'Invalid event type'], 400);
        }

        return response()->json(
            $this->bgCheckService->processCheckrWebhook($payload)
        );
    }

    /**
     * Handle report completed - specifically for report completion.
     *
     * POST /webhooks/checkr/report-complete
     * (Alternative endpoint for specific handling)
     */
    public function handleReportComplete(Request $request): JsonResponse
    {
        $payload = $request->all();

        // Verify this is a report-related event
        $type = $payload['type'] ?? '';
        if (! str_starts_with($type, 'report.')) {
            return response()->json(['error' => 'Invalid event type'], 400);
        }

        return response()->json(
            $this->bgCheckService->processCheckrWebhook($payload)
        );
    }
}
