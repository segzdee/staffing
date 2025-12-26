<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Traits\Functions;
use App\Models\AdminSettings;
use App\Models\Deposits;
use App\Models\Notifications;
use App\Models\PaymentGateways;
use App\Models\Plans;
use App\Models\ShiftPayment;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Laravel\Cashier\Subscription;

class StripeWebHookController extends WebhookController
{
    use Functions;

    /**
     * customer.subscription.deleted
     *
     * @return Response|\Symfony\Component\HttpFoundation\Response
     */
    public function handleCustomerSubscriptionDeleted(array $payload)
    {
        $user = $this->getUserByStripeId($payload['data']['object']['customer']);
        if ($user) {
            $user->subscriptions->filter(function ($subscription) use ($payload) {
                return $subscription->stripe_id === $payload['data']['object']['id'];
            })->each(function ($subscription) {
                $subscription->markAsCancelled();
            });
        }

        return new Response('Webhook Handled', 200);
    }

    /**
     * WEBHOOK Insert the information of each payment in the Payments table when successfully generating an invoice in Stripe
     *
     * @param  array  $payload
     * @return Response|\Symfony\Component\HttpFoundation\Response
     */
    public function handleInvoicePaymentSucceeded($payload)
    {
        try {
            $settings = AdminSettings::first();
            $data = $payload['data'];
            $object = $data['object'];
            $customer = $object['customer'];
            $amount = $settings->currency_code == 'JPY' ? $object['subtotal'] : ($object['subtotal'] / 100);
            $user = $this->getUserByStripeId($customer);
            $interval = $object['lines']['data'][0]['metadata']['interval'] ?? 'monthly';
            $taxes = $object['lines']['data'][0]['metadata']['taxes'] ?? null;

            if ($user) {
                $subscription = Subscription::whereStripeId($object['subscription'])->first();
                if ($subscription) {
                    $subscription->stripe_status = 'active';
                    $subscription->interval = $interval;
                    $subscription->save();

                    // User Plan
                    $user = Plans::whereName($subscription->stripe_price)->first();

                    // Get Payment Gateway
                    $payment = PaymentGateways::whereName('Stripe')->firstOrFail();

                    // Admin and user earnings calculation
                    $earnings = $this->earningsAdminUser($user->user()->custom_fee, $amount, $payment->fee, $payment->fee_cents);

                    // Insert Transaction
                    $this->transaction(
                        $object['id'],
                        $subscription->user_id,
                        $subscription->id,
                        $user->user()->id,
                        $amount,
                        $earnings['user'],
                        $earnings['admin'],
                        'Stripe', 'subscription',
                        $earnings['percentageApplied'],
                        $taxes ?? null
                    );

                    // Add Earnings to User
                    $user->user()->increment('balance', $earnings['user']);

                    // Send Notification to user
                    if ($object['billing_reason'] == 'subscription_cycle') {
                        // Notify to user - destination, author, type, target
                        Notifications::send($user->user()->id, $subscription->user_id, 12, $subscription->user_id);
                    }
                }

                return new Response('Webhook Handled: {handleInvoicePaymentSucceeded}', 200);
            }

            return new Response('Webhook Handled but user not found: {handleInvoicePaymentSucceeded}', 200);
        } catch (\Exception $exception) {
            Log::debug($exception->getMessage());

            return new Response('Webhook Unhandled: {handleInvoicePaymentSucceeded}', $exception->getCode());
        }
    }

    /**
     * checkout.session.completed
     *
     * @param  array  $payload
     * @return Response|\Symfony\Component\HttpFoundation\Response
     */
    public function handleCheckoutSessionCompleted($payload)
    {
        try {
            $settings = AdminSettings::first();
            $data = $payload['data'];
            $object = $data['object'];
            $user = $object['metadata']['user'] ?? null;
            $amount = $object['metadata']['amount'] ?? null;
            $taxes = $object['metadata']['taxes'] ?? null;
            $type = $object['metadata']['type'] ?? null;

            if (! isset($type)) {
                return new Response('Webhook Handled with error: type transaction not defined', 500);
            }

            // Add funds (Deposit)
            if (isset($type) && $type == 'deposit') {
                if ($object['payment_status'] == 'paid' && isset($user)) {
                    $amount_total = $object['amount_total'] / 100;

                    if (isset($amount) && $amount_total >= $amount) {

                        // Check transaction
                        $verifiedTxnId = Deposits::where('txn_id', $object['payment_intent'])->first();

                        if (! $verifiedTxnId) {
                            // Insert Deposit
                            $this->deposit($user, $object['payment_intent'], $amount, 'Stripe', $taxes);

                            // Add Funds to User
                            User::find($user)->increment('wallet', $amount);
                        }
                    }
                }
            }

            return new Response('Webhook Handled: {handleInvoicePaymentSucceeded}', 200);

        } catch (\Exception $exception) {
            Log::debug($exception->getMessage());

            return new Response('Webhook Unhandled: {handleInvoicePaymentSucceeded}', $exception->getCode());
        }
    }

