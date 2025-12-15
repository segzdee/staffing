<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdminSettings;
use App\Models\Notifications;
use App\Models\Plans;
use Illuminate\Http\Response;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Laravel\Cashier\Subscription;
use App\Models\PaymentGateways;
use App\Models\Transactions;
use App\Models\Deposits;
use Stripe\PaymentIntent as StripePaymentIntent;
use App\Models\User;
use App\Models\ShiftPayment;
use App\Models\ShiftAssignment;
use App\Http\Controllers\Traits\Functions;

class StripeWebHookController extends WebhookController
{
  use Functions;

    /**
     *
     * customer.subscription.deleted
     *
     * @param array $payload
     * @return Response|\Symfony\Component\HttpFoundation\Response
     */
    public function handleCustomerSubscriptionDeleted(array $payload) {
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
     *
     * WEBHOOK Insert the information of each payment in the Payments table when successfully generating an invoice in Stripe
     *
     * @param array $payload
     * @return Response|\Symfony\Component\HttpFoundation\Response
     */
    public function handleInvoicePaymentSucceeded($payload)
    {
        try {
            $settings = AdminSettings::first();
            $data     = $payload['data'];
            $object   = $data['object'];
            $customer = $object['customer'];
            $amount   = $settings->currency_code == 'JPY' ? $object['subtotal'] : ($object['subtotal'] / 100);
            $user     = $this->getUserByStripeId($customer);
            $interval = $object['lines']['data'][0]['metadata']['interval'] ?? 'monthly';
            $taxes    = $object['lines']['data'][0]['metadata']['taxes'] ?? null;

            if ($user) {
                $subscription = Subscription::whereStripeId($object['subscription'])->first();
                if ($subscription) {
                    $subscription->stripe_status = "active";
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
     *
     * checkout.session.completed
     *
     * @param array $payload
     * @return Response|\Symfony\Component\HttpFoundation\Response
     */
    public function handleCheckoutSessionCompleted($payload)
    {
        try {
            $settings = AdminSettings::first();
            $data     = $payload['data'];
            $object   = $data['object'];
            $user     = $object['metadata']['user'] ?? null;
            $amount   = $object['metadata']['amount'] ?? null;
            $taxes    = $object['metadata']['taxes'] ?? null;
            $type     = $object['metadata']['type'] ?? null;

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
     *
     * charge.refunded
     *
     * @param array $payload
     * @return Response|\Symfony\Component\HttpFoundation\Response
     */
    public function handleChargeRefunded($payload)
    {
        try {
          $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
          $stripe->subscriptions->cancel($payload['data']['object']['subscription'], []);

          return new Response('Webhook Handled: {handleChargeRefunded}', 200);

        } catch (\Exception $exception) {
            Log::debug("Exception Webhook {handleChargeRefunded}: " . $exception->getMessage() . ", Line: " . $exception->getLine() . ', File: ' . $exception->getFile());
            return new Response('Webhook Handled with error: {handleChargeRefunded}', 400);
        }
    }

    /**
     * WEBHOOK Manage the SCA by notifying the user by email
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleInvoicePaymentActionRequired(array $payload)
    {
        $subscription = Subscription::whereStripeId($payload['data']['object']['subscription'])->first();
        if ($subscription) {
            $subscription->stripe_status = "incomplete";
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
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handlePaymentIntentSucceeded(array $payload)
    {
        try {
            $paymentIntent = $payload['data']['object'];

            // Check if this is a shift payment
            $metadata = $paymentIntent['metadata'] ?? [];

            if (isset($metadata['type']) && $metadata['type'] === 'shift_payment') {
                $shiftPaymentId = $metadata['shift_payment_id'] ?? null;

                if ($shiftPaymentId) {
                    $shiftPayment = ShiftPayment::find($shiftPaymentId);

                    if ($shiftPayment && $shiftPayment->status === 'pending_escrow') {
                        $shiftPayment->update([
                            'status' => 'in_escrow',
                            'escrow_held_at' => now(),
                            'stripe_payment_intent' => $paymentIntent['id']
                        ]);

                        Log::info("Shift payment {$shiftPaymentId} successfully held in escrow");
                    }
                }
            }

            return new Response('Webhook Handled: {handlePaymentIntentSucceeded}', 200);

        } catch (\Exception $exception) {
            Log::error("Webhook error handlePaymentIntentSucceeded: " . $exception->getMessage());
            return new Response('Webhook Error: {handlePaymentIntentSucceeded}', 500);
        }
    }

    /**
     * Handle shift payment - payment_intent.payment_failed
     * Webhook when business payment fails
     *
     * @param  array  $payload
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
                            'error_message' => $paymentIntent['last_payment_error']['message'] ?? 'Payment failed'
                        ]);

                        // Update assignment status
                        if ($shiftPayment->assignment) {
                            $shiftPayment->assignment->update(['status' => 'payment_failed']);
                        }

                        Log::warning("Shift payment {$shiftPaymentId} failed: " . $paymentIntent['last_payment_error']['message'] ?? '');
                    }
                }
            }

            return new Response('Webhook Handled: {handlePaymentIntentPaymentFailed}', 200);

        } catch (\Exception $exception) {
            Log::error("Webhook error handlePaymentIntentPaymentFailed: " . $exception->getMessage());
            return new Response('Webhook Error: {handlePaymentIntentPaymentFailed}', 500);
        }
    }

    /**
     * Handle shift payout - transfer.created
     * Webhook when instant payout is initiated
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleTransferCreated(array $payload)
    {
        try {
            $transfer = $payload['data']['object'];
            $metadata = $transfer['metadata'] ?? [];

            if (isset($metadata['type']) && $metadata['type'] === 'shift_payout') {
                $shiftPaymentId = $metadata['shift_payment_id'] ?? null;

                if ($shiftPaymentId) {
                    $shiftPayment = ShiftPayment::find($shiftPaymentId);

                    if ($shiftPayment) {
                        $shiftPayment->update([
                            'stripe_transfer_id' => $transfer['id']
                        ]);

                        Log::info("Transfer created for shift payment {$shiftPaymentId}");
                    }
                }
            }

            return new Response('Webhook Handled: {handleTransferCreated}', 200);

        } catch (\Exception $exception) {
            Log::error("Webhook error handleTransferCreated: " . $exception->getMessage());
            return new Response('Webhook Error: {handleTransferCreated}', 500);
        }
    }

    /**
     * Handle shift payout - transfer.paid
     * Webhook when worker receives instant payout
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleTransferPaid(array $payload)
    {
        try {
            $transfer = $payload['data']['object'];
            $transferId = $transfer['id'];

            // Find shift payment by transfer ID
            $shiftPayment = ShiftPayment::where('stripe_transfer_id', $transferId)->first();

            if ($shiftPayment) {
                $shiftPayment->update([
                    'status' => 'paid_out',
                    'payout_completed_at' => now()
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
            }

            return new Response('Webhook Handled: {handleTransferPaid}', 200);

        } catch (\Exception $exception) {
            Log::error("Webhook error handleTransferPaid: " . $exception->getMessage());
            return new Response('Webhook Error: {handleTransferPaid}', 500);
        }
    }
}
