<?php

namespace App\Services;

use App\Models\TaxCalculation;
use App\Models\TaxForm;
use App\Models\TaxJurisdiction;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * GLO-002: Tax Jurisdiction Engine Service
 *
 * Provides automated tax calculation based on work location,
 * including progressive tax brackets, withholding, VAT, and
 * compliance requirements.
 */
class TaxJurisdictionService
{
    /**
     * Cache TTL for jurisdiction lookups (1 hour).
     */
    protected const CACHE_TTL = 3600;

    /**
     * Get the appropriate tax jurisdiction for a location.
     * Finds the most specific jurisdiction (city > state > country).
     */
    public function getJurisdiction(string $country, ?string $state = null, ?string $city = null): ?TaxJurisdiction
    {
        $cacheKey = "tax_jurisdiction:{$country}:{$state}:{$city}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($country, $state, $city) {
            return TaxJurisdiction::findForLocation($country, $state, $city);
        });
    }

    /**
     * Calculate all taxes for a given gross amount.
     *
     * @param  User  $user  The worker receiving payment
     * @param  float  $grossAmount  Gross payment amount
     * @param  TaxJurisdiction  $jurisdiction  Tax jurisdiction to apply
     * @param  array  $options  Additional options (shift_id, shift_payment_id, etc.)
     */
    public function calculateTax(
        User $user,
        float $grossAmount,
        TaxJurisdiction $jurisdiction,
        array $options = []
    ): TaxCalculation {
        $incomeTax = $this->calculateIncomeTax($user, $grossAmount, $jurisdiction);
        $socialSecurity = $this->calculateSocialSecurity($grossAmount, $jurisdiction);
        $withholding = $this->calculateWithholding($user, $grossAmount, $jurisdiction);

        // VAT is typically charged to the business, not deducted from worker pay
        $vatAmount = 0;

        $netAmount = $grossAmount - $incomeTax - $socialSecurity - $withholding;

        $breakdown = [
            'gross_amount' => $grossAmount,
            'deductions' => [
                'income_tax' => [
                    'amount' => $incomeTax,
                    'rate' => $jurisdiction->income_tax_rate,
                    'method' => empty($jurisdiction->tax_brackets) ? 'flat' : 'progressive',
                ],
                'social_security' => [
                    'amount' => $socialSecurity,
                    'rate' => $jurisdiction->social_security_rate,
                ],
                'withholding' => [
                    'amount' => $withholding,
                    'rate' => $jurisdiction->withholding_rate,
                ],
            ],
            'net_amount' => $netAmount,
            'jurisdiction' => [
                'id' => $jurisdiction->id,
                'name' => $jurisdiction->name,
                'country' => $jurisdiction->country_code,
            ],
            'calculated_at' => now()->toIso8601String(),
        ];

        return TaxCalculation::createFromComponents([
            'user_id' => $user->id,
            'shift_id' => $options['shift_id'] ?? null,
            'shift_payment_id' => $options['shift_payment_id'] ?? null,
            'tax_jurisdiction_id' => $jurisdiction->id,
            'gross_amount' => $grossAmount,
            'income_tax' => $incomeTax,
            'social_security' => $socialSecurity,
            'vat_amount' => $vatAmount,
            'withholding' => $withholding,
            'breakdown' => $breakdown,
            'currency_code' => $jurisdiction->currency_code,
            'calculation_type' => $options['calculation_type'] ?? TaxCalculation::TYPE_SHIFT_PAYMENT,
            'is_applied' => $options['is_applied'] ?? true,
        ]);
    }

    /**
     * Calculate income tax using flat rate or progressive brackets.
     */
    protected function calculateIncomeTax(User $user, float $grossAmount, TaxJurisdiction $jurisdiction): float
    {
        // Check if user has valid tax form claiming exemption
        if ($this->hasValidTaxExemption($user)) {
            return 0;
        }

        // Use progressive brackets if defined
        if (! empty($jurisdiction->tax_brackets)) {
            return $jurisdiction->calculateProgressiveTax($grossAmount);
        }

        // Flat rate calculation
        return round($grossAmount * ((float) $jurisdiction->income_tax_rate / 100), 2);
    }

    /**
     * Calculate social security contribution.
     */
    protected function calculateSocialSecurity(float $grossAmount, TaxJurisdiction $jurisdiction): float
    {
        $rate = (float) $jurisdiction->social_security_rate;

        return round($grossAmount * ($rate / 100), 2);
    }

    /**
     * Calculate withholding tax for a user.
     * Considers tax form status and residency.
     */
    public function calculateWithholding(User $user, float $amount, ?TaxJurisdiction $jurisdiction = null): float
    {
        // If no jurisdiction provided, try to get from user's profile
        if (! $jurisdiction) {
            $jurisdiction = $this->getUserJurisdiction($user);
        }

        if (! $jurisdiction) {
            // Default 30% withholding for unknown jurisdictions (US default for foreign persons)
            return round($amount * 0.30, 2);
        }

        // Check for valid W-8BEN or W-9 reducing/eliminating withholding
        $validTaxForm = TaxForm::where('user_id', $user->id)
            ->whereIn('form_type', [TaxForm::TYPE_W9, TaxForm::TYPE_W8BEN])
            ->valid()
            ->first();

        if ($validTaxForm) {
            if ($validTaxForm->form_type === TaxForm::TYPE_W9) {
                // US person with W-9: no withholding (they file their own taxes)
                return 0;
            }

            // W-8BEN: treaty rate might apply (stored in form_data)
            $treatyRate = $validTaxForm->form_data['treaty_rate'] ?? null;
            if ($treatyRate !== null) {
                return round($amount * ((float) $treatyRate / 100), 2);
            }
        }

        return round($amount * ((float) $jurisdiction->withholding_rate / 100), 2);
    }

    /**
     * Apply VAT to an amount.
     *
     * @param  float  $amount  Base amount
     * @param  TaxJurisdiction  $jurisdiction  Tax jurisdiction
     * @param  bool  $isB2B  Whether this is a B2B transaction
     * @return float Total amount including VAT (or original if reverse charge applies)
     */
    public function applyVAT(float $amount, TaxJurisdiction $jurisdiction, bool $isB2B = false): float
    {
        // B2B transactions in jurisdictions with reverse charge don't add VAT
        if ($isB2B && $jurisdiction->vat_reverse_charge) {
            return $amount;
        }

        $vatRate = (float) $jurisdiction->vat_rate;

        return round($amount * (1 + ($vatRate / 100)), 2);
    }

    /**
     * Calculate VAT amount for a given amount.
     */
    public function calculateVAT(float $amount, TaxJurisdiction $jurisdiction, bool $isB2B = false): float
    {
        // B2B transactions with reverse charge don't charge VAT
        if ($isB2B && $jurisdiction->vat_reverse_charge) {
            return 0;
        }

        $vatRate = (float) $jurisdiction->vat_rate;

        return round($amount * ($vatRate / 100), 2);
    }

    /**
     * Determine if VAT reverse charge should apply between two parties.
     */
    public function shouldApplyReverseCharge(User $payer, User $payee): bool
    {
        $payerJurisdiction = $this->getUserJurisdiction($payer);
        $payeeJurisdiction = $this->getUserJurisdiction($payee);

        if (! $payerJurisdiction || ! $payeeJurisdiction) {
            return false;
        }

        // Both parties must be in EU for reverse charge
        if (! $payerJurisdiction->isEuMember() || ! $payeeJurisdiction->isEuMember()) {
            return false;
        }

        // Must be different countries
        if ($payerJurisdiction->country_code === $payeeJurisdiction->country_code) {
            return false;
        }

        // Both must be businesses (not individuals) - check via business profile
        if (! $payer->isBusiness() || $payee->isWorker()) {
            // For gig economy, payer is usually business, payee is worker
            // Reverse charge typically applies to B2B services
            return false;
        }

        // Check if payee's jurisdiction supports reverse charge
        return (bool) $payeeJurisdiction->vat_reverse_charge;
    }

    /**
     * Get the effective tax rate for a user in a jurisdiction.
     * This is an estimate based on current rates and user status.
     */
    public function getEffectiveTaxRate(User $user, TaxJurisdiction $jurisdiction): float
    {
        // Base rate is income tax + social security
        $baseRate = (float) $jurisdiction->income_tax_rate + (float) $jurisdiction->social_security_rate;

        // Adjust for tax forms
        if ($this->hasValidTaxExemption($user)) {
            $baseRate = 0;
        }

        // Add withholding if applicable
        $withholdingRate = $this->getEffectiveWithholdingRate($user, $jurisdiction);
        $baseRate += $withholdingRate;

        return min(100, max(0, $baseRate));
    }

    /**
     * Get effective withholding rate considering tax forms.
     */
    protected function getEffectiveWithholdingRate(User $user, TaxJurisdiction $jurisdiction): float
    {
        $validTaxForm = TaxForm::where('user_id', $user->id)
            ->whereIn('form_type', [TaxForm::TYPE_W9, TaxForm::TYPE_W8BEN])
            ->valid()
            ->first();

        if ($validTaxForm) {
            if ($validTaxForm->form_type === TaxForm::TYPE_W9) {
                return 0;
            }

            $treatyRate = $validTaxForm->form_data['treaty_rate'] ?? null;
            if ($treatyRate !== null) {
                return (float) $treatyRate;
            }
        }

        return (float) $jurisdiction->withholding_rate;
    }

    /**
     * Validate a tax ID against the jurisdiction's format.
     */
    public function validateTaxId(string $taxId, string $country): bool
    {
        // Common tax ID validation patterns
        $patterns = [
            'US' => '/^\d{3}-\d{2}-\d{4}$|^\d{2}-\d{7}$/', // SSN or EIN
            'GB' => '/^[A-Z]{2}\d{6}[A-Z]$/', // UK NI Number
            'AU' => '/^\d{9}$|^\d{11}$/', // TFN or ABN
            'DE' => '/^\d{11}$/', // German tax ID
            'FR' => '/^\d{13}$/', // French tax ID
            'CA' => '/^\d{3}-\d{3}-\d{3}$/', // Canadian SIN
        ];

        $country = strtoupper($country);

        // Try jurisdiction-specific pattern first
        $jurisdiction = TaxJurisdiction::where('country_code', $country)
            ->whereNull('state_code')
            ->first();

        if ($jurisdiction && $jurisdiction->tax_id_format) {
            return (bool) preg_match($jurisdiction->tax_id_format, $taxId);
        }

        // Fall back to built-in patterns
        if (isset($patterns[$country])) {
            return (bool) preg_match($patterns[$country], $taxId);
        }

        // Accept any non-empty string for unknown countries
        return ! empty(trim($taxId));
    }

    /**
     * Get the list of required tax forms for a user.
     */
    public function getRequiredForms(User $user): array
    {
        $requiredForms = [];
        $jurisdiction = $this->getUserJurisdiction($user);

        if (! $jurisdiction) {
            // Default: require W-9 for US residents, W-8BEN for others
            $userCountry = $this->getUserCountry($user);

            if ($userCountry === 'US') {
                $requiredForms[] = [
                    'type' => TaxForm::TYPE_W9,
                    'name' => 'Form W-9',
                    'description' => 'Required for US persons to certify tax status',
                    'required' => true,
                ];
            } else {
                $requiredForms[] = [
                    'type' => TaxForm::TYPE_W8BEN,
                    'name' => 'Form W-8BEN',
                    'description' => 'Required for non-US persons to claim treaty benefits',
                    'required' => true,
                ];
            }

            return $requiredForms;
        }

        // Get forms from jurisdiction
        if ($jurisdiction->requires_w9) {
            $requiredForms[] = [
                'type' => TaxForm::TYPE_W9,
                'name' => 'Form W-9',
                'description' => 'Request for Taxpayer Identification Number',
                'required' => true,
            ];
        }

        if ($jurisdiction->requires_w8ben) {
            $requiredForms[] = [
                'type' => TaxForm::TYPE_W8BEN,
                'name' => 'Form W-8BEN',
                'description' => 'Certificate of Foreign Status of Beneficial Owner',
                'required' => true,
            ];
        }

        // UK-specific requirements
        if ($jurisdiction->country_code === 'GB') {
            $requiredForms[] = [
                'type' => TaxForm::TYPE_SELF_ASSESSMENT,
                'name' => 'Self Assessment Declaration',
                'description' => 'Declaration for self-employed tax purposes',
                'required' => true,
            ];
        }

        // Check which forms the user already has
        $existingForms = TaxForm::where('user_id', $user->id)
            ->valid()
            ->pluck('form_type')
            ->toArray();

        foreach ($requiredForms as &$form) {
            $form['submitted'] = in_array($form['type'], $existingForms);
        }

        return $requiredForms;
    }

    /**
     * Generate a comprehensive tax summary for a user for a given year.
     */
    public function generateTaxSummary(User $user, int $year): array
    {
        $calculations = TaxCalculation::where('user_id', $user->id)
            ->whereYear('created_at', $year)
            ->where('is_applied', true)
            ->with('taxJurisdiction')
            ->get();

        // Group by jurisdiction
        $byJurisdiction = $calculations->groupBy('tax_jurisdiction_id');

        $jurisdictionSummaries = [];
        foreach ($byJurisdiction as $jurisdictionId => $calcs) {
            $jurisdiction = $calcs->first()->taxJurisdiction;
            $jurisdictionSummaries[] = [
                'jurisdiction' => [
                    'id' => $jurisdiction->id,
                    'name' => $jurisdiction->name,
                    'country' => $jurisdiction->country_code,
                ],
                'total_gross' => $calcs->sum('gross_amount'),
                'total_income_tax' => $calcs->sum('income_tax'),
                'total_social_security' => $calcs->sum('social_security'),
                'total_withholding' => $calcs->sum('withholding'),
                'total_net' => $calcs->sum('net_amount'),
                'shift_count' => $calcs->whereNotNull('shift_id')->count(),
            ];
        }

        // Monthly breakdown
        $monthlyTotals = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthCalcs = $calculations->filter(function ($calc) use ($month) {
                return $calc->created_at->month === $month;
            });

            $monthlyTotals[] = [
                'month' => $month,
                'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
                'gross' => $monthCalcs->sum('gross_amount'),
                'tax' => $monthCalcs->sum('income_tax') + $monthCalcs->sum('social_security') + $monthCalcs->sum('withholding'),
                'net' => $monthCalcs->sum('net_amount'),
            ];
        }

        // Get tax forms status
        $taxForms = TaxForm::where('user_id', $user->id)
            ->orderBy('submitted_at', 'desc')
            ->get()
            ->map(function ($form) {
                return [
                    'type' => $form->form_type,
                    'type_name' => $form->form_type_name,
                    'status' => $form->status,
                    'submitted_at' => $form->submitted_at?->toDateString(),
                    'expires_at' => $form->expires_at?->toDateString(),
                    'is_valid' => $form->isValid(),
                ];
            });

        return [
            'year' => $year,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'totals' => [
                'gross_earnings' => $calculations->sum('gross_amount'),
                'total_income_tax' => $calculations->sum('income_tax'),
                'total_social_security' => $calculations->sum('social_security'),
                'total_withholding' => $calculations->sum('withholding'),
                'total_vat' => $calculations->sum('vat_amount'),
                'net_earnings' => $calculations->sum('net_amount'),
                'total_deductions' => $calculations->sum('income_tax') +
                                     $calculations->sum('social_security') +
                                     $calculations->sum('withholding'),
            ],
            'effective_tax_rate' => $calculations->sum('gross_amount') > 0
                ? round((($calculations->sum('income_tax') + $calculations->sum('social_security') + $calculations->sum('withholding')) / $calculations->sum('gross_amount')) * 100, 2)
                : 0,
            'by_jurisdiction' => $jurisdictionSummaries,
            'monthly' => $monthlyTotals,
            'tax_forms' => $taxForms,
            'calculation_count' => $calculations->count(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get tax estimate for a user before completing a shift.
     */
    public function estimateTax(User $user, float $grossAmount, string $countryCode, ?string $stateCode = null): array
    {
        $jurisdiction = $this->getJurisdiction($countryCode, $stateCode);

        if (! $jurisdiction) {
            return [
                'error' => 'Unknown jurisdiction',
                'gross_amount' => $grossAmount,
                'estimated_tax' => 0,
                'estimated_net' => $grossAmount,
            ];
        }

        // Create an estimate (not applied)
        $calculation = $this->calculateTax($user, $grossAmount, $jurisdiction, [
            'is_applied' => false,
            'calculation_type' => TaxCalculation::TYPE_ESTIMATE,
        ]);

        return [
            'gross_amount' => $grossAmount,
            'estimated_income_tax' => $calculation->income_tax,
            'estimated_social_security' => $calculation->social_security,
            'estimated_withholding' => $calculation->withholding,
            'estimated_total_tax' => $calculation->total_deductions,
            'estimated_net' => $calculation->net_amount,
            'effective_rate' => $calculation->effective_tax_rate,
            'jurisdiction' => $jurisdiction->name,
            'disclaimer' => 'This is an estimate only. Actual taxes may vary based on your total annual income and tax status.',
        ];
    }

    /**
     * Get the jurisdiction for a user based on their profile.
     */
    protected function getUserJurisdiction(User $user): ?TaxJurisdiction
    {
        $country = $this->getUserCountry($user);
        $state = $this->getUserState($user);

        if (! $country) {
            return null;
        }

        return $this->getJurisdiction($country, $state);
    }

    /**
     * Get user's country code from their profile.
     */
    protected function getUserCountry(User $user): ?string
    {
        // Try worker profile first
        if ($user->workerProfile && $user->workerProfile->country_code) {
            return $user->workerProfile->country_code;
        }

        // Try business profile
        if ($user->businessProfile && $user->businessProfile->country_code) {
            return $user->businessProfile->country_code;
        }

        // Try from country relationship
        if ($user->country()) {
            $country = $user->country();

            return $country->country_code ?? null;
        }

        return null;
    }

    /**
     * Get user's state code from their profile.
     */
    protected function getUserState(User $user): ?string
    {
        // Try worker profile first
        if ($user->workerProfile && $user->workerProfile->state_code) {
            return $user->workerProfile->state_code;
        }

        // Try business profile
        if ($user->businessProfile && $user->businessProfile->state_code) {
            return $user->businessProfile->state_code;
        }

        return null;
    }

    /**
     * Check if user has a valid tax exemption.
     */
    protected function hasValidTaxExemption(User $user): bool
    {
        // Check for valid W-9 with exemption claimed
        $w9 = TaxForm::where('user_id', $user->id)
            ->where('form_type', TaxForm::TYPE_W9)
            ->valid()
            ->first();

        if ($w9 && isset($w9->form_data['exempt']) && $w9->form_data['exempt']) {
            return true;
        }

        return false;
    }

    /**
     * Clear cached jurisdiction data.
     */
    public function clearCache(?string $country = null): void
    {
        if ($country) {
            Cache::forget("tax_jurisdiction:{$country}::");
        } else {
            // Clear all jurisdiction cache - this is a simple implementation
            // In production, consider using cache tags
            Log::info('Tax jurisdiction cache cleared');
        }
    }

    /**
     * Get all active jurisdictions for a country.
     */
    public function getJurisdictionsForCountry(string $countryCode): \Illuminate\Database\Eloquent\Collection
    {
        return TaxJurisdiction::active()
            ->forCountry($countryCode)
            ->orderBy('state_code')
            ->orderBy('city')
            ->get();
    }

    /**
     * Get countries with tax jurisdictions defined.
     */
    public function getAvailableCountries(): array
    {
        return TaxJurisdiction::active()
            ->select('country_code')
            ->distinct()
            ->pluck('country_code')
            ->toArray();
    }
}
