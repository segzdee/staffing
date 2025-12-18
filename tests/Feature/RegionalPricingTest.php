<?php

namespace Tests\Feature;

use App\Models\PriceAdjustment;
use App\Models\RegionalPricing;
use App\Services\RegionalPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GLO-009: Regional Pricing System Tests
 *
 * Tests for regional pricing calculations, adjustments, and service methods.
 */
class RegionalPricingTest extends TestCase
{
    use RefreshDatabase;

    protected RegionalPricingService $pricingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricingService = app(RegionalPricingService::class);
    }

    /**
     * Test creating regional pricing configuration.
     */
    public function test_can_create_regional_pricing(): void
    {
        $regional = RegionalPricing::create([
            'country_code' => 'US',
            'region_code' => null,
            'currency_code' => 'USD',
            'ppp_factor' => 1.000,
            'min_hourly_rate' => 15.00,
            'max_hourly_rate' => 100.00,
            'platform_fee_rate' => 15.00,
            'worker_fee_rate' => 5.00,
            'country_name' => 'United States',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('regional_pricing', [
            'country_code' => 'US',
            'currency_code' => 'USD',
            'ppp_factor' => 1.000,
        ]);

        $this->assertEquals('United States', $regional->display_name);
        $this->assertEquals('US', $regional->location_identifier);
    }

    /**
     * Test creating regional pricing with region code.
     */
    public function test_can_create_regional_pricing_with_region(): void
    {
        $regional = RegionalPricing::create([
            'country_code' => 'US',
            'region_code' => 'CA',
            'currency_code' => 'USD',
            'ppp_factor' => 1.050,
            'min_hourly_rate' => 16.00,
            'max_hourly_rate' => 120.00,
            'platform_fee_rate' => 15.00,
            'worker_fee_rate' => 5.00,
            'country_name' => 'United States',
            'region_name' => 'California',
            'is_active' => true,
        ]);

        $this->assertEquals('California, United States', $regional->display_name);
        $this->assertEquals('US-CA', $regional->location_identifier);
    }

    /**
     * Test PPP adjustment calculation.
     */
    public function test_ppp_adjustment_calculation(): void
    {
        $regional = RegionalPricing::create([
            'country_code' => 'IN',
            'currency_code' => 'INR',
            'ppp_factor' => 0.25,
            'min_hourly_rate' => 200.00,
            'max_hourly_rate' => 2000.00,
            'platform_fee_rate' => 10.00,
            'worker_fee_rate' => 3.00,
            'is_active' => true,
        ]);

        // $100 base price * 0.25 PPP = $25
        $adjustedPrice = $regional->applyPPPAdjustment(100.00);
        $this->assertEquals(25.00, $adjustedPrice);
    }

    /**
     * Test rate validation.
     */
    public function test_rate_validation(): void
    {
        $regional = RegionalPricing::create([
            'country_code' => 'US',
            'currency_code' => 'USD',
            'ppp_factor' => 1.000,
            'min_hourly_rate' => 15.00,
            'max_hourly_rate' => 100.00,
            'platform_fee_rate' => 15.00,
            'worker_fee_rate' => 5.00,
            'is_active' => true,
        ]);

        // Valid rate
        $this->assertTrue($regional->isRateValid(50.00));

        // Rate too low
        $this->assertFalse($regional->isRateValid(10.00));

        // Rate too high
        $this->assertFalse($regional->isRateValid(150.00));

        // Boundary rates
        $this->assertTrue($regional->isRateValid(15.00));
        $this->assertTrue($regional->isRateValid(100.00));
    }

    /**
     * Test platform fee calculation.
     */
    public function test_platform_fee_calculation(): void
    {
        $regional = RegionalPricing::create([
            'country_code' => 'US',
            'currency_code' => 'USD',
            'ppp_factor' => 1.000,
            'min_hourly_rate' => 15.00,
            'max_hourly_rate' => 100.00,
            'platform_fee_rate' => 15.00,
            'worker_fee_rate' => 5.00,
            'tier_adjustments' => RegionalPricing::DEFAULT_TIER_ADJUSTMENTS,
            'is_active' => true,
        ]);

        // Base fee calculation (15% of $100 = $15)
        $fee = $regional->calculatePlatformFee(100.00);
        $this->assertEquals(15.00, $fee);

        // With tier adjustment (professional = 0.8 modifier)
        // 15% * 0.8 = 12%, 12% of $100 = $12
        $feeWithTier = $regional->calculatePlatformFee(100.00, 'professional');
        $this->assertEquals(12.00, $feeWithTier);
    }

    /**
     * Test worker fee calculation.
     */
    public function test_worker_fee_calculation(): void
    {
        $regional = RegionalPricing::create([
            'country_code' => 'US',
            'currency_code' => 'USD',
            'ppp_factor' => 1.000,
            'min_hourly_rate' => 15.00,
            'max_hourly_rate' => 100.00,
            'platform_fee_rate' => 15.00,
            'worker_fee_rate' => 5.00,
            'tier_adjustments' => RegionalPricing::DEFAULT_TIER_ADJUSTMENTS,
            'is_active' => true,
        ]);

        // Base fee calculation (5% of $100 = $5)
        $fee = $regional->calculateWorkerFee(100.00);
        $this->assertEquals(5.00, $fee);

        // With tier adjustment (enterprise = 0.85 modifier)
        // 5% * 0.85 = 4.25%, 4.25% of $100 = $4.25
        $feeWithTier = $regional->calculateWorkerFee(100.00, 'enterprise');
        $this->assertEquals(4.25, $feeWithTier);
    }

    /**
     * Test finding regional pricing for location.
     */
    public function test_find_regional_pricing_for_location(): void
    {
        // Create country-level pricing
        $usCountry = RegionalPricing::create([
            'country_code' => 'US',
            'region_code' => null,
            'currency_code' => 'USD',
            'ppp_factor' => 1.000,
            'min_hourly_rate' => 15.00,
            'max_hourly_rate' => 100.00,
            'platform_fee_rate' => 15.00,
            'worker_fee_rate' => 5.00,
            'is_active' => true,
        ]);

        // Create region-level pricing
        $usCalifornia = RegionalPricing::create([
            'country_code' => 'US',
            'region_code' => 'CA',
            'currency_code' => 'USD',
            'ppp_factor' => 1.050,
            'min_hourly_rate' => 16.00,
            'max_hourly_rate' => 120.00,
            'platform_fee_rate' => 15.00,
            'worker_fee_rate' => 5.00,
            'is_active' => true,
        ]);

        // Should find region-specific pricing
        $found = RegionalPricing::findForLocation('US', 'CA');
        $this->assertEquals($usCalifornia->id, $found->id);

        // Should fall back to country pricing when region not found
        $found = RegionalPricing::findForLocation('US', 'NY');
        $this->assertEquals($usCountry->id, $found->id);

        // Should find country pricing when no region specified
        $found = RegionalPricing::findForLocation('US');
        $this->assertEquals($usCountry->id, $found->id);
    }

    /**
     * Test price adjustment creation.
     */
    public function test_can_create_price_adjustment(): void
    {
        $regional = RegionalPricing::create([
            'country_code' => 'US',
            'currency_code' => 'USD',
            'ppp_factor' => 1.000,
            'min_hourly_rate' => 15.00,
            'max_hourly_rate' => 100.00,
            'platform_fee_rate' => 15.00,
            'worker_fee_rate' => 5.00,
            'is_active' => true,
        ]);

        $adjustment = PriceAdjustment::create([
            'regional_pricing_id' => $regional->id,
            'adjustment_type' => PriceAdjustment::TYPE_SURGE,
            'name' => 'Weekend Surge',
            'multiplier' => 1.15,
            'fixed_adjustment' => 0,
            'valid_from' => now(),
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('price_adjustments', [
            'adjustment_type' => 'surge',
            'multiplier' => 1.15,
        ]);

        $this->assertTrue($adjustment->isCurrentlyValid());
        $this->assertEquals('Active', $adjustment->status);
    }

    /**
     * Test price adjustment application.
     */
    public function test_price_adjustment_application(): void
    {
        $regional = RegionalPricing::create([
            'country_code' => 'US',
            'currency_code' => 'USD',
            'ppp_factor' => 1.000,
            'min_hourly_rate' => 15.00,
            'max_hourly_rate' => 100.00,
            'platform_fee_rate' => 15.00,
            'worker_fee_rate' => 5.00,
            'is_active' => true,
        ]);

        // Test multiplier only
        $adjustment = new PriceAdjustment([
            'regional_pricing_id' => $regional->id,
            'adjustment_type' => PriceAdjustment::TYPE_SURGE,
            'multiplier' => 1.15,
            'fixed_adjustment' => 0,
            'valid_from' => now(),
            'is_active' => true,
        ]);

        // $100 * 1.15 = $115
        $this->assertEquals(115.00, $adjustment->applyToPrice(100.00));

        // Test multiplier with fixed adjustment
        $adjustment->fixed_adjustment = 5.00;
        // $100 * 1.15 + $5 = $120
        $this->assertEquals(120.00, $adjustment->applyToPrice(100.00));

        // Test discount multiplier with fixed deduction
        $adjustment->multiplier = 0.90;
        $adjustment->fixed_adjustment = -5.00;
        // $100 * 0.90 - $5 = $85
        $this->assertEquals(85.00, $adjustment->applyToPrice(100.00));
    }

    /**
     * Test adjustment validity periods.
     */
    public function test_adjustment_validity_periods(): void
    {
        $regional = RegionalPricing::create([
            'country_code' => 'US',
            'currency_code' => 'USD',
            'ppp_factor' => 1.000,
            'min_hourly_rate' => 15.00,
            'max_hourly_rate' => 100.00,
            'platform_fee_rate' => 15.00,
            'worker_fee_rate' => 5.00,
            'is_active' => true,
        ]);

        // Future adjustment
        $futureAdjustment = PriceAdjustment::create([
            'regional_pricing_id' => $regional->id,
            'adjustment_type' => PriceAdjustment::TYPE_SEASONAL,
            'multiplier' => 1.20,
            'fixed_adjustment' => 0,
            'valid_from' => now()->addDays(7),
            'is_active' => true,
        ]);
        $this->assertFalse($futureAdjustment->isCurrentlyValid());
        $this->assertEquals('Scheduled', $futureAdjustment->status);

        // Expired adjustment
        $expiredAdjustment = PriceAdjustment::create([
            'regional_pricing_id' => $regional->id,
            'adjustment_type' => PriceAdjustment::TYPE_PROMOTIONAL,
            'multiplier' => 0.80,
            'fixed_adjustment' => 0,
            'valid_from' => now()->subDays(30),
            'valid_until' => now()->subDays(1),
            'is_active' => true,
        ]);
        $this->assertFalse($expiredAdjustment->isCurrentlyValid());
        $this->assertEquals('Expired', $expiredAdjustment->status);

        // Current adjustment
        $currentAdjustment = PriceAdjustment::create([
            'regional_pricing_id' => $regional->id,
            'adjustment_type' => PriceAdjustment::TYPE_SURGE,
            'multiplier' => 1.10,
            'fixed_adjustment' => 0,
            'valid_from' => now()->subDays(1),
            'valid_until' => now()->addDays(7),
            'is_active' => true,
        ]);
        $this->assertTrue($currentAdjustment->isCurrentlyValid());
        $this->assertEquals('Active', $currentAdjustment->status);
    }

    /**
     * Test service get regional pricing.
     */
    public function test_service_get_regional_pricing(): void
    {
        RegionalPricing::create([
            'country_code' => 'GB',
            'currency_code' => 'GBP',
            'ppp_factor' => 0.95,
            'min_hourly_rate' => 12.00,
            'max_hourly_rate' => 75.00,
            'platform_fee_rate' => 15.00,
            'worker_fee_rate' => 5.00,
            'country_name' => 'United Kingdom',
            'is_active' => true,
        ]);

        $regional = $this->pricingService->getRegionalPricing('GB');

        $this->assertNotNull($regional);
        $this->assertEquals('GBP', $regional->currency_code);
        $this->assertEquals(0.95, (float) $regional->ppp_factor);
    }

    /**
     * Test service get min max rates.
     */
    public function test_service_get_min_max_rates(): void
    {
        RegionalPricing::create([
            'country_code' => 'AU',
            'currency_code' => 'AUD',
            'ppp_factor' => 1.20,
            'min_hourly_rate' => 22.00,
            'max_hourly_rate' => 85.00,
            'platform_fee_rate' => 15.00,
            'worker_fee_rate' => 5.00,
            'is_active' => true,
        ]);

        $rates = $this->pricingService->getMinMaxRates('AU');

        $this->assertEquals(22.00, $rates['min_hourly_rate']);
        $this->assertEquals(85.00, $rates['max_hourly_rate']);
        $this->assertEquals('AUD', $rates['currency_code']);
    }

    /**
     * Test service validate rate for region.
     */
    public function test_service_validate_rate_for_region(): void
    {
        RegionalPricing::create([
            'country_code' => 'DE',
            'currency_code' => 'EUR',
            'ppp_factor' => 0.88,
            'min_hourly_rate' => 12.00,
            'max_hourly_rate' => 70.00,
            'platform_fee_rate' => 14.00,
            'worker_fee_rate' => 5.00,
            'is_active' => true,
        ]);

        // Valid rate
        $result = $this->pricingService->validateRateForRegion(35.00, 'DE');
        $this->assertTrue($result['is_valid']);

        // Invalid rate (too low)
        $result = $this->pricingService->validateRateForRegion(8.00, 'DE');
        $this->assertFalse($result['is_valid']);
        $this->assertStringContainsString('must be between', $result['message']);
    }

    /**
     * Test service calculate shift fees.
     */
    public function test_service_calculate_shift_fees(): void
    {
        RegionalPricing::create([
            'country_code' => 'US',
            'currency_code' => 'USD',
            'ppp_factor' => 1.000,
            'min_hourly_rate' => 15.00,
            'max_hourly_rate' => 100.00,
            'platform_fee_rate' => 15.00,
            'worker_fee_rate' => 5.00,
            'tier_adjustments' => RegionalPricing::DEFAULT_TIER_ADJUSTMENTS,
            'is_active' => true,
        ]);

        $fees = $this->pricingService->calculateShiftFees(
            hourlyRate: 25.00,
            hours: 8,
            countryCode: 'US'
        );

        // Subtotal: $25 * 8 = $200
        $this->assertEquals(200.00, $fees['subtotal']);

        // Platform fee: $200 * 15% = $30
        $this->assertEquals(30.00, $fees['platform_fee']);

        // Worker fee: $200 * 5% = $10
        $this->assertEquals(10.00, $fees['worker_fee']);

        // Worker earnings: $200 - $10 = $190
        $this->assertEquals(190.00, $fees['worker_earnings']);

        // Business total: $200 + $30 = $230
        $this->assertEquals(230.00, $fees['business_total']);
    }

    /**
     * Test service upsert regional pricing.
     */
    public function test_service_upsert_regional_pricing(): void
    {
        // Create new
        $regional = $this->pricingService->upsertRegionalPricing([
            'country_code' => 'NZ',
            'currency_code' => 'NZD',
            'ppp_factor' => 1.15,
            'min_hourly_rate' => 23.00,
            'max_hourly_rate' => 80.00,
            'platform_fee_rate' => 15.00,
            'worker_fee_rate' => 5.00,
            'country_name' => 'New Zealand',
        ]);

        $this->assertDatabaseHas('regional_pricing', [
            'country_code' => 'NZ',
            'min_hourly_rate' => 23.00,
        ]);

        // Update existing
        $updatedRegional = $this->pricingService->upsertRegionalPricing([
            'country_code' => 'NZ',
            'currency_code' => 'NZD',
            'ppp_factor' => 1.18,
            'min_hourly_rate' => 24.00,
            'max_hourly_rate' => 85.00,
            'platform_fee_rate' => 15.00,
            'worker_fee_rate' => 5.00,
        ]);

        $this->assertEquals($regional->id, $updatedRegional->id);
        $this->assertEquals(24.00, (float) $updatedRegional->min_hourly_rate);
    }

    /**
     * Test service bulk import.
     */
    public function test_service_bulk_import(): void
    {
        $regions = [
            [
                'country_code' => 'JP',
                'currency_code' => 'JPY',
                'ppp_factor' => 0.70,
                'min_hourly_rate' => 1050.00,
                'max_hourly_rate' => 8000.00,
                'platform_fee_rate' => 14.00,
                'worker_fee_rate' => 5.00,
            ],
            [
                'country_code' => 'KR',
                'currency_code' => 'KRW',
                'ppp_factor' => 0.65,
                'min_hourly_rate' => 10000.00,
                'max_hourly_rate' => 80000.00,
                'platform_fee_rate' => 14.00,
                'worker_fee_rate' => 5.00,
            ],
        ];

        $results = $this->pricingService->bulkImport($regions);

        $this->assertEquals(2, $results['created']);
        $this->assertEquals(0, $results['updated']);
        $this->assertEmpty($results['errors']);

        $this->assertDatabaseHas('regional_pricing', ['country_code' => 'JP']);
        $this->assertDatabaseHas('regional_pricing', ['country_code' => 'KR']);
    }

    /**
     * Test rate clamping.
     */
    public function test_rate_clamping(): void
    {
        $regional = RegionalPricing::create([
            'country_code' => 'US',
            'currency_code' => 'USD',
            'ppp_factor' => 1.000,
            'min_hourly_rate' => 15.00,
            'max_hourly_rate' => 100.00,
            'platform_fee_rate' => 15.00,
            'worker_fee_rate' => 5.00,
            'is_active' => true,
        ]);

        // Rate too low should be clamped to min
        $this->assertEquals(15.00, $regional->clampRate(10.00));

        // Rate too high should be clamped to max
        $this->assertEquals(100.00, $regional->clampRate(150.00));

        // Rate within range should remain unchanged
        $this->assertEquals(50.00, $regional->clampRate(50.00));
    }

    /**
     * Test active countries retrieval.
     */
    public function test_get_active_countries(): void
    {
        RegionalPricing::create([
            'country_code' => 'US',
            'currency_code' => 'USD',
            'ppp_factor' => 1.000,
            'min_hourly_rate' => 15.00,
            'max_hourly_rate' => 100.00,
            'platform_fee_rate' => 15.00,
            'worker_fee_rate' => 5.00,
            'country_name' => 'United States',
            'is_active' => true,
        ]);

        RegionalPricing::create([
            'country_code' => 'GB',
            'currency_code' => 'GBP',
            'ppp_factor' => 0.95,
            'min_hourly_rate' => 12.00,
            'max_hourly_rate' => 75.00,
            'platform_fee_rate' => 15.00,
            'worker_fee_rate' => 5.00,
            'country_name' => 'United Kingdom',
            'is_active' => true,
        ]);

        RegionalPricing::create([
            'country_code' => 'DE',
            'currency_code' => 'EUR',
            'ppp_factor' => 0.88,
            'min_hourly_rate' => 12.00,
            'max_hourly_rate' => 70.00,
            'platform_fee_rate' => 14.00,
            'worker_fee_rate' => 5.00,
            'country_name' => 'Germany',
            'is_active' => false, // Inactive
        ]);

        $countries = RegionalPricing::getActiveCountries();

        $this->assertCount(2, $countries);
        $this->assertArrayHasKey('US', $countries);
        $this->assertArrayHasKey('GB', $countries);
        $this->assertArrayNotHasKey('DE', $countries);
    }
}