    /**
     * charge.refunded
     * SECURITY: Only cancel subscription if charge belongs to subscription invoice
     *
     * @param  array  $payload
     * @return Response|\Symfony\Component\HttpFoundation\Response
     */
    public function handleChargeRefunded($payload)
    {
        try {
            $charge = $payload['data']['object'];
            $subscriptionId = $charge['subscription'] ?? null;

            // SECURITY: Verify charge belongs to a subscription before canceling
            if (! $subscriptionId) {
                Log::info('charge.refunded: No subscription ID in payload, skipping cancellation', [
                    'charge_id' => $charge['id'] ?? null,
                ]);

                return new Response('Webhook Handled: {handleChargeRefunded} (no subscription)', 200);
            }

            // SECURITY: Verify the charge is actually from a subscription invoice
            // A refund doesn't always mean "cancel subscription" (partial refunds, dispute reversals, etc.)
            $invoiceId = $charge['invoice'] ?? null;
            if ($invoiceId) {
                $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
                try {
                    $invoice = $stripe->invoices->retrieve($invoiceId);
                    // Only cancel if this is a subscription invoice and business logic requires it
                    if ($invoice->subscription && $invoice->billing_reason === 'subscription_cycle') {
                        // PRIORITY-0: Add business logic check here - should we cancel on refund?
                        // For now, log and don't auto-cancel (requires explicit business rule)
                        Log::warning('charge.refunded: Subscription refund detected but auto-cancel disabled', [
                            'charge_id' => $charge['id'],
                            'subscription_id' => $subscriptionId,
                            'invoice_id' => $invoiceId,
                        ]);
                        // $stripe->subscriptions->cancel($subscriptionId, []);
                    }
                } catch (\Exception $e) {
                    Log::error('charge.refunded: Failed to verify invoice', [
                        'error' => $e->getMessage(),
                        'invoice_id' => $invoiceId,
                    ]);
                }
            }

            return new Response('Webhook Handled: {handleChargeRefunded}', 200);

        } catch (\Exception $exception) {
            Log::error('Exception Webhook {handleChargeRefunded}: '.$exception->getMessage().', Line: '.$exception->getLine().', File: '.$exception->getFile());

            return new Response('Webhook Handled with error: {handleChargeRefunded}', 400);
        }
    }

