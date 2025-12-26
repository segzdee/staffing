<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Notifications\AdminAlertNotification;
use App\Notifications\StripeConnectRequiredNotification;
use App\Notifications\StripePayoutFailedNotification;
use App\Notifications\StripePayoutSuccessNotification;
use App\Services\StripeConnectService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;

/**
 * StripeConnectWebhookController
 *
 * Handles Stripe Connect webhooks for agency payouts.
 *
 * TASK: AGY-003 - Stripe Connect Integration for Agency Payouts
 *
 * Webhooks handled:
 * - account.updated - Account status changes
 * - payout.paid - Payout successfully sent
 * - payout.failed - Payout failed
 * - transfer.created - Transfer created (agency commission)
 * - transfer.failed - Transfer failed
 * - capability.updated - Account capability changes
 *
 * Security:
 * - Webhook signature validation required
 * - Raw request body used for signature verification
 * - Logs all webhook events
 */
class StripeConnectWebhookController extends Controller
{
    protected StripeConnectService $stripeConnect;

    public function __construct(StripeConnectService $stripeConnect)
    {
        $this->stripeConnect = $stripeConnect;
    }

    /**
     * Handle incoming Stripe Connect webhook.
     *
     * POST /webhooks/stripe/connect
     */
    public function handle(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        // Verify webhook signature
        $event = $this->stripeConnect->verifyWebhookSignature($payload, $signature);

        if (! $event) {
            Log::warning('Stripe Connect webhook signature verification failed');

            return response('Invalid signature', 400);
        }

        $eventId = $event->id;
        $eventType = $event->type;

        // PRIORITY-0: Idempotency check
        $idempotencyService = app(\App\Services\WebhookIdempotencyService::class);
        $shouldProcess = $idempotencyService->shouldProcess('stripe', $eventId);

        if (! $shouldProcess['should_process']) {
            Log::info('Stripe Connect webhook event already processed, skipping', [
                'event_id' => $eventId,
                'event_type' => $eventType,
                'message' => $shouldProcess['message'],
            ]);

            return response('Event already processed', 200);
        }

        // Record event for idempotency
        $webhookEvent = $idempotencyService->recordEvent('stripe', $eventId, $eventType, $payload);

        // Mark as processing
        if (! $idempotencyService->markProcessing($webhookEvent)) {
            return response('Event already processing', 200);
        }

        Log::info('Stripe Connect webhook received', [
            'event_type' => $eventType,
            'event_id' => $eventId,
        ]);

        // Route to appropriate handler
        $methodName = $this->getHandlerMethod($event->type);

        if (method_exists($this, $methodName)) {
            try {
                $result = $this->$methodName($event);

                // PRIORITY-0: Mark as processed successfully
                $idempotencyService->markProcessed($webhookEvent, ['success' => true]);

                return $result;
            } catch (\Exception $e) {
                Log::error('Stripe Connect webhook handler error', [
                    'event_type' => $event->type,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // PRIORITY-0: Mark as failed
                $idempotencyService->markFailed($webhookEvent, $e->getMessage(), true);

                return response('Webhook handler error', 500);
            }
        }

        // Log unhandled event types
        Log::info('Unhandled Stripe Connect webhook event', [
            'event_type' => $event->type,
        ]);

        // PRIORITY-0: Mark as processed (unhandled but acknowledged)
        $idempotencyService->markProcessed($webhookEvent, ['unhandled' => true]);

        return response('Webhook received', 200);
    }

    /**
     * Handle account.updated webhook.
     *
     * Fired when a Connect account's status changes.
     */
    protected function handleAccountUpdated(\Stripe\Event $event): Response
    {
        $account = $event->data->object;
        $accountId = $account->id;

        $agency = $this->stripeConnect->findAgencyByStripeAccountId($accountId);

        if (! $agency) {
            Log::warning('Stripe Connect account not found for webhook', [
                'stripe_account_id' => $accountId,
            ]);

            return response('Account not found', 200);
        }

        // Update agency account status
        $agency->updateStripeAccountStatus([
            'charges_enabled' => $account->charges_enabled ?? false,
            'payouts_enabled' => $account->payouts_enabled ?? false,
            'details_submitted' => $account->details_submitted ?? false,
            'requirements' => $account->requirements ?? null,
        ]);

        // Check if onboarding is now complete
        if ($account->details_submitted && $account->payouts_enabled && ! $agency->stripe_onboarding_complete) {
            $agency->markStripeOnboardingComplete();

            Log::info('Agency completed Stripe Connect onboarding', [
                'agency_id' => $agency->id,
            ]);

            // Could send a notification here if needed
        }

        // Check for new requirements (may need additional info)
        if (! empty($account->requirements?->currently_due)) {
            Log::info('Agency has pending Stripe requirements', [
                'agency_id' => $agency->id,
                'requirements' => $account->requirements->currently_due,
            ]);

            // Notify agency about pending requirements
            try {
                if ($agency->user) {
                    $agency->user->notify(new StripeConnectRequiredNotification(
                        $agency,
                        $account->requirements->currently_due
                    ));
                }
            } catch (\Exception $e) {
                Log::warning('Failed to send Stripe requirements notification', [
                    'agency_id' => $agency->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response('Account updated', 200);
    }

    /**
     * Handle payout.paid webhook.
     *
     * Fired when a payout to a connected account's bank is successful.
     */
    protected function handlePayoutPaid(\Stripe\Event $event): Response
    {
        $payout = $event->data->object;

        // Get the connected account ID
        $accountId = $event->account ?? null;

        if (! $accountId) {
            Log::warning('No account ID in payout.paid webhook');

            return response('No account', 200);
        }

        $agency = $this->stripeConnect->findAgencyByStripeAccountId($accountId);

        if (! $agency) {
            Log::info('Payout for non-agency Connect account', [
                'stripe_account_id' => $accountId,
            ]);

            return response('Account not tracked', 200);
        }

        $amount = ($payout->amount ?? 0) / 100;
        $currency = strtoupper($payout->currency ?? 'USD');

        Log::info('Agency payout completed', [
            'agency_id' => $agency->id,
            'payout_id' => $payout->id,
            'amount' => $amount,
            'currency' => $currency,
        ]);

        // Send success notification
        try {
            if ($agency->user) {
                $agency->user->notify(new StripePayoutSuccessNotification(
                    $agency,
                    $amount,
                    $currency,
                    $payout->id
                ));
            }
        } catch (\Exception $e) {
            Log::warning('Failed to send payout success notification', [
                'agency_id' => $agency->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response('Payout recorded', 200);
    }

    /**
     * Handle payout.failed webhook.
     *
     * Fired when a payout to a connected account fails.
     */
    protected function handlePayoutFailed(\Stripe\Event $event): Response
    {
        $payout = $event->data->object;
        $accountId = $event->account ?? null;

        if (! $accountId) {
            return response('No account', 200);
        }

        $agency = $this->stripeConnect->findAgencyByStripeAccountId($accountId);

        if (! $agency) {
            return response('Account not tracked', 200);
        }

        $amount = ($payout->amount ?? 0) / 100;
        $failureCode = $payout->failure_code ?? 'unknown';
        $failureMessage = $payout->failure_message ?? 'Payout failed';

        Log::warning('Agency payout failed', [
            'agency_id' => $agency->id,
            'payout_id' => $payout->id,
            'amount' => $amount,
            'failure_code' => $failureCode,
            'failure_message' => $failureMessage,
        ]);

        // Record failed payout
        $agency->recordPayoutFailure();

        // Send failure notification
        try {
            if ($agency->user) {
                $agency->user->notify(new StripePayoutFailedNotification(
                    $agency,
                    $amount,
                    $failureCode,
                    $failureMessage
                ));
            }
        } catch (\Exception $e) {
            Log::warning('Failed to send payout failure notification', [
                'agency_id' => $agency->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response('Payout failure recorded', 200);
    }

    /**
     * Handle transfer.created webhook.
     *
     * Fired when a transfer to a connected account is created.
     */
    protected function handleTransferCreated(\Stripe\Event $event): Response
    {
        $transfer = $event->data->object;
        $destinationAccountId = $transfer->destination ?? null;

        if (! $destinationAccountId) {
            return response('No destination', 200);
        }

        $agency = $this->stripeConnect->findAgencyByStripeAccountId($destinationAccountId);

        if (! $agency) {
            return response('Account not tracked', 200);
        }

        $amount = ($transfer->amount ?? 0) / 100;
        $currency = strtoupper($transfer->currency ?? 'USD');

        Log::info('Transfer created to agency', [
            'agency_id' => $agency->id,
            'transfer_id' => $transfer->id,
            'amount' => $amount,
            'currency' => $currency,
        ]);

        return response('Transfer recorded', 200);
    }

    /**
     * Handle transfer.failed webhook.
     *
     * Fired when a transfer to a connected account fails.
     */
    protected function handleTransferFailed(\Stripe\Event $event): Response
    {
        $transfer = $event->data->object;
        $destinationAccountId = $transfer->destination ?? null;

        if (! $destinationAccountId) {
            return response('No destination', 200);
        }

        $agency = $this->stripeConnect->findAgencyByStripeAccountId($destinationAccountId);

        if (! $agency) {
            return response('Account not tracked', 200);
        }

        Log::error('Transfer failed to agency', [
            'agency_id' => $agency->id,
            'transfer_id' => $transfer->id,
            'amount' => ($transfer->amount ?? 0) / 100,
        ]);

        // Record failure
        $agency->recordPayoutFailure();

        // Notify admin of critical failure
        AdminAlertNotification::send(
            title: 'Stripe Transfer Failed to Agency',
            message: sprintf(
                'A Stripe transfer of %s to agency ID %d has failed. The agency payout was not completed.',
                number_format(($transfer->amount ?? 0) / 100, 2),
                $agency->id
            ),
            severity: AdminAlertNotification::SEVERITY_CRITICAL,
            context: [
                'agency_id' => $agency->id,
                'agency_name' => $agency->company_name ?? 'Unknown',
                'stripe_account_id' => $destinationAccountId,
                'transfer_id' => $transfer->id,
                'amount' => ($transfer->amount ?? 0) / 100,
                'currency' => strtoupper($transfer->currency ?? 'USD'),
            ],
            actionUrl: '/panel/admin/agency-payouts',
            actionLabel: 'View Agency Payouts',
            category: 'stripe_failure'
        );

        return response('Transfer failure recorded', 200);
    }

    /**
     * Handle capability.updated webhook.
     *
     * Fired when an account capability changes status.
     */
    protected function handleCapabilityUpdated(\Stripe\Event $event): Response
    {
        $capability = $event->data->object;
        $accountId = $capability->account ?? null;

        if (! $accountId) {
            return response('No account', 200);
        }

        $agency = $this->stripeConnect->findAgencyByStripeAccountId($accountId);

        if (! $agency) {
            return response('Account not tracked', 200);
        }

        Log::info('Capability updated for agency', [
            'agency_id' => $agency->id,
            'capability' => $capability->id,
            'status' => $capability->status,
        ]);

        // Refresh account status to pick up capability changes
        $this->stripeConnect->verifyAccountStatus($agency);

        return response('Capability recorded', 200);
    }

    /**
     * Convert webhook event type to handler method name.
     */
    protected function getHandlerMethod(string $eventType): string
    {
        // Convert "account.updated" to "handleAccountUpdated"
        $parts = explode('.', $eventType);
        $methodName = 'handle';

        foreach ($parts as $part) {
            $methodName .= ucfirst($part);
        }

        return $methodName;
    }
}
