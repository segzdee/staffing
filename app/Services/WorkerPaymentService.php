<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkerProfile;
use App\Notifications\PaymentSetupCompleteNotification;
use App\Notifications\PaymentSetupRequiredNotification;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

/**
 * STAFF-REG-008: Worker Payment Setup Service
 *
 * Handles Stripe Connect integration for worker payouts including:
 * - Express account creation
 * - Onboarding link generation
 * - Account status verification
 * - Payout capability checks
 */
class WorkerPaymentService
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
     * Create a Stripe Connect Express account for a worker.
     *
     * @param User $worker
     * @return array
     */
    public function createStripeConnectAccount(User $worker): array
    {
        try {
            $profile = $worker->workerProfile;

            // Check if worker already has a Connect account
            if ($profile && $profile->stripe_connect_account_id) {
                return [
                    'success' => true,
                    'account_id' => $profile->stripe_connect_account_id,
                    'message' => 'Account already exists.',
                ];
            }

            // Determine country code
            $countryCode = $this->getCountryCode($worker);

            // Create Express account
            $account = $this->stripe->accounts->create([
                'type' => 'express',
                'country' => $countryCode,
                'email' => $worker->email,
                'capabilities' => [
                    'transfers' => ['requested' => true],
                ],
                'business_type' => 'individual',
                'individual' => [
                    'email' => $worker->email,
                    'first_name' => $worker->first_name,
                    'last_name' => $worker->last_name,
                ],
                'business_profile' => [
                    'mcc' => '7361', // Employment agencies
                    'product_description' => 'Temporary staffing shift payments',
                ],
                'metadata' => [
                    'user_id' => $worker->id,
                    'user_type' => 'worker',
                    'platform' => 'overtimestaff',
                ],
                'settings' => [
                    'payouts' => [
                        'schedule' => [
                            'interval' => 'daily', // Default daily payouts
                        ],
                    ],
                ],
            ]);

            // Update worker profile with Stripe account ID
            if (!$profile) {
                $profile = WorkerProfile::create([
                    'user_id' => $worker->id,
                    'stripe_connect_account_id' => $account->id,
                    'stripe_account_type' => 'express',
                ]);
            } else {
                $profile->update([
                    'stripe_connect_account_id' => $account->id,
                    'stripe_account_type' => 'express',
                ]);
            }

            // Also update user model for backward compatibility
            $worker->update([
                'stripe_connect_id' => $account->id,
            ]);

            Log::info('Stripe Connect account created for worker', [
                'worker_id' => $worker->id,
                'stripe_account_id' => $account->id,
            ]);

            return [
                'success' => true,
                'account_id' => $account->id,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe Connect account for worker', [
                'worker_id' => $worker->id,
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
     * Generate an onboarding link for a worker.
     *
     * @param User $worker
     * @param string|null $refreshUrl
     * @param string|null $returnUrl
     * @return array
     */
    public function generateOnboardingLink(
        User $worker,
        ?string $refreshUrl = null,
        ?string $returnUrl = null
    ): array {
        try {
            $profile = $worker->workerProfile;

            // Create account if doesn't exist
            if (!$profile || !$profile->stripe_connect_account_id) {
                $result = $this->createStripeConnectAccount($worker);
                if (!$result['success']) {
                    return $result;
                }
                $profile = $worker->workerProfile()->first();
            }

            $refreshUrl = $refreshUrl ?? route('worker.payment.setup');
            $returnUrl = $returnUrl ?? route('worker.payment.callback');

            // Create account link for onboarding
            $accountLink = $this->stripe->accountLinks->create([
                'account' => $profile->stripe_connect_account_id,
                'refresh_url' => $refreshUrl,
                'return_url' => $returnUrl,
                'type' => 'account_onboarding',
                'collect' => 'eventually_due',
            ]);

            Log::info('Stripe Connect onboarding link generated for worker', [
                'worker_id' => $worker->id,
                'stripe_account_id' => $profile->stripe_connect_account_id,
            ]);

            return [
                'success' => true,
                'url' => $accountLink->url,
                'expires_at' => $accountLink->expires_at,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to generate Stripe onboarding link for worker', [
                'worker_id' => $worker->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $this->formatStripeError($e),
            ];
        }
    }

    /**
     * Verify the payout status of a worker's Stripe Connect account.
     *
     * @param User $worker
     * @return array
     */
    public function verifyPayoutEnabled(User $worker): array
    {
        try {
            $profile = $worker->workerProfile;

            if (!$profile || !$profile->stripe_connect_account_id) {
                return [
                    'success' => true,
                    'status' => 'not_created',
                    'payouts_enabled' => false,
                    'details' => [
                        'charges_enabled' => false,
                        'payouts_enabled' => false,
                        'details_submitted' => false,
                    ],
                ];
            }

            $account = $this->stripe->accounts->retrieve($profile->stripe_connect_account_id);

            // Update profile with current status
            $profile->update([
                'stripe_charges_enabled' => $account->charges_enabled,
                'stripe_payouts_enabled' => $account->payouts_enabled,
                'stripe_details_submitted' => $account->details_submitted,
                'stripe_requirements_current' => $account->requirements?->currently_due ?? [],
                'stripe_requirements_eventually_due' => $account->requirements?->eventually_due ?? [],
                'stripe_disabled_reason' => $account->requirements?->disabled_reason,
            ]);

            // Check if onboarding is complete
            if ($account->details_submitted && $account->payouts_enabled && !$profile->stripe_onboarding_complete) {
                $profile->update([
                    'stripe_onboarding_complete' => true,
                    'stripe_onboarding_completed_at' => now(),
                ]);

                // Update user model for backward compatibility
                $worker->update([
                    'completed_stripe_onboarding' => true,
                ]);

                // Send notification
                try {
                    $worker->notify(new PaymentSetupCompleteNotification());
                } catch (\Exception $e) {
                    Log::warning('Failed to send payment setup complete notification', [
                        'worker_id' => $worker->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $status = $this->determineAccountStatus($account);

            Log::info('Stripe Connect account status verified for worker', [
                'worker_id' => $worker->id,
                'status' => $status,
                'payouts_enabled' => $account->payouts_enabled,
            ]);

            return [
                'success' => true,
                'status' => $status,
                'payouts_enabled' => $account->payouts_enabled,
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
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // Account may have been deleted
            if (str_contains($e->getMessage(), 'No such account')) {
                $worker->workerProfile?->update([
                    'stripe_connect_account_id' => null,
                    'stripe_onboarding_complete' => false,
                    'stripe_payouts_enabled' => false,
                ]);

                return [
                    'success' => true,
                    'status' => 'deleted',
                    'payouts_enabled' => false,
                    'details' => [],
                ];
            }
            throw $e;
        } catch (ApiErrorException $e) {
            Log::error('Failed to verify Stripe Connect account for worker', [
                'worker_id' => $worker->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $this->formatStripeError($e),
            ];
        }
    }

    /**
     * Get missing requirements for payout capability.
     *
     * @param User $worker
     * @return array
     */
    public function getMissingRequirements(User $worker): array
    {
        $status = $this->verifyPayoutEnabled($worker);

        if (!$status['success']) {
            return [
                'success' => false,
                'error' => $status['error'] ?? 'Failed to check requirements.',
            ];
        }

        if ($status['status'] === 'not_created') {
            return [
                'success' => true,
                'needs_setup' => true,
                'requirements' => ['Create payment account'],
                'message' => 'You need to set up your payment account to receive payouts.',
            ];
        }

        $requirements = $status['details']['requirements'] ?? [];
        $eventuallyDue = $status['details']['eventually_due'] ?? [];

        $friendlyRequirements = array_map(
            fn($req) => $this->formatRequirement($req),
            array_merge($requirements, $eventuallyDue)
        );

        return [
            'success' => true,
            'needs_setup' => !$status['payouts_enabled'],
            'requirements' => array_unique($friendlyRequirements),
            'disabled_reason' => $status['details']['disabled_reason'] ?? null,
            'message' => $status['payouts_enabled']
                ? 'Your payment account is ready to receive payouts.'
                : 'Please complete the missing requirements to enable payouts.',
        ];
    }

    /**
     * Get payment status summary for a worker.
     *
     * @param User $worker
     * @return array
     */
    public function getPaymentStatus(User $worker): array
    {
        $profile = $worker->workerProfile;

        if (!$profile || !$profile->stripe_connect_account_id) {
            return [
                'has_account' => false,
                'onboarding_complete' => false,
                'payouts_enabled' => false,
                'status' => 'not_created',
                'message' => 'Payment account not set up.',
            ];
        }

        return [
            'has_account' => true,
            'onboarding_complete' => $profile->stripe_onboarding_complete,
            'payouts_enabled' => $profile->stripe_payouts_enabled,
            'status' => $profile->stripe_payouts_enabled ? 'active' : 'pending',
            'payout_schedule' => $profile->payout_schedule ?? 'daily',
            'preferred_payout_method' => $profile->preferred_payout_method,
            'last_payout_at' => $profile->last_payout_at,
            'last_payout_amount' => $profile->last_payout_amount,
            'total_payouts' => $profile->total_payouts,
            'lifetime_payout_amount' => $profile->lifetime_payout_amount,
            'instant_payouts_enabled' => $profile->instant_payouts_enabled,
            'message' => $profile->stripe_payouts_enabled
                ? 'Your payment account is active and ready to receive payouts.'
                : 'Please complete your payment setup to receive payouts.',
        ];
    }

    /**
     * Update payout schedule.
     *
     * @param User $worker
     * @param string $schedule
     * @param string|null $day
     * @return array
     */
    public function updatePayoutSchedule(User $worker, string $schedule, ?string $day = null): array
    {
        try {
            $profile = $worker->workerProfile;

            if (!$profile || !$profile->stripe_connect_account_id) {
                return [
                    'success' => false,
                    'error' => 'Payment account not set up.',
                ];
            }

            // Validate schedule
            $validSchedules = ['daily', 'weekly', 'monthly'];
            if (!in_array($schedule, $validSchedules)) {
                return [
                    'success' => false,
                    'error' => 'Invalid payout schedule.',
                ];
            }

            // Update Stripe account
            $scheduleConfig = ['interval' => $schedule];

            if ($schedule === 'weekly' && $day) {
                $scheduleConfig['weekly_anchor'] = strtolower($day);
            }

            if ($schedule === 'monthly' && $day) {
                $dayNumber = is_numeric($day) ? (int)$day : 1;
                $scheduleConfig['monthly_anchor'] = min(28, max(1, $dayNumber));
            }

            $this->stripe->accounts->update($profile->stripe_connect_account_id, [
                'settings' => [
                    'payouts' => [
                        'schedule' => $scheduleConfig,
                    ],
                ],
            ]);

            // Update local profile
            $profile->update([
                'payout_schedule' => $schedule,
                'payout_day' => $day,
            ]);

            return [
                'success' => true,
                'schedule' => $schedule,
                'day' => $day,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to update payout schedule', [
                'worker_id' => $worker->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $this->formatStripeError($e),
            ];
        }
    }

    /**
     * Create a Stripe Express dashboard login link.
     *
     * @param User $worker
     * @return array
     */
    public function createDashboardLink(User $worker): array
    {
        try {
            $profile = $worker->workerProfile;

            if (!$profile || !$profile->stripe_connect_account_id) {
                return [
                    'success' => false,
                    'error' => 'Payment account not set up.',
                ];
            }

            $loginLink = $this->stripe->accounts->createLoginLink(
                $profile->stripe_connect_account_id
            );

            return [
                'success' => true,
                'url' => $loginLink->url,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe dashboard link for worker', [
                'worker_id' => $worker->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $this->formatStripeError($e),
            ];
        }
    }

    /**
     * Handle Stripe Connect webhook events.
     *
     * @param \Stripe\Event $event
     * @return array
     */
    public function handleWebhook(\Stripe\Event $event): array
    {
        $account = $event->data->object;

        // Find the worker by Stripe account ID
        $profile = WorkerProfile::where('stripe_connect_account_id', $account->id)->first();

        if (!$profile) {
            return [
                'success' => false,
                'message' => 'Worker profile not found for this Stripe account.',
            ];
        }

        switch ($event->type) {
            case 'account.updated':
                return $this->handleAccountUpdated($profile, $account);

            case 'account.application.deauthorized':
                return $this->handleAccountDeauthorized($profile);

            default:
                return [
                    'success' => true,
                    'message' => 'Event type not handled.',
                ];
        }
    }

    /**
     * Handle account.updated webhook.
     */
    protected function handleAccountUpdated(WorkerProfile $profile, $account): array
    {
        $profile->update([
            'stripe_charges_enabled' => $account->charges_enabled,
            'stripe_payouts_enabled' => $account->payouts_enabled,
            'stripe_details_submitted' => $account->details_submitted,
            'stripe_requirements_current' => $account->requirements?->currently_due ?? [],
            'stripe_requirements_eventually_due' => $account->requirements?->eventually_due ?? [],
            'stripe_disabled_reason' => $account->requirements?->disabled_reason,
        ]);

        if ($account->details_submitted && $account->payouts_enabled && !$profile->stripe_onboarding_complete) {
            $profile->update([
                'stripe_onboarding_complete' => true,
                'stripe_onboarding_completed_at' => now(),
            ]);

            $profile->user?->update([
                'completed_stripe_onboarding' => true,
            ]);

            // Send notification
            try {
                $profile->user?->notify(new PaymentSetupCompleteNotification());
            } catch (\Exception $e) {
                Log::warning('Failed to send payment setup notification', [
                    'profile_id' => $profile->id,
                ]);
            }
        }

        return ['success' => true, 'message' => 'Account updated.'];
    }

    /**
     * Handle account.application.deauthorized webhook.
     */
    protected function handleAccountDeauthorized(WorkerProfile $profile): array
    {
        $profile->update([
            'stripe_connect_account_id' => null,
            'stripe_onboarding_complete' => false,
            'stripe_payouts_enabled' => false,
            'stripe_charges_enabled' => false,
            'stripe_details_submitted' => false,
        ]);

        $profile->user?->update([
            'stripe_connect_id' => null,
            'completed_stripe_onboarding' => false,
        ]);

        // Send notification that they need to reconnect
        try {
            $profile->user?->notify(new PaymentSetupRequiredNotification());
        } catch (\Exception $e) {
            Log::warning('Failed to send payment setup required notification', [
                'profile_id' => $profile->id,
            ]);
        }

        return ['success' => true, 'message' => 'Account deauthorized.'];
    }

    /**
     * Determine account status from Stripe account object.
     */
    protected function determineAccountStatus(\Stripe\Account $account): string
    {
        if (!$account->details_submitted) {
            return 'pending_details';
        }

        if (!empty($account->requirements?->currently_due)) {
            return 'pending_verification';
        }

        if (!$account->payouts_enabled) {
            return 'restricted';
        }

        return 'active';
    }

    /**
     * Get country code for a worker.
     */
    protected function getCountryCode(User $worker): string
    {
        $profile = $worker->workerProfile;

        $country = $profile?->location_country ??
                   $profile?->country ??
                   $worker->country()?->country_code ?? null;

        if ($country) {
            return $this->countryNameToCode($country);
        }

        return 'US';
    }

    /**
     * Convert country name to ISO code.
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
        ];

        $normalized = strtolower(trim($country));

        if (strlen($country) === 2) {
            return strtoupper($country);
        }

        return $countryMap[$normalized] ?? 'US';
    }

    /**
     * Format Stripe requirement for display.
     */
    protected function formatRequirement(string $requirement): string
    {
        $friendlyNames = [
            'individual.first_name' => 'First name',
            'individual.last_name' => 'Last name',
            'individual.dob.day' => 'Date of birth',
            'individual.dob.month' => 'Date of birth',
            'individual.dob.year' => 'Date of birth',
            'individual.address.line1' => 'Address',
            'individual.address.city' => 'City',
            'individual.address.state' => 'State/Province',
            'individual.address.postal_code' => 'Postal/ZIP code',
            'individual.phone' => 'Phone number',
            'individual.ssn_last_4' => 'Last 4 of SSN',
            'individual.id_number' => 'ID number',
            'external_account' => 'Bank account',
            'tos_acceptance' => 'Terms of service acceptance',
            'business_profile.url' => 'Business website',
            'business_profile.mcc' => 'Business category',
        ];

        foreach ($friendlyNames as $key => $name) {
            if (str_contains($requirement, $key)) {
                return $name;
            }
        }

        return ucwords(str_replace(['_', '.'], ' ', $requirement));
    }

    /**
     * Format Stripe error for display.
     */
    protected function formatStripeError(ApiErrorException $e): string
    {
        $stripeCode = $e->getStripeCode();

        $friendlyMessages = [
            'account_invalid' => 'The Stripe account is invalid or has been deleted.',
            'account_country_invalid' => 'Your country is not yet supported for payouts.',
            'balance_insufficient' => 'Insufficient balance for this operation.',
            'bank_account_declined' => 'Your bank account declined the connection.',
            'bank_account_unusable' => 'This bank account cannot receive transfers.',
            'instant_payouts_unsupported' => 'Instant payouts are not available for your account.',
        ];

        if ($stripeCode && isset($friendlyMessages[$stripeCode])) {
            return $friendlyMessages[$stripeCode];
        }

        $message = $e->getMessage();
        $message = preg_replace('/acct_[a-zA-Z0-9]+/', '[account_id]', $message);

        return $message;
    }
}
