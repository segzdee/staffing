<?php

namespace App\Services;

use App\Models\BusinessProfile;
use App\Models\BusinessPaymentMethod;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

/**
 * BusinessPaymentService
 *
 * BIZ-REG-007: Business Payment Setup
 *
 * Handles all Stripe-related payment method operations for businesses:
 * - Creating Stripe Customers
 * - Adding/managing payment methods (cards, bank accounts)
 * - Verification (card auth, micro-deposits, SEPA mandates)
 * - Setting default payment methods
 */
class BusinessPaymentService
{
    protected StripeClient $stripe;
    protected string $apiVersion = '2023-10-16';

    public function __construct()
    {
        $this->stripe = new StripeClient([
            'api_key' => config('services.stripe.secret'),
            'stripe_version' => $this->apiVersion,
        ]);
    }

    // =========================================
    // Customer Management
    // =========================================

    /**
     * Create or retrieve Stripe Customer for a business.
     */
    public function createStripeCustomer(BusinessProfile $business): array
    {
        try {
            // Check if customer already exists
            if ($business->stripe_customer_id) {
                $customer = $this->stripe->customers->retrieve($business->stripe_customer_id);
                return [
                    'success' => true,
                    'customer_id' => $customer->id,
                    'is_new' => false,
                ];
            }

            $user = $business->user;

            // Create new customer
            $customer = $this->stripe->customers->create([
                'email' => $business->billing_email ?? $user->email,
                'name' => $business->business_name,
                'phone' => $business->phone ?? $business->business_phone,
                'metadata' => [
                    'business_profile_id' => $business->id,
                    'user_id' => $business->user_id,
                    'platform' => 'overtimestaff',
                    'type' => 'business',
                ],
                'address' => [
                    'line1' => $business->address ?? $business->business_address,
                    'city' => $business->city ?? $business->business_city,
                    'state' => $business->state ?? $business->business_state,
                    'postal_code' => $business->zip_code,
                    'country' => $this->normalizeCountryCode($business->country ?? $business->business_country),
                ],
            ]);

            // Save customer ID
            $business->update(['stripe_customer_id' => $customer->id]);

            Log::info('Stripe customer created for business', [
                'business_id' => $business->id,
                'stripe_customer_id' => $customer->id,
            ]);

            return [
                'success' => true,
                'customer_id' => $customer->id,
                'is_new' => true,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe customer', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $this->formatStripeError($e),
            ];
        }
    }

    // =========================================
    // Payment Method Management
    // =========================================

    /**
     * Create a SetupIntent for adding a new payment method.
     */
    public function createSetupIntent(BusinessProfile $business, string $type = 'card'): array
    {
        try {
            // Ensure customer exists
            $customerResult = $this->createStripeCustomer($business);
            if (!$customerResult['success']) {
                return $customerResult;
            }

            $paymentMethodTypes = $this->getPaymentMethodTypes($type);

            $setupIntent = $this->stripe->setupIntents->create([
                'customer' => $customerResult['customer_id'],
                'payment_method_types' => $paymentMethodTypes,
                'usage' => 'off_session', // For future payments
                'metadata' => [
                    'business_profile_id' => $business->id,
                    'type' => $type,
                ],
            ]);

            return [
                'success' => true,
                'client_secret' => $setupIntent->client_secret,
                'setup_intent_id' => $setupIntent->id,
                'payment_method_types' => $paymentMethodTypes,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to create SetupIntent', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $this->formatStripeError($e),
            ];
        }
    }

