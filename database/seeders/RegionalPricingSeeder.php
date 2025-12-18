<?php

namespace Database\Seeders;

use App\Models\PriceAdjustment;
use App\Models\RegionalPricing;
use Illuminate\Database\Seeder;

/**
 * GLO-009: Regional Pricing System - Seeder
 *
 * Seeds the regional_pricing table with major regions and their pricing configurations.
 */
class RegionalPricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regions = [
            // North America
            [
                'country_code' => 'US',
                'region_code' => null,
                'currency_code' => 'USD',
                'ppp_factor' => 1.000,
                'min_hourly_rate' => 15.00,
                'max_hourly_rate' => 100.00,
                'platform_fee_rate' => 15.00,
                'worker_fee_rate' => 5.00,
                'country_name' => 'United States',
            ],
            [
                'country_code' => 'CA',
                'region_code' => null,
                'currency_code' => 'CAD',
                'ppp_factor' => 1.15,
                'min_hourly_rate' => 17.00,
                'max_hourly_rate' => 90.00,
                'platform_fee_rate' => 15.00,
                'worker_fee_rate' => 5.00,
                'country_name' => 'Canada',
            ],
            [
                'country_code' => 'MX',
                'region_code' => null,
                'currency_code' => 'MXN',
                'ppp_factor' => 0.35,
                'min_hourly_rate' => 150.00,
                'max_hourly_rate' => 800.00,
                'platform_fee_rate' => 12.00,
                'worker_fee_rate' => 4.00,
                'country_name' => 'Mexico',
            ],

            // Europe
            [
                'country_code' => 'GB',
                'region_code' => null,
                'currency_code' => 'GBP',
                'ppp_factor' => 0.95,
                'min_hourly_rate' => 12.00,
                'max_hourly_rate' => 75.00,
                'platform_fee_rate' => 15.00,
                'worker_fee_rate' => 5.00,
                'country_name' => 'United Kingdom',
            ],
            [
                'country_code' => 'DE',
                'region_code' => null,
                'currency_code' => 'EUR',
                'ppp_factor' => 0.88,
                'min_hourly_rate' => 12.00,
                'max_hourly_rate' => 70.00,
                'platform_fee_rate' => 14.00,
                'worker_fee_rate' => 5.00,
                'country_name' => 'Germany',
            ],
            [
                'country_code' => 'FR',
                'region_code' => null,
                'currency_code' => 'EUR',
                'ppp_factor' => 0.90,
                'min_hourly_rate' => 12.00,
                'max_hourly_rate' => 70.00,
                'platform_fee_rate' => 14.00,
                'worker_fee_rate' => 5.00,
                'country_name' => 'France',
            ],
            [
                'country_code' => 'NL',
                'region_code' => null,
                'currency_code' => 'EUR',
                'ppp_factor' => 0.92,
                'min_hourly_rate' => 13.00,
                'max_hourly_rate' => 75.00,
                'platform_fee_rate' => 14.00,
                'worker_fee_rate' => 5.00,
                'country_name' => 'Netherlands',
            ],
            [
                'country_code' => 'ES',
                'region_code' => null,
                'currency_code' => 'EUR',
                'ppp_factor' => 0.75,
                'min_hourly_rate' => 10.00,
                'max_hourly_rate' => 55.00,
                'platform_fee_rate' => 12.00,
                'worker_fee_rate' => 4.50,
                'country_name' => 'Spain',
            ],
            [
                'country_code' => 'IT',
                'region_code' => null,
                'currency_code' => 'EUR',
                'ppp_factor' => 0.78,
                'min_hourly_rate' => 10.00,
                'max_hourly_rate' => 55.00,
                'platform_fee_rate' => 12.00,
                'worker_fee_rate' => 4.50,
                'country_name' => 'Italy',
            ],
            [
                'country_code' => 'IE',
                'region_code' => null,
                'currency_code' => 'EUR',
                'ppp_factor' => 0.98,
                'min_hourly_rate' => 12.70,
                'max_hourly_rate' => 80.00,
                'platform_fee_rate' => 15.00,
                'worker_fee_rate' => 5.00,
                'country_name' => 'Ireland',
            ],
            [
                'country_code' => 'PL',
                'region_code' => null,
                'currency_code' => 'PLN',
                'ppp_factor' => 0.50,
                'min_hourly_rate' => 30.00,
                'max_hourly_rate' => 200.00,
                'platform_fee_rate' => 12.00,
                'worker_fee_rate' => 4.00,
                'country_name' => 'Poland',
            ],

            // Oceania
            [
                'country_code' => 'AU',
                'region_code' => null,
                'currency_code' => 'AUD',
                'ppp_factor' => 1.20,
                'min_hourly_rate' => 22.00,
                'max_hourly_rate' => 85.00,
                'platform_fee_rate' => 15.00,
                'worker_fee_rate' => 5.00,
                'country_name' => 'Australia',
            ],
            [
                'country_code' => 'NZ',
                'region_code' => null,
                'currency_code' => 'NZD',
                'ppp_factor' => 1.15,
                'min_hourly_rate' => 23.00,
                'max_hourly_rate' => 80.00,
                'platform_fee_rate' => 15.00,
                'worker_fee_rate' => 5.00,
                'country_name' => 'New Zealand',
            ],

            // Asia
            [
                'country_code' => 'IN',
                'region_code' => null,
                'currency_code' => 'INR',
                'ppp_factor' => 0.25,
                'min_hourly_rate' => 200.00,
                'max_hourly_rate' => 2000.00,
                'platform_fee_rate' => 10.00,
                'worker_fee_rate' => 3.00,
                'country_name' => 'India',
            ],
            [
                'country_code' => 'PH',
                'region_code' => null,
                'currency_code' => 'PHP',
                'ppp_factor' => 0.30,
                'min_hourly_rate' => 150.00,
                'max_hourly_rate' => 1500.00,
                'platform_fee_rate' => 10.00,
                'worker_fee_rate' => 3.00,
                'country_name' => 'Philippines',
            ],
            [
                'country_code' => 'SG',
                'region_code' => null,
                'currency_code' => 'SGD',
                'ppp_factor' => 0.85,
                'min_hourly_rate' => 10.00,
                'max_hourly_rate' => 80.00,
                'platform_fee_rate' => 15.00,
                'worker_fee_rate' => 5.00,
                'country_name' => 'Singapore',
            ],
            [
                'country_code' => 'JP',
                'region_code' => null,
                'currency_code' => 'JPY',
                'ppp_factor' => 0.70,
                'min_hourly_rate' => 1050.00,
                'max_hourly_rate' => 8000.00,
                'platform_fee_rate' => 14.00,
                'worker_fee_rate' => 5.00,
                'country_name' => 'Japan',
            ],
            [
                'country_code' => 'KR',
                'region_code' => null,
                'currency_code' => 'KRW',
                'ppp_factor' => 0.65,
                'min_hourly_rate' => 10000.00,
                'max_hourly_rate' => 80000.00,
                'platform_fee_rate' => 14.00,
                'worker_fee_rate' => 5.00,
                'country_name' => 'South Korea',
            ],

            // Middle East
            [
                'country_code' => 'AE',
                'region_code' => null,
                'currency_code' => 'AED',
                'ppp_factor' => 0.60,
                'min_hourly_rate' => 30.00,
                'max_hourly_rate' => 300.00,
                'platform_fee_rate' => 15.00,
                'worker_fee_rate' => 5.00,
                'country_name' => 'United Arab Emirates',
            ],
            [
                'country_code' => 'SA',
                'region_code' => null,
                'currency_code' => 'SAR',
                'ppp_factor' => 0.55,
                'min_hourly_rate' => 30.00,
                'max_hourly_rate' => 300.00,
                'platform_fee_rate' => 15.00,
                'worker_fee_rate' => 5.00,
                'country_name' => 'Saudi Arabia',
            ],

            // Africa
            [
                'country_code' => 'NG',
                'region_code' => null,
                'currency_code' => 'NGN',
                'ppp_factor' => 0.20,
                'min_hourly_rate' => 1500.00,
                'max_hourly_rate' => 15000.00,
                'platform_fee_rate' => 10.00,
                'worker_fee_rate' => 3.00,
                'country_name' => 'Nigeria',
            ],
            [
                'country_code' => 'ZA',
                'region_code' => null,
                'currency_code' => 'ZAR',
                'ppp_factor' => 0.35,
                'min_hourly_rate' => 50.00,
                'max_hourly_rate' => 500.00,
                'platform_fee_rate' => 12.00,
                'worker_fee_rate' => 4.00,
                'country_name' => 'South Africa',
            ],
            [
                'country_code' => 'KE',
                'region_code' => null,
                'currency_code' => 'KES',
                'ppp_factor' => 0.28,
                'min_hourly_rate' => 200.00,
                'max_hourly_rate' => 2500.00,
                'platform_fee_rate' => 10.00,
                'worker_fee_rate' => 3.00,
                'country_name' => 'Kenya',
            ],
            [
                'country_code' => 'GH',
                'region_code' => null,
                'currency_code' => 'GHS',
                'ppp_factor' => 0.22,
                'min_hourly_rate' => 20.00,
                'max_hourly_rate' => 200.00,
                'platform_fee_rate' => 10.00,
                'worker_fee_rate' => 3.00,
                'country_name' => 'Ghana',
            ],
            [
                'country_code' => 'EG',
                'region_code' => null,
                'currency_code' => 'EGP',
                'ppp_factor' => 0.25,
                'min_hourly_rate' => 100.00,
                'max_hourly_rate' => 1000.00,
                'platform_fee_rate' => 10.00,
                'worker_fee_rate' => 3.00,
                'country_name' => 'Egypt',
            ],

            // South America
            [
                'country_code' => 'BR',
                'region_code' => null,
                'currency_code' => 'BRL',
                'ppp_factor' => 0.40,
                'min_hourly_rate' => 25.00,
                'max_hourly_rate' => 300.00,
                'platform_fee_rate' => 12.00,
                'worker_fee_rate' => 4.00,
                'country_name' => 'Brazil',
            ],
            [
                'country_code' => 'AR',
                'region_code' => null,
                'currency_code' => 'ARS',
                'ppp_factor' => 0.18,
                'min_hourly_rate' => 3000.00,
                'max_hourly_rate' => 30000.00,
                'platform_fee_rate' => 12.00,
                'worker_fee_rate' => 4.00,
                'country_name' => 'Argentina',
            ],
            [
                'country_code' => 'CL',
                'region_code' => null,
                'currency_code' => 'CLP',
                'ppp_factor' => 0.45,
                'min_hourly_rate' => 5000.00,
                'max_hourly_rate' => 50000.00,
                'platform_fee_rate' => 12.00,
                'worker_fee_rate' => 4.00,
                'country_name' => 'Chile',
            ],
            [
                'country_code' => 'CO',
                'region_code' => null,
                'currency_code' => 'COP',
                'ppp_factor' => 0.30,
                'min_hourly_rate' => 15000.00,
                'max_hourly_rate' => 150000.00,
                'platform_fee_rate' => 12.00,
                'worker_fee_rate' => 4.00,
                'country_name' => 'Colombia',
            ],
        ];

        // Add tier adjustments to all regions
        $tierAdjustments = RegionalPricing::DEFAULT_TIER_ADJUSTMENTS;

        foreach ($regions as $region) {
            RegionalPricing::updateOrCreate(
                [
                    'country_code' => $region['country_code'],
                    'region_code' => $region['region_code'],
                ],
                array_merge($region, ['tier_adjustments' => $tierAdjustments])
            );
        }

        $this->command->info('Created '.count($regions).' regional pricing configurations.');

        // Create sample price adjustments
        $this->createSampleAdjustments();
    }

    /**
     * Create sample price adjustments for demonstration.
     */
    protected function createSampleAdjustments(): void
    {
        $usRegion = RegionalPricing::where('country_code', 'US')->first();
        $gbRegion = RegionalPricing::where('country_code', 'GB')->first();

        if ($usRegion) {
            // Surge pricing for weekends
            PriceAdjustment::updateOrCreate(
                [
                    'regional_pricing_id' => $usRegion->id,
                    'adjustment_type' => PriceAdjustment::TYPE_SURGE,
                    'name' => 'Weekend Surge',
                ],
                [
                    'description' => 'Weekend surge pricing for high-demand periods',
                    'multiplier' => 1.150,
                    'fixed_adjustment' => 0,
                    'valid_from' => now(),
                    'valid_until' => null,
                    'conditions' => ['days_of_week' => [0, 6]], // Sunday, Saturday
                    'is_active' => true,
                ]
            );

            // Holiday pricing
            PriceAdjustment::updateOrCreate(
                [
                    'regional_pricing_id' => $usRegion->id,
                    'adjustment_type' => PriceAdjustment::TYPE_HOLIDAY,
                    'name' => 'Holiday Season Pricing',
                ],
                [
                    'description' => 'Holiday season pricing adjustment',
                    'multiplier' => 1.250,
                    'fixed_adjustment' => 0,
                    'valid_from' => now()->startOfYear()->addMonths(11)->startOfMonth(), // December 1
                    'valid_until' => now()->startOfYear()->addMonths(11)->endOfMonth(), // December 31
                    'conditions' => null,
                    'is_active' => true,
                ]
            );
        }

        if ($gbRegion) {
            // Night shift premium
            PriceAdjustment::updateOrCreate(
                [
                    'regional_pricing_id' => $gbRegion->id,
                    'adjustment_type' => PriceAdjustment::TYPE_SERVICE_FEE,
                    'name' => 'Night Shift Premium',
                ],
                [
                    'description' => 'Premium for shifts between 10pm and 6am',
                    'multiplier' => 1.100,
                    'fixed_adjustment' => 2.00,
                    'valid_from' => now(),
                    'valid_until' => null,
                    'conditions' => ['time_start' => '22:00', 'time_end' => '06:00'],
                    'is_active' => true,
                ]
            );
        }

        $this->command->info('Created sample price adjustments.');
    }
}
