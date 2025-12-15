<?php

namespace App\Services;

use App\Models\AgencyProfile;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\InvalidRequestException;

/**
 * StripeConnectService
 *
 * Handles Stripe Connect integration for agency payouts.
 *
 * TASK: AGY-003 - Stripe Connect Integration for Agency Payouts
 *
 * Features:
 * - Create Stripe Connect Express accounts for agencies
 * - Generate onboarding links
 * - Verify account status
 * - Process payouts via Stripe Connect
 * - Retrieve account balances
 *
 * Security:
 * - API version 2023-10-16 or later
 * - Webhook signature validation
 * - Encrypted sensitive data storage
 */
class StripeConnectService
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

    /**
     * Create a Stripe Connect Express account for an agency.
     *
     * @param AgencyProfile $agency
     * @return array{success: bool, account_id?: string, error?: string}
     */
    public function createConnectedAccount(AgencyProfile $agency): array
    {
        try {
            // Check if agency already has a Connect account
            if ($agency->hasStripeConnectAccount()) {
                return [
                    'success' => true,
                    'account_id' => $agency->stripe_connect_account_id,
                    'message' => 'Account already exists',
                ];
            }

            $user = $agency->user;
            if (!$user) {
                return [
                    'success' => false,
                    'error' => 'Agency user not found',
                ];
            }

            // Determine country code
            $countryCode = $this->getCountryCode($agency);

            // Create Express account
            $account = $this->stripe->accounts->create([
                'type' => 'express',
                'country' => $countryCode,
                'email' => $user->email,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
                'business_type' => 'company',
                'business_profile' => [
                    'name' => $agency->agency_name,
                    'mcc' => '7361', // Employment Agencies
                    'product_description' => 'Staffing agency commission payouts',
                ],
                'metadata' => [
                    'agency_id' => $agency->id,
                    'user_id' => $agency->user_id,
                    'platform' => 'overtimestaff',
                ],
            ]);

            // Update agency with Stripe account ID
            $agency->update([
                'stripe_connect_account_id' => $account->id,
                'stripe_account_type' => 'express',
            ]);

            Log::info('Stripe Connect account created for agency', [
                'agency_id' => $agency->id,
                'stripe_account_id' => $account->id,
            ]);

            return [
                'success' => true,
                'account_id' => $account->id,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe Connect account', [
                'agency_id' => $agency->id,
                'error' => $e->getMessage(),
                'stripe_code' => $e->getStripeCode(),
            ]);

            return [
                'success' => false,
                'error' => $this->formatStripeError($e),
            ];
        }
    }

    /**
     * Generate an onboarding link for an agency.
     *
     * @param AgencyProfile $agency
     * @param string|null $refreshUrl
     * @param string|null $returnUrl
     * @return array{success: bool, url?: string, error?: string}
     */
    public function onboardAccount(AgencyProfile $agency, ?string $refreshUrl = null, ?string $returnUrl = null): array
    {
        try {
            // Create account if doesn't exist
            if (!$agency->hasStripeConnectAccount()) {
                $result = $this->createConnectedAccount($agency);
                if (!$result['success']) {
                    return $result;
                }
                $agency->refresh();
            }

            $refreshUrl = $refreshUrl ?? route('agency.stripe.connect');
            $returnUrl = $returnUrl ?? route('agency.stripe.callback');

            // Create account link for onboarding
            $accountLink = $this->stripe->accountLinks->create([
                'account' => $agency->stripe_connect_account_id,
                'refresh_url' => $refreshUrl,
                'return_url' => $returnUrl,
                'type' => 'account_onboarding',
                'collect' => 'eventually_due',
            ]);

            Log::info('Stripe Connect onboarding link generated', [
                'agency_id' => $agency->id,
                'stripe_account_id' => $agency->stripe_connect_account_id,
            ]);

            return [
                'success' => true,
                'url' => $accountLink->url,
                'expires_at' => $accountLink->expires_at,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to generate Stripe onboarding link', [
                'agency_id' => $agency->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $this->formatStripeError($e),
            ];
        }
    }

    /**
     * Verify the account status of an agency's Stripe Connect account.
     *
     * @param AgencyProfile $agency
     * @return array{success: bool, status?: string, details?: array, error?: string}
     */
    public function verifyAccountStatus(AgencyProfile $agency): array
    {
        try {
            if (!$agency->hasStripeConnectAccount()) {
                return [
                    'success' => true,
                    'status' => 'not_created',
                    'details' => [
                        'charges_enabled' => false,
                        'payouts_enabled' => false,
                        'details_submitted' => false,
                    ],
                ];
            }

            $account = $this->stripe->accounts->retrieve($agency->stripe_connect_account_id);

            // Update agency with current status
            $agency->updateStripeAccountStatus([
                'charges_enabled' => $account->charges_enabled,
                'payouts_enabled' => $account->payouts_enabled,
                'details_submitted' => $account->details_submitted,
                'requirements' => $account->requirements,
            ]);

            // Check if onboarding is complete
            if ($account->details_submitted && $account->payouts_enabled && !$agency->stripe_onboarding_complete) {
                $agency->markStripeOnboardingComplete();
            }

            $status = $this->determineAccountStatus($account);

            Log::info('Stripe Connect account status verified', [
                'agency_id' => $agency->id,
                'status' => $status,
                'charges_enabled' => $account->charges_enabled,
                'payouts_enabled' => $account->payouts_enabled,
            ]);

            return [
                'success' => true,
                'status' => $status,
                'details' => [
                    'charges_enabled' => $account->charges_enabled,
                    'payouts_enabled' => $account->payouts_enabled,
                    'details_submitted' => $account->details_submitted,
                    'requirements' => $account->requirements?->currently_due ?? [],
                    'eventually_due' => $account->requirements?->eventually_due ?? [],
                    'pending_verification' => $account->requirements?->pending_verification ?? [],
                    'disabled_reason' => $account->requirements?->disabled_reason,
                    'default_currency' => $account->default_currency,
                ],
            ];
        } catch (InvalidRequestException $e) {
            // Account may have been deleted or doesn't exist
            if (str_contains($e->getMessage(), 'No such account')) {
                $agency->update([
                    'stripe_connect_account_id' => null,
                    'stripe_onboarding_complete' => false,
                    'stripe_payout_enabled' => false,
                ]);

                return [
                    'success' => true,
                    'status' => 'deleted',
                    'details' => [],
                ];
            }

            throw $e;
        } catch (ApiErrorException $e) {
            Log::error('Failed to verify Stripe Connect account status', [
                'agency_id' => $agency->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $this->formatStripeError($e),
            ];
        }
    }

    /**
     * Create a payout to an agency's Stripe Connect account.
     *
     * @param AgencyProfile $agency
     * @param float $amount Amount in dollars
     * @param string $currency Currency code (default: USD)
     * @param string|null $description Payout description
     * @return array{success: bool, payout_id?: string, transfer_id?: string, error?: string}
     */
    public function createPayout(AgencyProfile $agency, float $amount, string $currency = 'USD', ?string $description = null): array
    {
        try {
            // Validate agency can receive payouts
            if (!$agency->canReceivePayouts()) {
                return [
                    'success' => false,
                    'error' => 'Agency cannot receive payouts. Please complete Stripe Connect onboarding.',
                    'requires_onboarding' => true,
                ];
            }

            // Convert to cents
            $amountInCents = (int) round($amount * 100);

            if ($amountInCents <= 0) {
                return [
                    'success' => false,
                    'error' => 'Payout amount must be greater than zero.',
                ];
            }

            $description = $description ?? sprintf(
                'OvertimeStaff Commission Payout - %s',
                now()->format('M j, Y')
            );

            // Create a transfer to the connected account
            $transfer = $this->stripe->transfers->create([
                'amount' => $amountInCents,
                'currency' => strtolower($currency),
                'destination' => $agency->stripe_connect_account_id,
                'description' => $description,
                'metadata' => [
                    'agency_id' => $agency->id,
                    'user_id' => $agency->user_id,
                    'type' => 'agency_commission_payout',
                    'platform' => 'overtimestaff',
                ],
            ]);

            // Record successful payout
            $agency->recordPayout($amount, $transfer->id);

            Log::info('Stripe Connect payout created', [
                'agency_id' => $agency->id,
                'transfer_id' => $transfer->id,
                'amount' => $amount,
                'currency' => $currency,
            ]);

            return [
                'success' => true,
                'transfer_id' => $transfer->id,
                'amount' => $amount,
                'currency' => $currency,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe Connect payout', [
                'agency_id' => $agency->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            // Record failed payout
            $agency->recordPayoutFailure();

            return [
                'success' => false,
                'error' => $this->formatStripeError($e),
            ];
        }
    }

    /**
     * Retrieve the balance for an agency's Stripe Connect account.
     *
     * @param AgencyProfile $agency
     * @return array{success: bool, balance?: array, error?: string}
     */
    public function retrieveBalance(AgencyProfile $agency): array
    {
        try {
            if (!$agency->hasStripeConnectAccount()) {
                return [
                    'success' => false,
                    'error' => 'Agency does not have a Stripe Connect account.',
                ];
            }

            $balance = $this->stripe->balance->retrieve([], [
                'stripe_account' => $agency->stripe_connect_account_id,
            ]);

            $formattedBalance = [];
            foreach ($balance->available as $available) {
                $formattedBalance[$available->currency] = [
                    'available' => $available->amount / 100,
                    'pending' => 0,
                ];
            }

            foreach ($balance->pending as $pending) {
                if (!isset($formattedBalance[$pending->currency])) {
                    $formattedBalance[$pending->currency] = ['available' => 0, 'pending' => 0];
                }
                $formattedBalance[$pending->currency]['pending'] = $pending->amount / 100;
            }

            return [
                'success' => true,
                'balance' => $formattedBalance,
                'raw_balance' => $balance->toArray(),
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to retrieve Stripe Connect balance', [
                'agency_id' => $agency->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $this->formatStripeError($e),
            ];
        }
    }

    /**
     * Generate a login link to the Stripe Express dashboard.
     *
     * @param AgencyProfile $agency
     * @return array{success: bool, url?: string, error?: string}
     */
    public function createDashboardLink(AgencyProfile $agency): array
    {
        try {
            if (!$agency->hasStripeConnectAccount()) {
                return [
                    'success' => false,
                    'error' => 'Agency does not have a Stripe Connect account.',
                ];
            }

            $loginLink = $this->stripe->accounts->createLoginLink(
                $agency->stripe_connect_account_id
            );

            return [
                'success' => true,
                'url' => $loginLink->url,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe dashboard link', [
                'agency_id' => $agency->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $this->formatStripeError($e),
            ];
        }
    }

    /**
     * Retrieve a Stripe Connect account directly.
     *
     * @param string $accountId
     * @return \Stripe\Account|null
     */
    public function retrieveAccount(string $accountId): ?\Stripe\Account
    {
        try {
            return $this->stripe->accounts->retrieve($accountId);
        } catch (ApiErrorException $e) {
            Log::error('Failed to retrieve Stripe account', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Find agency by Stripe Connect account ID.
     *
     * @param string $accountId
     * @return AgencyProfile|null
     */
    public function findAgencyByStripeAccountId(string $accountId): ?AgencyProfile
    {
        return AgencyProfile::where('stripe_connect_account_id', $accountId)->first();
    }

    /**
     * Verify a Stripe webhook signature.
     *
     * @param string $payload
     * @param string $signature
     * @param string|null $secret
     * @return \Stripe\Event|null
     */
    public function verifyWebhookSignature(string $payload, string $signature, ?string $secret = null): ?\Stripe\Event
    {
        try {
            $secret = $secret ?? config('services.stripe.webhook_secret_connect');

            if (!$secret) {
                Log::warning('Stripe Connect webhook secret not configured');
                return null;
            }

            return \Stripe\Webhook::constructEvent($payload, $signature, $secret);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::warning('Invalid Stripe webhook signature', [
                'error' => $e->getMessage(),
            ]);
            return null;
        } catch (\UnexpectedValueException $e) {
            Log::warning('Invalid Stripe webhook payload', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Determine the account status from a Stripe account object.
     */
    protected function determineAccountStatus(\Stripe\Account $account): string
    {
        if (!$account->details_submitted) {
            return 'pending_details';
        }

        if (!empty($account->requirements?->currently_due)) {
            return 'pending_verification';
        }

        if (!$account->charges_enabled || !$account->payouts_enabled) {
            return 'restricted';
        }

        return 'active';
    }

    /**
     * Get the country code for an agency.
     */
    protected function getCountryCode(AgencyProfile $agency): string
    {
        // Try to get from agency or user
        $country = $agency->country ?? $agency->user?->country ?? null;

        if ($country) {
            // Convert country name to ISO code if needed
            return $this->countryNameToCode($country);
        }

        // Default to US if no country is set
        return 'US';
    }

    /**
     * Convert country name to ISO 2-letter code.
     */
    protected function countryNameToCode(string $country): string
    {
        $countryMap = [
            'united states' => 'US',
            'usa' => 'US',
            'united kingdom' => 'GB',
            'uk' => 'GB',
            'canada' => 'CA',
            'australia' => 'AU',
            'germany' => 'DE',
            'france' => 'FR',
            'india' => 'IN',
            'nigeria' => 'NG',
            'south africa' => 'ZA',
            'brazil' => 'BR',
            'mexico' => 'MX',
            // Add more as needed
        ];

        $normalized = strtolower(trim($country));

        // If already a 2-letter code, return uppercase
        if (strlen($country) === 2) {
            return strtoupper($country);
        }

        return $countryMap[$normalized] ?? 'US';
    }

    /**
     * Format Stripe error for display.
     */
    protected function formatStripeError(ApiErrorException $e): string
    {
        $stripeCode = $e->getStripeCode();

        $friendlyMessages = [
            'account_invalid' => 'The Stripe account is invalid or has been deleted.',
            'account_country_invalid' => 'The country specified is not supported for Stripe Connect.',
            'balance_insufficient' => 'Insufficient balance for this payout.',
            'bank_account_declined' => 'The bank account declined the transfer.',
            'bank_account_unusable' => 'The bank account cannot receive transfers.',
            'instant_payouts_unsupported' => 'Instant payouts are not supported for this account.',
            'invalid_amount' => 'The payout amount is invalid.',
            'payout_not_allowed' => 'Payouts are not allowed for this account.',
        ];

        if ($stripeCode && isset($friendlyMessages[$stripeCode])) {
            return $friendlyMessages[$stripeCode];
        }

        // Return a sanitized version of the error message
        $message = $e->getMessage();

        // Remove any sensitive information
        $message = preg_replace('/acct_[a-zA-Z0-9]+/', '[account_id]', $message);

        return $message;
    }
}