    /**
     * Add a payment method after SetupIntent completion.
     */
    public function addPaymentMethod(
        BusinessProfile $business,
        string $setupIntentId,
        array $billingDetails = []
    ): array {
        try {
            // Retrieve the SetupIntent
            $setupIntent = $this->stripe->setupIntents->retrieve($setupIntentId);

            if ($setupIntent->status !== 'succeeded') {
                return [
                    'success' => false,
                    'error' => 'Setup not completed. Current status: ' . $setupIntent->status,
                    'requires_action' => $setupIntent->status === 'requires_action',
                    'next_action' => $setupIntent->next_action,
                ];
            }

            $stripePaymentMethodId = $setupIntent->payment_method;

            // Retrieve payment method details
            $stripePaymentMethod = $this->stripe->paymentMethods->retrieve($stripePaymentMethodId);

            // Create local record
            $paymentMethod = $this->createPaymentMethodRecord(
                $business,
                $stripePaymentMethod,
                $setupIntentId,
                $billingDetails
            );

            // Set as default if first payment method
            $existingMethods = BusinessPaymentMethod::where('business_profile_id', $business->id)
                ->usable()
                ->count();

            if ($existingMethods === 0) {
                $this->setDefaultPaymentMethod($business, $paymentMethod->id);
            }

            Log::info('Payment method added', [
                'business_id' => $business->id,
                'payment_method_id' => $paymentMethod->id,
                'type' => $paymentMethod->type,
            ]);

            return [
                'success' => true,
                'payment_method' => $paymentMethod,
                'needs_verification' => $paymentMethod->requiresAction(),
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to add payment method', [
                'business_id' => $business->id,
                'setup_intent_id' => $setupIntentId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $this->formatStripeError($e),
            ];
        }
    }

    /**
     * Create local payment method record from Stripe data.
     */
    protected function createPaymentMethodRecord(
        BusinessProfile $business,
        \Stripe\PaymentMethod $stripePaymentMethod,
        string $setupIntentId,
        array $billingDetails
    ): BusinessPaymentMethod {
        $type = $stripePaymentMethod->type;
        $data = [
            'business_profile_id' => $business->id,
            'stripe_customer_id' => $business->stripe_customer_id,
            'stripe_payment_method_id' => $stripePaymentMethod->id,
            'stripe_setup_intent_id' => $setupIntentId,
            'type' => $type,
            'currency' => $billingDetails['currency'] ?? 'USD',
        ];

        // Billing address
        if (!empty($billingDetails)) {
            $data['billing_name'] = $billingDetails['name'] ?? null;
            $data['billing_email'] = $billingDetails['email'] ?? null;
            $data['billing_phone'] = $billingDetails['phone'] ?? null;
            $data['billing_address_line1'] = $billingDetails['address']['line1'] ?? null;
            $data['billing_address_line2'] = $billingDetails['address']['line2'] ?? null;
            $data['billing_city'] = $billingDetails['address']['city'] ?? null;
            $data['billing_state'] = $billingDetails['address']['state'] ?? null;
            $data['billing_postal_code'] = $billingDetails['address']['postal_code'] ?? null;
            $data['billing_country'] = $billingDetails['address']['country'] ?? null;
        }

        // Type-specific data
        if ($type === 'card' && $stripePaymentMethod->card) {
            $card = $stripePaymentMethod->card;
            $data['display_brand'] = $card->brand;
            $data['display_last4'] = $card->last4;
            $data['display_exp_month'] = str_pad($card->exp_month, 2, '0', STR_PAD_LEFT);
            $data['display_exp_year'] = $card->exp_year;
            $data['three_d_secure_supported'] = isset($card->three_d_secure_usage) &&
                                                 $card->three_d_secure_usage->supported;
            // Cards are verified immediately
            $data['verification_status'] = BusinessPaymentMethod::VERIFICATION_VERIFIED;
            $data['verified_at'] = now();
            $data['verification_method'] = 'instant';
        }

        if ($type === 'us_bank_account' && $stripePaymentMethod->us_bank_account) {
            $bank = $stripePaymentMethod->us_bank_account;
            $data['bank_name'] = $bank->bank_name;
            $data['bank_account_type'] = $bank->account_type;
            $data['display_last4'] = $bank->last4;
            $data['bank_routing_display'] = '****' . substr($bank->routing_number ?? '', -4);

            // Check verification status
            if ($bank->financial_connections_account) {
                // Instant verification via Plaid/Financial Connections
                $data['verification_status'] = BusinessPaymentMethod::VERIFICATION_VERIFIED;
                $data['verified_at'] = now();
                $data['verification_method'] = 'instant';
            } else {
                // Micro-deposit verification needed
                $data['verification_status'] = BusinessPaymentMethod::VERIFICATION_REQUIRES_ACTION;
                $data['verification_method'] = 'micro_deposits';
            }
        }

        if ($type === 'sepa_debit' && $stripePaymentMethod->sepa_debit) {
            $sepa = $stripePaymentMethod->sepa_debit;
            $data['display_last4'] = $sepa->last4;
            $data['iban_last4'] = $sepa->last4;
            $data['bank_name'] = $sepa->bank_code;
            $data['billing_country'] = $sepa->country;
            // SEPA requires mandate acceptance
            $data['verification_status'] = BusinessPaymentMethod::VERIFICATION_VERIFIED;
            $data['verified_at'] = now();
            $data['verification_method'] = 'mandate';
        }

        if ($type === 'bacs_debit' && $stripePaymentMethod->bacs_debit) {
            $bacs = $stripePaymentMethod->bacs_debit;
            $data['display_last4'] = $bacs->last4;
            $data['sort_code_display'] = $bacs->sort_code;
            // BACS typically requires verification
            $data['verification_status'] = BusinessPaymentMethod::VERIFICATION_REQUIRES_ACTION;
            $data['verification_method'] = 'manual';
        }

        return BusinessPaymentMethod::create($data);
    }

