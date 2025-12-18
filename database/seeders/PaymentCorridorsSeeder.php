<?php

namespace Database\Seeders;

use App\Models\PaymentCorridor;
use Illuminate\Database\Seeder;

/**
 * GLO-008: Cross-Border Payments - Payment Corridors Seeder
 *
 * Seeds default payment corridors for:
 * - SEPA transfers within EU/EEA
 * - ACH transfers within US
 * - Faster Payments within UK
 * - SWIFT for international transfers
 */
class PaymentCorridorsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing corridors
        PaymentCorridor::truncate();

        $corridors = [];

        // US Domestic (ACH)
        $corridors = array_merge($corridors, $this->getUsDomesticCorridors());

        // UK Domestic (Faster Payments)
        $corridors = array_merge($corridors, $this->getUkDomesticCorridors());

        // SEPA Corridors (EU/EEA)
        $corridors = array_merge($corridors, $this->getSepaCorridors());

        // International SWIFT Corridors
        $corridors = array_merge($corridors, $this->getSwiftCorridors());

        // Local corridors for key markets
        $corridors = array_merge($corridors, $this->getLocalCorridors());

        // Insert all corridors
        foreach ($corridors as $corridor) {
            PaymentCorridor::create($corridor);
        }

        $this->command->info('Created '.count($corridors).' payment corridors.');
    }

    /**
     * US Domestic ACH Corridors.
     */
    protected function getUsDomesticCorridors(): array
    {
        return [
            [
                'source_country' => 'US',
                'destination_country' => 'US',
                'source_currency' => 'USD',
                'destination_currency' => 'USD',
                'payment_method' => PaymentCorridor::METHOD_ACH,
                'estimated_days_min' => 1,
                'estimated_days_max' => 3,
                'fee_fixed' => 0.00,
                'fee_percent' => 0.25,
                'min_amount' => 1.00,
                'max_amount' => 100000.00,
                'is_active' => true,
            ],
            // Instant ACH (same-day)
            [
                'source_country' => 'US',
                'destination_country' => 'US',
                'source_currency' => 'USD',
                'destination_currency' => 'USD',
                'payment_method' => PaymentCorridor::METHOD_LOCAL,
                'estimated_days_min' => 0,
                'estimated_days_max' => 1,
                'fee_fixed' => 1.50,
                'fee_percent' => 0.50,
                'min_amount' => 1.00,
                'max_amount' => 25000.00,
                'is_active' => true,
            ],
        ];
    }

    /**
     * UK Domestic Faster Payments Corridors.
     */
    protected function getUkDomesticCorridors(): array
    {
        return [
            [
                'source_country' => 'GB',
                'destination_country' => 'GB',
                'source_currency' => 'GBP',
                'destination_currency' => 'GBP',
                'payment_method' => PaymentCorridor::METHOD_FASTER_PAYMENTS,
                'estimated_days_min' => 0,
                'estimated_days_max' => 1,
                'fee_fixed' => 0.00,
                'fee_percent' => 0.20,
                'min_amount' => 1.00,
                'max_amount' => 250000.00,
                'is_active' => true,
            ],
        ];
    }

    /**
     * SEPA Corridors for EU/EEA countries.
     */
    protected function getSepaCorridors(): array
    {
        $sepaCountries = PaymentCorridor::SEPA_COUNTRIES;
        $corridors = [];

        // Create SEPA corridors from key source countries
        $sourceCountries = ['US', 'GB'];

        foreach ($sourceCountries as $source) {
            $sourceCurrency = $source === 'US' ? 'USD' : 'GBP';

            foreach ($sepaCountries as $dest) {
                // Skip GB for SEPA (use Faster Payments)
                if ($dest === 'GB') {
                    continue;
                }

                $corridors[] = [
                    'source_country' => $source,
                    'destination_country' => $dest,
                    'source_currency' => $sourceCurrency,
                    'destination_currency' => 'EUR',
                    'payment_method' => PaymentCorridor::METHOD_SEPA,
                    'estimated_days_min' => 1,
                    'estimated_days_max' => 2,
                    'fee_fixed' => 0.50,
                    'fee_percent' => 0.35,
                    'min_amount' => 10.00,
                    'max_amount' => 50000.00,
                    'is_active' => true,
                ];
            }
        }

        // Intra-SEPA (EUR to EUR) - fastest and cheapest
        $majorSepaCountries = ['DE', 'FR', 'ES', 'IT', 'NL', 'BE', 'AT', 'IE', 'PT'];
        foreach ($majorSepaCountries as $dest) {
            $corridors[] = [
                'source_country' => 'EU', // Generic EU source
                'destination_country' => $dest,
                'source_currency' => 'EUR',
                'destination_currency' => 'EUR',
                'payment_method' => PaymentCorridor::METHOD_SEPA,
                'estimated_days_min' => 0,
                'estimated_days_max' => 1,
                'fee_fixed' => 0.20,
                'fee_percent' => 0.10,
                'min_amount' => 1.00,
                'max_amount' => 100000.00,
                'is_active' => true,
            ];
        }

        return $corridors;
    }

    /**
     * SWIFT Corridors for international transfers.
     */
    protected function getSwiftCorridors(): array
    {
        $corridors = [];

        // Key destination countries from US
        $usDestinations = [
            'AU' => ['currency' => 'AUD', 'fee_fixed' => 15.00, 'fee_percent' => 0.50, 'days_min' => 2, 'days_max' => 5],
            'CA' => ['currency' => 'CAD', 'fee_fixed' => 10.00, 'fee_percent' => 0.40, 'days_min' => 1, 'days_max' => 3],
            'JP' => ['currency' => 'JPY', 'fee_fixed' => 20.00, 'fee_percent' => 0.60, 'days_min' => 2, 'days_max' => 4],
            'SG' => ['currency' => 'SGD', 'fee_fixed' => 15.00, 'fee_percent' => 0.50, 'days_min' => 2, 'days_max' => 4],
            'HK' => ['currency' => 'HKD', 'fee_fixed' => 15.00, 'fee_percent' => 0.50, 'days_min' => 2, 'days_max' => 4],
            'IN' => ['currency' => 'INR', 'fee_fixed' => 10.00, 'fee_percent' => 0.75, 'days_min' => 2, 'days_max' => 5],
            'MX' => ['currency' => 'MXN', 'fee_fixed' => 8.00, 'fee_percent' => 0.60, 'days_min' => 1, 'days_max' => 3],
            'BR' => ['currency' => 'BRL', 'fee_fixed' => 12.00, 'fee_percent' => 0.80, 'days_min' => 2, 'days_max' => 5],
            'ZA' => ['currency' => 'ZAR', 'fee_fixed' => 15.00, 'fee_percent' => 0.75, 'days_min' => 2, 'days_max' => 5],
            'NG' => ['currency' => 'NGN', 'fee_fixed' => 10.00, 'fee_percent' => 1.00, 'days_min' => 2, 'days_max' => 5],
            'KE' => ['currency' => 'KES', 'fee_fixed' => 10.00, 'fee_percent' => 1.00, 'days_min' => 2, 'days_max' => 5],
            'PH' => ['currency' => 'PHP', 'fee_fixed' => 10.00, 'fee_percent' => 0.75, 'days_min' => 2, 'days_max' => 5],
            'GB' => ['currency' => 'GBP', 'fee_fixed' => 10.00, 'fee_percent' => 0.40, 'days_min' => 1, 'days_max' => 3],
        ];

        foreach ($usDestinations as $country => $config) {
            $corridors[] = [
                'source_country' => 'US',
                'destination_country' => $country,
                'source_currency' => 'USD',
                'destination_currency' => $config['currency'],
                'payment_method' => PaymentCorridor::METHOD_SWIFT,
                'estimated_days_min' => $config['days_min'],
                'estimated_days_max' => $config['days_max'],
                'fee_fixed' => $config['fee_fixed'],
                'fee_percent' => $config['fee_percent'],
                'min_amount' => 50.00,
                'max_amount' => 250000.00,
                'is_active' => true,
            ];
        }

        // Key destinations from UK
        $gbDestinations = [
            'US' => ['currency' => 'USD', 'fee_fixed' => 10.00, 'fee_percent' => 0.40, 'days_min' => 1, 'days_max' => 3],
            'AU' => ['currency' => 'AUD', 'fee_fixed' => 12.00, 'fee_percent' => 0.50, 'days_min' => 2, 'days_max' => 4],
            'CA' => ['currency' => 'CAD', 'fee_fixed' => 10.00, 'fee_percent' => 0.40, 'days_min' => 1, 'days_max' => 3],
            'IN' => ['currency' => 'INR', 'fee_fixed' => 8.00, 'fee_percent' => 0.70, 'days_min' => 2, 'days_max' => 5],
            'NG' => ['currency' => 'NGN', 'fee_fixed' => 8.00, 'fee_percent' => 0.90, 'days_min' => 2, 'days_max' => 5],
            'PH' => ['currency' => 'PHP', 'fee_fixed' => 8.00, 'fee_percent' => 0.70, 'days_min' => 2, 'days_max' => 5],
        ];

        foreach ($gbDestinations as $country => $config) {
            $corridors[] = [
                'source_country' => 'GB',
                'destination_country' => $country,
                'source_currency' => 'GBP',
                'destination_currency' => $config['currency'],
                'payment_method' => PaymentCorridor::METHOD_SWIFT,
                'estimated_days_min' => $config['days_min'],
                'estimated_days_max' => $config['days_max'],
                'fee_fixed' => $config['fee_fixed'],
                'fee_percent' => $config['fee_percent'],
                'min_amount' => 50.00,
                'max_amount' => 250000.00,
                'is_active' => true,
            ];
        }

        return $corridors;
    }

    /**
     * Local payment corridors for specific markets.
     */
    protected function getLocalCorridors(): array
    {
        return [
            // Australia domestic
            [
                'source_country' => 'AU',
                'destination_country' => 'AU',
                'source_currency' => 'AUD',
                'destination_currency' => 'AUD',
                'payment_method' => PaymentCorridor::METHOD_LOCAL,
                'estimated_days_min' => 1,
                'estimated_days_max' => 2,
                'fee_fixed' => 0.00,
                'fee_percent' => 0.20,
                'min_amount' => 1.00,
                'max_amount' => 100000.00,
                'is_active' => true,
            ],
            // Canada domestic
            [
                'source_country' => 'CA',
                'destination_country' => 'CA',
                'source_currency' => 'CAD',
                'destination_currency' => 'CAD',
                'payment_method' => PaymentCorridor::METHOD_LOCAL,
                'estimated_days_min' => 1,
                'estimated_days_max' => 2,
                'fee_fixed' => 0.00,
                'fee_percent' => 0.25,
                'min_amount' => 1.00,
                'max_amount' => 100000.00,
                'is_active' => true,
            ],
            // India domestic (IMPS/UPI style)
            [
                'source_country' => 'IN',
                'destination_country' => 'IN',
                'source_currency' => 'INR',
                'destination_currency' => 'INR',
                'payment_method' => PaymentCorridor::METHOD_LOCAL,
                'estimated_days_min' => 0,
                'estimated_days_max' => 1,
                'fee_fixed' => 0.00,
                'fee_percent' => 0.15,
                'min_amount' => 1.00,
                'max_amount' => 1000000.00,
                'is_active' => true,
            ],
            // Nigeria domestic
            [
                'source_country' => 'NG',
                'destination_country' => 'NG',
                'source_currency' => 'NGN',
                'destination_currency' => 'NGN',
                'payment_method' => PaymentCorridor::METHOD_LOCAL,
                'estimated_days_min' => 0,
                'estimated_days_max' => 1,
                'fee_fixed' => 0.00,
                'fee_percent' => 0.50,
                'min_amount' => 100.00,
                'max_amount' => 10000000.00,
                'is_active' => true,
            ],
            // Kenya domestic (M-Pesa style)
            [
                'source_country' => 'KE',
                'destination_country' => 'KE',
                'source_currency' => 'KES',
                'destination_currency' => 'KES',
                'payment_method' => PaymentCorridor::METHOD_LOCAL,
                'estimated_days_min' => 0,
                'estimated_days_max' => 1,
                'fee_fixed' => 0.00,
                'fee_percent' => 0.50,
                'min_amount' => 10.00,
                'max_amount' => 1000000.00,
                'is_active' => true,
            ],
            // Philippines domestic
            [
                'source_country' => 'PH',
                'destination_country' => 'PH',
                'source_currency' => 'PHP',
                'destination_currency' => 'PHP',
                'payment_method' => PaymentCorridor::METHOD_LOCAL,
                'estimated_days_min' => 0,
                'estimated_days_max' => 1,
                'fee_fixed' => 0.00,
                'fee_percent' => 0.25,
                'min_amount' => 50.00,
                'max_amount' => 5000000.00,
                'is_active' => true,
            ],
            // Mexico domestic
            [
                'source_country' => 'MX',
                'destination_country' => 'MX',
                'source_currency' => 'MXN',
                'destination_currency' => 'MXN',
                'payment_method' => PaymentCorridor::METHOD_LOCAL,
                'estimated_days_min' => 0,
                'estimated_days_max' => 1,
                'fee_fixed' => 0.00,
                'fee_percent' => 0.30,
                'min_amount' => 10.00,
                'max_amount' => 2000000.00,
                'is_active' => true,
            ],
            // Brazil domestic (PIX style)
            [
                'source_country' => 'BR',
                'destination_country' => 'BR',
                'source_currency' => 'BRL',
                'destination_currency' => 'BRL',
                'payment_method' => PaymentCorridor::METHOD_LOCAL,
                'estimated_days_min' => 0,
                'estimated_days_max' => 0,
                'fee_fixed' => 0.00,
                'fee_percent' => 0.00,
                'min_amount' => 1.00,
                'max_amount' => 500000.00,
                'is_active' => true,
            ],
        ];
    }
}