    /**
     * WEBHOOK Manage the SCA by notifying the user by email
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleInvoicePaymentActionRequired(array $payload)
    {
        $subscription = Subscription::whereStripeId($payload['data']['object']['subscription'])->first();
        if ($subscription) {
            $subscription->stripe_status = 'incomplete';
            $subscription->last_payment = $payload['data']['object']['payment_intent'];
            $subscription->save();
        }

        if (is_null($notification = config('cashier.payment_notification'))) {
            return $this->successMethod();
        }

        if ($user = $this->getUserByStripeId($payload['data']['object']['customer'])) {
            if (in_array(Notifiable::class, class_uses_recursive($user))) {
                $payment = new \Laravel\Cashier\Payment(Cashier::stripe()->paymentIntents->retrieve(
                    $payload['data']['object']['payment_intent']
                ));

                $user->notify(new $notification($payment));
            }
        }

        return $this->successMethod();
    }

    /**
     * Handle shift payment - payment_intent.succeeded
     * Webhook when escrow payment is successfully captured
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handlePaymentIntentSucceeded(array $payload)
    {
        try {
            $paymentIntent = $payload['data']['object'];
            $paymentIntentId = $paymentIntent['id'];

            // PRIORITY-0: Idempotency check
            $idempotencyService = app(\App\Services\WebhookIdempotencyService::class);
            $shouldProcess = $idempotencyService->shouldProcess('stripe', $paymentIntentId);

            if (! $shouldProcess['should_process']) {
                Log::info('payment_intent.succeeded already processed', [
                    'payment_intent_id' => $paymentIntentId,
                ]);

                return new Response('Event already processed', 200);
            }

            // Record event for idempotency
            $webhookEvent = $idempotencyService->recordEvent('stripe', $paymentIntentId, 'payment_intent.succeeded', $payload);
            $idempotencyService->markProcessing($webhookEvent);

            // PRIORITY-0 FIX: Check for both 'shift_payment' and 'shift_escrow' metadata types
            // EscrowService creates payment intents with type='shift_escrow'
            $metadata = $paymentIntent['metadata'] ?? [];
            $isShiftPayment = isset($metadata['type']) &&
                              in_array($metadata['type'], ['shift_payment', 'shift_escrow']);

            if ($isShiftPayment) {
                // Try to find by shift_payment_id in metadata first
                $shiftPaymentId = $metadata['shift_payment_id'] ?? null;

                // If not found, try to find by payment_intent_id
                if (! $shiftPaymentId) {
                    $shiftPayment = ShiftPayment::where('payment_intent_id', $paymentIntentId)->first();
                } else {
                    $shiftPayment = ShiftPayment::find($shiftPaymentId);
                }

                if ($shiftPayment) {
                    // PRIORITY-0: Route to escrow confirmation if status is PENDING
                    // SECURITY: Use standard EscrowRecord status constants (migrated from PENDING_CAPTURE)
                    if ($shiftPayment->status === \App\Models\EscrowRecord::STATUS_PENDING || $shiftPayment->status === 'PENDING_CAPTURE') {
                        $escrowService = app(\App\Services\EscrowService::class);
                        $confirmed = $escrowService->confirmEscrowCapture($shiftPayment);

                        if ($confirmed) {
                            Log::info('Escrow confirmed via payment_intent.succeeded webhook', [
                                'payment_id' => $shiftPayment->id,
                                'payment_intent_id' => $paymentIntentId,
                            ]);

                            $idempotencyService->markProcessed($webhookEvent, ['escrow_confirmed' => true]);

                            return new Response('Escrow confirmed', 200);
                        }
                    } elseif ($shiftPayment->status === 'pending_escrow') {
                        // Legacy status handling - migrate to standard status
                        // SECURITY: Standardize to EscrowRecord constants
                        $shiftPayment->update([
                            'status' => \App\Models\EscrowRecord::STATUS_HELD, // Use standard constant
                            'escrow_held_at' => now(),
                            'stripe_payment_intent' => $paymentIntentId,
                        ]);

                        // Update escrow record if exists
                        if ($shiftPayment->escrowRecord) {
                            $shiftPayment->escrowRecord->update([
                                'status' => \App\Models\EscrowRecord::STATUS_HELD,
                                'captured_at' => now(),
                            ]);
                        }

                        Log::info("Shift payment {$shiftPayment->id} successfully held in escrow (legacy status migrated)");
                        $idempotencyService->markProcessed($webhookEvent, ['escrow_held' => true]);

                        return new Response('Escrow held', 200);
                    }
                } else {
                    Log::warning('Shift payment not found for payment_intent.succeeded', [
                        'payment_intent_id' => $paymentIntentId,
                        'metadata' => $metadata,
                    ]);
                }
            }

            // Mark as processed (may not be a shift payment)
            $idempotencyService->markProcessed($webhookEvent, ['processed' => true, 'is_shift_payment' => $isShiftPayment]);

            return new Response('Webhook Handled', 200);

        } catch (\Exception $exception) {
            Log::error('Webhook error handlePaymentIntentSucceeded', [
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            // PRIORITY-0: Mark as failed
            if (isset($webhookEvent)) {
                $idempotencyService->markFailed($webhookEvent, $exception->getMessage(), true);
            }

            return new Response('Webhook Error', 500);
        }
    }

    /**
     * Handle shift payment - payment_intent.payment_failed
     * Webhook when business payment fails
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handlePaymentIntentPaymentFailed(array $payload)
    {
        try {
            $paymentIntent = $payload['data']['object'];
            $metadata = $paymentIntent['metadata'] ?? [];

            if (isset($metadata['type']) && $metadata['type'] === 'shift_payment') {
                $shiftPaymentId = $metadata['shift_payment_id'] ?? null;

                if ($shiftPaymentId) {
                    $shiftPayment = ShiftPayment::find($shiftPaymentId);

                    if ($shiftPayment) {
                        $shiftPayment->update([
                            'status' => 'failed',
                            'error_message' => $paymentIntent['last_payment_error']['message'] ?? 'Payment failed',
                        ]);

                        // Update assignment status
                        if ($shiftPayment->assignment) {
                            $shiftPayment->assignment->update(['status' => 'payment_failed']);
                        }

                        Log::warning("Shift payment {$shiftPaymentId} failed: ".$paymentIntent['last_payment_error']['message'] ?? '');
                    }
                }
            }

            return new Response('Webhook Handled: {handlePaymentIntentPaymentFailed}', 200);

        } catch (\Exception $exception) {
            Log::error('Webhook error handlePaymentIntentPaymentFailed: '.$exception->getMessage());

            return new Response('Webhook Error: {handlePaymentIntentPaymentFailed}', 500);
        }
    }

    /**
     * Handle shift payout - transfer.created
     * Webhook when instant payout is initiated
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleTransferCreated(array $payload)
    {
        try {
            $transfer = $payload['data']['object'];
            $transferId = $transfer['id'];
            $metadata = $transfer['metadata'] ?? [];

            // PRIORITY-0: Idempotency check for transfer events
            $idempotencyService = app(\App\Services\WebhookIdempotencyService::class);
            $shouldProcess = $idempotencyService->shouldProcess('stripe', $transferId);

            if (! $shouldProcess['should_process']) {
                Log::info('transfer.created already processed', [
                    'transfer_id' => $transferId,
                ]);

                return new Response('Event already processed', 200);
            }

            // Record event for idempotency
            $webhookEvent = $idempotencyService->recordEvent('stripe', $transferId, 'transfer.created', $payload);
            $idempotencyService->markProcessing($webhookEvent);

            if (isset($metadata['type']) && $metadata['type'] === 'shift_payout') {
                $shiftPaymentId = $metadata['shift_payment_id'] ?? null;

                if ($shiftPaymentId) {
                    $shiftPayment = ShiftPayment::find($shiftPaymentId);

                    if ($shiftPayment) {
                        $shiftPayment->update([
                            'stripe_transfer_id' => $transferId,
                        ]);

                        Log::info("Transfer created for shift payment {$shiftPaymentId}");
                        $idempotencyService->markProcessed($webhookEvent, ['transfer_created' => true]);
                    }
                }
            }

            // Mark as processed even if not a shift payout
            if (! isset($webhookEvent->status) || $webhookEvent->status === 'processing') {
                $idempotencyService->markProcessed($webhookEvent, ['processed' => true]);
            }

            return new Response('Webhook Handled: {handleTransferCreated}', 200);

        } catch (\Exception $exception) {
            Log::error('Webhook error handleTransferCreated: '.$exception->getMessage());

            // Mark as failed if webhook event exists
            if (isset($webhookEvent)) {
                $idempotencyService->markFailed($webhookEvent, $exception->getMessage(), true);
            }

            return new Response('Webhook Error: {handleTransferCreated}', 500);
        }
    }

    /**
     * Handle shift payout - transfer.paid
     * Webhook when worker receives instant payout
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleTransferPaid(array $payload)
    {
        try {
            $transfer = $payload['data']['object'];
            $transferId = $transfer['id'];

            // PRIORITY-0: Idempotency check for transfer events
            $idempotencyService = app(\App\Services\WebhookIdempotencyService::class);
            $eventId = 'transfer.paid.'.$transferId; // Unique event ID for this transfer
            $shouldProcess = $idempotencyService->shouldProcess('stripe', $eventId);

            if (! $shouldProcess['should_process']) {
                Log::info('transfer.paid already processed', [
                    'transfer_id' => $transferId,
                ]);

                return new Response('Event already processed', 200);
            }

            // Record event for idempotency
            $webhookEvent = $idempotencyService->recordEvent('stripe', $eventId, 'transfer.paid', $payload);
            $idempotencyService->markProcessing($webhookEvent);

            // Find shift payment by transfer ID
            $shiftPayment = ShiftPayment::where('stripe_transfer_id', $transferId)->first();

            if ($shiftPayment) {
                $shiftPayment->update([
                    'status' => 'paid_out',
                    'payout_completed_at' => now(),
                ]);

                // Update assignment payment status
                if ($shiftPayment->assignment) {
                    $shiftPayment->assignment->update(['payment_status' => 'paid_out']);
                }

                // Send notification to worker
                if ($shiftPayment->worker) {
                    Notifications::send(
                        $shiftPayment->worker_id,
                        $shiftPayment->business_id,
                        'payment_received',
                        $shiftPayment->shift_id
                    );
                }

                Log::info("Worker received payout for shift payment {$shiftPayment->id}");
                $idempotencyService->markProcessed($webhookEvent, ['payout_completed' => true]);
            } else {
                // Mark as processed even if shift payment not found
                $idempotencyService->markProcessed($webhookEvent, ['processed' => true, 'shift_payment_found' => false]);
            }

            return new Response('Webhook Handled: {handleTransferPaid}', 200);

        } catch (\Exception $exception) {
            Log::error('Webhook error handleTransferPaid: '.$exception->getMessage());

            // Mark as failed if webhook event exists
            if (isset($webhookEvent)) {
                $idempotencyService->markFailed($webhookEvent, $exception->getMessage(), true);
            }

            return new Response('Webhook Error: {handleTransferPaid}', 500);
        }
    }
}