    /**
     * Get list of payment methods for a business.
     */
    public function getPaymentMethods(BusinessProfile $business): array
    {
        $methods = BusinessPaymentMethod::where('business_profile_id', $business->id)
            ->whereNull('deleted_at')
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'success' => true,
            'payment_methods' => $methods,
            'default_id' => $methods->firstWhere('is_default', true)?->id,
            'has_usable_method' => $methods->contains(fn($m) => $m->isUsable()),
        ];
    }

    /**
     * Set a payment method as default.
     */
    public function setDefaultPaymentMethod(BusinessProfile $business, int $paymentMethodId): array
    {
        try {
            $paymentMethod = BusinessPaymentMethod::where('business_profile_id', $business->id)
                ->where('id', $paymentMethodId)
                ->first();

            if (!$paymentMethod) {
                return [
                    'success' => false,
                    'error' => 'Payment method not found.',
                ];
            }

            if (!$paymentMethod->isUsable()) {
                return [
                    'success' => false,
                    'error' => 'Cannot set unverified or inactive payment method as default.',
                ];
            }

            DB::transaction(function () use ($business, $paymentMethod) {
                // Remove default from all other methods
                BusinessPaymentMethod::where('business_profile_id', $business->id)
                    ->where('id', '!=', $paymentMethod->id)
                    ->update(['is_default' => false]);

                // Set this one as default
                $paymentMethod->update(['is_default' => true]);

                // Update Stripe customer default
                if ($business->stripe_customer_id && $paymentMethod->stripe_payment_method_id) {
                    $this->stripe->customers->update($business->stripe_customer_id, [
                        'invoice_settings' => [
                            'default_payment_method' => $paymentMethod->stripe_payment_method_id,
                        ],
                    ]);
                }

                // Update business profile reference
                $business->update(['default_payment_method' => $paymentMethod->id]);
            });

            return [
                'success' => true,
                'payment_method' => $paymentMethod->fresh(),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to set default payment method', [
                'business_id' => $business->id,
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to update default payment method.',
            ];
        }
    }

    /**
     * Remove a payment method.
     */
    public function removePaymentMethod(BusinessProfile $business, int $paymentMethodId): array
    {
        try {
            $paymentMethod = BusinessPaymentMethod::where('business_profile_id', $business->id)
                ->where('id', $paymentMethodId)
                ->first();

            if (!$paymentMethod) {
                return [
                    'success' => false,
                    'error' => 'Payment method not found.',
                ];
            }

            // Check if this is the only usable method
            $usableCount = BusinessPaymentMethod::where('business_profile_id', $business->id)
                ->usable()
                ->count();

            if ($paymentMethod->isUsable() && $usableCount <= 1) {
                return [
                    'success' => false,
                    'error' => 'Cannot remove the only payment method. Add another method first.',
                ];
            }

            // Detach from Stripe
            if ($paymentMethod->stripe_payment_method_id) {
                try {
                    $this->stripe->paymentMethods->detach($paymentMethod->stripe_payment_method_id);
                } catch (ApiErrorException $e) {
                    // Log but continue - may already be detached
                    Log::warning('Could not detach payment method from Stripe', [
                        'payment_method_id' => $paymentMethod->stripe_payment_method_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $wasDefault = $paymentMethod->is_default;

            // Soft delete the record
            $paymentMethod->delete();

            // If this was default, set another as default
            if ($wasDefault) {
                $nextDefault = BusinessPaymentMethod::where('business_profile_id', $business->id)
                    ->usable()
                    ->first();

                if ($nextDefault) {
                    $this->setDefaultPaymentMethod($business, $nextDefault->id);
                }
            }

            return [
                'success' => true,
                'message' => 'Payment method removed successfully.',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to remove payment method', [
                'business_id' => $business->id,
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to remove payment method.',
            ];
        }
    }

    // =========================================
    // Verification Methods
    // =========================================

    /**
     * Verify card with $1 authorization and cancel.
     */
    public function verifyCard(BusinessProfile $business, int $paymentMethodId): array
    {
        try {
            $paymentMethod = BusinessPaymentMethod::where('business_profile_id', $business->id)
                ->where('id', $paymentMethodId)
                ->where('type', 'card')
                ->first();

            if (!$paymentMethod) {
                return [
                    'success' => false,
                    'error' => 'Card payment method not found.',
                ];
            }

            // Create a $1 authorization
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => 100, // $1.00 in cents
                'currency' => strtolower($paymentMethod->currency ?? 'usd'),
                'customer' => $business->stripe_customer_id,
                'payment_method' => $paymentMethod->stripe_payment_method_id,
                'capture_method' => 'manual', // Don't capture, just authorize
                'confirm' => true,
                'off_session' => true,
                'metadata' => [
                    'type' => 'card_verification',
                    'business_id' => $business->id,
                ],
            ]);

            if ($paymentIntent->status === 'requires_capture') {
                // Authorization successful, cancel it
                $this->stripe->paymentIntents->cancel($paymentIntent->id);

                $paymentMethod->markVerified();

                return [
                    'success' => true,
                    'message' => 'Card verified successfully.',
                    'payment_method' => $paymentMethod->fresh(),
                ];
            }

            // Handle 3D Secure if needed
            if ($paymentIntent->status === 'requires_action') {
                $paymentMethod->update([
                    'verification_status' => BusinessPaymentMethod::VERIFICATION_REQUIRES_ACTION,
                    'three_d_secure_status' => 'required',
                ]);

                return [
                    'success' => false,
                    'requires_action' => true,
                    'client_secret' => $paymentIntent->client_secret,
                    'message' => '3D Secure authentication required.',
                ];
            }

            return [
                'success' => false,
                'error' => 'Card verification failed. Status: ' . $paymentIntent->status,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Card verification failed', [
                'business_id' => $business->id,
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage(),
            ]);

            $paymentMethod?->markVerificationFailed($e->getMessage());

            return [
                'success' => false,
                'error' => $this->formatStripeError($e),
            ];
        }
    }

    /**
     * Initiate micro-deposit verification for bank account.
     */
    public function initiateMicroDepositVerification(BusinessProfile $business, int $paymentMethodId): array
    {
        try {
            $paymentMethod = BusinessPaymentMethod::where('business_profile_id', $business->id)
                ->where('id', $paymentMethodId)
                ->whereIn('type', ['us_bank_account'])
                ->first();

            if (!$paymentMethod) {
                return [
                    'success' => false,
                    'error' => 'Bank account payment method not found.',
                ];
            }

            if ($paymentMethod->isVerified()) {
                return [
                    'success' => true,
                    'message' => 'Bank account is already verified.',
                ];
            }

            // Stripe automatically sends micro-deposits when using SetupIntent
            // This method triggers a verification reminder

            $paymentMethod->recordMicroDepositSent();

            return [
                'success' => true,
                'message' => 'Two small deposits will appear in your bank account within 1-2 business days. ' .
                            'Once you see them, verify your account by entering the amounts.',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to initiate micro-deposit verification', [
                'business_id' => $business->id,
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to initiate verification.',
            ];
        }
    }

    /**
     * Verify bank account with micro-deposit amounts.
     */
    public function verifyMicroDeposits(
        BusinessProfile $business,
        int $paymentMethodId,
        int $amount1,
        int $amount2
    ): array {
        try {
            $paymentMethod = BusinessPaymentMethod::where('business_profile_id', $business->id)
                ->where('id', $paymentMethodId)
                ->where('type', 'us_bank_account')
                ->first();

            if (!$paymentMethod) {
                return [
                    'success' => false,
                    'error' => 'Bank account payment method not found.',
                ];
            }

            // Verify via Stripe
            $setupIntent = $this->stripe->setupIntents->retrieve($paymentMethod->stripe_setup_intent_id);

            $verifiedIntent = $this->stripe->setupIntents->verifyMicrodeposits(
                $paymentMethod->stripe_setup_intent_id,
                ['amounts' => [$amount1, $amount2]]
            );

            if ($verifiedIntent->status === 'succeeded') {
                $paymentMethod->markVerified();

                return [
                    'success' => true,
                    'message' => 'Bank account verified successfully!',
                    'payment_method' => $paymentMethod->fresh(),
                ];
            }

            $paymentMethod->incrementMicroDepositAttempt();

            return [
                'success' => false,
                'error' => 'Verification failed. Please check the amounts and try again.',
                'attempts_remaining' => 3 - $paymentMethod->micro_deposit_attempts,
            ];
        } catch (ApiErrorException $e) {
            $paymentMethod?->incrementMicroDepositAttempt();

            Log::error('Micro-deposit verification failed', [
                'business_id' => $business->id,
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Verification failed: ' . $this->formatStripeError($e),
                'attempts_remaining' => $paymentMethod ? 3 - $paymentMethod->micro_deposit_attempts : 0,
            ];
        }
    }

    // =========================================
    // Payment Setup Status
    // =========================================

    /**
     * Mark payment setup as complete for business.
     */
    public function completePaymentSetup(BusinessProfile $business): array
    {
        // Check if there's at least one usable payment method
        $hasUsableMethod = BusinessPaymentMethod::where('business_profile_id', $business->id)
            ->usable()
            ->exists();

        if (!$hasUsableMethod) {
            return [
                'success' => false,
                'error' => 'At least one verified payment method is required.',
            ];
        }

        $business->update([
            'payment_setup_complete' => true,
            'payment_setup_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => 'Payment setup completed successfully.',
        ];
    }

    /**
     * Check if business can post shifts (has verified payment).
     */
    public function canBusinessPostShifts(BusinessProfile $business): bool
    {
        if (!$business->payment_setup_complete) {
            return false;
        }

        return BusinessPaymentMethod::where('business_profile_id', $business->id)
            ->usable()
            ->exists();
    }

    // =========================================
    // Helper Methods
    // =========================================

    /**
     * Get payment method types for setup intent.
     */
    protected function getPaymentMethodTypes(string $type): array
    {
        return match($type) {
            'card' => ['card'],
            'us_bank_account', 'ach' => ['us_bank_account'],
            'sepa_debit', 'sepa' => ['sepa_debit'],
            'bacs_debit', 'bacs' => ['bacs_debit'],
            'all' => ['card', 'us_bank_account', 'sepa_debit', 'bacs_debit'],
            default => ['card'],
        };
    }

    /**
     * Normalize country code.
     */
    protected function normalizeCountryCode(?string $country): string
    {
        if (!$country) {
            return 'US';
        }

        // If already 2-letter code
        if (strlen($country) === 2) {
            return strtoupper($country);
        }

        $map = [
            'united states' => 'US',
            'usa' => 'US',
            'united kingdom' => 'GB',
            'uk' => 'GB',
            'canada' => 'CA',
            'australia' => 'AU',
            'germany' => 'DE',
            'france' => 'FR',
            'malta' => 'MT',
            'netherlands' => 'NL',
            'spain' => 'ES',
            'italy' => 'IT',
        ];

        return $map[strtolower(trim($country))] ?? 'US';
    }

    /**
     * Format Stripe error for display.
     */
    protected function formatStripeError(ApiErrorException $e): string
    {
        $code = $e->getStripeCode();

        $friendlyMessages = [
            'card_declined' => 'Your card was declined. Please try a different card.',
            'expired_card' => 'Your card has expired. Please use a different card.',
            'incorrect_cvc' => 'The security code (CVC) is incorrect.',
            'processing_error' => 'An error occurred while processing. Please try again.',
            'incorrect_number' => 'The card number is incorrect.',
            'invalid_cvc' => 'The security code is invalid.',
            'invalid_expiry_month' => 'The expiration month is invalid.',
            'invalid_expiry_year' => 'The expiration year is invalid.',
            'bank_account_declined' => 'Your bank account was declined.',
            'bank_account_unusable' => 'This bank account cannot be used.',
        ];

        if ($code && isset($friendlyMessages[$code])) {
            return $friendlyMessages[$code];
        }

        return 'Payment processing error. Please try again or contact support.';
    }
}
